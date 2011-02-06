<?php
/*
	include/services/inc_services_invoicegen.php

	Provides functions for generating new invoices for customer services. These functions
	are called by the execute cronjob daily or whenever manually requested by a user from
	the web interface.
*/



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

					// usually we only need to generate a single period, however it is possible for a service to be
					// created with a historical date and in this situation we want to generate all the periods in the past
					// so that the customer gets a single invoice.
					//
					// therefore we keep looping, until there are no outstanding periods.
					$complete = 0;
					while ($complete == 0)
					{
						// fetch the period next date
						$sql_service_obj		= New sql_query;
						$sql_service_obj->string	= "SELECT date_period_next FROM services_customers WHERE services_customers.id='". $data["id"] ."' LIMIT 1";
						$sql_service_obj->execute();
						$sql_service_obj->fetch_array();

						// check if we need to generate a new period
						if (time_date_to_timestamp($sql_service_obj->data[0]["date_period_next"]) <= mktime())
						{
							log_debug("inc_services_invoicegen", "Generating billing period for service with next billing date of ". $sql_service_obj->data[0]["date_period_next"]);

							// the latest billing period has finished, we need to generate a new time period.
							if (!service_periods_add($data["id"], $billing_mode))
							{
								$_SESSION["error"]["message"][] = "Fatal error whilst trying to create new time period";
								return 0;
							}
						}
						else
						{
							unset($sql_service_obj);

							$complete = 1;
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


					// usually we only need to generate a single period, however it is possible for a service to be
					// created with a historical date and in this situation we want to generate all the periods in the past
					// so that the customer gets a single invoice.
					//
					// therefore we keep looping, until there are no outstanding periods.
					$complete = 0;
					while ($complete == 0)
					{
						// fetch the period next date
						$sql_service_obj		= New sql_query;
						$sql_service_obj->string	= "SELECT date_period_next FROM services_customers WHERE services_customers.id='". $data["id"] ."' LIMIT 1";
						$sql_service_obj->execute();
						$sql_service_obj->fetch_array();

						// check if we need to generate a new period
						if (time_date_to_timestamp($sql_service_obj->data[0]["date_period_next"]) <= $date_period_next)
						{
							log_debug("inc_services_invoicegen", "Generating advance billing period for service with next billing date of $date_period_next");

	
							// generate the new billing period (in advance)
							if (!service_periods_add($data["id"], $billing_mode))
							{
								$_SESSION["error"]["message"][] = "Fatal error whilst trying to create new time period";
								return 0;
							}
						}
						else
						{
							unset($sql_service_obj);

							$complete = 1;
						}
					}
				
				break;


				case "monthtelco":
				case "periodtelco":
					/*
						MONTHTELCO OR PERIODTELCO

						Create a new period on the date set in services_customers.date_period_next.

						Both periodend and monthend are treated the same way, the only difference is that when the new period
						is created, the date with either be +1 month (periodend), or the last day in the following month (monthend)
					*/

					log_debug("inc_services_invoicegen", "Processing monthtelco/periodtelco service type");

					// usually we only need to generate a single period, however it is possible for a service to be
					// created with a historical date and in this situation we want to generate all the periods in the past
					// so that the customer gets a single invoice.
					//
					// therefore we keep looping, until there are no outstanding periods.
					$complete = 0;
					while ($complete == 0)
					{
						// fetch the period next date
						$sql_service_obj		= New sql_query;
						$sql_service_obj->string	= "SELECT date_period_next FROM services_customers WHERE services_customers.id='". $data["id"] ."' LIMIT 1";
						$sql_service_obj->execute();
						$sql_service_obj->fetch_array();

						// check if we need to generate a new period
						if (time_date_to_timestamp($sql_service_obj->data[0]["date_period_next"]) <= mktime())
						{
							log_debug("inc_services_invoicegen", "Generating billing period for service with next billing date of ". $sql_service_obj->data[0]["date_period_next"]);

							// the latest billing period has finished, we need to generate a new time period.
							if (!service_periods_add($data["id"], $billing_mode))
							{
								$_SESSION["error"]["message"][] = "Fatal error whilst trying to create new time period";
								return 0;
							}
						}
						else
						{
							unset($sql_service_obj);

							$complete = 1;
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
	id_service_customer		ID of the service entry for the customer in services_customers.
	billing_mode			name of the billing mode

	Results
	0			failure
	1			success
*/
function service_periods_add($id_service_customer, $billing_mode)
{
	log_debug("inc_services_invoicegen", "Executing service_periods_add($id_service_customer, $billing_mode)");


	/*
		Fetch required information from services_customers (service-customer assignment table)
	*/
	
	$sql_custserv_obj		= New sql_query;
	$sql_custserv_obj->string	= "SELECT serviceid, date_period_first, date_period_next FROM services_customers WHERE id='$id_service_customer' LIMIT 1";
	$sql_custserv_obj->execute();
	$sql_custserv_obj->fetch_array();

	$serviceid		= $sql_custserv_obj->data[0]["serviceid"];
	$date_period_start	= $sql_custserv_obj->data[0]["date_period_next"];



	/*
		Handle new services

		If the service has not been billed before, the date_period_first value will have been set, but not the date_period_next value.

		For periodend, periodadvance, periodtelco billing modes, we just want to start from this date. However, for the monthend, monthadvance & monthtelco modes
		we need to bill from the first till the end of the month. Our current solution is to treat these services the same as periodend/periodadvance/periodtelco which
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
		case "weekly":
			$sql_add_string = "1 WEEK";
		break;

		case "fortnightly":
			$sql_add_string = "2 WEEK";
		break;

		case "monthly":
			$sql_add_string = "1 MONTH";
		break;

		case "6monthly":
			$sql_add_string = "6 MONTH";
		break;

		case "quarterly":
			$sql_add_string = "1 QUARTER";
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
		case "periodtelco":
			// PERIODEND / PERIODADVANCE / PERIODTELCO
			//
			// Periods start of any date of the month and end on date -1 of the next month
	
			// Add time to the date_period_start date.
			$date_period_next       = sql_get_singlevalue("SELECT DATE_ADD('$date_period_start', INTERVAL $sql_add_string ) as value");
			$date_period_end        = sql_get_singlevalue("SELECT DATE_SUB('$date_period_next', INTERVAL 1 DAY ) as value");
		break;

			
		case "monthend":
		case "monthadvance":
		case "monthtelco":
			// MONTHEND / MONTHADVANCE / MONTHTELCO
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

		case "periodtelco":
		case "monthtelco":
			// PERIODTELCO / MONTHTELCO
			//
			// With Telco periods, we need to bill on the first day of the new period.

			$date_period_billing = $data_period_start;

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
		Start Transaction
	*/

	$sql_obj = New sql_query;
	$sql_obj->trans_begin();


	/*
		Add a new period
	*/
	$sql_obj->string	= "INSERT INTO services_customers_periods (id_service_customer, date_start, date_end, date_billed) VALUES ('$id_service_customer', '$date_period_start', '$date_period_end', '$date_period_billing')";
	$sql_obj->execute();
			

	/*
		Update services_customers
	*/
	$sql_obj->string	= "UPDATE services_customers SET date_period_next='$date_period_next' WHERE id='$id_service_customer' LIMIT 1";
	$sql_obj->execute();



	/*
		Commit
	*/
	if (error_check())
	{
		$sql_obj->trans_rollback();

		log_write("error", "process", "An error occured whilst attempting to add a new period to a service. No changes were made.");
		return 0;
	}
	else
	{
		$sql_obj->trans_commit();
	}

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
	$sql_customers_obj->string	= "SELECT id, code_customer FROM customers";

	if ($customerid)
		$sql_customers_obj->string .= " WHERE id='$customerid' LIMIT 1";


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
								."services_customers.date_period_first, "
								."services_customers.id as id_service_customer, "
								."services_customers.quantity, "
								."services_customers.serviceid "
								."FROM services_customers_periods "
								."LEFT JOIN services_customers ON services_customers.id = services_customers_periods.id_service_customer "
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
					Start Transaction

					(one transaction per invoice)
				*/

				$sql_obj = New sql_query;
				$sql_obj->trans_begin();



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
				$menuid = sql_get_singlevalue("SELECT id as value FROM account_chart_menu WHERE value='ar_summary_account' LIMIT 1");

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
					log_write("error", "services_invoicegen", "No AR summary account could be found");

					$sql_obj->trans_rollback();
					return 0;
				}


				if (!$invoice->action_create())
				{
					log_write("error", "services_invoicegen", "Unexpected problem occured whilst attempting to create invoice.");

					$sql_obj->trans_rollback();
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
					$obj_service			= New service_bundle;

					$obj_service->option_type		= "customer";
					$obj_service->option_type_id		= $period_data["id_service_customer"];

					if (!$obj_service->verify_id_options())
					{
						log_write("error", "customers_services", "Unable to verify service ID of ". $period_data["id_service_customer"] ." as being valid.");
						return 0;
					}
		
					$obj_service->load_data();
					$obj_service->load_data_options();


					if ($obj_service->data["billing_mode_string"] == "monthend" || $obj_service->data["billing_mode_string"] == "monthadvance" || $obj_service->data["billing_mode_string"] == "monthtelco")
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
							$obj_service->data["price"] = ( ($obj_service->data["price"] / $extra_month_days_total) * $extra_month_days_extra ) + $obj_service->data["price"];

							// calculate number of included units - round up to nearest full unit
							if ($obj_service->data["typeid_string"] != "licenses")
							{	
								$obj_service->data["included_units"] = sprintf("%d", ( ($obj_service->data["included_units"] / $extra_month_days_total) * $extra_month_days_extra ) + $obj_service->data["included_units"] );
							}
						}
					}



					/*
						Service Base Plan Item

						Note: There can be a suitation where we shouldn't charge for the base plan
						fee, when the period end date is older than the service first start date.

						This can happen due to the migration mode, which creates a usage-only period
						before the actual plan starts properly.
					*/
					
					if (time_date_to_timestamp($period_data["date_period_first"]) < time_date_to_timestamp($period_data["date_end"]))
					{
						// date of the period is newer than the start date

						// start the item
						$invoice_item				= New invoice_items;
						
						$invoice_item->id_invoice		= $invoiceid;
						
						$invoice_item->type_invoice		= "ar";
						$invoice_item->type_item		= "service";
						
						$itemdata = array();


						// chart ID
						$itemdata["chartid"]		= $obj_service->data["chartid"];

						// service ID
						$itemdata["customid"]		= $obj_service->id;

						// description
						switch ($service_type)
						{
							case "phone_single":

								$itemdata["description"]	= $obj_service->data["name_service"] ." from ". $period_data["date_start"] ." to ". $period_data["date_end"] ." (". $obj_service->data["phone_ddi_single"] .")";
								
								if ($obj_service->data["description"])
								{
									$itemdata["description"]	.= "\n\n";
									$itemdata["description"]	.= $obj_service->data["description"];
								}
							break;

							default:
								$itemdata["description"]	= $obj_service->data["name_service"] ." from ". $period_data["date_start"] ." to ". $period_data["date_end"];

								if ($obj_service->data["description"])
								{
									$itemdata["description"]	.= "\n\n";
									$itemdata["description"]	.= $obj_service->data["description"];
								}
							break;
						}

					
						// is this service item part of a bundle? if it is, we shouldn't charge for the plan
						if ($obj_service->data["id_bundle_component"])
						{
							// no charge for the base plan, since this is handled by
							// the bundle item itself

							$itemdata["price"]		= "0.00";
							$itemdata["quantity"]		= 1;

							// append description
							$itemdata["description"]	.= " (part of bundle)";
						}
						else
						{
							// amount
							$itemdata["price"]		= $obj_service->data["price"];
							$itemdata["quantity"]		= 1;
							$itemdata["discount"]		= $obj_service->data["discount"];
						}


						// TODO: is this still needed? with the new service type logic?

						// fetch all tax options for this service from the database
						//
						// note: if any options aren't suitable for the customer, the invoicing
						// code will handle this for us and unselect them.
						//
						$sql_tax_obj		= New sql_query;
						$sql_tax_obj->string	= "SELECT taxid FROM services_taxes WHERE serviceid='". $obj_service->id ."'";
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
						$invoice_item->action_update();

						unset($invoice_item);
					}



					/*
						Service Usage Items

						Create another item on the invoice for any usage, provided that the service type is a usage service)
					*/

					$period_usage_data	= array();



					/*
						Depending on the service type, we need to handle the usage period in one of two ways:
						1. Invoice usage for the current period that has just ended (periodend/monthend)
						2. Invoice usage for the period BEFORE the current period. (periodtelco/monthtelco)
					*/

					if ($obj_service->data["billing_mode_string"] == "periodtelco" || $obj_service->data["billing_mode_string"] == "monthtelco")
					{
						log_write("debug", "service_invoicegen", "Determining previous period for telco-style usage billing");

						// fetch previous period (if any) - we can determine the previous by subtracting one day from the current period start date.
						// TODO: here


						// generate period end date
						$tmp_date			= explode("-", $period_data["date_start"]);

						$period_usage_data["date_end"]	= date("Y-m-d", mktime(0,0,0,$tmp_date[1], ($tmp_date[2] - 1), $tmp_date[0]));


						// fetch period data
						$sql_obj		= New sql_query;
						$sql_obj->string	= "SELECT id, date_start, date_end FROM services_customers_periods WHERE id_service_customer='". $period_data["id_service_customer"] ."' AND date_end='". $period_usage_data["date_end"] ."' LIMIT 1";
						$sql_obj->execute();

						if ($sql_obj->num_rows())
						{
							log_write("debug", "service_invoicegen", "Billing for seporate past usage period (". $period_data["date_start"] ." to ". $period_data["date_end"] ."");

							// there is a valid usage period
							$period_usage_data["active"]		= "yes";


							// fetch dates
							$sql_obj->fetch_array();

							$period_usage_data["id_service_customer"]	= $period_data["id_service_customer"];
							$period_usage_data["id"]			= $sql_obj->data[0]["id"];

							$period_usage_data["date_start"]		= $sql_obj->data[0]["date_start"];
							$period_usage_data["date_end"]			= $sql_obj->data[0]["date_end"];
						}
						else
						{
							log_write("debug", "service_invoicegen", "Not billing for past usage, as this appears to be the first plan period so no usage can exist yet");
						}

					}
					else
					{
						log_write("debug", "service_invoicegen", "Using plan period as data usage period (". $period_data["date_start"] ." to ". $period_data["date_end"] ."");


						// use current period
						$period_usage_data["active"]			= "yes";

						$period_usage_data["id_service_customer"]	= $period_data["id_service_customer"];
						$period_usage_data["id"]			= $period_data["id"];

						$period_usage_data["date_start"]		= $period_data["date_start"];
						$period_usage_data["date_end"]			= $period_data["date_end"];

					}


					/*
						Create usage items if there is a valid usage period
					*/
					if ($period_usage_data["active"])
					{
						switch ($obj_service->data["typeid_string"])
						{
							case "generic_with_usage":
								/*
									GENERIC_WITH_USAGE

									This service is to be used for any non-traffic, non-time accounting service that needs to track usage. Examples of this
									could be counting the number of API requests, size of disk usage on a vhost, etc.
								*/



								/*
									Usage Item Basics
								*/

								// start the item
								$invoice_item				= New invoice_items;
							
								$invoice_item->id_invoice		= $invoiceid;
							
								$invoice_item->type_invoice		= "ar";
								$invoice_item->type_item		= "service_usage";
							
								$itemdata = array();

								$itemdata["chartid"]		= $obj_service->data["chartid"];
								$itemdata["description"]	= $obj_service->data["name_service"] ." usage from ". $period_usage_data["date_start"] ." to ". $period_usage_data["date_end"];
								$itemdata["customid"]		= $obj_service->id;

											

								/*
									Fetch usage amount
								*/
								
								$usage_obj					= New service_usage_generic;
								$usage_obj->id_service_customer			= $period_usage_data["id_service_customer"];
								$usage_obj->date_start				= $period_usage_data["date_start"];
								$usage_obj->date_end				= $period_usage_data["date_end"];
								
								if ($usage_obj->load_data_service())
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

								$unitname = $obj_service->data["units"];

								if ($usage > $obj_service->data["included_units"])
								{
									// there is excess usage that we can bill for.
									$usage_excess = $usage - $obj_service->data["included_units"];

									// set item attributes
									$itemdata["price"]	= $obj_service->data["price_extraunits"];
									$itemdata["quantity"]	= $usage_excess;
									$itemdata["units"]	= $unitname;


									// description example:		Used 120 ZZ out of 50 ZZ included in plan
									//				Excess usage of 70 ZZ charged at $5.00 per ZZ
									$itemdata["description"] .= "\nUsed $usage $unitname out of ". $obj_service->data["included_units"] ." $unitname included in plan.";
									$itemdata["description"] .= "\nExcess usage of $usage_excess $unitname charged at ". $obj_service->data["price_extraunits"] ." per $unitname.";
								}
								else
								{

									// description example:		Used 120 ZZ out of 50 ZZ included in plan
									$itemdata["description"] .= "\nUsed $usage $unitname out of ". $obj_service->data["included_units"] ." $unitname included in plan.";
								}



								/*
									Add the item to the invoice
								*/

								$invoice_item->prepare_data($itemdata);
								$invoice_item->action_update();

								unset($invoice_item);


								/*
									Update usage value for period - this summary value is visable on the service
									history page and saves having to query lots of records to generate period totals.
								*/
								$sql_obj		= New sql_query;
								$sql_obj->string	= "UPDATE services_customers_periods SET usage_summary='$usage' WHERE id='". $period_usage_data["id"] ."' LIMIT 1";
								$sql_obj->execute();

							break;
		

							case "licenses":
								/*
									LICENSES

									No data usage, but there is a quantity field for the customer's account to specify the
									quantity of licenses that they have.
								*/


								/*
									Usage Item Basics
								*/

								// start the item
								$invoice_item				= New invoice_items;
							
								$invoice_item->id_invoice		= $invoiceid;
							
								$invoice_item->type_invoice		= "ar";
								$invoice_item->type_item		= "service_usage";
							
								$itemdata = array();

								$itemdata["chartid"]		= $obj_service->data["chartid"];
								$itemdata["description"]	= $obj_service->data["name_service"] ." usage from ". $period_usage_data["date_start"] ." to ". $period_usage_data["date_end"];
								$itemdata["customid"]		= $obj_service->id;


								/*
									Determine Additional Charges
								*/

								// charge for any extra licenses
								if ($period_data["quantity"] > $obj_service->data["included_units"])
								{
									// there is excess usage that we can bill for.
									$licenses_excess = $period_data["quantity"] - $obj_service->data["included_units"];


									// set item attributes
									$itemdata["price"]	= $obj_service->data["price_extraunits"];
									$itemdata["quantity"]	= $licenses_excess;
									$itemdata["units"]	= $obj_service->data["units"];


									// description example:		10 licences included
									//				2 additional licenses charged at $24.00 each
									$itemdata["description"] .= "\n". $obj_service->data["included_units"] ." ". $obj_service->data["units"] ." included";
									$itemdata["description"] .= "\n$licenses_excess additional ". $obj_service->data["units"] ." charged at ". $obj_service->data["price_extraunits"] ." each.";
								}
								else
								{
									// description example:		10 licenses
									$itemdata["description"] .= "\n". $period_data["quantity"] ." ". $period_data["units"] .".";
								}


								/*
									Add the item to the invoice
								*/

								$invoice_item->prepare_data($itemdata);
								$invoice_item->action_update();

								unset($invoice_item);
							break;


							
							case "time":
							case "data_traffic":
								/*
									TIME or DATA_TRAFFIC

									Simular to the generic usage type, but instead of units being a text field, units
									is an ID to the service_units table.
								*/



								/*
									Usage Item Basics
								*/

								// start the item
								$invoice_item				= New invoice_items;
							
								$invoice_item->id_invoice		= $invoiceid;
							
								$invoice_item->type_invoice		= "ar";
								$invoice_item->type_item		= "service_usage";
							
								$itemdata = array();

								$itemdata["chartid"]		= $obj_service->data["chartid"];
								$itemdata["description"]	= $obj_service->data["name_service"] ." usage from ". $period_usage_data["date_start"] ." to ". $period_usage_data["date_end"];
								$itemdata["customid"]		= $obj_service->id;



								/*
									Fetch usage amount
								*/
								
								$usage_obj					= New service_usage_generic;
								$usage_obj->id_service_customer			= $period_usage_data["id_service_customer"];
								$usage_obj->date_start				= $period_usage_data["date_start"];
								$usage_obj->date_end				= $period_usage_data["date_end"];
								
								if ($usage_obj->load_data_service())
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

								$unitname = sql_get_singlevalue("SELECT name as value FROM service_units WHERE id='". $obj_service->data["units"] ."'");

								if ($usage > $obj_service->data["included_units"])
								{
									// there is excess usage that we can bill for.
									$usage_excess = $usage - $obj_service->data["included_units"];

									// set item attributes
									$itemdata["price"]	= $obj_service->data["price_extraunits"];
									$itemdata["quantity"]	= $usage_excess;
									$itemdata["units"]	= $unitname;

									// description example:		Used 120 GB out of 50 GB included in plan
									//				Excess usage of 70 GB charged at $5.00 per GB
									$itemdata["description"] .= "\nUsed $usage $unitname out of ". $obj_service->data["included_units"] ." $unitname included in plan.";
									$itemdata["description"] .= "\nExcess usage of $usage_excess $unitname charged at ". $obj_service->data["price_extraunits"] ." per $unitname.";
								}
								else
								{
									// description example:		Used 10 out of 50 included units
									$itemdata["description"] .= "\nUsed $usage $unitname out of ". $obj_service->data["included_units"] ." $unitname included in plan.";
								}


								/*
									Add the item to the invoice
								*/

								$invoice_item->prepare_data($itemdata);
								$invoice_item->action_update();

								unset($invoice_item);


								/*
									Update usage value for period - this summary value is visable on the service
									history page and saves having to query lots of records to generate period totals.
								*/
								$sql_obj		= New sql_query;
								$sql_obj->string	= "UPDATE services_customers_periods SET usage_summary='$usage' WHERE id='". $period_usage_data["id"] ."' LIMIT 1";
								$sql_obj->execute();

							break;


							case "phone_single":
							case "phone_tollfree":
							case "phone_trunk":
								/*
									PHONE_* SERVICES

									The phone services are special and contain multiple usage items, for:
									* Additional DDI numbers
									* Additional Trunks

									There are also multiple items for the call charges, grouped into one
									item for each DDI.
								*/


								// setup usage object
								$usage_obj					= New service_usage_cdr;

								$usage_obj->id_service_customer			= $period_usage_data["id_service_customer"];
								$usage_obj->date_start				= $period_usage_data["date_start"];
								$usage_obj->date_end				= $period_usage_data["date_end"];

								$usage_obj->load_data_service();

								

								/*
									1. DDI CHARGES

									We need to fetch the total number of DDIs and see if there are any excess
									charges due to overage of the allocated amount in the plan.
								*/

								if ($obj_service->data["typeid_string"] == "phone_trunk")
								{

									// start service item
									$invoice_item				= New invoice_items;
								
									$invoice_item->id_invoice		= $invoiceid;
									
									$invoice_item->type_invoice		= "ar";
									$invoice_item->type_item		= "service_usage";
								
									$itemdata = array();

									$itemdata["chartid"]			= $obj_service->data["chartid"];
									$itemdata["customid"]			= $obj_service->id;


									// fetch DDI usage
									$usage = $usage_obj->load_data_ddi();

									// determine excess usage charges
									if ($usage > $obj_service->data["phone_ddi_included_units"])
									{
										// there is excess usage that we can bill for.
										$usage_excess			= $usage - $obj_service->data["phone_ddi_included_units"];

										// set item attributes
										$itemdata["price"]		= $obj_service->data["phone_ddi_price_extra_units"];
										$itemdata["quantity"]		= $usage_excess;
										$itemdata["units"]		= "DDIs";

										$itemdata["description"]	= $obj_service->data["phone_ddi_included_units"] ."x DDI numbers included in service plan plus additional ". $usage_excess ."x numbers.";
									}
									else
									{	
										// no charge for this item
										$itemdata["description"]	= $obj_service->data["phone_ddi_included_units"] ."x DDI numbers included in service plan";
									}


									// add trunk usage item
									$invoice_item->prepare_data($itemdata);
									$invoice_item->action_update();

									unset($invoice_item);
								}


								/*
									2. Trunk Charges
								*/

								if ($obj_service->data["typeid_string"] == "phone_trunk")
								{
									// fetch the number of trunks included in the plan, along with the number provided
									// we can then see if there are any excess charges for these


									// start service item
									$invoice_item				= New invoice_items;
								
									$invoice_item->id_invoice		= $invoiceid;
									
									$invoice_item->type_invoice		= "ar";
									$invoice_item->type_item		= "service_usage";
								
									$itemdata = array();

									$itemdata["chartid"]			= $obj_service->data["chartid"];
									$itemdata["customid"]			= $obj_service->id;

									// determine excess usage charges
									if ($obj_service->data["phone_trunk_quantity"] > $obj_service->data["phone_trunk_included_units"])
									{
										// there is excess usage that we can bill for.
										$usage_excess = $obj_service->data["phone_trunk_quantity"] - $obj_service->data["phone_trunk_included_units"];

										// set item attributes
										$itemdata["price"]		= $obj_service->data["phone_trunk_price_extra_units"];
										$itemdata["quantity"]		= $usage_excess;
										$itemdata["units"]		= "trunks";

										$itemdata["description"]	= $obj_service->data["phone_trunk_included_units"] ."x trunks included in service plan plus additional ". $usage_excess ."x trunks.";
									}
									else
									{	
										// no charge for this item
										$itemdata["description"]	= $obj_service->data["phone_trunk_included_units"] ."x trunks included in service plan";
									}


									// add trunk usage item
									$invoice_item->prepare_data($itemdata);
									$invoice_item->action_update();

									unset($invoice_item);
								}



								/*
									Call Charges

									Use CDR usage billing module to handle call charges.

								*/

								$usage_obj					= New service_usage_cdr;
								$usage_obj->id_service_customer			= $period_usage_data["id_service_customer"];
								$usage_obj->date_start				= $period_usage_data["date_start"];
								$usage_obj->date_end				= $period_usage_data["date_end"];
								
								if ($usage_obj->load_data_service())
								{
									$usage_obj->fetch_usage_calls();


									foreach ($usage_obj->data_ddi as $ddi)
									{
										// start service item
										$invoice_item				= New invoice_items;
									
										$invoice_item->id_invoice		= $invoiceid;
										
										$invoice_item->type_invoice		= "ar";
										$invoice_item->type_item		= "service_usage";
									
										$itemdata = array();

										$itemdata["chartid"]			= $obj_service->data["chartid"];
										$itemdata["customid"]			= $obj_service->id;

										// determine excess usage charges
										$itemdata["price"]			= $usage_obj->data[ $ddi ]["charges"];
										$itemdata["quantity"]			= "1";
										$itemdata["units"]			= "";
										$itemdata["description"]		= "Call charges for $ddi from ". $period_usage_data["date_start"] ." to ". $period_usage_data["date_end"] ."";


										// add trunk usage item
										$invoice_item->prepare_data($itemdata);
										$invoice_item->action_update();

										unset($invoice_item);
									}

								}
								
								unset($usage_obj);

							break;


						} // end of processing usage			



					} // end if service has a valid data period



					/*
						Set invoice ID for period - this prevents the period from being added to
						any other invoices and allows users to see which invoice it was billed under
					*/

					// set for plan period
					$sql_obj		= New sql_query;
					$sql_obj->string	= "UPDATE services_customers_periods SET invoiceid='$invoiceid' WHERE id='". $period_data["id"] . "' LIMIT 1";
					$sql_obj->execute();

					// set for usage period, if it differs
					if ($period_usage_data["id"] != $period_data["id"])
					{
						$sql_obj		= New sql_query;
						$sql_obj->string	= "UPDATE services_customers_periods SET invoiceid_usage='$invoiceid' WHERE id='". $period_usage_data["id"] . "' LIMIT 1";
						$sql_obj->execute();
					}
				

				} // end of processing periods


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
					



				/*
					Commit
				*/
				$sql_obj = New sql_query;

				if (error_check())
				{
					$sql_obj->trans_rollback();

					log_write("error", "inc_services_invoicegen", "An error occured whilst creating service invoice. No changes have been made.");
				}
				else
				{
					$sql_obj->trans_commit();

					// invoice creation complete - remove any notifications made by the invoice functions and return
					// our own notification
					$_SESSION["notification"]["message"] = array();

					log_write("notification", "inc_services_invoicegen", "New invoice $invoicecode for customer ". $customer_data["code_customer"] ." created");
				}



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

						if ($invoice->data["amount_total"] > 0)
						{
							// load customer information
							$arr_sql_contact		= sql_get_singlerow("SELECT id, contact FROM customer_contacts WHERE customer_id = '" .$customer_data["customerid"]. "' AND role = 'accounts' LIMIT 1");
							$arr_sql_contact_details	= sql_get_singlerow("SELECT detail AS contact_email FROM customer_contact_records WHERE contact_id = '" .$arr_sql_contact["id"]. "' AND type = 'email' LIMIT 1");
		
							// place the contact details into the customer details array.			
							$customer_data["name_contact"]	= $arr_sql_contact["contact"];			
							$customer_data["contact_email"] = $arr_sql_contact_details["contact_email"];


							// send email
							$invoice->email_invoice("system", $customer_data["name_contact"] ."<". $customer_data["contact_email"] .">", "", "", "Invoice $invoicecode", "Please see attached invoice in PDF format.");

							// complete
							log_write("notification", "inc_services_invoicegen", "Invoice $invoicecode has been emailed to customer (". $customer_data["contact_email"] .")");
						}
						else
						{
							// complete - invoice is for $0, so don't want to email out
							log_write("notification", "inc_services_invoicegen", "Invoice $invoicecode has not been emailed to the customer due to invoice being for $0.");
						}

						unset ($invoice);
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
