<?php
/*
	include/services/inc_services_invoicegen.php

	Provides functions for generating new invoices for customer services. These functions
	are called by the execute cronjob daily or whenever manually requested by a user from
	the web interface.
*/


// dependencies
require("inc_services_usage.php");



/*
	FUNCTIONS
*/


/*
	services_periods_generate

	Reads all the customer service information and calculates any new billing periods when required.

	Values
	customerid		(optional) ID of the customer account to generate new period
				information for. If blank, will execute for all customers.
				
	Results
	0			failure
	1			success
*/
function service_periods_generate($customerid = NULL)
{
	log_debug("inc_services_invoicegen", "Executing service_periods_generate($customerid)");

	/*
		Fetch configuration Options
	*/

	// advancebilling - number of days to bill in advance for period/month advance billing modes
	$accounts_services_advancebilling = sql_get_singlevalue("SELECT value FROM config WHERE name='ACCOUNTS_SERVICES_ADVANCEBILLING' LIMIT 1");



	/*
		Fetch all the assigned services information
	*/
	$sql_custserv_obj		= New sql_query;
	$sql_custserv_obj->string	= "SELECT services_customers.id, services.billing_mode, serviceid, date_period_next FROM services_customers LEFT JOIN services ON services.id = services_customers.serviceid WHERE services_customers.active='1'";

	if ($customerid)
		$sql_custserv_obj->string .= " AND customerid='$customerid'";


	$sql_custserv_obj->execute();

	if ($sql_custserv_obj->num_rows())
	{
		$sql_custserv_obj->fetch_array();

		foreach ($sql_custserv_obj->data as $data)
		{
			/*
				Based on the billing mode, we now need to determine what date we need to create the next plan entry
				for the selected service.
			*/

			$billing_mode = sql_get_singlevalue("SELECT name as value FROM billing_modes WHERE id='". $data["billing_mode"] ."'");
			
			switch ($billing_mode)
			{
				case "monthend":
				case "periodend":
					/*
						PERIODEND or MONTHEND

						Create a new period on the date set in services_customers.date_period_next.

						Both periodend and monthend are treated the same way, the only difference is that when the new period
						is created, the date with either be +1 month (periodend), or the last day in the following month (monthend)
					*/

					log_debug("inc_services_invoicegen", "Processing periodend/monthend service type");

					if (time_date_to_timestamp($data["date_period_next"]) <= mktime())
					{
						// the latest billing period has finished, we need to generate a new time period.
						if (!service_periods_add($data["id"], $billing_mode))
						{
							$_SESSION["error"]["message"][] = "Fatal error whilst trying to create new time period";
							return 0;
						}
					}
				
				break;

				case "periodadvance":
				case "monthadvance":
					/*
						PERIODADVANCE or MONTHADVANCE

						Create a new period in advance of the actual date that the period begins.

						Example Scenario:
							services_customers.date_period_next	== 28-03-2008
							ACCOUNTS_SERVICES_ADVANCEBILLING	== 20 (days)

							Date to generate new period and issue invoice will be 08-03-2008, which
							is 20 days before the billing period begins.

						If the date_period_next value is equal or less than todays date + ACCOUNTS_SERVICES_ADVANCEBILLING, then
						we need to generate a new billing period.
							
					*/
					
					log_debug("inc_services_invoicegen", "Processing periodadvance/monthadvance service type");


					// calculate period next date in the future
					$date_period_next = mktime(0, 0, 0, date("m"), date("d")+$accounts_services_advancebilling, date("Y"));

					if (time_date_to_timestamp($data["date_period_next"]) <= $date_period_next)
					{
						log_debug("inc_services_invoicegen", "Generating advance billing period for service with next billing date of $date_period_next");


						// generate the new billing period (in advance)
						if (!service_periods_add($data["id"], $billing_mode))
						{
							$_SESSION["error"]["message"][] = "Fatal error whilst trying to create new time period";
							return 0;
						}
					}
				
				break;

				default:
					$_SESSION["error"]["message"][] = "Unknown billing mode ". $data["billing_mode"] ." provided.";
					return 0;
				break;
			}

		} // loop through services
	}
	else
	{
		log_debug("inc_services_invoicegen", "No services assigned to customer $customerid");
	}


	return 1;
}




/*
	services_periods_add

	This function is typically called by the services_periods_generate function and is used to create
	a new billing period which can then be used by the services_invoices_generate function to generate new invoices.

	Values
	services_customers_id		ID of the service entry for the customer in services_customers.
	billing_mode			name of the billing mode

	Results
	0			failure
	1			success
*/
function service_periods_add($services_customers_id, $billing_mode)
{
	log_debug("inc_services_invoicegen", "Executing service_periods_add($services_customers_id, $billing_mode)");


	/*
		Fetch required information from services_customers (service-customer assignment table)
	*/
	
	$sql_custserv_obj		= New sql_query;
	$sql_custserv_obj->string	= "SELECT serviceid, date_period_first, date_period_next FROM services_customers WHERE id='$services_customers_id' LIMIT 1";
	$sql_custserv_obj->execute();
	$sql_custserv_obj->fetch_array();

	$serviceid		= $sql_custserv_obj->data[0]["serviceid"];
	$date_period_start	= $sql_custserv_obj->data[0]["date_period_next"];



	/*
		Handle new services

		If the service has not been billed before, the date_period_first value will have been set, but not the date_period_next value.

		For periodend and periodadvance billing modes, we just want to start from this date. However, for the monthend and monthadvance modes
		we need to bill from the first till the end of the month. Our current solution is to treat these services the same as periodend/periodadvance which
		causes the first invoice to include a full month + a partial month.

		A possible enhancement would be to either generate a seporate invoice for an inital partial month or not, depending on when in the month the service starts.
	*/

	if ($sql_custserv_obj->data[0]["date_period_next"] == "0000-00-00")
	{
		$date_period_start = $sql_custserv_obj->data[0]["date_period_first"];
	}



	/*
		Calculate the new dates for the billing period

		We use MySQL's DATE_ADD function to do the month caluclations for us, since it is smart
		enough to handle the different month lengths.

			For example:
			DATE_ADD('2010-01-31', INTERVAL 1 MONTH )		==	2010-02-28
			DATE_ADD('2010-01-07', INTERVAL 1 MONTH )		==	2010-02-07
		
	*/

	
	// get the billing cycle
	$billing_cycle = sql_get_singlevalue("SELECT billing_cycles.name as value FROM services LEFT JOIN billing_cycles ON billing_cycles.id = services.billing_cycle WHERE services.id='$serviceid'");


	// Work out how much time to add onto the start date to find the period end
	$sql_add_string = "";

	switch ($billing_cycle)
	{
		case "monthly":
			$sql_add_string = "1 MONTH";
		break;

		case "6monthly":
			$sql_add_string = "6 MONTH";
		break;

		case "yearly":
			$sql_add_string = "12 MONTH";
		break;
	}



	
	// perform calculations for the relevent mode type	
	switch ($billing_mode)
	{
		case "periodend":
		case "periodadvance":
			// PERIODEND / PERIODADVANCE
			//
			// Periods start of any date of the month.
	
			// Add time to the date_period_start date.
			$date_period_end	= sql_get_singlevalue("SELECT DATE_ADD('$date_period_start', INTERVAL $sql_add_string ) as value");
			$date_period_next	= sql_get_singlevalue("SELECT DATE_ADD('$date_period_end', INTERVAL 1 DAY ) as value");
		break;

			
		case "monthend":
		case "monthadvance":
			// MONTHEND
			//
			// Periods start on the 1st and end on the last day of the month.

			if (time_calculate_daynum($date_period_start) != "01")
			{
				log_debug("inc_services_invoicegen", "Note: generating extra long period due to new service.");

				// the service is starting not on the first day of the month - the most likely cause is that this
				// is the first time a new service is being billed for a customer.
				//
				// we need to generate a period end date for the end of the next month, so that the first period is one
				// whole month + the extra number of days.
				//


				// Add time to the date_period_start date.
				$date_period_end	= sql_get_singlevalue("SELECT DATE_ADD('$date_period_start', INTERVAL $sql_add_string ) as value");

				// fetch the end of the month date
				$date_period_end	= sql_get_singlevalue("SELECT LAST_DAY('$date_period_end') as value");

				// fetch the next period's start date (the first of the next month)
				$date_period_next	= sql_get_singlevalue("SELECT DATE_ADD('$date_period_end', INTERVAL 1 DAY ) as value");

			}
			else
			{
				// regular billing period - ie: not the first time.
			
				// fetch the end of the month date
				$date_period_end	= sql_get_singlevalue("SELECT LAST_DAY('$date_period_start') as value");

				// fetch the next period's start date (the first of the next month)
				$date_period_next	= sql_get_singlevalue("SELECT DATE_ADD('$date_period_end', INTERVAL 1 DAY ) as value");
			}


		break;
	}


	
	/*
		Calculate date to bill the period on
	*/
	switch ($billing_mode)
	{
		case "periodadvance":
		case "monthadvance":
			// PERIODADVANCE / MONTHADVANCE
			//
			// Billing date should be set to today, since the period will have just been generated in advance today and
			// we don't need to bother regenerating the billing period.
			//

			$date_period_billing = date("Y-m-d");
		break;

			
		case "periodend":
		case "monthend":
			// PERIODEND /  MONTHEND
			//
			// We can't bill for this period until it's end, so we set the billing date to the start of the next period,
			// so that the period has completely finished before we invoice.

			$date_period_billing = $date_period_next;

		break;
	}







	/*
		Add a new period
	*/
	$sql_obj		= New sql_query;
	$sql_obj->string	= "INSERT INTO services_customers_periods (services_customers_id, date_start, date_end, date_billed) VALUES ('$services_customers_id', '$date_period_start', '$date_period_end', '$date_period_billing')";
	$sql_obj->execute();

			

	/*
		Update services_customers
	*/
	$sql_obj		= New sql_query;
	$sql_obj->string	= "UPDATE services_customers SET date_period_next='$date_period_next' WHERE id='$services_customers_id'";
	$sql_obj->execute();


	return 1;
}







/*
	service_invoices_generate

	Processes all the service periods for customers, and bills accordingly. All the calcuations to work out which
	services need to be billed have been performed by the service_periods_generate function, so this function
	simply needs to get a list of unbilled invoices from services_customers_periods and perform billing.

	This function is smart enough to put multiple services on the same invoice if they fall on the same billing date.

	Values
	customerid		(optional) ID of the customer account to generate new period
				information for. If blank, will execute for all customers.

	logmode			(optional) Mode either "web" or "script". This causes logging to be
				either printed directly or displayed to screen.

	Results
	0			failure
	1			success
*/
function service_invoices_generate($customerid = NULL)
{
	log_debug("inc_services_invoicegen", "Executing service_invoices_generate($customerid)");



	/*
		Run through all the customers
	*/
	$sql_customers_obj		= New sql_query;
	$sql_customers_obj->string	= "SELECT id, code_customer, name_contact, contact_email FROM customers";

	if ($customerid)
		$sql_customers_obj->string .= " WHERE id='$customerid'";


	$sql_customers_obj->execute();

	if ($sql_customers_obj->num_rows())
	{
		$sql_customers_obj->fetch_array();


		foreach ($sql_customers_obj->data as $customer_data)
		{
			/*
				Fetch all periods belonging to this customer which need to be billed.
			*/

			$sql_periods_obj		= New sql_query;
			$sql_periods_obj->string	= "SELECT "
								."services_customers_periods.id, "
								."services_customers_periods.invoiceid, "
								."services_customers_periods.date_start, "
								."services_customers_periods.date_end, "
								."services_customers.id as services_customers_id, "
								."services_customers.quantity, "
								."services_customers.description, "
								."services_customers.serviceid "
								."FROM services_customers_periods "
								."LEFT JOIN services_customers ON services_customers.id = services_customers_periods.services_customers_id "
								."WHERE "
								."services_customers.customerid='". $customer_data["id"] ."' "
								."AND invoiceid = '0' "
								."AND date_billed <= '". date("Y-m-d")."'";
			$sql_periods_obj->execute();

			if ($sql_periods_obj->num_rows())
			{
				$sql_periods_obj->fetch_array();

				/*
					BILL CUSTOMER

					This customer has at least one service that needs to be billed. We need to create
					a new invoice, and then process each service, adding the services to the invoice as 
					items.
				*/



				/*
					Create new invoice
				*/
				$invoice		= New invoice;
				$invoice->type		= "ar";
				
				$invoice->prepare_code_invoice();
				
				$invoice->data["customerid"]	= $customer_data["id"];
				$invoice->data["employeeid"]	= 1;				// set employee to the default internal user


				/*
					TODO: Determine optimal solution for dest_account in service invoices.
					
					To determine the dest_account for use in the invoice, we fetch the first AR account in the list - a
					better solution needs to be worked out, since it is possible that a user might have more than 1
					AR summary account.
				*/
					

				// fetch the ID of the summary type label
				$menuid = sql_get_singlevalue("SELECT id as value FROM account_chart_menu WHERE value='ar_summary_account'");

				// fetch the top AR account
				$sql_query	= "SELECT "
						."account_charts.id as value "
						."FROM account_charts "
						."LEFT JOIN account_charts_menus ON account_charts_menus.chartid = account_charts.id "
						."WHERE account_charts_menus.menuid='$menuid' "
						."LIMIT 1";
								
				$invoice->data["dest_account"]	= sql_get_singlevalue($sql_query);

				if (!$invoice->data["dest_account"])
				{
					log_debug("services_invoicegen", "Error: No AR summary account could be found");
					return 0;
				}


				if (!$invoice->action_create())
				{
					log_debug("services_invoicegen","Error: Unexpected problem occured whilst attempting to create invoice.");
					return 0;
				}

				$invoiceid	= $invoice->id;
				$invoicecode	= $invoice->data["code_invoice"];
				unset($invoice);



				/*
					Create Service Items
										
					We need to create an item for basic service plan - IE: the regular fixed fee, and another item for any
					excess usage.
				*/
				foreach ($sql_periods_obj->data as $period_data)
				{
					// fetch service details
					$sql_service_obj		= New sql_query;
					$sql_service_obj->string	= "SELECT * FROM services WHERE id='". $period_data["serviceid"] . "' LIMIT 1";
					$sql_service_obj->execute();
					$sql_service_obj->fetch_array();

					// fetch service type
					$service_type = sql_get_singlevalue("SELECT name as value FROM service_types WHERE id='". $sql_service_obj->data[0]["typeid"] ."'");
					
				
					// fetch billing mode
					$billing_mode = sql_get_singlevalue("SELECT name as value FROM billing_modes WHERE id='". $sql_service_obj->data[0]["billing_mode"] ."'");


					if ($billing_mode == "monthend" || $billing_mode == "monthadvance")
					{
						log_debug("services_invoicegen", "Invoice bills by month date");

						/*
							Handle monthly billing

							Normally, monthly billing is easy, however the very first billing period is special, as it may span a more than 1 month.

							Eg: if a service is started on 2008-01-09, the end of the billing period will be 2008-02-29, which is 1 month + 21 day.

							To handle this, we increase the base costs and included units by the following method:

								( standard_cost / normal_month_num_days ) * num_days_in_partial_month == extra_amount

								total_amount = (extra_amount + normal_amount)


							Note: we do not increase included units for licenses service types, since these need to remain fixed and do not
							scale with the month.
						*/

						// check if the period is the very first period - the start and end dates will be in different months.
						if (time_calculate_monthnum($period_data["date_start"]) != time_calculate_monthnum($period_data["date_end"]))
						{
							// very first billing month
							log_debug("services_invoicegen", "Very first billing month - adjusting prices/included units to suit the extra time included.");

							// work out the number of days extra
							$extra_month_days_total = time_calculate_daynum( time_calculate_monthdate_last($period_data["date_start"]) );
							$extra_month_days_extra	= $extra_month_days_total - time_calculate_daynum($period_data["date_start"]);

							log_debug("services_invoicegen", "$extra_month_days_extra additional days ontop of started billing period");

							// calculate correct base fee
							$sql_service_obj->data[0]["price"] = ( ($sql_service_obj->data[0]["price"] / $extra_month_days_total) * $extra_month_days_extra ) + $sql_service_obj->data[0]["price"];

							// calculate number of included units - round up to nearest full unit
							if ($service_type != "licenses")
							{	
								$sql_service_obj->data[0]["included_units"] = sprintf("%d", ( ($sql_service_obj->data[0]["included_units"] / $extra_month_days_total) * $extra_month_days_extra ) + $sql_service_obj->data[0]["included_units"] );
							}
						}
					}



					/*
						Service Base Plan Item
					*/
					
					
					// start the item
					$invoice_item				= New invoice_items;
					
					$invoice_item->id_invoice		= $invoiceid;
					
					$invoice_item->type_invoice		= "ar";
					$invoice_item->type_item		= "standard";
					
					$itemdata = array();


					// chart ID
					$itemdata["chartid"]		= $sql_service_obj->data[0]["chartid"];

					// description
					$itemdata["description"]	= $sql_service_obj->data[0]["name_service"] ." from ". $period_data["date_start"] ." to ". $period_data["date_end"];
					$itemdata["description"]	.= "\n\n";
					$itemdata["description"]	.= $period_data["description"];

					// amount
					$itemdata["amount"]		= $sql_service_obj->data[0]["price"];


					// fetch all tax options for this service from the database
					//
					// note: if any options aren't suitable for the customer, the invoices
					// code will handle this for us and unselect them.
					//
					$sql_tax_obj		= New sql_query;
					$sql_tax_obj->string	= "SELECT taxid FROM services_taxes WHERE serviceid='". $period_data["serviceid"] ."'";
					$sql_tax_obj->execute();

					if ($sql_tax_obj->num_rows())
					{
						$sql_tax_obj->fetch_array();

						foreach ($sql_tax_obj->data as $data_tax)
						{
							$itemdata["tax_". $data_tax["taxid"] ] = "on";
						}

					} // end of loop through taxes


					// create item
					$invoice_item->prepare_data($itemdata);
					$invoice_item->action_create();

					unset($invoice_item);



					/*
						Service Usage Items

						Create another item on the invoice for any usage, provided that the service type is a usage service)
					*/

					if ($service_type == "generic_with_usage" || $service_type == "licenses" || $service_type == "time" || $service_type == "data_traffic")
					{
	
						// start the item
						$invoice_item				= New invoice_items;
					
						$invoice_item->id_invoice		= $invoiceid;
					
						$invoice_item->type_invoice		= "ar";
						$invoice_item->type_item		= "standard";
					
						$itemdata = array();


						// chart ID
						$itemdata["chartid"]		= $sql_service_obj->data[0]["chartid"];

						// description
						$itemdata["description"]	= $sql_service_obj->data[0]["name_service"] ." usage from ". $period_data["date_start"] ." to ". $period_data["date_end"];


						
						// calculate the amount to charge
						switch ($service_type)
						{
							case "generic_with_usage":
								/*
									GENERIC_WITH_USAGE

									This service is to be used for any non-traffic, non-time accounting service that needs to track usage. Examples of this
									could be counting the number of API requests, size of disk usage on a vhost, etc.
								*/

										

								/*
									Fetch usage amount
								*/
								
								$usage_obj					= New service_usage;
								$usage_obj->services_customers_id		= $period_data["services_customers_id"];
								$usage_obj->date_start				= $period_data["date_start"];
								$usage_obj->date_end				= $period_data["date_end"];
								
								if ($usage_obj->prepare_load_servicedata())
								{
									$usage_obj->fetch_usagedata();

									if ($usage_obj->data["total_byunits"])
									{
										$usage = $usage_obj->data["total_byunits"];
									}
									else
									{
										$usage = $usage_obj->data["total"];
									}
								}

								unset($usage_obj);


								/*
									Charge for the usage in units
								*/

								$unitname = $sql_service_obj->data[0]["units"];

								if ($usage > $sql_service_obj->data[0]["included_units"])
								{
									// there is excess usage that we can bill for.
									$usage_excess = $usage - $sql_service_obj->data[0]["included_units"];

									$itemdata["amount"] += ($usage_excess * $sql_service_obj->data[0]["price_extraunits"]);

									// description example:		Used 120 ZZ out of 50 ZZ included in plan
									//				Excess usage of 70 ZZ charged at $5.00 per ZZ
									$itemdata["description"] .= "\nUsed $usage $unitname out of ". $sql_service_obj->data[0]["included_units"] ." $unitname included in plan.";
									$itemdata["description"] .= "\nExcess usage of $usage_excess $unitname charged at ". $sql_service_obj->data[0]["price_extraunits"] ." per $unitname.";
								}
								else
								{

									// description example:		Used 120 ZZ out of 50 ZZ included in plan
									$itemdata["description"] .= "\nUsed $usage $unitname out of ". $sql_service_obj->data[0]["included_units"] ." $unitname included in plan.";
								}

							break;
							
							case "licenses":
								/*
									LICENSES

									No data usage, but there is a quantity field for the customer's account to specify the
									quantity of licenses that they have.
								*/

								// charge for any extra licenses
								if ($period_data["quantity"] > $sql_service_obj->data[0]["included_units"])
								{
									// there is excess usage that we can bill for.
									$licenses_excess = $period_data["quantity"] - $sql_service_obj->data[0]["included_units"];

									$itemdata["amount"] += ($licenses_excess * $sql_service_obj->data[0]["price_extraunits"]);

									// description example:		10 licences included
									//				2 additional licenses charged at $24.00 each
									$itemdata["description"] .= "\n". $sql_service_obj->data[0]["included_units"] ." ". $sql_service_obj->data[0]["units"] ." included";
									$itemdata["description"] .= "\n$licenses_excess additional ". $sql_service_obj->data[0]["units"] ." charged at ". $sql_service_obj->data[0]["price_extraunits"] ." each.";
								}
								else
								{
									// description example:		10 licenses
									$itemdata["description"] .= "\n". $period_data["quantity"] ." ". $period_data["units"] .".";
								}


							break;


							
							case "time":
							case "data_traffic":
								/*
									TIME or DATA_TRAFFIC

									Simular to the generic usage type, but instead of units being a text field, units
									is an ID to the service_units table.
								*/

								/*
									Fetch usage amount
								*/
								
								$usage_obj					= New service_usage;
								$usage_obj->services_customers_id		= $period_data["services_customers_id"];
								$usage_obj->date_start				= $period_data["date_start"];
								$usage_obj->date_end				= $period_data["date_end"];
								
								if ($usage_obj->prepare_load_servicedata())
								{
									$usage_obj->fetch_usagedata();

									if ($usage_obj->data["total_byunits"])
									{
										$usage = $usage_obj->data["total_byunits"];
									}
									else
									{
										$usage = $usage_obj->data["total"];
									}
								}
								
								unset($usage_obj);

								
								

								/*
									Charge for the usage in units
								*/

								$unitname = sql_get_singlevalue("SELECT name as value FROM service_units WHERE id='". $sql_service_obj->data[0]["units"] ."'");

								if ($usage > $sql_service_obj->data[0]["included_units"])
								{
									// there is excess usage that we can bill for.
									$usage_excess = $usage - $sql_service_obj->data[0]["included_units"];

									$itemdata["amount"] += ($usage_excess * $sql_service_obj->data[0]["price_extraunits"]);

									// description example:		Used 120 GB out of 50 GB included in plan
									//				Excess usage of 70 GB charged at $5.00 per GB
									$itemdata["description"] .= "\nUsed $usage $unitname out of ". $sql_service_obj->data[0]["included_units"] ." $unitname included in plan.";
									$itemdata["description"] .= "\nExcess usage of $usage_excess $unitname charged at ". $sql_service_obj->data[0]["price_extraunits"] ." per $unitname.";
								}
								else
								{
									// description example:		Used 10 out of 50 included units
									$itemdata["description"] .= "\nUsed $usage $unitname out of ". $sql_service_obj->data[0]["included_units"] ." $unitname included in plan.";
								}

							break;


						} // end of processing usage


						// fetch all tax options for this service from the database
						//
						// note: if any options aren't suitable for the customer, the invoices
						// code will handle this for us and unselect them.
						//
						$sql_tax_obj		= New sql_query;
						$sql_tax_obj->string	= "SELECT taxid FROM services_taxes WHERE serviceid='". $period_data["serviceid"] ."'";
						$sql_tax_obj->execute();

						if ($sql_tax_obj->num_rows())
						{
							$sql_tax_obj->fetch_array();

							foreach ($sql_tax_obj->data as $data_tax)
							{
								$itemdata["tax_". $data_tax["taxid"] ] = "on";
							}

						} // end of loop through taxes


						// create the item
						$invoice_item->prepare_data($itemdata);
						$invoice_item->action_create();

						unset($invoice_item);


					} // end if service is usage type


					/*
						Update the invoice details + Ledger

						Processes:
						- taxes
						- ledger
						- invoice summary

						We use the invoice_items class to perform these tasks, but we don't need
						to define an item ID for the functions being used to work.
					*/

					$invoice = New invoice_items;
					
					$invoice->id_invoice	= $invoiceid;
					$invoice->type_invoice	= "ar";

					$invoice->action_update_tax();
					$invoice->action_update_ledger();
					$invoice->action_update_total();

					unset($invoice);



					/*
						Update period information with invoiceid
					*/
					
					$sql_obj		= New sql_query;
					$sql_obj->string	= "UPDATE services_customers_periods SET invoiceid='$invoiceid' WHERE id='". $period_data["id"] . "'";
					$sql_obj->execute();
					
				} // end of processing periods


				// invoice creation complete - remove any notifications made by the invoice functions and return
				// our own notification
				$_SESSION["notification"]["message"] = array();

				log_write("notification", "inc_services_invoicegen", "New invoice $invoicecode for customer ". $customer_data["code_customer"] ." created");


				/*
						Send the invoice to the customer as a PDF via email
				*/

				if (sql_get_singlevalue("SELECT value FROM config WHERE name='EMAIL_ENABLE'") == "enabled")
				{
					if (sql_get_singlevalue("SELECT value FROM config WHERE name='ACCOUNTS_INVOICE_AUTOEMAIL'") == "enabled")
					{
						// load completed invoice data
						$invoice	= New invoice;
						$invoice->id	= $invoiceid;
						$invoice->type	= "ar";
						$invoice->load_data();

						// send email
						$invoice->email_invoice("system", $customer_data["name_contact"] ."<". $customer_data["contact_email"] .">", "", "", "Invoice $invoicecode", "Please see attached invoice in PDF format.");

						// complete
						unset ($invoice);
						log_write("notification", "inc_services_invoicegen", "Invoice $invoicecode has been emailed to customer (". $customer_data["contact_email"] .")");
					}
				}


			} // end of processing customers

		} // end of if customers exist
	}
	else
	{
		log_debug("inc_services_invoicegen", "No services assigned to customer $customerid");
	}


	return 1;
}



?>
