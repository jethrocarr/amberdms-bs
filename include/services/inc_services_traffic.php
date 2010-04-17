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




?>
