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
			$obj_service			= New service_bundle;

			$obj_service->option_type	= "customer";
			$obj_service->option_type_id	= $customer_data["id"];

			if (!$obj_service->verify_id_options())
			{
				log_write("error", "customers_services", "Unable to verify service ID of ". $customer_data["id"] ." as being valid.");
				return 0;
			}
		
			$obj_service->load_data();
			$obj_service->load_data_options();


		
			
			
			// fetch customer details
			$sql_customer_obj		= sql_get_singlerow("SELECT name_customer FROM customers WHERE id='". $customer_data["customerid"] ."' LIMIT 1");			
			$arr_sql_contact		= sql_get_singlerow("SELECT id, contact FROM customer_contacts WHERE customer_id = '" .$customer_data["customerid"]. "' AND role = 'accounts' LIMIT 1");
			$arr_sql_contact_details	= sql_get_singlerow("SELECT detail AS contact_email FROM customer_contact_records WHERE contact_id = '" .$arr_sql_contact["id"]. "' AND type = 'email' LIMIT 1");
	
			// place the contact details into the customer details array.			
			$sql_customer_obj["name_contact"]	= $arr_sql_contact["contact"];			
			$sql_customer_obj["contact_email"]	= $arr_sql_contact_details["contact_email"];
			


			// check the service type
			$service_type = sql_get_singlevalue("SELECT name as value FROM service_types WHERE id='". $obj_service->data["typeid"] ."'");


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
									."usage_summary, "
									."usage_alerted "
									."FROM services_customers_periods "
									."WHERE "
									."id_service_customer='". $customer_data["id"] ."' "
									."AND invoiceid_usage = '0' "
									."AND date_end >= '". date("Y-m-d")."' LIMIT 1";
				$sql_periods_obj->execute();

				if ($sql_periods_obj->num_rows())
				{
					$sql_periods_obj->fetch_array();
					$period_data = $sql_periods_obj->data[0];


					// fetch billing mode
					$billing_mode = sql_get_singlevalue("SELECT name as value FROM billing_modes WHERE id='". $obj_service->data["billing_mode"] ."'");

					// fetch unit naming
					if ($service_type == "generic_with_usage")
					{
						$unitname = $obj_service->data["units"];
					}
					else
					{
						$unitname = sql_get_singlevalue("SELECT name as value FROM service_units WHERE id='". $obj_service->data["units"] ."'");
					}



					/*
						Calculate number of included units

						TODO: replace with ratio logic
					*/

					$ratio = 1;

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
							$obj_service->data["included_units"] = sprintf("%d", ( ($obj_service->data["included_units"] / $extra_month_days_total) * $extra_month_days_extra ) + $obj_service->data["included_units"] );
						}
					}




					/*
						Process usage for each service type - there are differences between particular platforms.
					*/

					switch ($service_type)
					{
						case "generic_with_usage":
						case "time":
	
							/*
								Fetch the amount of usage
							*/
				
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



							/*
								Run usage notification logic
							*/

							if ($GLOBALS["config"]["SERVICES_USAGEALERTS_ENABLE"])
							{
								$message = "";

								if ($usage > $obj_service->data["included_units"])
								{
									// usage is over 100% - check if we should report this
									log_debug("inc_service_usage", "Usage is over 100%");

									if ($obj_service->data["alert_extraunits"])
									{
										// check at what usage amount we last reported, and if
										// we have used alert_extraunits more usage since then, send
										// an alert to the customer.

										if (($usage - $period_data["usage_alerted"]) >= $obj_service->data["alert_extraunits"])
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
											$usage_excess = $usage - $obj_service->data["included_units"];

											// prepare message
											$message .= "This email has been sent to advise you that you have gone over the included usage on your plan\n";
											$message .= "\n";
											$message .= "You have now used $usage_excess excess $unitname on your ". $obj_service->data["name_service"] ." plan.\n";
											$message .= "\n";
											$message .= "Used $usage $unitname out of ". $obj_service->data["included_units"] ." $unitname included in plan.\n";
											$message .= "Excess usage of $usage_excess $unitname charged at ". $obj_service->data["price_extraunits"] ." per $unitname (exc taxes).\n";
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


											// update alerted amount
											$sql_obj		= New sql_query;
											$sql_obj->string	= "UPDATE services_customers_periods SET usage_alerted='$usage' WHERE id='". $period_data["id"] ."' LIMIT 1";
											$sql_obj->execute();

										}
									}

								}
								else
								{
									// calculate 80% of the included usage
									$included_usage_80pc = $obj_service->data["included_units"] * 0.80;


									if ($usage == $obj_service->data["included_units"])
									{
										log_debug("inc_service_usage", "Usage is at 100%");

										// usage is at 100%
										//
										// make sure that:
										// 1. 100% usage alerting is enabled
										// 2. that we have not already sent this alert (by checking period_data["usage_summary"])
										//
										if ($obj_service->data["alert_100pc"] && $period_data["usage_summary"] < $obj_service->data["included_units"])
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
											$message .= "This email has been sent to advise you that you have used 100% of your included usage on your ". $obj_service->data["name_service"] ." plan.\n";
											$message .= "\n";
											$message .= "Used $usage $unitname out of ". $obj_service->data["included_units"] ." $unitname included in plan.\n";
											$message .= "Any excess usage will be charged at ". $obj_service->data["price_extraunits"] ." per $unitname (exc taxes).\n";
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

											// update alerted amount
											$sql_obj		= New sql_query;
											$sql_obj->string	= "UPDATE services_customers_periods SET usage_alerted='$usage' WHERE id='". $period_data["id"] ."' LIMIT 1";
											$sql_obj->execute();

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
										if ($obj_service->data["alert_80pc"] && $period_data["usage_summary"] < $included_usage_80pc)
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
											$message .= "This email has been sent to advise you that you have used over 80% of your included usage on your ". $obj_service->data["name_service"] ." plan.\n";
											$message .= "\n";
											$message .= "Used $usage $unitname out of ". $obj_service->data["included_units"] ." $unitname included in plan.\n";
											$message .= "Any excess usage will be charged at ". $obj_service->data["price_extraunits"] ." per $unitname (exc taxes).\n";
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

											// update alerted amount
											$sql_obj		= New sql_query;
											$sql_obj->string	= "UPDATE services_customers_periods SET usage_alerted='$usage' WHERE id='". $period_data["id"] ."' LIMIT 1";
											$sql_obj->execute();

										}

									}
								}
			
							} // end if alerts enabled
							else
							{
								log_write("notification", "inc_service_usage", "Not sending usage notification/reminder due to SERVICES_USAGEALERTS_ENABLE being disabled");
							}
							


							/*
								Update Usage Summary - this used for various user interfaces and is the *total* transfer usage report.
							*/

							$sql_obj		= New sql_query;
							$sql_obj->string	= "UPDATE services_customers_periods SET usage_summary='$usage' WHERE id='". $period_data["id"] ."' LIMIT 1";
							$sql_obj->execute();


						break;


						case "data_traffic":
							/*
								DATA_TRAFFIC

								Data traffic services are more complex for usage checks and notifications than other services, due to the need to 
								fetch usage amounts for each cap type and notify as appropiate.

								Some data services may also be uncapped/unlimited, in which case we want to record their current usage amount but
								won't need to ever send usage notifications.
							*/



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
							$traffic_types_obj->id_service		= $customer_data["serviceid"];
							$traffic_types_obj->id_service_customer	= $customer_data["id"];

							$traffic_types_obj->load_data_traffic_caps();
							$traffic_types_obj->load_data_override_caps();
							


							/*
								Fetch the amount of usage
							*/

							$usage_obj					= New service_usage_traffic;
							$usage_obj->id_service_customer			= $customer_data["id"];
							$usage_obj->date_start				= $period_data["date_start"];
							$usage_obj->date_end				= $period_data["date_end"];
							
							if ($usage_obj->load_data_service())
							{
								$usage_obj->fetch_usage_traffic();
							}



							/*
								Update service usage database record for each cap

								Create a new usage alert summary record - this record defines the usage as at a certain date
								and tracks whether usage alerts were sent or not.
							*/

							$usage_alert_id = array(); // holds IDs of inserted rows

							foreach ($traffic_types_obj->data as $traffic_cap)
							{
								log_write("debug", "inc_service_usage", "Service ". $customer_data["id"] ." data usage for traffic type ". $traffic_type["type_name"] ." is ". $usage_obj->data["total_byunits"][ $traffic_cap["type_label"] ] ." units");
								
								// create new record
								$sql_obj		= New sql_query;
								$sql_obj->string	= "INSERT INTO service_usage_alerts
												(id_service_customer,
												 id_service_period,
												 id_type,
												 date_update,
												 usage_current)
												VALUES
												('". $customer_data["id"] ."',
												 '". $period_data["id"] ."',
												 '". $traffic_cap["id_type"] ."',
												 '". date("Y-m-d") ."',
												 '". $usage_obj->data["total_byunits"][ $traffic_cap["type_label"] ] ."')";
								$sql_obj->execute();


								// record type label ID
								$usage_alert_id[ $traffic_cap["type_label"] ] = $sql_obj->fetch_insert_id();
							}


							// Update Usage Summary - this used for various user interfaces and is the *total* transfer usage report.
							log_write("notification", "inc_service_usage", "Customer ". $sql_customer_obj["name_customer"] ." has used a total of ". $usage_obj->data["total_byunits"]["total"] ." $unitname traffic.");

							$sql_obj		= New sql_query;
							$sql_obj->string	= "UPDATE services_customers_periods SET usage_summary='". $usage_obj->data["total_byunits"]["total"] ."' WHERE id='". $period_data["id"] ."' LIMIT 1";
							$sql_obj->execute();





							/*
								Run usage notification logic
							*/

							if ($GLOBALS["config"]["SERVICES_USAGEALERTS_ENABLE"])
							{
								// fetch usage - in particular, the last usage amount that we alerted for.
								$usage		= $usage_obj->data["total_byunits"][ $traffic_cap["type_label"] ];
								$usage_alerted	= 0;

								$sql_obj->string	= "SELECT date_sent, usage_alerted FROM service_usage_alerts WHERE id_service_customer='". $customer_data["id"] ."' AND id_service_period='". $period_data["id"] ."' AND id_type='". $traffic_cap["id_type"] ."' AND id!='". $usage_alert_id[ $traffic_cap["type_label"] ] ."' ORDER BY date_sent DESC, id DESC LIMIT 1";
								$sql_obj->execute();

								if ($sql_obj->num_rows())
								{
									$sql_obj->fetch_array();

									if ($sql_obj->data[0]["date_sent"] != "0000-00-00")
									{
										$usage_alerted = $sql_obj->data[0]["usage_alerted"];
									}

								}


								// used to flag the usage caps that need alerting
								$alert_80	= array();
								$alert_100	= array();
								$alert_extra	= array();
								$alert_none	= array();


								// run through current caps, flag all which need notifications.
								foreach ($traffic_types_obj->data as $traffic_cap)
								{
									// we don't care about unlimited traffic
									if ($traffic_cap["cap_mode"] != "capped")
									{
										// skip
										log_write("debug", "inc_service_usage", "Skipping traffic cap due to mode of ". $traffic_cap["cap_mode"] ."");

										$alert_none[] = $traffic_cap["type_label"];

										continue;
									}


									// determine caps
									$cap_included_units			= $traffic_cap["cap_units_included"] * $ratio;	// apply ratios
									$traffic_cap["cap_units_included"]	= $cap_included_units;				// override, no permanant changes


									// determine threshholds
									$cap_100	= $cap_included_units;
									$cap_80		= $cap_included_units * 0.80;

									if ($usage >= $cap_100)
									{
										// usage is at or over 100%

										if ($usage < ($cap_100 + $obj_service->data["alert_extraunits"]))
										{
											// just over 100%, but less than the excess alert count - consider as 100%

											if ($usage_alerted < $cap_100)
											{
												$alert_100[] = $traffic_cap["type_label"];
											}
											else
											{
												$alert_none[] = $traffic_cap["type_label"];
											}
										}
										else
										{
											// well over 100%
											if ($obj_service->data["alert_extraunits"])
											{
												// check at what usage amount we last reported, and if
												// we have used alert_extraunits+ more usage since then, send
												// an alert to the customer.

												if (($usage - $usage_alerted) >= $obj_service->data["alert_extraunits"])
												{
													// excess usage
													$alert_extra[] = $traffic_cap["type_label"];
												}
												else
												{
													$alert_none[] = $traffic_cap["type_label"];
												}
											}
											else
											{
												// no extra unit alerts configured, so we should not alert to excess
												// usage.
												$alert_none[] = $traffic_cap["type_label"];
											}
										}
									}
									elseif ($usage >= $cap_80 && $usage < $cap_100)
									{
										// usage between 80% and 100%
										if ($obj_service->data["alert_80pc"] && $usage_alerted < $cap_80)
										{
											// we haven't alerted for this yet, so flag it
											$alert_80[] = $traffic_cap["type_label"];
										}
										else
										{
											$alert_none[] = $traffic_cap["type_label"];
										}
									}
									else
									{
										// usage is below 80% mark
										$alert_none[] = $traffic_cap["type_label"];
									}

								} // end of traffic loops
	
	
								log_write("debug", "inc_service_usage", "Following data caps are at 80% alert: ". format_arraytocommastring($alert_80) ."");
								log_write("debug", "inc_service_usage", "Following data caps are at 100% alert: ". format_arraytocommastring($alert_100) ."");
								log_write("debug", "inc_service_usage", "Following data caps are at 100% + extra blocks alert: ". format_arraytocommastring($alert_extra) ."");
								log_write("debug", "inc_service_usage", "Following data caps do not require alerting: ". format_arraytocommastring($alert_none) ."");


								/*
									Process Usage Notifications

									Here we need to loop through all caps flagged for notifications and write a message to the customer
									for all overage caps
								*/
								if ( !empty($alert_80) || !empty($alert_100)  || !empty($alert_extra) )
								{
									log_write("debug", "inc_service_usage", "Alerting for service, preparing email message.");

									/*
										Now we run through all the alert flagged data caps and use it to assemble a usage notification warning email.


										Example Message
										---------------

										DATA USAGE ADVISORY

										This email has been sent to advise you about your data service usage as of 18-05-2011.

										Service "My Example Internet Service"
										

										NATIONAL

										You have used 150% of your National data cap.

										Used 15GB out of 10GB included in plan.
										Excess usage of 5GB charged at $5.00 per GB (exc taxes)


										INTERNATIONAL

										You have used 84% of your International data cap.

										Used 84GB out of 100GB included in plan.
										Any future excess usage will be charged at $8.00 per GB (exc taxes)


										BILLING PERIOD

										Your current billing period ends on YYYY-MM-DD.
									*/

									$message  = "\n";
									$message .= "DATA USAGE ADVISORY\n";
									$message .= "\n";
									$message .= "This email has been sent to advise you about your data service usage as of ". time_format_humandate() ."\n";
									$message .= "\n";
									$message .= "Service \"". $obj_service->data["name_service"] ."\"\n";
									$message .= "\n";
									$message .= "\n";
									$message .= "\n";
									


									foreach ($traffic_types_obj->data as $traffic_cap)
									{
										log_write("debug", "inc_service_usage", "Preparing usage warning email messages for: \"". $traffic_cap["type_label"] ."\"");


										// determine percentage used
										$percentage  = sprintf("%d", (($usage_obj->data["total_byunits"][ $traffic_cap["type_label"] ] / $traffic_cap["cap_units_included"]) * 100));

										// if there is only one cap, adjust label
										if ($traffic_types_obj->data_num_rows == 1)
										{
											$traffic_cap["type_name"] = "All Traffic";
										}


										// 80%-100%
										if (in_array($traffic_cap["type_label"], $alert_80))
										{
											$message .= strtoupper($traffic_cap["type_name"]) ."\n";
											$message .= "\n";
											$message .= "You have used $percentage% of your ". $traffic_cap["type_name"] ." data cap\n";
											$message .= "\n";
											$message .= "Used ". $usage_obj->data["total_byunits"][ $traffic_cap["type_label"] ] ." $unitname out of ". $traffic_cap["cap_units_included"] ." $unitname included in plan.\n";
											$message .= "Any future excess usage will be charged at ". format_money($traffic_cap["cap_units_price"]) ." per $unitname (exc taxes)\n";
											$message .= "\n\n";
										}

										// 100%
										if (in_array($traffic_cap["type_label"], $alert_100))
										{
											$message .= strtoupper($traffic_cap["type_name"]) ."\n";
											$message .= "\n";
											$message .= "You have used $percentage% of your ". $traffic_cap["type_name"] ." data cap\n";
											$message .= "\n";
											$message .= "Used ". $usage_obj->data["total_byunits"][ $traffic_cap["type_label"] ] ." $unitname out of ". $traffic_cap["cap_units_included"] ." $unitname included in plan.\n";
											$message .= "Any future excess usage will be charged at ". format_money($traffic_cap["cap_units_price"]) ." per $unitname (exc taxes)\n";
											$message .= "\n\n";
										}

										// 100% ++ excess
										if (in_array($traffic_cap["type_label"], $alert_extra))
										{
											$usage_excess = $usage_obj->data["total_byunits"][ $traffic_cap["type_label"] ] - $traffic_cap["cap_units_included"];

											$message .= strtoupper($traffic_cap["type_name"]) ."\n";
											$message .= "\n";
											$message .= "You have used $percentage% of your ". $traffic_cap["type_name"] ." data cap\n";
											$message .= "\n";
											$message .= "Used ". $usage_obj->data["total_byunits"][ $traffic_cap["type_label"] ] ." out of ". $traffic_cap["cap_units_included"] ." $unitname included in plan.\n";
											$message .= "Excess usage of ". $usage_excess ." $unitname charged at ". format_money($traffic_cap["cap_units_price"]) ." per $unitname (exc taxes)\n";
											$message .= "\n\n";
										}
									}


									/*
										Footer
									*/

									$message .= "BILLING PERIOD\n";
									$message .= "\n";
									$message .= "Your current billing period ends on ". time_format_humandate($period_data["date_end"]) ."\n";
									$message .= "\n";


									/*
										Issue Email

										TODO:	Future enhancements will include the capability to select a usage contact email address first, before
											falling back to the regular accounts email address. This will make it easier for companies to send bills
											to AR but usage alerts to staff.
									*/

									if ($sql_customer_obj["contact_email"])
									{
										$headers = "From: $email_sender\r\n";

										mail($sql_customer_obj["name_contact"] ."<". $sql_customer_obj["contact_email"] .">", "Service usage notification", $message, $headers);


										log_write("notification", "inc_service_usage", "Issuing usage notification email to ". $sql_customer_obj["name_contact"] ." at ". $sql_customer_obj["contact_email"] ."");
									}
									else
									{
										log_write("error", "inc_service_usage", "Customer ". $sql_customer_obj["name_customer"] ." does not have an email address, unable to send usage notifications.");
									}



									/*	
										Update alerted amount tracker
									*/

									$sql_obj		= New sql_query;
									$sql_obj->string	= "UPDATE service_usage_alerts SET usage_alerted='". $usage_obj->data["total_byunits"][ $traffic_cap["type_label"] ] ."', date_sent='". date("Y-m-d") ."' WHERE id='". $usage_alert_id[ $traffic_cap["type_label"] ] ."' LIMIT 1";
									$sql_obj->execute();


								} // end if usage notifications to process
			
							} // end if alerts enabled


							unset($traffic_types_obj);
							unset($usage_obj);

						break;

					} // end of switch service type

				} // end if usage periods exist

			}  // end if a usage service

		} // end of loop through customer services

	} // end if customer(s) services exist

} // end of service_usage_alerts_generate




?>
