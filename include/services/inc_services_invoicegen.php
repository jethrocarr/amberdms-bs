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
	$sql_custserv_obj->string	= "SELECT services_customers.id, services.billing_mode, serviceid, date_period_next, date_period_last FROM services_customers LEFT JOIN services ON services.id = services_customers.serviceid WHERE services_customers.active='1'";

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
						$sql_service_obj->string	= "SELECT date_period_next, date_period_last FROM services_customers WHERE services_customers.id='". $data["id"] ."' LIMIT 1";
						$sql_service_obj->execute();
						$sql_service_obj->fetch_array();

						// check if the service has ended
						if ($sql_service_obj->data[0]["date_period_next"] == "0000-00-00" &&  $sql_service_obj->data[0]["date_period_last"] != "0000-00-00")
						{
							// service has ended - no next date set, but a last date set
							unset($sql_service_obj);

							$complete = 1;
						}
						elseif (time_date_to_timestamp($sql_service_obj->data[0]["date_period_next"]) <= mktime())
						{
							// check if we need to generate a new period
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
						$sql_service_obj->string	= "SELECT date_period_next, date_period_last FROM services_customers WHERE services_customers.id='". $data["id"] ."' LIMIT 1";
						$sql_service_obj->execute();
						$sql_service_obj->fetch_array();

						// check if we need to generate a new period
						if ($sql_service_obj->data[0]["date_period_next"] == "0000-00-00" &&  $sql_service_obj->data[0]["date_period_last"] != "0000-00-00")
						{
							// service has ended - no next date set, but a last date set
							unset($sql_service_obj);

							$complete = 1;
						}
						elseif (time_date_to_timestamp($sql_service_obj->data[0]["date_period_next"]) <= $date_period_next)
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
						$sql_service_obj->string	= "SELECT date_period_next, date_period_last FROM services_customers WHERE services_customers.id='". $data["id"] ."' LIMIT 1";
						$sql_service_obj->execute();
						$sql_service_obj->fetch_array();

						// check if we need to generate a new period
						if ($sql_service_obj->data[0]["date_period_next"] == "0000-00-00" &&  $sql_service_obj->data[0]["date_period_last"] != "0000-00-00")
						{
							// service has ended - no next date set, but a last date set
							unset($sql_service_obj);

							$complete = 1;
						}
						elseif (time_date_to_timestamp($sql_service_obj->data[0]["date_period_next"]) <= mktime())
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

	The logic is smart enough to handle services with an ending date, to ensure that they get a proper last date period
	correctly handled.

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
	$sql_custserv_obj->string	= "SELECT serviceid, date_period_first, date_period_next, date_period_last FROM services_customers WHERE id='$id_service_customer' LIMIT 1";
	$sql_custserv_obj->execute();
	$sql_custserv_obj->fetch_array();

	$serviceid		= $sql_custserv_obj->data[0]["serviceid"];
	$date_period_start	= $sql_custserv_obj->data[0]["date_period_next"];
	$date_period_last	= $sql_custserv_obj->data[0]["date_period_last"];

	if (empty($date_period_last) || $date_period_last == "0000-00-00")
	{
		$date_period_last = NULL;
	}



	/*
		Has the service reached it's end date?
	*/

	if ($date_period_last)
	{
		/*
			Is deactivation required?
		*/

		if (time_date_to_timestamp($date_period_last) <= time())
		{
			log_write("debug", "inc_services_invoicegen", "Service $id_service_customer has reached deactivation date ($date_period_last), disabling service.");

			// disable the service
			$obj_service				= New customer_services;
			$obj_service->id_service_customer	= $id_service_customer;

			$obj_service->verify_id_service_customer();
			$obj_service->service_disable();

			unset($obj_service);
		}


		/*
			Have we reached the end of the service with the current period?
		*/

		if ($date_period_start == $date_period_last)	
		{
			// start date the same as the last date
			log_write("debug", "inc_services_invoicegen", "Service $id_service_customer is due to be deactived on $date_period_last, new period due to start on $date_period_start, preventing new period generation and making no changes.");

			return 1;
		}


		/*
			Catch any glitches, where the period start date is newer than the period last date, and correct.
		*/

		if (time_date_to_timestamp($date_period_start) > time_date_to_timestamp($date_period_last))
		{
			log_write("debug", "inc_services_invoicegen", "Service start date of $date_period_start, but last period date is for $date_period_last - adjusting start date to match last");

			$date_period_start = $date_period_last;
		}
	}



	/*
		Handle new services

		If the service has not been billed before, the date_period_first value will have been set, but not the date_period_next value.
	*/

	if ($sql_custserv_obj->data[0]["date_period_next"] == "0000-00-00")
	{
		$date_period_start = $sql_custserv_obj->data[0]["date_period_first"];
	}



	/*
		Fetch Dates
	*/

	$billing_cycle	= sql_get_singlevalue("SELECT billing_cycles.name as value FROM services LEFT JOIN billing_cycles ON billing_cycles.id = services.billing_cycle WHERE services.id='$serviceid'");

	$dates		= service_period_dates_generate($date_period_start, $billing_cycle, $billing_mode);

	$date_period_start	= $dates["start"];
	$date_period_end	= $dates["end"];
	$date_period_next	= $dates["next"];



	/*
		Handle Last Date

		If the service has reached the date_period_last, we need to flag the period as being the last - this means
		if an end/last date is set half way during a period, the period will be changed into a partial period.
	*/

	if ($date_period_last)
	{
		if (time_date_to_timestamp($date_period_last) <= time_date_to_timestamp($date_period_end))
		{
			/*
				Period ending date is later than the last period date - adjust the end date to align.
			*/

			$date_period_end	= $date_period_last;
			$date_period_next	= "0000-00-00";
		}


		if ($date_period_last == $date_period_end)
		{
			/*
				Period end date same as the service last date.
			*/

			$date_period_next	= "0000-00-00";
		}
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

			$date_period_billing = $date_period_start;

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

	log_write("debug", "inc_customers", "Calculated billing date of \"". $date_period_billing ."\" for service billing mode of \"". $billing_mode ."\"");



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
		If the period is ending, we need to create a special 0-day period - this is used
		to account for usage on services, by having a final period with no service charge
		but with usage for the previous (final) period.
	*/

	if ($date_period_last && $date_period_next == "0000-00-00")
	{
		/*
			This period is for one day after the service terminates - it will force an invoice to be generated for the
			terminated service, with $0 plan charges and any usage charges that apply.

			Depending on configuration, the customer may or may not recieve a copy of the invoice.
		*/

		$sql_obj->string	= "INSERT INTO services_customers_periods (id_service_customer, date_start, date_end, date_billed) VALUES ('$id_service_customer',  DATE_ADD('$date_period_end', INTERVAL 1 DAY), DATE_ADD('$date_period_end', INTERVAL 1 DAY), DATE_ADD('$date_period_billing', INTERVAL 1 DAY))";
		$sql_obj->execute();
	}


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
	service_period_dates_generate

	Takes the provided start date, billing cycle and mode, returns the start, end and next billing date
	for the selected period.
	
	Fields
	date_period_start	YYYY-MM-DD
	billing_cycle		String of billing cycle name
	billing_mode		String of billing mode name

	Returns
	0		Failure
	array		Associative array, keys "start", "end", "next"
*/

function service_period_dates_generate($date_period_start, $billing_cycle, $billing_mode)
{
	log_write("debug", "inc_service_invoicegen", "Executing service_period_dates_generate($date_period_start, $billing_cycle, $billing_mode)");


	/*
		Calculate the new dates for the billing period

		We use MySQL's DATE_ADD function to do the month caluclations for us, since it is smart
		enough to handle the different month lengths.

			For example:
			DATE_ADD('2010-01-31', INTERVAL 1 MONTH )		==	2010-02-28
			DATE_ADD('2010-01-07', INTERVAL 1 MONTH )		==	2010-02-07
		
	*/

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
				log_debug("inc_services_invoicegen", "Note: This period is the first period for this service. Performing partial/long period handling.");

				/*
					The service is starting not on the first day of the month - the most likely cause is that this
				 	is the first time a new service is being billed for a customer.

					For periodend, periodadvance, periodtelco billing modes, we just want to start from this 
					date. 
					
					However, for the monthend, monthadvance & monthtelco modes we need to bill from the first 
					till the end of the month - we handle the irregular time by either creating a smaller-than-usual
					period or by creating a larger than usual.
				*/

				if ($GLOBALS["config"]["SERVICE_PARTPERIOD_MODE"] == "seporate")
				{
					log_debug("inc_services_invoicegen", "Executing SERVICE_PARTPERIOD_MODE == seporate");

					// SEPORATE
					//
					// we need to generate a partital period invoice for the first part of the billing month
					//
					//	eg:	14-02-2010 -> 28-02-2010
					//		01-03-2010 -> 31-03-2010
					//

					// fetch the end of the month date
					$date_period_end	= sql_get_singlevalue("SELECT LAST_DAY('$date_period_start') as value");

					// fetch the next period's start date (the first of the next month)
					$date_period_next	= sql_get_singlevalue("SELECT DATE_ADD('$date_period_end', INTERVAL 1 DAY ) as value");

				}
				else
				{
					log_debug("inc_services_invoicegen", "Executing SERVICE_PARTPERIOD_MODE == merge");

					// MERGE
					//
					// we need to generate a period end date for the end of the next month, so that the first period is one
					// whole month + the extra number of days.
					//
					//	eg:	14-02-2010 -> 31-03-2010
					//

					// Add time to the date_period_start date.
					$date_period_end	= sql_get_singlevalue("SELECT DATE_ADD('$date_period_start', INTERVAL $sql_add_string ) as value");

					// fetch the end of the month date
					$date_period_end	= sql_get_singlevalue("SELECT LAST_DAY('$date_period_end') as value");

					// fetch the next period's start date (the first of the next month)
					$date_period_next	= sql_get_singlevalue("SELECT DATE_ADD('$date_period_end', INTERVAL 1 DAY ) as value");
				}
			}
			else
			{
				// regular billing period - ie: not the first time.
				//
				// We need to generate the period as the end of the month for the period range (month/year/etc). This code is a bit
				// confusing, essentially we need to add the period to the current date, minus by one month, to get the next date.
				//
				// This is a bit overly complicated for monthly services, but makes sense for longer services such as yearly:
				//
				// For example:
				//
				//	Monthly Service:
				//
				//	Period start date:	2012-01-01
				//	Period +1month:		2012-02-01
				//	Period -1month:		2012-01-01
				//	Period Last_day:	2012-01-31
				//
				//	Yearly Service:
				//
				//	Period start date:	2012-01-01
				//	Period +12months:	2013-01-01
				//	Period -1month:		2012-12-01
				//	Period Last_day:	2012-12-31
				//
				//
				//

						
				// fetch the end of the month date
				$date_period_end	= sql_get_singlevalue("SELECT DATE_ADD('$date_period_start', INTERVAL $sql_add_string) as value");
				$date_period_end	= sql_get_singlevalue("SELECT DATE_SUB('$date_period_end', INTERVAL 1 MONTH) as value");
				$date_period_end	= sql_get_singlevalue("SELECT LAST_DAY('$date_period_end') as value");

				// fetch the next period's start date (the first of the next month)
				$date_period_next	= sql_get_singlevalue("SELECT DATE_ADD('$date_period_end', INTERVAL 1 DAY ) as value");
			}


		break;
	}


	// return
	$return = array();

	$return["start"]	= $date_period_start;
	$return["end"]		= $date_period_end;
	$return["next"]		= $date_period_next;

	return $return;

} // end of service_period_dates_generate







/*
	service_invoices_generate

	Processes all the service periods for customers, and bills accordingly. All the calcuations to work out which
	services need to be billed have been performed by the service_periods_generate function, so this function
	simply needs to get a list of unbilled invoices from services_customers_periods and perform billing.

	This function is smart enough to put multiple services on the same invoice if they fall on the same billing date.

	Values
	customerid		(optional) ID of the customer account to generate new period
				information for. If blank, will execute for all customers.

	Results
	0			failure
	1			success
*/
function service_invoices_generate($customerid = NULL)
{
	log_debug("inc_services_invoicegen", "Executing service_invoices_generate($customerid)");


	/*
		Invoice Report Statistics
	*/

	$invoice_stats			= array();
	$invoice_stats["time_start"]	= time();
	$invoice_stats["total"]		= 0;
	$invoice_stats["total_failed"]	= 0;



	/*
		Run through all the customers
	*/
	$sql_customers_obj		= New sql_query;
	$sql_customers_obj->string	= "SELECT id, code_customer, name_customer FROM customers";

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
								."services_customers_periods.rebill, "
								."services_customers_periods.invoiceid, "
								."services_customers_periods.invoiceid_usage, "
								."services_customers_periods.date_start, "
								."services_customers_periods.date_end, "
								."services_customers.date_period_first, "
								."services_customers.date_period_next, "
								."services_customers.date_period_last, "
								."services_customers.id as id_service_customer, "
								."services_customers.quantity, "
								."services_customers.serviceid "
								."FROM services_customers_periods "
								."LEFT JOIN services_customers ON services_customers.id = services_customers_periods.id_service_customer "
								."WHERE "
								."services_customers.customerid='". $customer_data["id"] ."' "
								."AND (invoiceid = '0' OR rebill = '1')"
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

				$invoice->prepare_date_shift();

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

					/*
						TODO:

						We should be able to re-bill usage here when needed with a clever cheat - if we load the periods to be billed,
						we can then ignore the plan item, and rebill the usage item.
					*/

					$period_data["mode"] = "standard";


					if ($period_data["rebill"])
					{
						if (!$period_data["invoiceid_usage"] && $period_data["invoiceid"])
						{
							// the selected period has been billed, but the usage has been flagged for rebilling - we need to *ignore* the base plan
							// item and only bill for the usage range.

							$period_data["mode"] = "rebill_usage";
						}
					}



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


					// ratio is used to adjust prices for partial periods
					$ratio = 1;


					if ($obj_service->data["billing_mode_string"] == "monthend" || $obj_service->data["billing_mode_string"] == "monthadvance" || $obj_service->data["billing_mode_string"] == "monthtelco")
					{
						log_debug("services_invoicegen", "Invoice bills by month date");

						/*
							Handle monthly billing

							Normally, monthly billing is easy, however the very first billing period is special, as it may span a more than 1 month, or
							consist of less than the full month, depending on the operational mode.

							SERVICE_PARTPERIOD_MODE == seporate

								If a service is started on 2008-01-09, the end date of the billing period will be 2008-01-31, which is less
								than one month - 22 days.

								We can calculate with:

								( standard_cost / normal_month_num_days ) * num_days_in_partial_month == total_amount


							SERVICE_PARTPERIOD_MODE == merge

								If a service is started on 2008-01-09, the end of the billing period will be 2008-02-29, which is 1 month + 21 day.

								We can calculate with:

								( standard_cost / normal_month_num_days ) * num_days_in_partial_month == extra_amount
								total_amount = (extra_amount + normal_amount)

							Note: we do not increase included units for licenses service types, since these need to remain fixed and do not
							scale with the month.
						*/

						// check if the period start date is not the first of the month - this means that the period
						// is an inital partial period.

						if (time_calculate_daynum($period_data["date_start"]) != "01")
						{
							// very first billing month
							log_write("debug", "services_invoicegen", "First billing month for this service, adjusting pricing to suit days.");

							// figure out normal period length - rememeber, whilst service may be configured to align monthly, it needs to
							// handle pricing calculations for variations such as month, year, etc.
							

							// get the number of days of a regular period - this correctly handles, month, year, etc
							// 1. generate the next period dates, this will be of regular length
							// 2. calculate number of days

							$tmp_dates = service_period_dates_generate($period_data["date_period_next"], $obj_service->data["billing_cycle_string"], $obj_service->data["billing_mode_string"]);

							$regular_period_num_days = sql_get_singlevalue("SELECT DATEDIFF('". $tmp_dates["end"] ."', '". $tmp_dates["start"] ."') as value");


							// process for short/long periods
							//
							if ($GLOBALS["config"]["SERVICE_PARTPERIOD_MODE"] == "seporate")
							{
								log_write("debug", "services_invoicegen", "Adjusting for partial month period (SERVICE_PARTPERIOD_MODE == seporate)");


								// work out the total number of days in partial month
								$short_month_days_total = time_calculate_daynum( time_calculate_monthdate_last($period_data["date_start"]) );
								$short_month_days_short	= $short_month_days_total - time_calculate_daynum($period_data["date_start"]);

								log_write("debug", "services_invoicegen", "Short initial billing period of $short_month_days_short days");


								// calculate correct base fee
								$obj_service->data["price"] = ($obj_service->data["price"] / $regular_period_num_days) * $short_month_days_short;

								// calculate ratio
								$ratio = ($short_month_days_short / $regular_period_num_days);

								log_write("debug", "services_invoicegen", "Calculated service bill ratio of $ratio to handle short period.");
							}
							else
							{
								log_write("debug", "services_invoicegen", "Adjusting for extended month period (SERVICE_PARTPERIOD_MODE == merge");
							
								// work out the number of days extra
								$extra_month_days_total = time_calculate_daynum( time_calculate_monthdate_last($period_data["date_start"]) );
								$extra_month_days_extra	= $extra_month_days_total - time_calculate_daynum($period_data["date_start"]);

								log_debug("services_invoicegen", "$extra_month_days_extra additional days ontop of started billing period");

								// calculate correct base fee
								$obj_service->data["price"] = ( ($obj_service->data["price"] / $regular_period_num_days) * $extra_month_days_extra ) + $obj_service->data["price"];
								
								// calculate ratio
								$ratio = (($extra_month_days_extra + $extra_month_days_total) / $regular_period_num_days);

								log_write("debug", "services_invoicegen", "Calculated service bill ratio of $ratio to handle extended period.");

							}
						}
					}



					/*
						Service Last Period Handling

						If this is the last period for a service, it may be of a different link to the regular full period term
						- this effects both month and period based services.
					*/

					if ($period_data["date_period_last"] != "0000-00-00")
					{
						log_write("debug", "services_invoicegen", "Service has a final period date set (". $period_data["date_period_last"] .")");

						if ($period_data["date_period_last"] == $period_data["date_end"] || time_date_to_timestamp($period_data["date_period_last"]) < time_date_to_timestamp($period_data["date_end"]))
						{
							log_write("debug", "services_invoicegen", "Service is a final period, checking for time adjustment (if any)");

							// fetch the regular end date
							$orig_dates = service_period_dates_generate($period_data["date_start"], $obj_service->data["billing_cycle_string"], $obj_service->data["billing_mode_string"]);

							if ($orig_dates["end"] != $period_data["date_end"])
							{
								// work out the total number of days
								$time = NULL;

								$time["start"]		= time_date_to_timestamp($period_data["date_start"]);

								$time["end_orig"]	= time_date_to_timestamp($orig_dates["end"]);
								$time["end_new"]	= time_date_to_timestamp($period_data["date_end"]);

								$time["orig_days"]	= sprintf("%d", ($time["end_orig"] - $time["start"]) / 86400);
								$time["new_days"]	= sprintf("%d", ($time["end_new"] - $time["start"]) / 86400);

								log_write("debug", "services_invoicegen", "Short initial billing period of ". $time["new_days"] ." days rather than expected ". $time["orig_days"] ."");


								// calculate correct base fee
								$obj_service->data["price"] = ($obj_service->data["price"] / $time["orig_days"]) * $time["new_days"];

								// calculate ratio
								$ratio = ($time["new_days"] / $time["orig_days"]);

								log_write("debug", "services_invoicegen", "Calculated service bill ratio of $ratio to handle short period.");

								unset($time);
							}
							else
							{
								log_write("debug", "services_invoicegen", "Final service period is regular size, no adjustment required.");
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
					
					if (time_date_to_timestamp($period_data["date_period_first"]) < time_date_to_timestamp($period_data["date_end"]) && $period_data["mode"] == "standard")
					{
						// date of the period is newer than the start date
						log_write("debug", "inc_service_invoicegen", "Generating base plan item for period ". $period_data["date_start"] ." to ". $period_data["date_end"] ."");


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

						// no units apply
						$itemdata["units"]		= "";


						// description
						switch ($obj_service->data["typeid_string"])
						{
							case "phone_single":

								$itemdata["description"]	= addslashes($obj_service->data["name_service"]) ." from ". $period_data["date_start"] ." to ". $period_data["date_end"] ." (". $obj_service->data["phone_ddi_single"] .")";
								
								if ($obj_service->data["description"])
								{
									$itemdata["description"]	.= "\n\n";
									$itemdata["description"]	.= addslashes($obj_service->data["description"]);
								}
							break;

							default:
								$itemdata["description"]	= addslashes($obj_service->data["name_service"]) ." from ". $period_data["date_start"] ." to ". $period_data["date_end"];

								if ($obj_service->data["description"])
								{
									$itemdata["description"]	.= "\n\n";
									$itemdata["description"]	.= addslashes($obj_service->data["description"]);
								}
							break;
						}

					
						// handle final periods
						if ($period_data["date_period_last"] != "0000-00-00" && $period_data["date_start"] == $period_data["date_end"])
						{
							$itemdata["description"]	= addslashes($obj_service->data["name_service"]) ." service terminated as of ". $period_data["date_end"] ."";
						}


						// is this service item part of a bundle? if it is, we shouldn't charge for the plan
						if (!empty($obj_service->data["id_bundle_component"]))
						{
							// no charge for the base plan, since this is handled by
							// the bundle item itself

							$itemdata["price"]		= "0.00";
							$itemdata["quantity"]		= 1;
							$itemdata["discount"]		= "0";

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


						// create item
						$invoice_item->prepare_data($itemdata);
						$invoice_item->action_update();

						unset($invoice_item);
					}
					else
					{
						log_write("debug", "inc_service_invoicegen", "Skipping base plan item generation, due to migration or rebill mode operation");
					}



					/*
						Service Usage Items

						Create another item on the invoice for any usage, provided that the service type is a usage service
					*/

					$period_usage_data	= array();



					/*
						Depending on the service type, we need to handle the usage period in one of two ways:
						1. Invoice usage for the current period that has just ended (periodend/monthend)
						2. Invoice usage for the period BEFORE the current period. (periodtelco/monthtelco)
					*/

					if ($obj_service->data["billing_mode_string"] == "periodtelco" || $obj_service->data["billing_mode_string"] == "monthtelco")
					{
						log_write("debug", "service_invoicegen", "Determining previous period for telco-style usage billing (if any)");

						if ($period_data["mode"] == "standard")
						{
							// fetch previous period (if any) - we can determine the previous by subtracting one day from the current period start date.
							$tmp_date			= explode("-", $period_data["date_start"]);

							$period_usage_data["date_end"]	= date("Y-m-d", mktime(0,0,0,$tmp_date[1], ($tmp_date[2] - 1), $tmp_date[0]));
						}
						elseif ($period_data["mode"] == "rebill_usage")
						{
							// use the period as the usage period
							log_write("debug", "service_invoicegen", "Using the selected period ". $period_usage_data["date_end"] ." for rebill_usage mode");

							$period_usage_data["date_end"]	= $period_data["date_end"];
						}


						// fetch period data to confirm previous period
						$sql_obj		= New sql_query;
						$sql_obj->string	= "SELECT id, date_start, date_end FROM services_customers_periods WHERE id_service_customer='". $period_data["id_service_customer"] ."' AND date_end='". $period_usage_data["date_end"] ."' LIMIT 1";
						$sql_obj->execute();

						if ($sql_obj->num_rows())
						{
							log_write("debug", "service_invoicegen", "Billing for seporate past usage period");

							// there is a valid usage period
							$period_usage_data["active"]		= "yes";


							// fetch dates
							$sql_obj->fetch_array();

							$period_usage_data["id_service_customer"]	= $period_data["id_service_customer"];
							$period_usage_data["id"]			= $sql_obj->data[0]["id"];

							$period_usage_data["date_start"]		= $sql_obj->data[0]["date_start"];
							$period_usage_data["date_end"]			= $sql_obj->data[0]["date_end"];


							// tracing
							log_write("debug", "service_invoicegen", "Current period is (". $period_data["date_start"] ." to ". $period_data["date_end"] ."), usage period is (". $period_usage_data["date_start"] ." to ". $period_usage_data["date_end"] .")");

							// reset ratio
							$ratio = 1;


							/*
								TODO: This code is a replicant of the section further above for calculating partial
								periods and should really be functionalised as part of service usage continual improvements
							*/

							// calculate usage abnormal period
							if ($obj_service->data["billing_mode_string"] == "monthend" || $obj_service->data["billing_mode_string"] == "monthadvance" || $obj_service->data["billing_mode_string"] == "monthtelco")
							{
								log_debug("services_invoicegen", "Usage period service bills by month date");

								if (time_calculate_daynum($period_usage_data["date_start"]) != "01")
								{
									// very first billing month
									log_write("debug", "services_invoicegen", "First billing month for this usage period, adjusting pricing to suit days.");
										
									if ($GLOBALS["config"]["SERVICE_PARTPERIOD_MODE"] == "seporate")
									{
										log_write("debug", "services_invoicegen", "Adjusting for partial month period (SERVICE_PARTPERIOD_MODE == seporate)");

										// work out the total number of days
										$short_month_days_total = time_calculate_daynum( time_calculate_monthdate_last($period_usage_data["date_start"]) );
										$short_month_days_short	= $short_month_days_total - time_calculate_daynum($period_usage_data["date_start"]);

										log_write("debug", "services_invoicegen", "Short initial billing period of $short_month_days_short days");

										// calculate ratio
										$ratio = ($short_month_days_short / $short_month_days_total);

										log_write("debug", "services_invoicegen", "Calculated service bill ratio of $ratio to handle short period.");
									}
									else
									{
										log_write("debug", "services_invoicegen", "Adjusting for extended month period (SERVICE_PARTPERIOD_MODE == merge");
										
										// work out the number of days extra
										$extra_month_days_total = time_calculate_daynum( time_calculate_monthdate_last($period_usage_data["date_start"]) );
										$extra_month_days_extra	= $extra_month_days_total - time_calculate_daynum($period_usage_data["date_start"]);

										log_debug("services_invoicegen", "$extra_month_days_extra additional days ontop of started billing period");

										// calculate ratio
										$ratio = (($extra_month_days_extra + $extra_month_days_total) / $extra_month_days_total);

										log_write("debug", "services_invoicegen", "Calculated service bill ratio of $ratio to handle extended period.");
									}
								}

							} // end of calculate usage abnormal period


							if ($period_data["date_period_last"] != "0000-00-00")
							{
								log_write("debug", "services_invoicegen", "Service has a final period date set (". $period_data["date_period_last"] .")");

								if ($period_data["date_period_last"] == $period_usage_data["date_end"] || (time_date_to_timestamp($period_data["date_period_last"]) < time_date_to_timestamp($period_usage_data["date_end"]) ) )
								{
									log_write("debug", "services_invoicegen", "Service is a final period, checking for time adjustment (if any)");

									// fetch the regular end date
									$orig_dates = service_period_dates_generate($period_usage_data["date_start"], $obj_service->data["billing_cycle_string"], $obj_service->data["billing_mode_string"]);

									if ($orig_dates["end"] != $period_usage_data["date_end"])
									{
										// work out the total number of days
										$time = NULL;

										$time["start"]		= time_date_to_timestamp($period_usage_data["date_start"]);

										$time["end_orig"]	= time_date_to_timestamp($orig_dates["end"]);
										$time["end_new"]	= time_date_to_timestamp($period_usage_data["date_end"]);

										$time["orig_days"]	= sprintf("%d", ($time["end_orig"] - $time["start"]) / 86400);
										$time["new_days"]	= sprintf("%d", ($time["end_new"] - $time["start"]) / 86400);

										log_write("debug", "services_invoicegen", "Short initial billing period of ". $time["new_days"] ." days rather than expected ". $time["orig_days"] ."");


										// calculate correct base fee
										$obj_service->data["price"] = ($obj_service->data["price"] / $time["orig_days"]) * $time["new_days"];

										// calculate ratio
										$ratio = ($time["new_days"] / $time["orig_days"]);

										log_write("debug", "services_invoicegen", "Calculated service bill ratio of $ratio to handle short period.");

										unset($time);
									}
									else
									{
										log_write("debug", "services_invoicegen", "Final service period is regular size, no adjustment required.");
									}
								}
							}

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
					if (!empty($period_usage_data["active"]))
					{
						log_write("debug", "service_invoicegen", "Creating usage items due to active usage period");

						switch ($obj_service->data["typeid_string"])
						{
							case "generic_with_usage":
								/*
									GENERIC_WITH_USAGE

									This service is to be used for any non-traffic, non-time accounting service that needs to track usage. Examples of this
									could be counting the number of API requests, size of disk usage on a vhost, etc.
								*/

								log_write("debug", "service_invoicegen", "Processing usage items for generic_with_usage");



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
								$itemdata["description"]	= addslashes($obj_service->data["name_service"]) ." usage from ". $period_usage_data["date_start"] ." to ". $period_usage_data["date_end"];
								$itemdata["customid"]		= $obj_service->id;
								$itemdata["discount"]		= 0;

								
								/*
									Adjust Included Units to handle partial or extended periods
								*/

								if ($ratio != "1")
								{
									$obj_service->data["included_units"] = sprintf("%d", ($obj_service->data["included_units"] * $ratio ));
								}


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

								$unitname = addslashes($obj_service->data["units"]);

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
								
								log_write("debug", "service_invoicegen", "Processing usage items for licenses");


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
								$itemdata["description"]	= addslashes($obj_service->data["name_service"]) ." usage from ". $period_usage_data["date_start"] ." to ". $period_usage_data["date_end"];
								$itemdata["customid"]		= $obj_service->id;
								$itemdata["discount"]		= 0;


								/*
									Determine Additional Charges
								*/

								// charge for any extra licenses
								if ($period_data["quantity"] > $obj_service->data["included_units"])
								{
									// there is excess usage that we can bill for.
									$licenses_excess = $period_data["quantity"] - $obj_service->data["included_units"];


									// set item attributes
									$itemdata["price"]	= $obj_service->data["price_extraunits"] * $ratio;
									$itemdata["quantity"]	= $licenses_excess;
									$itemdata["units"]	= addslashes($obj_service->data["units"]);


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
								/*
									TIME

									Simular to the generic usage type, but instead of units being a text field, units
									is an ID to the service_units table.
								*/

								log_write("debug", "service_invoicegen", "Processing usage items for time traffic");



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
								$itemdata["description"]	= addslashes($obj_service->data["name_service"]) ." usage from ". $period_usage_data["date_start"] ." to ". $period_usage_data["date_end"];
								$itemdata["customid"]		= $obj_service->id;
								$itemdata["discount"]		= 0;


								/*
									Adjust Included Units to handle partial or extended periods
								*/

								if ($ratio != "1")
								{
									$obj_service->data["included_units"] = sprintf("%d", ($obj_service->data["included_units"] * $ratio ));
								}


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

							
							case "data_traffic":
								/*
									DATA_TRAFFIC

									We make use of the service_usage_traffic logic to determine the usage across all IP
									addressess assigned to this customer and then bill accordingly.
								*/

								log_write("debug", "service_invoicegen", "Processing usage items for time/data_traffic");



								/*
									Fetch data traffic plan usage type
								*/

								$unitname = sql_get_singlevalue("SELECT name as value FROM service_units WHERE id='". $obj_service->data["units"] ."'");


								/*
									Fetch usage amount - the returned usage structure will include breakdown of traffic
									by configured types.
								*/
								
								$usage_obj					= New service_usage_traffic;
								$usage_obj->id_service_customer			= $period_usage_data["id_service_customer"];
								$usage_obj->date_start				= $period_usage_data["date_start"];
								$usage_obj->date_end				= $period_usage_data["date_end"];
								

								/*
									Fetch Traffic Caps & Details

									Returns all the traffic cap types including overrides.

									id_type,
									id_cap,
									type_name,
									type_label,
									cap_mode,
									cap_units_included,
									cap_units_price
								*/
								
								$traffic_types_obj = New traffic_caps;
								$traffic_types_obj->id_service		= $obj_service->id;
								$traffic_types_obj->id_service_customer	= $period_usage_data["id_service_customer"];

								$traffic_types_obj->load_data_traffic_caps();
								$traffic_types_obj->load_data_override_caps();
								


								/*
									Generate Traffic Bills
								*/

								if ($usage_obj->load_data_service())
								{
									$usage_obj->fetch_usage_traffic();
	
									foreach ($traffic_types_obj->data as $data_traffic_cap)
									{
										// Adjust Included Units to handle partial or extended periods
										if ($ratio != "1")
										{
											$data_traffic_cap["cap_units_included"] = sprintf("%d", ($data_traffic_cap["cap_units_included"] * $ratio ));
										}

										// if there is only a single traffic cap, we should make the traffic type name blank, since there's only going to be
										// one line item anyway.

										if ($traffic_types_obj->data_num_rows == 1)
										{
											$data_traffic_cap["type_name"] = "";
										}


										// if the any traffic type is zero and there are other traffic types, we should skip it, since most likely
										// the other traffic types provide everything expected.
										//
										if ($traffic_types_obj->data_num_rows > 1 && $data_traffic_cap["type_label"] == "*" && $usage_obj->data["total_byunits"]["*"] == 0)
										{
											continue;
										}

								

										// start service item
										$invoice_item				= New invoice_items;
									
										$invoice_item->id_invoice		= $invoiceid;
										
										$invoice_item->type_invoice		= "ar";
										$invoice_item->type_item		= "service_usage";
									
										$itemdata = array();

										$itemdata["chartid"]			= $obj_service->data["chartid"];
										$itemdata["customid"]			= $obj_service->id;


										// base details
										$itemdata["price"]			= 0;
										$itemdata["quantity"]			= 0;
										$itemdata["discount"]			= 0;
										$itemdata["units"]			= "";
										$itemdata["description"]		= addslashes($obj_service->data["name_service"]) ." usage from ". $period_usage_data["date_start"] ." to ". $period_usage_data["date_end"];


										if ($data_traffic_cap["cap_mode"] == "unlimited")
										{
											// unlimited data cap, there will never be any excess traffic charges, so the line item
											// description should be purely for informative purposes.

											$itemdata["description"] .= "\nUnlimited ". addslashes($data_traffic_cap["type_name"]) ." traffic, total of ". $usage_obj->data["total_byunits"][ $data_traffic_cap["type_label"] ] ." $unitname used\n";
	
										}
										else
										{
											// capped traffic - check for excess changes, otherwise just report on how much traffic
											// that the customer used.
											
											// description example:		Used 10 GB out of 50 GB included in plan
											$itemdata["description"] .= "\nCapped ". addslashes($data_traffic_cap["type_name"]) ." traffic, used ". $usage_obj->data["total_byunits"][ $data_traffic_cap["type_label"] ] ." $unitname out of ". $data_traffic_cap["cap_units_included"] ." $unitname in plan.";

									
											// handle excess charges
											//
											if ($usage_obj->data["total_byunits"][ $data_traffic_cap["type_label"] ] > $data_traffic_cap["cap_units_included"])
											{
												// there is excess usage that we can bill for.
												$usage_excess = $usage_obj->data["total_byunits"][ $data_traffic_cap["type_label"] ] - $data_traffic_cap["cap_units_included"];

												// set item attributes
												$itemdata["price"]	= $data_traffic_cap["cap_units_price"];
												$itemdata["quantity"]	= $usage_excess;
												$itemdata["units"]	= $unitname;

												// description example:		Excess usage of 70 GB charged at $5.00 per GB
												$itemdata["description"] .= "\nExcess usage of $usage_excess $unitname charged at ". format_money($data_traffic_cap["cap_units_price"]) ." per $unitname.";
											}

										} // end of traffic cap mode


										// add trunk usage item
										//
										$invoice_item->prepare_data($itemdata);
										$invoice_item->action_update();

										unset($invoice_item);
									}
								}

								
								/*
									Update usage value for period - this summary value is visable on the service
									history page and saves having to query lots of records to generate period totals.
								*/
								$sql_obj		= New sql_query;
								$sql_obj->string	= "UPDATE services_customers_periods SET usage_summary='". $usage_obj->data["total_byunits"]["total"] ."' WHERE id='". $period_usage_data["id"] ."' LIMIT 1";
								$sql_obj->execute();

								
								unset($usage_obj);
								unset($traffic_types_obj);

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

								log_write("debug", "service_invoicegen", "Processing usage items for phone_single/phone_tollfree/phone_trunk");



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

								if ($obj_service->data["typeid_string"] == "phone_trunk" && $period_data["mode"] == "standard")
								{

									// start service item
									$invoice_item				= New invoice_items;
								
									$invoice_item->id_invoice		= $invoiceid;
									
									$invoice_item->type_invoice		= "ar";
									$invoice_item->type_item		= "service_usage";
								
									$itemdata = array();

									$itemdata["chartid"]			= $obj_service->data["chartid"];
									$itemdata["customid"]			= $obj_service->id;
									$itemdata["discount"]			= 0;


									// fetch DDI usage
									$usage = $usage_obj->load_data_ddi();

									// determine excess usage charges
									if ($usage > $obj_service->data["phone_ddi_included_units"])
									{
										// there is excess usage that we can bill for.
										$usage_excess			= $usage - $obj_service->data["phone_ddi_included_units"];

										// set item attributes
										$itemdata["price"]		= $obj_service->data["phone_ddi_price_extra_units"] * $ratio;
										log_write("debug", "DEBUG", "Ratio is $ratio, price is ". $itemdata["price"] ."");
										$itemdata["quantity"]		= $usage_excess;
										$itemdata["units"]		= "DDIs";
										
										if ($obj_service->data["phone_ddi_included_units"])
										{
											$itemdata["description"]	= $obj_service->data["phone_ddi_included_units"] ."x DDI numbers included in service plan plus additional ". $usage_excess ."x numbers from ". $period_usage_data["date_start"] ." to ". $period_usage_data["date_end"] ."";
										}
										else
										{
											// no included units, we use an alternative string format
											$itemdata["description"]	= $usage_excess ."x DDI numbers from ". $period_usage_data["date_start"] ." to ". $period_usage_data["date_end"] ."";
										}
									}
									else
									{	
										// no charge for this item
										$itemdata["description"]	= $obj_service->data["phone_ddi_included_units"] ."x DDI numbers included in service plan from ". $period_usage_data["date_start"] ." to ". $period_usage_data["date_end"] ."";
									}


									// add trunk usage item
									$invoice_item->prepare_data($itemdata);
									$invoice_item->action_update();

									unset($invoice_item);
								}


								/*
									2. Trunk Charges
								*/

								if (($obj_service->data["typeid_string"] == "phone_trunk" || $obj_service->data["typeid_string"] == "phone_tollfree") && $period_data["mode"] == "standard")
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
									$itemdata["discount"]			= 0;

									// determine excess usage charges
									if ($obj_service->data["phone_trunk_quantity"] > $obj_service->data["phone_trunk_included_units"])
									{
										// there is excess usage that we can bill for.
										$usage_excess = $obj_service->data["phone_trunk_quantity"] - $obj_service->data["phone_trunk_included_units"];

										// set item attributes
										$itemdata["price"]		= ($obj_service->data["phone_trunk_price_extra_units"] * $ratio);
										$itemdata["quantity"]		= $usage_excess;
										$itemdata["units"]		= "trunks";


										if ($obj_service->data["phone_trunk_included_units"])
										{
											$itemdata["description"]	= $obj_service->data["phone_trunk_included_units"] ."x trunks included in service plan plus additional ". $usage_excess ."x trunks from ". $period_usage_data["date_start"] ." to ". $period_usage_data["date_end"] ."";
										}
										else
										{
											// no included trunks, adjust string to suit.
											$itemdata["description"]	= $usage_excess ."x trunks from ". $period_usage_data["date_start"] ." to ". $period_usage_data["date_end"] ."";
										}
									}
									else
									{	
										// no charge for this item
										$itemdata["description"]	= $obj_service->data["phone_trunk_included_units"] ."x trunks included in service plan from ". $period_usage_data["date_start"] ." to ". $period_usage_data["date_end"] ."";
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

								$billgroup_obj					= New sql_query;
								$billgroup_obj->string				= "SELECT id, billgroup_name FROM cdr_rate_billgroups";
								$billgroup_obj->execute();
								$billgroup_obj->fetch_array();
								
								if ($usage_obj->load_data_service())
								{
									$usage_obj->fetch_usage_calls();
	
									foreach ($billgroup_obj->data as $data_billgroup)
									{
										foreach ($usage_obj->data_ddi as $ddi)
										{
											if ($usage_obj->data[ $ddi ][ $data_billgroup["id"] ]["charges"] > 0)
											{
												// start service item
												$invoice_item				= New invoice_items;
											
												$invoice_item->id_invoice		= $invoiceid;
												
												$invoice_item->type_invoice		= "ar";
												$invoice_item->type_item		= "service_usage";
											
												$itemdata = array();

												$itemdata["chartid"]			= $obj_service->data["chartid"];
												$itemdata["customid"]			= $obj_service->id;

												// extra service details
												$itemdata["id_service_customer"]	= $period_usage_data["id_service_customer"];
												$itemdata["id_period"]			= $period_usage_data["id"];

												// determine excess usage charges
												$itemdata["discount"]			= 0;
												$itemdata["price"]			= $usage_obj->data[ $ddi ][ $data_billgroup["id"] ]["charges"];
												$itemdata["quantity"]			= "1";
												$itemdata["units"]			= "";
												$itemdata["description"]		= $data_billgroup["billgroup_name"] ." call charges for $ddi from ". $period_usage_data["date_start"] ." to ". $period_usage_data["date_end"] ."";
												$itemdata["cdr_billgroup"]		= $data_billgroup["id"];

												// add trunk usage item
												$invoice_item->prepare_data($itemdata);
												$invoice_item->action_update();

												unset($invoice_item);
											}
											else
											{
												log_write("debug", "inc_service_invoicegen", "Excluding DDI $ddi from ". $data_billgroup["billgroup_name"] ." due to no charges for the current period");
											}
										}
									}
								}
								
								unset($usage_obj);



								/*
									If enabled, generate the CDR output format for the current service usage and attach
									it to the invoice via the invoice journal.

									This feature can be enabled/disabled on a per-customer per-service basis.
								*/

								if ($obj_service->data["billing_cdr_csv_output"])
								{
									log_write("debug", "inc_service_invoicegen", "Generating CDR export file and attaching to invoice journal");

									// generate the CSV formatted export.
									$cdr_options = array(
										'id_customer'		=> $customer_data["id"],
										'id_service_customer'	=> $period_usage_data["id_service_customer"],
										'period_start'		=> $period_usage_data["date_start"],
										'period_end'		=> $period_usage_data["date_end"],
									);

									$csv = new cdr_csv($cdr_options);

									if (!$cdr_output = $csv->getCSV())
									{
										log_write("error", "inc_service_invoicegen", "Unable to generate CSV ouput for the configured range");
										return 0;
									}


									// create journal entry
									$journal = New journal_process;
									
									$journal->prepare_set_journalname("account_ar");
									$journal->prepare_set_customid($invoiceid);
									$journal->prepare_set_type("file");

									// we use the prefix "SERVICE:" to find the journal at invoice time
									$journal->prepare_set_title("SERVICE: Service CDR Export Attachment");

									// details can be anything (just a text block)
									$data["content"] = NULL;
									$data["content"] .= "Automatically exported CDR for service ". addslashes($obj_service->data["name_service"]) ."\n";
									$data["content"] .= "\n";
									
									$journal->prepare_set_content($data["content"]);

									$journal->action_update();		// create journal entry
									$journal->action_lock();		// lock entry to avoid users deleting it or breaking it


									// upload file as an attachment for the journal
									$file_obj			= New file_storage;
									$file_obj->data["type"]		= "journal";
									$file_obj->data["customid"]	= $journal->structure["id"];
									$file_obj->data["file_name"]	= "invoice_". $invoicecode ."_service_CDR_export.csv";

									if (!$file_obj->action_update_var($cdr_output))
									{
										log_write("error", "inc_service_invoicegen", "Unable to upload export CDR invoice to journal.");
									}
									
									unset($csv);
									unset($journal);
									unset($file_obj);
									unset($cdr_output);
								}


							break;
	
							case "generic_no_usage":
							case "bundle":
								// nothing todo for these service types
								log_write("debug", "service_invoicegen", "Not processing usage, this is a non-usage service type");
							break;

							default:
								// we should always match all service types, even if we don't need to do anything
								// in particular for that type.

								die("Unable to process unknown service type: ". $obj_service->data["typeid_string"] ."");
							break;
						} // end of processing usage			



					} // end if service has a valid data period
					else
					{
						log_write("debug", "service_invoicegen", "Not billing for current usage, as this appears to be the first plan period so no usage can exist yet");
					}



					/*
						Set invoice ID for period - this prevents the period from being added to
						any other invoices and allows users to see which invoice it was billed under
					*/

					// set for plan period
					$sql_obj		= New sql_query;
					$sql_obj->string	= "UPDATE services_customers_periods SET invoiceid='$invoiceid', rebill='0' WHERE id='". $period_data["id"] . "' LIMIT 1";
					$sql_obj->execute();

					// set for usage period
					if (!empty($period_usage_data["active"]))
					{
						$sql_obj		= New sql_query;
						$sql_obj->string	= "UPDATE services_customers_periods SET invoiceid_usage='$invoiceid' WHERE id='". $period_usage_data["id"] . "' LIMIT 1";
						$sql_obj->execute();
					}
				

				} // end of processing periods


				/*
					Only process orders and invoice
					summary details if we had no errors above.
				*/

				if (!error_check())
				{
					/*
						Process any customer orders
					*/

					if ($GLOBALS["config"]["ORDERS_BILL_ONSERVICE"])
					{
						log_write("debug", "inc_service_invoicegen", "Checking for customer orders to add to service invoice");

						$obj_customer_orders 		= New customer_orders;
						$obj_customer_orders->id	= $customer_data["id"];

						if ($obj_customer_orders->check_orders_num())
						{
							log_write("debug", "inc_service_invoicegen", "Order items exist, adding them to service invoice");

							$obj_customer_orders->invoice_generate($invoiceid);
						}
					}
					else
					{
						log_write("debug", "inc_service_invoicegen", "Not checking for customer orders, ORDERS_BILL_ONSERVICE is disabled currently");
					}




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
						
				} // end if error check


				/*
					Automatic Payments

					Makes automatic invoice payments using sources such as customer credit pools, reoccuring credit card transactions
					and other sources.
				*/


				if ($GLOBALS["config"]["ACCOUNTS_AUTOPAY"])
				{
					log_write("debug", "inc_services_invoicegen", "Autopay Functionality Enabled, running appropiate functions for invoice ID $invoiceid");

					$obj_autopay			= New invoice_autopay;
					$obj_autopay->id_invoice	= $invoiceid;
					$obj_autopay->type_invoice	= "ar";

					$obj_autopay->autopay();

					unset($obj_autopay);
				}


				/*
					Commit

					Conduct final error check, before commiting the new invoice and sending the customer an email
					if appropiate.

					(we obviously don't want to email them if the invoice is getting rolled back!)
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



					/*
							Send the invoice to the customer as a PDF via email
					*/

					$emailed = "unsent";

					if (sql_get_singlevalue("SELECT value FROM config WHERE name='EMAIL_ENABLE'") == "enabled")
					{
						if (sql_get_singlevalue("SELECT value FROM config WHERE name='ACCOUNTS_INVOICE_AUTOEMAIL'") == "enabled")
						{
							// load completed invoice data
							$invoice	= New invoice;
							$invoice->id	= $invoiceid;
							$invoice->type	= "ar";

							$invoice->load_data();
							$invoice->load_data_export();

							if ($invoice->data["amount_total"] > 0)
							{
								// generate an email
								$email = $invoice->generate_email();

								// send email
								$invoice->email_invoice("system", $email["to"], $email["cc"], $email["bcc"], $email["subject"], $email["message"]);

								// complete
								log_write("notification", "inc_services_invoicegen", "Invoice $invoicecode has been emailed to customer (". $email["to"] .")");
							}
							else
							{
								// complete - invoice is for $0, so don't want to email out
								log_write("notification", "inc_services_invoicegen", "Invoice $invoicecode has not been emailed to the customer due to invoice being for $0.");
							}

							$emailed = "emailed - " .$email["to"];

							unset ($invoice);
						}

					} // end if email enabled

				} // end if commit successful 



				/*
					Review Status

					Here we need to check whether invoicing succeded/failed for the selected customer and process accordingly - we
					also want to re-set the error flag if running a batch mode, to enable other customers to still be invoiced.
				*/

				if (error_check())
				{
					// an error occured
					$invoice_stats["total_failed"]++;

					$invoice_stats["failed"][]	= array("code_customer" => $customer_data["code_customer"],
										"name_customer" => $customer_data["name_customer"],
										"code_invoice" => $invoicecode
										);

					// clear the error strings if we are processing from CLI this will allow us to continue on
					// with additional invoices.
					
					if (!empty($_SESSION["mode"]))
					{
						if ($_SESSION["mode"] == "cli")
						{
							log_write("debug", "inc_services_invoicegen", "Processing from CLI, clearing error flag and continuing with additional invoices");
							error_clear();
						}
					}
				}
				else
				{
					// successful
					$invoice_stats["total"]++;

					$invoice_stats["generated"][]	= array("code_customer" => $customer_data["code_customer"],
										"name_customer" => $customer_data["name_customer"],
										"code_invoice" => $invoicecode,
										"sent" => $emailed
										);
				} // end of success/stats review


			} // end of processing customers

		} // end of if customers exist
	}
	else
	{
		log_debug("inc_services_invoicegen", "No services assigned to customer $customerid");
	}


	/*
		Write Invoicing Report
		
		This only takes place if no customer ID is provided, eg we have run a full automatic invoice generation report.
	*/

	if ($customerid == NULL)
	{

		log_write("debug", "inc_service_invoicegen", "Generating invoice report for invoice generation process");


		/*
			Invoice Stats Calculations
		*/

		$invoice_stats["time"]	= time() - $invoice_stats["time_start"];

		if ($invoice_stats["time"] == 0)
		{
			$invoice_stats["time"] = 1;
		}



		/*
			Write Invoice Report
		*/

		$invoice_report		= array();
		$invoice_report[]	= "Complete Invoicing Run Time:\t". $invoice_stats["time"] ." seconds";
		$invoice_report[]	= "Total Invoices Generated:\t". $invoice_stats["total"];
		$invoice_report[]	= "Failed Invoices/Customers:\t". $invoice_stats["total_failed"];

		if (isset($invoice_stats["generated"]))
		{
			$invoice_report[]	= "";
			$invoice_report[]	= "Customers / Invoices Generated";

			foreach (array_keys($invoice_stats["generated"]) as $id)
			{
				$invoice_report[] = " * ". $invoice_stats["generated"][ $id ]["code_invoice"] .": ".  $invoice_stats["generated"][ $id ]["code_customer"] ." -- ". $invoice_stats["generated"][ $id ]["name_customer"];
				$invoice_report[] = "   [". $invoice_stats["generated"][ $id ]["sent"] ."]";	
			}

			$invoice_report[]	= "";
		}
		

		if (isset($invoice_stats["failed"]))
		{
			$invoice_report[]	= "";
			$invoice_report[]	= "Failed Customers / Invoices";

			foreach (array_keys($invoice_stats["failed"]) as $id)
			{
				$invoice_report[] = " * ".  $invoice_stats["failed"][ $id ]["code_customer"] ." -- ". $invoice_stats["failed"][ $id ]["name_customer"];
			}

			$invoice_report[]	= "";
			$invoice_report[]	= "Failed invoices will be attempted again on the following billing run.";
			$invoice_report[]	= "";
		}

		$invoice_report[] = "Invoicing Run Complete";
		

		// display to debug log
		log_write("debug", "inc_service_invoicegen", "----");

		foreach ($invoice_report as $line)
		{
			// loop through invoice report lines
			log_write("debug", "inc_service_invoicegen", $line);
		}

		log_write("debug", "inc_service_invoicegen", "----");



		// email if appropiate
		if ($GLOBALS["config"]["ACCOUNTS_INVOICE_BATCHREPORT"] && ($invoice_stats["total"] > 0 || $invoice_stats["total_failed"] > 0))
		{
			log_write("debug", "inc_service_invoicegen", "Emailing invoice generation report to ". $GLOBALS["config"]["ACCOUNTS_EMAIL_ADDRESS"] ."");


			/*
				External dependency of Mail_Mime
			*/

			if (!@include_once('Mail.php'))
			{
				log_write("error", "invoice", "Unable to find Mail module required for sending email");
				break;
			}
			
			if (!@include_once('Mail/mime.php'))
			{
				log_write("error", "invoice", "Unable to find Mail::Mime module required for sending email");
				break;
			}


			/*
				Email the Report
			*/

			$email_sender	= $GLOBALS["config"]["ACCOUNTS_EMAIL_ADDRESS"];
			$email_to	= $GLOBALS["config"]["ACCOUNTS_EMAIL_ADDRESS"];
			$email_subject	= "Invoice Batch Process Report";

			$email_message	= $GLOBALS["config"]["COMPANY_NAME"] ."\n\nInvoice Batch Process Report for ". time_format_humandate() ."\n\n";

			foreach ($invoice_report as $line)
			{
				$email_message .= $line ."\n";
			}	

			// prepare headers
			$mail_headers = array(
					'From'   	=> $email_sender,
					'Subject'	=> $email_subject
			);

			$mail_mime = new Mail_mime("\n");
				
			$mail_mime->setTXTBody($email_message);

			$mail_body	= $mail_mime->get();
			$mail_headers	= $mail_mime->headers($mail_headers);

			$mail		= & Mail::factory('mail');
			$status 	= $mail->send($email_to, $mail_headers, $mail_body);

			if (PEAR::isError($status))
			{
				log_write("error", "inc_service_invoicegen", "An error occured whilst attempting to send the batch report email: ". $status->getMessage() ."");
			}
			else
			{
				log_write("debug", "inc_service_invoicegen", "Successfully sent batch report email.");
			}

		} // end if email

	} // end if report


	return 1;
}



?>
