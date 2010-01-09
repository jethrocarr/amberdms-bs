<?php
/*
	include/services/inc_services_usages.php

	Provides functions for managing usage of services, including functions to add usage
	records and functions to fetch total amounts of usage to be used for billing/display
	purposes, as well as functions for checking for customer usage alerts.
*/




/*
	CLASSES
*/

class service_usage
{
	var $services_customers_id;			// ID of the service-customer mapping
	var $serviceid;					// ID of the service
	
	var $date_start;				// start of usage period
	var $date_end;					// end of usage period

	var $sql_service_obj;				// service data is loaded in here.

	var $data;					// usage information is saved here.


	/*
		Constructor
	*/
	function service_usage()
	{
		log_debug("service_usage", "Executing service_usage()");

		// define service object
		$this->sql_service_obj = New sql_query;
	}



	/*
		prepare_load_servicedata
		
		Load service information by using the services_customers_id

		Returns
		0		failure
		1		success
	*/
	function prepare_load_servicedata()
	{
		log_debug("service_usage", "Executing prepare_load_servicedata");

		// fetch the serviceid
		$this->serviceid = sql_get_singlevalue("SELECT serviceid as value FROM services_customers WHERE id='". $this->services_customers_id ."' LIMIT 1");

		if (!$this->serviceid)
		{
			log_write("error", "service_usage", "Unable to fetch serviceid from services_customers");
			return 0;
		}

		// fetch the service information we require
		$this->sql_service_obj->string = "SELECT id, usage_mode, units, typeid FROM services WHERE id='". $this->serviceid ."'";
		$this->sql_service_obj->execute();

		if ($this->sql_service_obj->num_rows())
		{
			$this->sql_service_obj->fetch_array();
			return 1;
		}
		else
		{
			log_write("error", "service_usage", "Unable to find service with ID of ". $this->serviceid . "");
			return 0;
		}
		
	} // end of prepare_load_servicedata



	/*
		fetch_usagedata

		Fetch the usage for the supplied period and perform any processing required for the usage mode type. Results are
		saved into the $this->data array, with the following structure:

		Data structure:
		
			["usage1"]		Value of usage1 as per usage mode
			["usage2"]		Value of usage2 as per usage mode
			["total"]		Addition of usage1 + usage2

			["total_byunits"]	total is further processed by the service unit to provide a value suitable for billing.


		Examples:
		
			["usage1"]		1024
			["usage2"]		2048
			["total"]		3072

			["total_byunits"]	3		(using MiB unit to return number of MBs)
			

		Note: All math calculations are performed using MySQL, since the SQL engine is capable of handling 64-bit intergers without
		wrapping them on 32bit systems. If PHP was used to perform these calculations on a 32bit CPU, values over 32bits would wrap
		and give incorrect results.

		For further details about 32bit counter wraps, please refer to the Amberdms Billing System design +  developer documentation.


		Returns
		0		failure
		1		success
	*/
	function fetch_usagedata()
	{
		log_debug("service_usage", "Executing fetch_usagedata()");

		/*
			Fetch usage mode
		*/
		$this->data["usage_mode"] = sql_get_singlevalue("SELECT name as value FROM service_usage_modes WHERE id='". $this->sql_service_obj->data[0]["usage_mode"] ."' LIMIT 1");

		if (!$this->data["usage_mode"])
		{
			log_debug("service_usage", "Error: Unable to determine usage mode of service");
			return 0;
		}



		/*
			Fetch usage data

			We need to fetch in different ways, depending on the usage mode.
		*/
		switch ($this->data["usage_mode"])
		{
			case "incrementing":
				/*
					INCREMENTING

					Data during the usage period increments and we bill based on the total amount of data. This is typically
					used for time or data_traffic service types.
				*/
				
				$sql_obj			= New sql_query;
				$sql_obj->string		= "SELECT SUM(usage1) as usage1, SUM(usage2) as usage2 FROM service_usage_records WHERE services_customers_id='". $this->services_customers_id ."' AND date>='". $this->date_start ."' AND date<='". $this->date_end ."'";
				$sql_obj->execute();
				$sql_obj->fetch_array();

				$this->data["usage1"]	= $sql_obj->data[0]["usage1"];
				$this->data["usage2"]	= $sql_obj->data[0]["usage2"];

			break;


			case "peak":
				/*
					PEAK USAGE

					Only bill for the highest amount used during the period. This is suitable for use with services such as online backup
					or storage services charging for the amount of space consumed.
				*/
				
				$sql_obj			= New sql_query;
				$sql_obj->string		= "SELECT MAX(usage1) as usage1, MAX(usage2) as usage2 FROM service_usage_records WHERE services_customers_id='". $this->services_customers_id ."' AND date>='". $this->date_start ."' AND date<='". $this->date_end ."'";
				$sql_obj->execute();
				$sql_obj->fetch_array();

				$this->data["usage1"]	= $sql_obj->data[0]["usage1"];
				$this->data["usage2"]	= $sql_obj->data[0]["usage2"];
				
			break;

			case "average":
				/*
					AVERAGE USAGE

					Only bill for the average amount used during the period. This is suitable for use with services such as online backup
					or storage services charging for the amount of space consumed.
				*/
				
				$sql_obj			= New sql_query;
				$sql_obj->string		= "SELECT AVG(usage1) as usage1, AVG(usage2) as usage2 FROM service_usage_records WHERE services_customers_id='". $this->services_customers_id ."' AND date>='". $this->date_start ."' AND date<='". $this->date_end ."'";
				$sql_obj->execute();
				$sql_obj->fetch_array();

				$this->data["usage1"]	= $sql_obj->data[0]["usage1"];
				$this->data["usage2"]	= $sql_obj->data[0]["usage2"];
				
			break;

			default:
				log_debug("service_usage", "Error: Unknown usage mode provided.");
				return 0;
			break;
			
		} // end of usage mode switch


		// create a total of both usage columns
		$sql_obj			= New sql_query;
		$sql_obj->string		= "SELECT '". $this->data["usage1"] ."' + '". $this->data["usage2"] ."' as totalusage";
		$sql_obj->execute();
		$sql_obj->fetch_array();
	
		$this->data["total"] 	= $sql_obj->data[0]["totalusage"];


		
		/*
			Fetch usage by units

			Not all services do this, but we can tell, if the units is just a number, that it is an ID to the service_units table for us to use.
		*/
		if (preg_match("/^[0-9]*$/", $this->sql_service_obj->data[0]["units"]))
		{
			// fetch the number of raw units to unit ratio
			$this->data["numrawunits"] = sql_get_singlevalue("SELECT numrawunits as value FROM service_units WHERE id='". $this->sql_service_obj->data[0]["units"] ."' LIMIT 1");

			if (!$this->data["numrawunits"])
			{
				log_debug("service_usage", "Error: Unable to fetch number of raw units for the units type");
				return 0;
			}
			
			// calculate
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT '". $this->data["total"] ."' / '". $this->data["numrawunits"] ."' as value";
			$sql_obj->execute();
			$sql_obj->fetch_array();

			$this->data["total_byunits"]	= $sql_obj->data[0]["value"];
		}


		// complete :-)
		return 1;
		
	} // end of fetch_usagedata



} // END OF SERVICE_USAGE CLASS




/*
	FUNCTIONS
*/


/*
	services_usage_alerts_generate

	Check the usage for the customer and generate any required alerts (eg: 80% data usage)

	Values
	customerid		(optional) ID of the customer account to check. If blank, will check
					   all customers.
				
	Results
	-1			email disabled
	0			failure
	1			success
*/
function service_usage_alerts_generate($customerid = NULL)
{
	log_debug("inc_services_usage", "Executing service_usage_alerts_generate($customerid)");

	/*
		Fetch configuration Options
	*/

	// check that email is enabled
	if (sql_get_singlevalue("SELECT value FROM config WHERE name='EMAIL_ENABLE' LIMIT 1") != "enabled")
	{
		log_write("error", "inc_services_usage", "Unable to email customer usage alerts, due to EMAIL_ENABLE being disabled");
		return -1;
	}

	// fetch email address to send as.
	$email_sender = sql_get_singlevalue("SELECT value FROM config WHERE name='COMPANY_NAME'") ." <". sql_get_singlevalue("SELECT value FROM config WHERE name='COMPANY_CONTACT_EMAIL'") .">";



	/*
		Run through all the active services
	*/

	$sql_custserv_obj		= New sql_query;
	$sql_custserv_obj->string	= "SELECT id, customerid, serviceid, description FROM services_customers WHERE services_customers.active='1'";

	if ($customerid)
		$sql_custserv_obj->string .= " AND customerid='$customerid'";


	$sql_custserv_obj->execute();

	if ($sql_custserv_obj->num_rows())
	{
		$sql_custserv_obj->fetch_array();

		foreach ($sql_custserv_obj->data as $customer_data)
		{
			/*
				Process each service that the customer has, provided that it is a usage service. Any non-usage services we
				can simply skip.
			*/
			
			// fetch service details
			$sql_service_obj		= New sql_query;
			$sql_service_obj->string	= "SELECT * FROM services WHERE id='". $customer_data["serviceid"] . "' LIMIT 1";
			$sql_service_obj->execute();
			$sql_service_obj->fetch_array();

			// fetch customer details
			$sql_customer_obj		= New sql_query;
			$sql_customer_obj->string	= "SELECT name_customer, name_contact, contact_email FROM customers WHERE id='". $customer_data["customerid"] ."' LIMIT 1";
			$sql_customer_obj->execute();
			$sql_customer_obj->fetch_array();


			// check the service type
			$service_type = sql_get_singlevalue("SELECT name as value FROM service_types WHERE id='". $sql_service_obj->data[0]["typeid"] ."'");


			// only process data_traffic, time or generic_usage services
			if ($service_type == "generic_with_usage" || $service_type == "time" || $service_type == "data_traffic")
			{
				log_debug("inc_services_usage", "Processing service ". $customer_data["id"] ." for customer ". $customer_data["customerid"] ."");


				/*
					Fetch the customer's currently active period.
				*/

				$sql_periods_obj		= New sql_query;
				$sql_periods_obj->string	= "SELECT "
									."id, "
									."date_start, "
									."date_end, "
									."usage_summary "
									."FROM services_customers_periods "
									."WHERE "
									."services_customers_id='". $customer_data["id"] ."' "
									."AND invoiceid = '0' "
									."AND date_end >= '". date("Y-m-d")."' LIMIT 1";
				$sql_periods_obj->execute();

				if ($sql_periods_obj->num_rows())
				{
					$sql_periods_obj->fetch_array();
					$period_data = $sql_periods_obj->data[0];


					// fetch billing mode
					$billing_mode = sql_get_singlevalue("SELECT name as value FROM billing_modes WHERE id='". $sql_service_obj->data[0]["billing_mode"] ."'");

					// fetch unit naming
					if ($service_type == "generic_with_usage")
					{
						$unitname = $sql_service_obj->data[0]["units"];
					}
					else
					{
						$unitname = sql_get_singlevalue("SELECT name as value FROM service_units WHERE id='". $sql_service_obj->data[0]["units"] ."'");
					}



					/*
						Calculate number of included units
					*/
					if ($billing_mode == "monthend" || $billing_mode == "monthadvance")
					{
						log_debug("inc_services_usage", "Service is billed by calender month");

						/*
							Handle monthly billing

							Normally, monthly billing is easy, however the very first billing period is special, as it may span a more than 1 month.

							Eg: if a service is started on 2008-01-09, the end of the billing period will be 2008-02-29, which is 1 month + 21 day.

							To handle this, we increase the number of included units by the following method:

								( standard_cost / normal_month_num_days ) * num_days_in_partial_month == extra_amount

								total_amount = (extra_amount + normal_amount)

							Note: This code is based off the section found for services_invoicegen.php. Could be worth creating a function?
						*/

						// check if the period is the very first period - the start and end dates will be in different months.
						if (time_calculate_monthnum($period_data["date_start"]) != time_calculate_monthnum($period_data["date_end"]))
						{
							// very first billing month
							log_debug("inc_services_usage", "Very first billing month - adjusting included units to suit the extra time included.");

							// work out the number of days extra
							$extra_month_days_total = time_calculate_daynum( time_calculate_monthdate_last($period_data["date_start"]) );
							$extra_month_days_extra	= $extra_month_days_total - time_calculate_daynum($period_data["date_start"]);

							log_debug("services_invoicegen", "$extra_month_days_extra additional days ontop of started billing period");

							// calculate number of included units - round up to nearest full unit
							$sql_service_obj->data[0]["included_units"] = sprintf("%d", ( ($sql_service_obj->data[0]["included_units"] / $extra_month_days_total) * $extra_month_days_extra ) + $sql_service_obj->data[0]["included_units"] );
						}
					}


					


					/*
						Fetch the amount of usage
					*/
			
					$usage_obj					= New service_usage;
					$usage_obj->services_customers_id		= $customer_data["id"];
					$usage_obj->date_start				= $period_data["date_start"];
					$usage_obj->date_end				= $period_data["date_end"];
					
					if ($usage_obj->prepare_load_servicedata())
					{
						$usage_obj->fetch_usagedata();

						if (isset($usage_obj->data["total_byunits"]))
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
						Send alerts if required
					*/

					$message = "";

					if ($usage > $sql_service_obj->data[0]["included_units"])
					{
						// usage is over 100% - check if we should report this
						log_debug("inc_service_usage", "Usage is over 100%");

						if ($sql_service_obj->data[0]["alert_extraunits"])
						{
							// check at what usage amount we last reported, and if
							// we have used alert_extraunits more usage since then, send
							// an alert to the customer.

							if (($usage - $period_data["usage_summary"]) > $sql_service_obj->data[0]["alert_extraunits"])
							{
								log_debug("inc_service_usage", "Sending excess usage notification (over 100%)");

								/*
									Send excess usage notification (over 100%)

									Message Example:
									This email has been sent to advise you that you have gone over the
									included usage on your plan.

									You have now used 70 excess ZZ on your Example Service plan.

									Used 120 ZZ out of 50 ZZ included in plan
									Excess usage of 70 ZZ charged at $5.00 per ZZ (exc taxes)

									Your current billing period ends on YYYY-MM-DD.

								*/

								// there is excess usage
								$usage_excess = $usage - $sql_service_obj->data[0]["included_units"];

								// prepare message
								$message .= "This email has been sent to advise you that you have gone over the included usage on your plan\n";
								$message .= "\n";
								$message .= "You have now used $usage_excess excess $unitname on your ". $sql_service_obj->data[0]["name_service"] ." plan.\n";
								$message .= "\n";
								$message .= "Used $usage $unitname out of ". $sql_service_obj->data[0]["included_units"] ." $unitname included in plan.\n";
								$message .= "Excess usage of $usage_excess $unitname charged at ". $sql_service_obj->data[0]["price_extraunits"] ." per $unitname (exc taxes).\n";
								$message .= "\n";
								$message .= "Your current billing period ends on ". $period_data["date_end"] ."\n";
								$message .= "\n";


								// send email
								if ($sql_customer_obj->data[0]["contact_email"])
								{
									$headers = "From: $email_sender\r\n";

									mail($sql_customer_obj->data[0]["name_contact"] ."<". $sql_customer_obj->data[0]["contact_email"] .">", "Excess usage notification", $message, $headers);
								}
								else
								{
									log_write("error", "inc_service_usage", "Customer ". $sql_customer_obj->data[0]["name_customer"] ." does not have an email address, unable to send usage notifications.");
								}
							}
						}

					}
					else
					{
						// calculate 80% of the included usage
						$included_usage_80pc = $sql_service_obj->data[0]["included_units"] * 0.80;


						if ($usage == $sql_service_obj->data[0]["included_units"])
						{
							log_debug("inc_service_usage", "Usage is at 100%");

							// usage is at 100%
							//
							// make sure that:
							// 1. 100% usage alerting is enabled
							// 2. that we have not already sent this alert (by checking period_data["usage_summary"])
							//
							if ($sql_service_obj->data[0]["alert_100pc"] && $period_data["usage_summary"] < $sql_service_obj->data[0]["included_units"])
							{
								log_debug("inc_service_usage", "Sending excess usage notification (100% reached)");

								/*
									Send 100% usage notification

									Message Example:
									This email has been sent to advise you that you have used 100% of
									your included usage on your Example Service plan.

									Used 50 ZZ out of 50 ZZ included in plan

									Any excess usage will be charged at $5.00 per ZZ (exc taxes)

									Your current billing period ends on YYYY-MM-DD.
								*/

								// prepare message
								$message .= "This email has been sent to advise you that you have used 100% of your included usage on your ". $sql_service_obj->data[0]["name_service"] ." plan.\n";
								$message .= "\n";
								$message .= "Used $usage $unitname out of ". $sql_service_obj->data[0]["included_units"] ." $unitname included in plan.\n";
								$message .= "Any excess usage will be charged at ". $sql_service_obj->data[0]["price_extraunits"] ." per $unitname (exc taxes).\n";
								$message .= "\n";
								$message .= "Your current billing period ends on ". $period_data["date_end"] ."\n";
								$message .= "\n";


								// send email
								if ($sql_customer_obj->data[0]["contact_email"])
								{
									$headers = "From: $email_sender\r\n";

									mail($sql_customer_obj->data[0]["name_contact"] ."<". $sql_customer_obj->data[0]["contact_email"] .">", "100% usage notification", $message, $headers);
								}
								else
								{
									log_write("error", "inc_service_usage", "Customer ". $sql_customer_obj->data[0]["name_customer"] ." does not have an email address, unable to send usage notifications.");
								}
							}
						}
						elseif ($usage > $included_usage_80pc)
						{
							log_debug("inc_service_usage", "Usage is between 80% & 100%");

							// usage is between 80 and 100%
							//
							// make sure that:
							// 1. 80% usage alerting is enabled
							// 2. that we have not already sent this alert (by checking period_data["usage_summary"])
							//
							if ($sql_service_obj->data[0]["alert_80pc"] && $period_data["usage_summary"] < $included_usage_80pc)
							{
								log_debug("inc_service_usage", "Sending excess usage notification (80% - 100%)");

								/*
									Send 80% usage notification

									Message Example:
									This email has been sent to advise you that you have used over 80% of
									your included usage on your Example Service plan.

									Used 50 ZZ out of 50 ZZ included in plan

									Any excess usage will be charged at $5.00 per ZZ (exc taxes)

									Your current billing period ends on YYYY-MM-DD.
								*/

								// prepare message
								$message .= "This email has been sent to advise you that you have used over 80% of your included usage on your ". $sql_service_obj->data[0]["name_service"] ." plan.\n";
								$message .= "\n";
								$message .= "Used $usage $unitname out of ". $sql_service_obj->data[0]["included_units"] ." $unitname included in plan.\n";
								$message .= "Any excess usage will be charged at ". $sql_service_obj->data[0]["price_extraunits"] ." per $unitname (exc taxes).\n";
								$message .= "\n";
								$message .= "Your current billing period ends on ". $period_data["date_end"] ."\n";
								$message .= "\n";


								// send email
								if ($sql_customer_obj->data[0]["contact_email"])
								{
									$headers = "From: $email_sender\r\n";

									mail($sql_customer_obj->data[0]["name_contact"] ."<". $sql_customer_obj->data[0]["contact_email"] .">", "80% usage notification", $message, $headers);
								}
								else
								{
									log_write("error", "inc_service_usage", "Customer ". $sql_customer_obj->data[0]["name_customer"] ." does not have an email address, unable to send usage notifications.");
								}
							}

						}
					}

				} // end if usage alerts required



				/*
					Update usage value for period - this summary value is visable on the service
					history page and saves having to query lots of records to generate period totals.
				*/
				$sql_obj		= New sql_query;
				$sql_obj->string	= "UPDATE services_customers_periods SET usage_summary='$usage' WHERE id='". $period_data["id"] ."' LIMIT 1";
				$sql_obj->execute();
				

			}  // end if a usage service

		} // end of loop through customer services

	} // end if customer(s) services exist

} // end of service_usage_alerts_generate




?>
