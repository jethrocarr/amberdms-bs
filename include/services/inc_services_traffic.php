<?php
/*
	include/services/inc_service_traffic.php

	Provides various functions for handling data traffic service types
	including defining IP addresses and fetching usage amounts.
*/


	

/*
	CLASS: traffic_customer_service_ipv4

	Functions for managing IPv4 addresses for a selected customer-service.
*/

class traffic_customer_service_ipv4
{
	var $id;			// ID of the DDI record
	var $data;			// DDI record data/values to change.

	var $id_customer;		//
	var $id_service_customer;	//



	/*
		verify_id

		Verify that the supplied ID is valid and fetch the customer and service-customer IDs that go along with it.

		Results
		0	Failure to find the ID
		1	Success
	*/

	function verify_id()
	{
		log_debug("traffic_customer_service_ipv4", "Executing verify_id()");

		if ($this->id)
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id_service_customer, services_customers.customerid as id_customer FROM `services_customers_ipv4` LEFT JOIN services_customers ON services_customers.id = services_customers_ipv4.id_service_customer WHERE services_customers_ipv4.id='". $this->id ."' LIMIT 1";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				$sql_obj->fetch_array();


				// verify id_service_customer
				if ($this->id_service_customer)
				{
					if ($sql_obj->data[0]["id_service_customer"] == $this->id_service_customer)
					{
						log_write("debug", "traffic_customer_service_ipv4", "The selected service-customer matches the IPv4 entry");
					}
					else
					{
						log_write("error", "traffic_customer_service_ipv4", "The seleced service-customer (". $this->id_service_customer .") does not match the selected customer (". $this->id .").");
						return 0;
					}
				}
				else
				{
					$this->id_service_customer = $sql_obj->data[0]["id_service_customer"];

					log_write("debug", "traffic_customer_service_ipv4", "Setting id_service_customer to ". $this->id_service_customer ."");
				}


				// verify customer ID
				if ($this->id_customer)
				{
					if ($sql_obj->data[0]["id_customer"] == $this->id_customer)
					{
						log_write("debug", "traffic_customer_service_ipv4", "The selected IPv4 address belongs to the correct customer and service-customer mapping");
						return 1;
					}
					else
					{
						log_write("error", "traffic_customer_service_ipv4", "The selected IPv4 address does not belong to the selected customer ". $this->id ."");
						return 0;
					}

				}
				else
				{
					$this->id_customer = $sql_obj->data[0]["id_customer"];

					log_write("debug", "traffic_customer_service_ipv4", "Setting id_customer to ". $this->id ."");
					return 1;
				}
			}
		}

		return 0;

	} // end of verify_id



	/*
		verify_unique_ipv4

		Verifies that the supplied IPv4 address/subnet is not already used by any other service/customer

		Results
		0	Failure - address is assigned to another customer
		1	Success - address is available
	*/

	function verify_unique_ipv4()
	{
		log_debug("traffic_customer_service_ipv4", "Executing verify_unique_ipv4()");
/*
		TODO: write me

		$sql_obj			= New sql_query;
		$sql_obj->string		= "SELECT id FROM `services_customers_ipv4` WHERE r='". $this->data["code_customer"] ."' ";

		if ($this->id)
			$sql_obj->string	.= " AND id!='". $this->id ."'";

		$sql_obj->string		.= " LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			return 0;
		}
*/
		return 1;

	} // end of verify_unique_ipv4



	/*
		load_data

		Load the IPv4 data

		Results
		0	Failure
		1	Success
	*/
	function load_data()
	{
		log_write("debug", "traffic_customer_service_ipv4", "Executing load_data()");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT ipv4_address, ipv4_cidr, description FROM services_customers_ipv4 WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$sql_obj->fetch_array();

			$this->data = $sql_obj->data[0];

			return 1;
		}

		return 0;

	} // end of load_data
	


	/*
		action_create

		Create a new IPv4 record based on the data in $this->data

		Results
		0	Failure
		#	Success - return ID
	*/
	function action_create()
	{
		log_debug("traffic_customer_service_ipv4", "Executing action_create()");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "INSERT INTO `services_customers_ipv4` (id_service_customer) VALUES ('". $this->id_service_customer . "')";
		$sql_obj->execute();

		$this->id = $sql_obj->fetch_insert_id();

		return $this->id;

	} // end of action_create




	/*
		action_update

		Updates the IPv4 record

		Returns
		0	failure
		#	success - returns the ID
	*/
	function action_update()
	{
		log_debug("traffic_customer_service_ipv4", "Executing action_update()");

		/*
			Start Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();



		/*
			If no ID supplied, create a new DDI first
		*/
		if (!$this->id)
		{
			$mode = "create";

			if (!$this->action_create())
			{
				return 0;
			}
		}
		else
		{
			$mode = "update";
		}



		/*
			Update IPv4 value
		*/

		$sql_obj->string	= "UPDATE `services_customers_ipv4` SET "
						."ipv4_address='". $this->data["ipv4_address"] ."', "
						."ipv4_cidr='". $this->data["ipv4_cidr"] ."', "
						."description='". $this->data["description"] ."' "
						."WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		
		/*
			Commit
		*/

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "traffic_customer_service_ipv4", "An error occured when updating customer IPv4 address");

			return 0;
		}
		else
		{
			$sql_obj->trans_commit();

			if ($mode == "update")
			{
				log_write("notification", "traffic_customer_service_ipv4", "Customer IPv4 address successfully updated.");
			}
			else
			{
				log_write("notification", "traffic_customer_service_ipv4", "Customer IPv4 address successfully created.");
			}
			
			return $this->id;
		}

	} // end of action_update



	/*
		action_delete

		Deletes a IPv4 address

		Results
		0	failure
		1	success
	*/
	function action_delete()
	{
		log_debug("traffic_customer_service_ipv4", "Executing action_delete()");


		/*
			Start Transaction
		*/

		$sql_obj = New sql_query;
		$sql_obj->trans_begin();


		/*
			Delete DDI
		*/
			
		$sql_obj->string	= "DELETE FROM services_customers_ipv4 WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();


		/*
			Commit
		*/
		
		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "traffic_customer_service_ipv4", "An error occured whilst trying to delete the IPv4 address.");

			return 0;
		}
		else
		{
			$sql_obj->trans_commit();

			log_write("notification", "traffic_customer_service_ipv4", "IPv4 address has been successfully deleted.");

			return 1;
		}
	}


} // end of class: traffic_customer_service_ipv4




/*
	CLASS: service_usage_traffic

	Functions for querying traffic usage for billing purposes
*/
class service_usage_traffic extends service_usage
{

	var $data_ipv4;		// contains IPv4 address array loaded by load_data_ipv4


	/*
		load_data_ipv4

		Fetches an array of all the customer's IPv4 addresses into the $this->data value and returns the number of addresses assigned.

		Returns
		0		No addresses / An error occured
		#		Number of IPv4 addresses belonging to the customer.
	*/

	function load_data_ipv4()
	{
		log_write("debug", "service_usage_traffic", "Executing load_data_ipv4()");


		// fetch all the DDIs for this service-customer
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT ipv4_address, ipv4_cidr FROM services_customers_ipv4 WHERE id_service_customer='". $this->id_service_customer ."'";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$sql_obj->fetch_array();

			$this->data_ipv4	= array();

			foreach ($sql_obj->data as $data_ipv4)
			{
				if ($data_ipv4["ipv4_cidr"] == "32")
				{
					// single IP
					$this->data_ipv4[]		= $data_ipv4["ipv4_address"];
				}
				else
				{
					// subnet
					foreach (ipv4_subnet_members($data_ipv4["ipv4_address"] ."/". $data_ipv4["ipv4_cidr"], TRUE) as $address)
					{
						$this->data_ipv4[]	= $address;
					}
				}
			}

			// return the total number of addresses
			$total	= count($this->data_ipv4);

			log_write("debug", "service_usage_traffic", "Customer has ". $total ." IPv4 addresses on their service");

			return $total;
		}
		else
		{
			log_write("warning", "service_usage_traffic", "There are no ipv4 addresses assigned to id_service_customer ". $this->id_service_customer ."");
			return 0;
		}

	} // end of load_data_ipv4


	/*
		load_data_ipv6
		
		// TODO: Implement IPv6 support
	*/


	/*
		fetch_usage_ipv4

		// TODO: potential place for IP address allocation charging, where the cost of each IP address will be calculated
			 and then billed for accordingly.

	*/

	/*
		fetch_usage_ipv6

		// TODO: potential place for IP address allocation charging? Is anyone even going to care about charging for IPv6 addresses?

	*/



	/*
		fetch_usage_traffic

		Fetching data traffic from database and returns total usage amount.

		Returns
		0		failure
		1		success
	*/
	function fetch_usage_traffic()
	{
		log_write("debug", "service_usage_traffic", "Executing fetch_usage_traffic()");



		/*
			Fetch raw usage data from DB
		*/

		if ($GLOBALS["config"]["SERVICE_TRAFFIC_MODE"] == "internal")
		{
			/*
				Internal Database

				Use the internal database - this stores the usage information for upload/download mapped against the customer's
				IP address.
			*/

			log_write("debug", "service_usage_traffic", "Fetching traffic records from internal database");

			// fetch upload/download stats
			$sql_obj			= New sql_query;
			$sql_obj->string		= "SELECT SUM(usage1) as usage1, SUM(usage2) as usage2 FROM service_usage_records WHERE id_service_customer='". $this->id_service_customer ."' AND date>='". $this->date_start ."' AND date<='". $this->date_end ."'";
			$sql_obj->execute();
			$sql_obj->fetch_array();

			$this->data["usage1"]	= $sql_obj->data[0]["usage1"];
			$this->data["usage2"]	= $sql_obj->data[0]["usage2"];


			// create a total of both usage columns
			$sql_obj			= New sql_query;
			$sql_obj->string		= "SELECT '". $this->data["usage1"] ."' + '". $this->data["usage2"] ."' as totalusage";
			$sql_obj->execute();
			$sql_obj->fetch_array();
	
			$this->data["total"] 	= $sql_obj->data[0]["totalusage"];


			// we now have the raw usage
			log_write("debug", "service_usage_traffic", "Total raw traffic usage for ". $this->date_start ." until ". $this->date_end ." is ". $this->data["total"] ." bytes");
		}
		else
		{
			/*
				Connect to External SQL database

				External DBs are common with larger teleco providers since it allows easier storage, splicing and archiving of usage information
				for data traffic services.
			*/


			switch ($GLOBALS["config"]["SERVICE_TRAFFIC_DB_TYPE"])
			{
				case "mysql_netflow_daily":
					/*
						MODE: mysql_netflow_daily

						In this mode, there are netflow tables for each day which we need to read through and aggregate data from, typically
						this is done so that busy ISPs don't end up with massive monthly/yearly tables.

						eg:
						traffic_20110420
						traffic_20110421
						traffic_20110422

					*/

					log_write("debug", "service_usage_traffic", "Processing external database mysql_netflow_daily");



					/*
						Connect to external database
					*/

					$obj_traffic_db_sql = New sql_query;

					if (!$obj_traffic_db_sql->session_init("mysql", $GLOBALS["config"]["SERVICE_TRAFFIC_DB_HOST"], $GLOBALS["config"]["SERVICE_TRAFFIC_DB_NAME"], $GLOBALS["config"]["SERVICE_TRAFFIC_DB_USERNAME"], $GLOBALS["config"]["SERVICE_TRAFFIC_DB_PASSWORD"]))
					{
						log_write("error", "service_usage_traffic", "Unable to establish a connection to the external traffic DB, unable to run data usage processing.");

						return 0;
					}



					/*
						Workout the date range, since we need to query a different table for each day

						TODO: this would be nice as a generic function?
					*/


					$tmp_date		= $this->date_start;
					$date_range		= array();

					$date_range[]		= $this->date_start;

					while ($tmp_date != $this->date_end)
					{
						$tmp_date	= explode("-", $tmp_date);
						$tmp_date 	= date("Y-m-d", mktime(0,0,0,$tmp_date[1], ($tmp_date[2] +1), $tmp_date[0]));

						$date_range[]	= $tmp_date;
					}

					for ($i=0; $i < count($date_range); $i++)
					{
						// strip "-" charactor
						$date_range[$i] = str_replace("-", "", $date_range[$i]);
					}


					/*
						We work out the usage by fetching the totals for each IPv4 address belonging to this
						service and aggregating the total.
					*/

					// make sure we have the array of IPv4 addresses
					if (!$this->data_ipv4)
					{
						$this->load_data_ipv4();
					}

					// blank current total
					$this->data["total"] = 0;

					// verify IPv4 address have been configured
					if (!is_array($this->data_ipv4))
					{
						log_write("warning", "service_usage_traffic", "Note: No IPv4 addresses have been configured for this customer");

						return 0;
					}


					// run through each IP
					foreach ($this->data_ipv4 as $ipv4)
					{
						/*
							Fetch Data

							We run through each IP and for each IP, we fetch the total from all the daily tables. Note that
							we make the assumption that daily tables might not exist if there's nothing to be processed for that
							day, so the code is written accordingly.

							Note that we use the SQL database for *ALL* calculations, this is due to the SQL DB being able
							to handle 64bit integers, whereas PHP will vary depending on the host platform.
						*/

						log_write("debug", "service_usage_traffic", "Fetching usage records FOR address $ipv4 FOR date ". $this->date_start ." to ". $this->date_end ."");


						// run through the dates
						foreach ($date_range as $date)
						{
							// check that the table exists
							$obj_traffic_db_sql->string		= "SHOW TABLES LIKE 'traffic_$date'";
							$obj_traffic_db_sql->execute();

							if ($obj_traffic_db_sql->num_rows())
							{
								// query the current date for the current IP
								$obj_traffic_db_sql->string		= "SELECT SUM(bytes) as total FROM traffic_$date WHERE ip_src='$ipv4' OR ip_dst='$ipv4'";
								$obj_traffic_db_sql->execute();

								$obj_traffic_db_sql->fetch_array();

								if (!empty($obj_traffic_db_sql->data[0]["total"]))
								{
									// add to running total
									$sql_obj			= New sql_query;
									$sql_obj->string		= "SELECT '". $this->data["total"] ."' + '". $obj_traffic_db_sql->data[0]["total"] ."' as totalusage";
									$sql_obj->execute();
									$sql_obj->fetch_array();

									$this->data["total"] 	= $sql_obj->data[0]["totalusage"];

								} // end if traffic exists

							} // end if table exists/query succeeds
							else
							{
								log_write("warning", "service_usage_traffic", "SQL database table traffic_$date does not exist");
							}

						} // end foreach date

						log_write("debug", "service_usage_traffic", "Total usage for address $ipv4 is ". $this->data["total"] ." bytes");

					} // end foreach ipv4
					
					log_write("debug", "service_usage_traffic", "Total usage for all addresses in the date range is ". $this->data["total"] ." bytes");



					/*
						TODO: Investigating extending data traffic to be like CDR codes with data rate tables to
						allow different charging for certain networks, such as domestic or within the data center.

						We do not currently need to worry about rating the traffic differently depending on
						who the packets are going to, we just need a total for the service for that customer.
					*/


					/*
						Disconnect from database
					*/
	
					$obj_traffic_db_sql->session_terminate();


				break;



				case "mysql_traffic_summary":
					/*
						MODE: mysql_traffic_summary

						In this mode, the database contains a single table "traffic_summary" which includes the following key fields:
						* ip_address			IPv4 Address
						* traffic_datetime		Date/Time Field
						* total				Total Bytes transfered

						Ideally this table should contain one row per IP address, per day, to enable billing to occur.
					*/

					log_write("debug", "service_usage_traffic", "Processing external database mysql_traffic_summary");


					/*
						Connect to external database
					*/

					$obj_traffic_db_sql = New sql_query;

					if (!$obj_traffic_db_sql->session_init("mysql", $GLOBALS["config"]["SERVICE_TRAFFIC_DB_HOST"], $GLOBALS["config"]["SERVICE_TRAFFIC_DB_NAME"], $GLOBALS["config"]["SERVICE_TRAFFIC_DB_USERNAME"], $GLOBALS["config"]["SERVICE_TRAFFIC_DB_PASSWORD"]))
					{
						log_write("error", "service_usage_traffic", "Unable to establish a connection to the external traffic DB, unable to run data usage processing.");

						return 0;
					}



					/*
						Loop through each IP and fetch usage for that IP.
					*/

					// make sure we have the array of IPv4 addresses
					if (!$this->data_ipv4)
					{
						$this->load_data_ipv4();
					}

					// blank current total
					$this->data["total"] = 0;

					// verify IPv4 address have been configured
					if (!is_array($this->data_ipv4))
					{
						log_write("warning", "service_usage_traffic", "Note: No IPv4 addresses have been configured for this customer");

						return 0;
					}


					// run through each IP
					foreach ($this->data_ipv4 as $ipv4)
					{
						/*
							Fetch Data

							We run through each IP and for each IP, we fetch the total for the date range.

							Note that we use the SQL database for *ALL* calculations, this is due to the SQL DB being able
							to handle 64bit integers, whereas PHP will vary depending on the host platform.
						*/

						log_write("debug", "service_usage_traffic", "Fetching usage records FOR address $ipv4 FOR date ". $this->date_start ." to ". $this->date_end ."");


						// check that the table exists
						$obj_traffic_db_sql->string		= "SHOW TABLES LIKE 'traffic_summary'";
						$obj_traffic_db_sql->execute();

						if ($obj_traffic_db_sql->num_rows())
						{
							// query the current date for the current IP
							$obj_traffic_db_sql->string		= "SELECT SUM(total) as total FROM traffic_summary WHERE ip_address='$ipv4' AND traffic_datetime >= '". $this->date_start ."' AND traffic_datetime <= '". $this->date_end ."'";
							$obj_traffic_db_sql->execute();

							$obj_traffic_db_sql->fetch_array();

							if (!empty($obj_traffic_db_sql->data[0]["total"]))
							{
								// add to running total
								$sql_obj			= New sql_query;
								$sql_obj->string		= "SELECT '". $this->data["total"] ."' + '". $obj_traffic_db_sql->data[0]["total"] ."' as totalusage";
								$sql_obj->execute();
								$sql_obj->fetch_array();

								$this->data["total"] 		= $sql_obj->data[0]["totalusage"];

							} // end if traffic exists

						} // end if table exists/query succeeds
						else
						{
							log_write("error", "service_usage_traffic", "SQL database table traffic_summary does not exist");
						}

						log_write("debug", "service_usage_traffic", "Total usage for address $ipv4 is ". $this->data["total"] ." bytes");

					} // end foreach ipv4
				
					log_write("debug", "service_usage_traffic", "Total usage for all addresses in the date range is ". $this->data["total"] ." bytes");



					/*
						TODO: Investigating extending data traffic to be like CDR codes with data rate tables to
						allow different charging for certain networks, such as domestic or within the data center.

						We do not currently need to worry about rating the traffic differently depending on
						who the packets are going to, we just need a total for the service for that customer.
					*/


					/*
						Disconnect from database
					*/
	
					$obj_traffic_db_sql->session_terminate();


				break;



				default:
					/*
						Unknown DB type, we should fail.
					*/
					log_write("error", "debug", "External DB type ". $GLOBALS["config"]["SERVICE_TRAFFIC_DB_TYPE"] ." is not supported.");
					return 0;
				break;

			} // end of switch between DB types

		} // end of external data source



		/*
			Generate formatted usage

			We have the number of raw units, we now need to generate the number of human readable/formatted units
			from this figure.
		*/

		log_write("debug", "service_usage_traffic", "Generating formatted usage totals");

		$this->data["numrawunits"] = sql_get_singlevalue("SELECT numrawunits as value FROM service_units WHERE id='". $this->obj_service->data["units"] ."' LIMIT 1");

		if (!$this->data["numrawunits"])
		{
			log_debug("service_usage_traffic", "Error: Unable to fetch number of raw units for the units type");
			return 0;
		}
			
		// calculate
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT '". $this->data["total"] ."' / '". $this->data["numrawunits"] ."' as value";
		$sql_obj->execute();
		$sql_obj->fetch_array();

		$this->data["total_byunits"]	= $sql_obj->data[0]["value"];

		
		log_write("debug", "service_usage_traffic", "Total traffic usage for period is ". $this->data["total_byunits"] ."");



		/*
			Complete
		*/

		return 1;

	} // end of fetch_usage_traffic

} // end of class: service_usage_traffic




?>
