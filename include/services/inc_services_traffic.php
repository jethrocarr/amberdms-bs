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

		TODO: Investigating extending data traffic to be like CDR codes with data rate tables to
			allow different charging for certain networks, such as domestic or within the data center.

		Returns
		0		failure
		1		success
	*/
	function fetch_usage_traffic()
	{
		log_write("debug", "service_usage_traffic", "Executing fetch_usage_traffic()");



		/*
			Query Call Records
		*/

		if ($GLOBALS["config"]["SERVICE_TRAFFIC_MODE"] == "internal")
		{
			log_write("error", "service_usage_traffic", "Internal traffic records not yet implemented.");

			// TODO: implement internal IPv4 traffic record handling code.
		}
		else
		{
			/*
				Connect to External SQL database

				Mode: mysql_netflow_daily

				In this mode, there are netflow tables for each day which we need to read through and aggregate data from.
			*/

			$obj_traffic_db_sql = New sql_query;

			if (!$obj_traffic_db_sql->session_init("mysql", $GLOBALS["config"]["SERVICE_TRAFFIC_DB_HOST"], $GLOBALS["config"]["SERVICE_TRAFFIC_DB_NAME"], $GLOBALS["config"]["SERVICE_TRAFFIC_DB_USERNAME"], $GLOBALS["config"]["SERVICE_TRAFFIC_DB_PASSWORD"]))
			{
				return 0;
			}


			/*
				Workout the data range, since we need to query a different table for each day
			*/

			$date_tmp		= $this->date_start;
			$date_range		= array();

			$date_range[]		= $this->date_start;

			while ($tmp_date != $this->date_end)
			{
				$tmp_date	= explode("-", $tmp_date);
				$tmp_date 	= date("Y-m-d", mktime(0,0,0,$tmp_date[1], ($tmp_date[2] +1), $tmp_date[0]));
			}



			/*
				We work out the usage by fetching the totals for each IPv4 address belonging to this
				service and aggregating the total.
			*/

			if (!$this->data_ipv4)
			{
				$this->load_data_ipv4();
			}

			foreach ($this->data_ipv4 as $ipv4)
			{
				// TODO: working here on data traffic charging logic


				/*
					Fetch Data
				*/
				log_write("debug", "service_usage_traffic", "Fetching usage records FOR $ipv4 FROM $date_start TO $date_end");

					$obj_cdr_db_sql->string		= "SELECT calldate, billsec, src, dst FROM cdr WHERE disposition='ANSWERED' AND src='$ddi' AND calldate >= '$date_start' AND calldate < '$date_end'";
					$obj_cdr_db_sql->execute();


				/*
					Calculate costs of calls
				*/
				if ($obj_cdr_db_sql->num_rows())
				{
					$obj_cdr_db_sql->fetch_array();

					foreach ($obj_cdr_db_sql->data as $data_cdr)
					{
						// determine price
						$charges			= $obj_cdr_rate_table->calculate_charges($data_cdr["billsec"], $data_cdr["src"], $data_cdr["dst"]);

						// create local usage record for record keeping purposes
						$sql_obj			= New sql_query;
						$sql_obj->string		= "INSERT INTO service_usage_records (id_service_customer, date, price, usage1, usage2, usage3) VALUES ('". $this->id_service_customer ."', '". $data_cdr["calldate"] ."', '". $charges ."', '". $data_cdr["src"] ."', '". $data_cdr["dst"] ."', '". $data_cdr["billsec"] ."')";
						$sql_obj->execute();

						// add to structure
						$this->data[ $ddi ]["charges"]	+= $charges;
					}
				}

			} // end of DDI loop



			/*
				Disconnect from database
			*/

			$obj_cdr_db_sql->session_terminate();

		} // end of external data source

	} // end of fetch_usage_calls

} // end of class: service_usage_cdr




?>
