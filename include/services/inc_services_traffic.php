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
	CLASS traffic_types

	Functions for managing and querying traffic types.
*/
class traffic_types
{
	var $id;		// traffic_type id
	var $data;		// traffic_type data



	/*
		verify_id

		Check that the supplied traffic type exists.

		Results
		0	Failure to find the ID
		1	Success - service exists
	*/

	function verify_id()
	{
		log_debug("traffic_types", "Executing verify_id()");

		if ($this->id)
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM `traffic_types` WHERE id='". $this->id ."' LIMIT 1";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				return 1;
			}
		}

		return 0;

	} // end of verify_id



	/*
		verify_fields

		Runs certains checks on inputs to ensure that the traffic type can be safely adjusted - examples include
		name or label conflicts, as well as special keywords.

		Results
		0	Unacceptable
		1	Acceptable
	*/

	function verify_fields()
	{
		log_debug("traffic_types", "Executing verify_fields()");


		if (!empty($this->data["type_name"]))
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM traffic_types WHERE type_name='". $this->data["type_name"] ."' LIMIT 1";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				log_write("error", "traffic_types", "This name is already in use, please select another");
				error_flag_field("type_name");

				return 0;
			}
		}

		if (!empty($this->data["type_label"]))
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM traffic_types WHERE type_label='". $this->data["type_label"] ."' LIMIT 1";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				log_write("error", "traffic_types", "This label is already in use, please select another");
				error_flag_field("type_label");

				return 0;
			}
		}

		if ($this->data["type_name"] == "any" || $this->data["type_name"] == "Any")
		{
			log_write("error", "traffic_types", "Any is a reserved cap name for catchall caps.");
			error_flag_field("type_name");

			return 0;
		}

		if ($this->data["type_label"] == "*" || $this->data["type_label"] == "any" || $this->data["type_label"] == "Any")
		{
			log_write("error", "traffic_types", "Any/* is a reserved label type for catchall caps.");
			error_flag_field("type_label");

			return 0;
		}

		return 1;

	} // end of verify_field




	/*
		check_delete_lock

		Checks whether we can safely delete the selected traffic type - if the traffic type is assigned
		to service caps it can't be deleted, nor can it be deleted if the traffic type ID == 1 (default catch all)

		Results
		0	Unlocked
		1	Locked
	*/

	function check_delete_lock()
	{
		log_debug("traffic_types", "Executing check_delete_lock()");

		// unable to delete anything with an ID of 1
		if ($this->id == 1)
		{
			return 1;
		}

		// makes sure not in use by any traffic caps
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM traffic_caps WHERE id_traffic_type='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			return 1;
		}


		// unlocked
		return 0;

	}  // end of check_delete_lock




	/*
		load_data

		Loads the traffic type data into $this->data

		Returns
		0	failure
		1	success
	*/
	function load_data()
	{
		log_debug("traffic_types", "Executing load_data()");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT type_name, type_description, type_label FROM traffic_types WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			// fetch basic service data
			$sql_obj->fetch_array();

			$this->data["type_name"]		= $sql_obj->data[0]["type_name"];
			$this->data["type_label"]		= $sql_obj->data[0]["type_label"];
			$this->data["type_description"]		= $sql_obj->data[0]["type_description"];

			return 1;
		}

		// failure
		return 0;

	} // end of load_data



	/*
		action_create
	
		Create a traffic type item based on the data in $this->data

		Results
		0	Failure
		#	Success - return ID
	*/
	function action_create()
	{
		log_write("debug", "traffic_types", "Executing action_create()");


		/*
			Start Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();


		/*
			Create CDR Rate Table
		*/
		$sql_obj->string	= "INSERT INTO `traffic_types` (type_name) VALUES ('". $this->data["type_name"]. "')";
		$sql_obj->execute();

		$this->id = $sql_obj->fetch_insert_id();


		/*
			Commit
		*/

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "traffic_types", "An error occured when attemping to define a new traffic type.");

			return 0;
		}
		else
		{
			$sql_obj->trans_commit();

			return $this->id;
		}


	} // end of action_create




	/*
		action_update

		Update the details for the selected traffic type based on the data in $this->data. If no ID is provided,
		it will first call the action_create function to add a new rate table.

		Returns
		0	failure
		#	success - returns the ID
	*/
	function action_update()
	{
		log_write("debug", "traffic_types", "Executing action_update()");


		/*
			Start Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();



		/*
			If no ID supplied, create a new rate table first
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
			Update Rate Table Details
		*/

		$sql_obj->string	= "UPDATE `traffic_types` SET "
						."type_name='". $this->data["type_name"] ."', "
						."type_label='". $this->data["type_label"] ."', "
						."type_description='". $this->data["type_description"] ."' "
						."WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		

		/*
			Commit
		*/

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "traffic_types", "An error occured when updating traffic type details.");

			return 0;
		}
		else
		{
			$sql_obj->trans_commit();

			if ($mode == "update")
			{
				log_write("notification", "traffic_types", "Traffic type successfully updated.");
			}
			else
			{
				log_write("notification", "traffic_types", "Traffic type successfully added.");
			}
			
			return $this->id;
		}

	} // end of action_update



	/*
		action_delete

		Deletes the selected rate table - note that check_delete_lock should be executed
		before calling this function.

		Results
		0	Failure
		1	Success
	*/
	function action_delete()
	{
		log_write("debug", "traffic_types", "Executing action_delete()");


		/*
			Start Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();


		/*
			Delete Traffic Type
		*/

		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM `traffic_types` WHERE id='". $this->id ."'";
		$sql_obj->execute();



		/*
			Commit
		*/

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "traffic_types", "An error occured when deleting the selected traffic type, no changes have been made.");

			return 0;
		}
		else
		{
			$sql_obj->trans_commit();

			log_write("notification", "traffic_types", "Traffic type successfully deleted");

			return 1;
		}

	} // end of action_delete



} // end of class traffic_types



/*
	CLASS traffic_caps

	Support functions for working with traffic caps - traffic caps are configured against service and traffic_types, however there
	is also a need to query override data when dealing with services.
*/
class traffic_caps
{
	var $id_service;		// service ID
	var $id_service_customer;	// customer's service ID

	var $data;		// traffic cap data
	var $data_num_rows;	// number of traffic caps


	/*
		load_data_traffic_caps

		Loads all traffic caps for the selected service into $this->data, with structure of:
		$this->data[$i]["FIELD"].

		Used by any function needing all the service cap information - of course, this function
		will only report on *active* traffic types.

		Results
		0	Failure
		1	Success
	*/

	function load_data_traffic_caps()
	{
		log_debug("traffic_caps", "Executing load_data_traffic_caps()");

		
		$obj_sql_traffic_types		= New sql_query;
		$obj_sql_traffic_types->string	= "SELECT
							traffic_types.id as id_type,
							traffic_caps.id as id_cap,
							traffic_types.type_name as type_name,
							traffic_types.type_label as type_label,
							traffic_caps.mode as cap_mode,
							traffic_caps.units_included as cap_units_included,
							traffic_caps.units_price as cap_units_price
						FROM `traffic_caps`
						LEFT JOIN traffic_types ON traffic_types.id = traffic_caps.id_traffic_type
						WHERE
							traffic_caps.id_service='". $this->id_service ."'
						ORDER BY
							traffic_types.id='1',
							traffic_types.type_name DESC";
		
		if (!$obj_sql_traffic_types->execute())
		{
			return 0;
		}

		$obj_sql_traffic_types->fetch_array();
		
		$this->data_num_rows = $obj_sql_traffic_types->num_rows();
		
		for ($i=0; $i < $obj_sql_traffic_types->data_num_rows; $i++)
		{
			foreach (array_keys($obj_sql_traffic_types->data[0]) as $field)
			{
				$this->data[$i][ $field ] = $obj_sql_traffic_types->data[$i][ $field ];
			}
		}

		unset($obj_sql_traffic_types);

		return 1;

	} // end of load_data_traffic_caps




	/*
		load_data_override_caps

		Replaces values loaded by load_data_override_caps with customer-specific override values

		Returns
		0	Failure
		1	Success
	*/

	function load_data_override_caps()
	{
		log_debug("traffic_caps", "Executing load_data_override_caps()");

		/*
			The query is somewhat complex - we need to load any option values in the format of:
			cap_mode_#
			cap_units_included_#
			cap_units_price_#

			With # being the type ID. (not the CAP ID, which can change....)

			We then take this data and replace the appropiate values in $this->data with the overriden ones,
			as well as flagging $this->data[$i]["override"]	= "yes" to allow the UI to highlight which values
			are overridden or not.
		*/

		$obj_sql_cap_overrides		= New sql_query;
		$obj_sql_cap_overrides->string	= "SELECT option_name, option_value FROM services_options WHERE option_type='customer' AND option_type_id='". $this->id_service_customer ."' AND option_name LIKE 'cap_%'";
		$obj_sql_cap_overrides->execute();

		if ($obj_sql_cap_overrides->num_rows())
		{
			$obj_sql_cap_overrides->fetch_array();

			$data_override = array();

			foreach ($obj_sql_cap_overrides->data as $data_row)
			{
				if (preg_match("/^cap_(\S*)_([0-9]*)$/", $data_row["option_name"], $matches))
				{
					$tmp = array();
					$tmp["id"]	= $matches[2];
					$tmp["field"]	= $matches[1];
					$tmp["value"]	= $data_row["option_value"];

					$data_override[ $tmp["id"] ]["id_type"]		= $tmp["id"];
					$data_override[ $tmp["id"] ][ $tmp["field"] ]	= $tmp["value"];
				}
			}

			for ($i=0; $i < $this->data_num_rows; $i++)
			{
				foreach ($data_override as $data_row)
				{
					if ($this->data[$i]["id_type"] == $data_row["id_type"])
					{
						$id = $data_row["id_type"];

						$this->data[$i]["override"]		= "yes";

						if (isset($data_row["mode"]))
						{
							$this->data[$i]["cap_mode"]		= $data_row["mode"];
						}

						if (isset($data_row["units_price"]))
						{
							$this->data[$i]["cap_units_price"]	= $data_row["units_price"];
						}

						if (isset($data_row["units_included"]))
						{
							$this->data[$i]["cap_units_included"]	= $data_row["units_included"];
						}
					}
				}
			}
		}

		unset($obj_sql_cap_overrides);
		unset($data_override);

		return 1;

	} // end of load_data_override_caps


} // end of CLASS traffic_caps





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


		Total traffic counts are stored in:
		$this->data["total"]["total"]	Total traffic of all types combined in bytes.
		$this->data["total"][ $type ]	Total of specific traffic type in bytes.

		$this->data["total_byunit"]["total"]	Total traffic of all types combined by the service units (eg total GB or MB)
		$this->data["total_byunit"]["type"]	Total of a specific traffic type by the service units (eg total GB or MB)

		Returns
		0		failure
		1		success
	*/
	function fetch_usage_traffic()
	{
		log_write("debug", "service_usage_traffic", "Executing fetch_usage_traffic()");



		/*
			Fetch data traffic types

			Note that this doesn't query overrides, since the override options will never impact which traffic types that exist,
			only how they are billed by include/services/inc_services_invoicegen.php
		*/

		$traffic_types = sql_get_singlecol("SELECT traffic_types.type_label as col FROM `traffic_caps` LEFT JOIN traffic_types ON traffic_types.id = traffic_caps.id_traffic_type WHERE traffic_caps.id_service='". $this->obj_service->id ."'");


		/*
			Fetch raw usage data from DB
		*/

		if ($GLOBALS["config"]["SERVICE_TRAFFIC_MODE"] == "internal")
		{
			/*
				Internal Database

				Use the internal database - this stores the usage information for upload/download mapped against the customer's
				IP address.

				TODO:	Currently all traffic is just assigned against type any/*, this should be upgraded to properly support
					the different traffic types.
			*/

			log_write("debug", "service_usage_traffic", "Fetching traffic records from internal database");

			// fetch upload/download stats
			$sql_obj			= New sql_query;
			$sql_obj->string		= "SELECT SUM(usage1) as usage1, SUM(usage2) as usage2 FROM service_usage_records WHERE id_service_customer='". $this->id_service_customer ."' AND date>='". $this->date_start ."' AND date<='". $this->date_end ."'";
			$sql_obj->execute();
			$sql_obj->fetch_array();

			$this->data["usage1"]	= $sql_obj->data[0]["usage1"];
			$this->data["usage2"]	= $sql_obj->data[0]["usage2"];

			unset($sql_obj);


			// create a total of both usage columns
			$sql_obj			= New sql_query;
			$sql_obj->string		= "SELECT '". $this->data["usage1"] ."' + '". $this->data["usage2"] ."' as totalusage";
			$sql_obj->execute();
			$sql_obj->fetch_array();
	
			$this->data["total"]["*"] 	= $sql_obj->data[0]["totalusage"];
			$this->data["total"]["total"] 	= $sql_obj->data[0]["totalusage"];
			
			unset($sql_obj);


			// we now have the raw usage
			log_write("debug", "service_usage_traffic", "Total raw traffic usage for ". $this->date_start ." until ". $this->date_end ." is ". $this->data["total"]["total"] ." bytes");
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

						TODO:	Currently all traffic is just assigned against type any/*, this should be upgraded to properly support
							the different traffic types.
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
					$this->data["total"]["total"] = 0;

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
									$sql_obj->string		= "SELECT '". $this->data["total"]["total"] ."' + '". $obj_traffic_db_sql->data[0]["total"] ."' as totalusage";
									$sql_obj->execute();
									$sql_obj->fetch_array();

									$this->data["total"]["total"] 	= $sql_obj->data[0]["totalusage"];

								} // end if traffic exists

							} // end if table exists/query succeeds
							else
							{
								log_write("warning", "service_usage_traffic", "SQL database table traffic_$date does not exist");
							}

						} // end foreach date

						log_write("debug", "service_usage_traffic", "Completed usage query for address $ipv4.");

					} // end foreach ipv4
					
					log_write("debug", "service_usage_traffic", "Total usage for all addresses in the date range is ". $this->data["total"]["total"] ." bytes");


					/*
						Disconnect from database
					*/
	
					$obj_traffic_db_sql->session_terminate();

					unset($obj_traffic_db_sql);


				break;



				case "mysql_traffic_summary":
					/*
						MODE: mysql_traffic_summary

						In this mode, the database contains a single table "traffic_summary" which includes the following key fields:
						* ip_address			IPv4 Address
						* traffic_datetime		Date/Time Field
						* traffic_type			Type of traffic
						* total				Total Bytes transfered

						Ideally this table should contain one row per IP address, per day, to enable billing to occur.

						TODO: update to support traffic types
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

					// blank current overall and per-type totals
					$this->data["total"]["total"]		= 0;
					$this->data["total_byunits"]["total"]	= 0;

					foreach ($traffic_types as $type)
					{
						$this->data["total"][$type]		= 0;
						$this->data["total_byunits"][$type]	= 0;
					}

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
							$obj_traffic_db_sql->string		= "SELECT SUM(total) as total, traffic_type FROM traffic_summary WHERE ip_address='$ipv4' AND traffic_datetime >= '". $this->date_start ."' AND traffic_datetime <= '". $this->date_end ."' GROUP BY traffic_type";
							$obj_traffic_db_sql->execute();

							if ($obj_traffic_db_sql->num_rows())
							{
								$obj_traffic_db_sql->fetch_array();

								foreach ($obj_traffic_db_sql->data as $data_traffic)
								{
									if (in_array($data_traffic["traffic_type"], $traffic_types))
									{
										$type = $data_traffic["traffic_type"];
									}
									else
									{
										// unmatched type, assign to any
										$type = "*";
									}
									
									// add to running total
									$sql_obj			= New sql_query;
									$sql_obj->string		= "SELECT '". $this->data["total"][ $type ] ."' + '". $data_traffic["total"] ."' as totalusage";
									$sql_obj->execute();
									$sql_obj->fetch_array();

									$this->data["total"][$type]	= $sql_obj->data[0]["totalusage"];

									unset($sql_obj);
								}

							} // end if traffic exists

						} // end if table exists/query succeeds
						else
						{
							log_write("error", "service_usage_traffic", "SQL database table traffic_summary does not exist");
						}

						log_write("debug", "service_usage_traffic", "Total usage for address $ipv4 is ". $this->data["total"] ." bytes");

					} // end foreach ipv4


					// produce overall total
					$sql_obj			= New sql_query;

					foreach ($traffic_types as $type)
					{
						$sql_obj->string		= "SELECT '". $this->data["total"]["total"] ."' + '". $this->data["total"][ $type ] ."' as totalusage";
						$sql_obj->execute();
						$sql_obj->fetch_array();

						$this->data["total"]["total"]	= $sql_obj->data[0]["totalusage"];
					}

					unset($sql_obj);
				
					log_write("debug", "service_usage_traffic", "Total usage for all addresses in the date range is ". $this->data["total"] ." bytes");


					/*
						Disconnect from database
					*/
	
					$obj_traffic_db_sql->session_terminate();

					unset($obj_traffic_db_sql);


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


		foreach (array_keys($this->data["total"]) as $type)
		{
			// calculate
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT '". $this->data["total"][ $type ] ."' / '". $this->data["numrawunits"] ."' as value";
			$sql_obj->execute();
			$sql_obj->fetch_array();

			$this->data["total_byunits"][$type]	= $sql_obj->data[0]["value"];
		}

		
		log_write("debug", "service_usage_traffic", "Total traffic usage for period is ". $this->data["total_byunits"]["total"] ."");



		/*
			Complete
		*/

		return 1;

	} // end of fetch_usage_traffic

} // end of class: service_usage_traffic




?>
