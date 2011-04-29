<?php
/*
	include/services/inc_services_usages.php

	Provides non-class structured functions for handling service usage
	data, such as functions for generating usage alerts and reports.

	For actual usage calculation classes, see the type specific files at:
	* inc_services_cdr.php
	* inc_services_traffic.php
	* inc_services_generic.php
*/


/*
	FUNCTIONS
	
	TODO: Long-term, these should be broken up into a more modular class-structure and
	      better documented to make it easier to understand and maintain.
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
			$sql_customer_obj		= sql_get_singlerow("SELECT name_customer FROM customers WHERE id='". $customer_data["customerid"] ."' LIMIT 1");			
			$arr_sql_contact		= sql_get_singlerow("SELECT id, contact FROM customer_contacts WHERE customer_id = '" .$customer_data["customerid"]. "' AND role = 'accounts' LIMIT 1");
			$arr_sql_contact_details	= sql_get_singlerow("SELECT detail AS contact_email FROM customer_contact_records WHERE contact_id = '" .$arr_sql_contact["id"]. "' AND type = 'email' LIMIT 1");
	
			// place the contact details into the customer details array.			
			$sql_customer_obj["name_contact"]	= $arr_sql_contact["contact"];			
			$sql_customer_obj["contact_email"]	= $arr_sql_contact_details["contact_email"];
			


			// check the service type
			$service_type = sql_get_singlevalue("SELECT name as value FROM service_types WHERE id='". $sql_service_obj->data[0]["typeid"] ."'");


			// only process data_traffic, time or generic_usage services
			//
			// (call services have usage, but we don't currently alert for those)
			//
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
									."id_service_customer='". $customer_data["id"] ."' "
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

					switch ($service_type)
					{
						case "generic_with_usage":
						case "time":
					
							$usage_obj					= New service_usage_generic;
							$usage_obj->id_service_customer			= $customer_data["id"];
							$usage_obj->date_start				= $period_data["date_start"];
							$usage_obj->date_end				= $period_data["date_end"];
							
							if ($usage_obj->load_data_service())
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
						break;


						case "data_traffic":

							$usage_obj					= New service_usage_traffic;
							$usage_obj->id_service_customer			= $customer_data["id"];
							$usage_obj->date_start				= $period_data["date_start"];
							$usage_obj->date_end				= $period_data["date_end"];
							
							if ($usage_obj->load_data_service())
							{
								$usage_obj->fetch_usage_traffic();

								$usage = $usage_obj->data["total_byunits"];
							}
							
							unset($usage_obj);

						break;
					}


					/*
						Send alerts if required
					*/
					
					if ($GLOBALS["config"]["ACCOUNTS_INVOICE_AUTOEMAIL"] == "enabled")
					{
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

								if (($usage - $period_data["usage_summary"]) >= $sql_service_obj->data[0]["alert_extraunits"])
								{
									log_write("notification", "inc_service_usage", "Sending excess usage notification (over 100%)");

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
									if ($sql_customer_obj["contact_email"])
									{
										$headers = "From: $email_sender\r\n";

										mail($sql_customer_obj["name_contact"] ."<". $sql_customer_obj["contact_email"] .">", "Excess usage notification", $message, $headers);
									}
									else
									{
										log_write("error", "inc_service_usage", "Customer ". $sql_customer_obj["name_customer"] ." does not have an email address, unable to send usage notifications.");
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
									log_write("notification", "inc_service_usage", "Sending excess usage notification (100% reached)");

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
									if ($sql_customer_obj["contact_email"])
									{
										$headers = "From: $email_sender\r\n";

										mail($sql_customer_obj["name_contact"] ."<". $sql_customer_obj["contact_email"] .">", "100% usage notification", $message, $headers);
									}
									else
									{
										log_write("error", "inc_service_usage", "Customer ". $sql_customer_obj["name_customer"] ." does not have an email address, unable to send usage notifications.");
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
									log_write("notification", "inc_service_usage", "Sending excess usage notification (80% - 100%)");

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


									// fetch email


									// send email
									if ($sql_customer_obj["contact_email"])
									{
										$headers = "From: $email_sender\r\n";

										mail($sql_customer_obj["name_contact"] ."<". $sql_customer_obj["contact_email"] .">", "80% usage notification", $message, $headers);
									}
									else
									{
										log_write("error", "inc_service_usage", "Customer ". $sql_customer_obj["name_customer"] ." does not have an email address, unable to send usage notifications.");
									}
								}

							}
						}

					} // end if usage alerts required

				} // end if alerts enabled
				else
				{
					log_write("notification", "inc_service_usage", "Not sending usage notification/reminder due to ACCOUNTS_INVOICE_AUTOEMAIL being disabled");
				}


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
