<?php
/*
	include/services/inc_service_cdr.php

	Provides various functions and classes for handling CDR billing and the
	configuration of CDR items.
*/



/*
	CLASS cdr_rate_table

	Functions for querying and managing CDR rate tables.
*/
class cdr_rate_table
{
	var $id;		// rate table ID
	var $data;		// rate table data



	/*
		verify_id

		Check that the supplied rate table ID is valid.

		Results
		0	Failure to find the ID
		1	Success - service exists
	*/

	function verify_id()
	{
		log_debug("cdr_rate_table", "Executing verify_id()");

		if ($this->id)
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM `cdr_rate_tables` WHERE id='". $this->id ."' LIMIT 1";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				return 1;
			}
		}

		return 0;

	} // end of verify_id

	

	/*
		verify_rate_table_name

		Verify that the supplied rate table name has not been taken.

		Results
		0	Failure - name in use
		1	Success - name is available
	*/

	function verify_rate_table_name()
	{
		log_write("debug", "cdr_rate_table", "Executing verify_rate_table_name()");

		$sql_obj			= New sql_query;
		$sql_obj->string		= "SELECT id FROM `cdr_rate_tables` WHERE rate_table_name='". $this->data["rate_table_name"] ."' ";

		if ($this->id)
			$sql_obj->string	.= " AND id!='". $this->id ."'";

		$sql_obj->string		.= " LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			return 0;
		}
		
		return 1;

	} // end of verify_rate_table_name


	/*
		check_delete_lock

		Checks if the customer is safe to delete or not

		Results
		0	Unlocked
		1	Locked
	*/

	function check_delete_lock()
	{
		log_debug("inc_customers", "Executing check_delete_lock()");


		// makes sure not in use by any services
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM services WHERE id_rate_table='". $this->id ."' LIMIT 1";
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

		Load the rate table into the $this->data array.

		Returns
		0	failure
		1	success
	*/
	function load_data()
	{
		log_debug("cdr_rate_table", "Executing load_data()");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id_vendor, id_usage_mode, rate_table_name, rate_table_description FROM cdr_rate_tables WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			// fetch basic service data
			$sql_obj->fetch_array();

			$this->data["id_vendor"]		= $sql_obj->data[0]["id_vendor"];
			$this->data["id_usage_mode"]		= $sql_obj->data[0]["id_usage_mode"];
			$this->data["rate_table_name"]		= $sql_obj->data[0]["rate_table_name"];
			$this->data["rate_table_description"]	= $sql_obj->data[0]["rate_table_description"];

			return 1;
		}

		// failure
		return 0;

	} // end of load_data



	/*
		action_create
	
		Create a rate table based on the data in $this->data

		Results
		0	Failure
		#	Success - return ID
	*/
	function action_create()
	{
		log_write("debug", "cdr_rate_table", "Executing action_create()");


		/*
			Start Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();


		/*
			Create CDR Rate Table
		*/
		$sql_obj->string	= "INSERT INTO `cdr_rate_tables` (rate_table_name) VALUES ('". $this->data["rate_table_name"]. "')";
		$sql_obj->execute();

		$this->id = $sql_obj->fetch_insert_id();



		/*
			Create Default Rate Item
		*/

		$sql_obj->string	= "INSERT INTO `cdr_rate_tables_values` (id_rate_table, rate_prefix) VALUES ('". $this->id ."', 'DEFAULT')";
		$sql_obj->execute();



		/*
			Commit
		*/

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "cdr_rate_table", "An error occured when attemping to create a new rate table.");

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

		Update the details for the selected rate table based on the data in $this->data. If no ID is provided,
		it will first call the action_create function to add a new rate table.

		Returns
		0	failure
		#	success - returns the ID
	*/
	function action_update()
	{
		log_write("debug", "cdr_rate_table", "Executing action_update()");

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

		$sql_obj->string	= "UPDATE `cdr_rate_tables` SET "
						."id_vendor='". $this->data["id_vendor"] ."', "
						."id_usage_mode='". $this->data["id_usage_mode"] ."', "
						."rate_table_name='". $this->data["rate_table_name"] ."', "
						."rate_table_description='". $this->data["rate_table_description"] ."' "
						."WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		

		/*
			Commit
		*/

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "cdr_rate_table", "An error occured when updating rate table details.");

			return 0;
		}
		else
		{
			$sql_obj->trans_commit();

			if ($mode == "update")
			{
				log_write("notification", "cdr_rate_table", "Rate table details successfully updated.");
			}
			else
			{
				log_write("notification", "cdr_rate_table", "Rate table successfully created.");
			}
			
			return $this->id;
		}

	} // end of action_update



	/*
		action_delete

		Deletes the selected rate table.

		Results
		0	Failure
		1	Success
	*/
	function action_delete()
	{
		log_write("debug", "cdr_rate_table", "Executing action_delete()");


		/*
			Start Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();


		/*
			delete rate table
		*/

		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM `cdr_rate_tables` WHERE id='". $this->id ."'";
		$sql_obj->execute();


		/*
			delete rate table items
		*/

		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM `cdr_rate_tables_values` WHERE id_rate_table='". $this->id ."'";
		$sql_obj->execute();



		/*
			Commit
		*/

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "cdr_rate_table", "An error occured when deleting the selected rate table, no changes have been made.");

			return 0;
		}
		else
		{
			$sql_obj->trans_commit();

			log_write("notification", "cdr_rate_table", "Rate table details successfully deleted.");

			return 1;
		}

	} // end of action_delete



} // end of class cdr_rate_table	




/*
	CLASS cdr_rate_table_rates

	Functions for querying and managing rates inside of a rate table.
*/
class cdr_rate_table_rates extends cdr_rate_table
{
	var $data_rate;		// data for a single rate to manipulate
	var $id_rate;		// ID of a single rate to manipulate



	/*
		verify_id_rate

		Check that the supplied table rate item id is valid ($this->id_rate) and also
		verifies that it belongs to the selected rate table, or selects it if it is not.

		Results
		0	Failure to find the ID
		1	Success - service exists
	*/

	function verify_id_rate()
	{
		log_debug("cdr_rate_table_rates", "Executing verify_id_rate()");

		if ($this->id_rate)
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id, id_rate_table FROM `cdr_rate_tables_values` WHERE id='". $this->id_rate ."' LIMIT 1";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				$sql_obj->fetch_array();


				if ($this->id)
				{
					if ($sql_obj->data[0]["id_rate_table"] == $this->id)
					{
						return 1;
					}
					else
					{
						log_write("error", "cdr_rate_table_rates", "The selected rate (". $this->id_rate .") does not match the selected rate table (". $this->id .")");
						return 0;
					}
				}
				else
				{
					$this->id = $sql_obj->data[0]["id_rate_table"];

					return 1;
				}

			}
		}

		return 0;

	} // end of verify_id_rate



	/*
		verify_rate_prefix

		Verify that the supplied rate prefix is not already in use.
		
		Results
		0	Failure - prefix in use
		1	Success - prefix is available
	*/

	function verify_rate_prefix()
	{
		log_write("debug", "cdr_rate_table", "Executing verify_rate_prefix()");

		$sql_obj			= New sql_query;
		$sql_obj->string		= "SELECT id FROM `cdr_rate_tables_values` WHERE rate_prefix='". $this->data_rate["rate_prefix"] ."' ";

		if ($this->id_rate)
			$sql_obj->string	.= " AND id!='". $this->id_rate ."'";

		$sql_obj->string		.= " LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			return 0;
		}
		
		return 1;

	} // end of verify_rate_prefix





	/*
		load_data_rate_all

		Load the rate table into the $this->data["rates"] array.

		Returns
		0	failure
		1	success
	*/
	function load_data_rate_all()
	{
		log_debug("cdr_rate_table", "Executing load_data_rate_all()");


		// fetch rates
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id, rate_prefix, rate_description, rate_price_sale, rate_price_cost FROM cdr_rate_tables_values WHERE id_rate_table='". $this->id ."'";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$sql_obj->fetch_array();

			foreach ($sql_obj->data as $data_rates)
			{
				$this->data["rates"][ $data_rates["rate_prefix"] ]["id_rate"]		= $data_rates["id"];
				$this->data["rates"][ $data_rates["rate_prefix"] ]["rate_prefix"]	= $data_rates["rate_prefix"];
				$this->data["rates"][ $data_rates["rate_prefix"] ]["rate_description"]	= $data_rates["rate_description"];
				$this->data["rates"][ $data_rates["rate_prefix"] ]["rate_price_sale"]	= $data_rates["rate_price_sale"];
				$this->data["rates"][ $data_rates["rate_prefix"] ]["rate_price_cost"]	= $data_rates["rate_price_cost"];

			}

			return 1;
		}

		// failure
		return 0;

	} // end of load_data_rate_all



	/*
		load_data_rate

		Load a single data rate value into $this->data_rate

		Returns
		0	failure
		1	success
	*/
	function load_data_rate()
	{
		log_debug("cdr_rate_table", "Executing load_data_rate()");


		// fetch rates
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id, rate_prefix, rate_description, rate_price_sale, rate_price_cost FROM cdr_rate_tables_values WHERE id_rate_table='". $this->id ."' AND id='". $this->id_rate ."' LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$sql_obj->fetch_array();

			$this->data_rate["rate_prefix"]		= $sql_obj->data[0]["rate_prefix"];
			$this->data_rate["rate_description"]	= $sql_obj->data[0]["rate_description"];
			$this->data_rate["rate_price_sale"]	= $sql_obj->data[0]["rate_price_sale"];
			$this->data_rate["rate_price_cost"]	= $sql_obj->data[0]["rate_price_cost"];

			return 1;
		}

		// failure
		return 0;

	} // end of load_data_rates

	

	/*
		action_rate_create

		Create a new rate item for the selected rate table.

		Results
		0	Failure
		#	Success - return ID
	*/
	function action_rate_create()
	{
		log_write("debug", "cdr_rate_table_rates", "Executing action_rate_create()");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "INSERT INTO `cdr_rate_tables_values` (id_rate_table, rate_prefix) VALUES ('". $this->id ."', '". $this->data_rate["rate_prefix"]. "')";
		$sql_obj->execute();

		$this->id_rate = $sql_obj->fetch_insert_id();

		return $this->id_rate;

	} // end of action_rate_create




	/*
		action_rate_update

		Update the details for the selected rate based on the data in $this->data_rate. If no ID is provided,
		it will first call the action_rate_create function to add a new rate.

		Returns
		0	failure
		#	success - returns the ID
	*/
	function action_rate_update()
	{
		log_write("debug", "cdr_rate_table_rates", "Executing action_rate_update()");

		/*
			Start Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();



		/*
			If no ID supplied, create a new rate first
		*/
		if (!$this->id_rate)
		{
			$mode = "create";

			if (!$this->action_rate_create())
			{
				return 0;
			}
		}
		else
		{
			$mode = "update";
		}



		/*
			Update Rate Details
		*/

		$sql_obj->string	= "UPDATE `cdr_rate_tables_values` SET "
						."rate_prefix='". $this->data_rate["rate_prefix"] ."', "
						."rate_description='". $this->data_rate["rate_description"] ."', "
						."rate_price_sale='". $this->data_rate["rate_price_sale"] ."', "
						."rate_price_cost='". $this->data_rate["rate_price_cost"] ."' "
						."WHERE id='". $this->id_rate ."' LIMIT 1";
		$sql_obj->execute();

		

		/*
			Commit
		*/

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "cdr_rate_table", "An error occured when updating rate item details.");

			return 0;
		}
		else
		{
			$sql_obj->trans_commit();

			if ($mode == "update")
			{
				log_write("notification", "cdr_rate_table_rates", "Rate item successfully updated.");
			}
			else
			{
				log_write("notification", "cdr_rate_table_rates", "Rate item successfully created.");
			}
			
			return $this->id_rate;
		}

	} // end of action_rate_update



	/*
		action_rate_delete

		Deletes the selected rate from it's rate table.

		Results
		0	Failure
		1	Success
	*/
	function action_rate_delete()
	{
		log_write("debug", "cdr_rate_table_rates", "Executing action_rate_delete()");

		if ($this->data_rate["rate_prefix"] == "DEFAULT")
		{
			log_write("error", "cdr_rate_table_rates", "Unable to delete the DEFAULT prefix, this is required incase calls don't match any other prefix");
			return 0;
		}

		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM `cdr_rate_tables_values` WHERE id='". $this->id_rate ."' LIMIT 1";
	
		if ($sql_obj->execute())
		{
			log_write("notification", "cdr_rate_table_rates", "Requested rate item has been deleted.");
			return 1;
		}
		else
		{
			log_write("error", "cdr_rate_table_rates", "An error occured whilst attempting to delete the requested rate item (". $this->id_rate .") for rate table (". $this->id .")");
			return 0;
		}

	} // end of action_rate_delete


} // end of cdr_rate_table_rates





/*
	CLASS cdr_rate_table_rates_override

	Functions for querying and retreving override for CDR rates.
*/
class cdr_rate_table_rates_override extends cdr_rate_table_rates
{
	var $option_type;		// option type category ("customer" or "service")
	var $option_type_id;		// option_type id
	var $option_type_serviceid;	// option service id (used by customer overrides)

	var $id_rate_override;	// id of a particular rate to manipulate


	/*
		verify_id_override

		Verify the supplied options IDs in order to fetch the override
		data.

		Results
		0	Failure to verify
		1	Success
	*/
	function verify_id_override()
	{
		log_write("debug", "cdr_rate_table", "Executing verify_id_override()");



		if ($this->option_type == "service")
		{
			/*
				Verify and return rate table ID
			*/
			$obj_sql		= New sql_query;
			$obj_sql->string	= "SELECT id, id_rate_table as id_service FROM services WHERE id='". $this->option_type_id ."' LIMIT 1";
			$obj_sql->execute();

			if ($obj_sql->num_rows())
			{
				$obj_sql->fetch_array();

				$id_rate_table = $obj_sql->data[0]["id_rate_table"];

			}
			else
			{
				log_write("error", "cdr_rate_table", "Unable to find ID $option_type_id in services");
				return 0;
			}
		}
		elseif ($this->option_type == "customer")
		{
			/*
				verify customer-services ID by fetching rate table ID for the service.
			*/


			// fetch service ID
			$obj_sql		= New sql_query;
			$obj_sql->string	= "SELECT serviceid as id_service FROM services_customers WHERE id='". $this->option_type_id ."' LIMIT 1";
			$obj_sql->execute();

			if ($obj_sql->num_rows())
			{
				$obj_sql->fetch_array();

				$this->option_type_serviceid = $obj_sql->data[0]["id_service"];
			}
			else
			{
				log_write("error", "cdr_rate_table", "Unable to find service with ID of $option_type_id in services_customers");
				return 0;
			}


			// fetch service rate table ID
			$obj_sql		= New sql_query;
			$obj_sql->string	= "SELECT id, id_rate_table FROM services WHERE id='". $this->option_type_serviceid ."' LIMIT 1";
			$obj_sql->execute();

			if ($obj_sql->num_rows())
			{
				$obj_sql->fetch_array();

				$id_rate_table = $obj_sql->data[0]["id_rate_table"];
			}
			else
			{
				log_write("error", "cdr_rate_table", "Unable to find ID $option_type_id in services - perhaps service is not a phone service?");
				return 0;
			}
	
		}
		else
		{
			log_write("warning", "cdr_rate_table", "No such option type $option_type");
			return 0;
		}



		/*
			Verify or select rate table ID
		*/

		if (!$this->id)
		{
			// no rate table selected, select the one that belongs to the service ID
			$this->id = $id_rate_table;

			log_write("debug", "cdr_rate_table", "Selecting rate table ID \"". $this->id ."\"");

			return 1;
		}
		else
		{
			// verify the service ID against the currently selected rate_table
			if ($this->id != $id_rate_table)
			{
				log_write("error", "cdr_rate_table", "Service options returned id_rate_table of $id_rate_table but currently selected rate table is ". $this->id ."");
				return 0;
			}
			else
			{
				// valid match
				return 1;
			}
			
		}

		return 0;
	}



	/*
		verify_id_rate_override

		Returns
		0	Failure - ID not found
		1	Success
	*/

	function verify_id_rate_override()
	{
		log_debug("cdr_rate_table_rates", "Executing verify_id_rate_override()");

		if ($this->id_rate_override)
		{
			// TODO: verify option_type and option_type_id here?

			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM `cdr_rate_tables_overrides` WHERE id='". $this->id_rate_override ."' AND option_type='". $this->option_type ."' AND option_type_id='". $this->option_type_id ."' LIMIT 1";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				return 1;

			}
		}

		return 0;

	} // end of verify_id_rate_override



	/*
		verify_rate_prefix_override

		Verify that the supplied rate prefix is not already in use in the override table
		
		Results
		0	Failure - prefix in use
		1	Success - prefix is available
	*/

	function verify_rate_prefix_override()
	{
		log_write("debug", "cdr_rate_table", "Executing verify_rate_prefix_override()");

		$sql_obj			= New sql_query;
		$sql_obj->string		= "SELECT id FROM `cdr_rate_tables_overrides` WHERE rate_prefix='". $this->data_rate["rate_prefix"] ."' AND option_type='". $this->option_type ."' AND option_type_id='". $this->option_type_id ."'";

		if ($this->id_rate_override)
			$sql_obj->string	.= " AND id!='". $this->id_rate_override ."'";

		$sql_obj->string		.= " LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			return 0;
		}
		
		return 1;

	} // end of verify_rate_prefix_override





	/*
		load_data_rate_all_override

		Load the rate table into the $this->data["rates"] array. If you want to get the full rate table including the non-overridden values
		then do a load_data_rate_all followed by load_data_rate_all_override

		Returns
		0	failure
		1	success
	*/
	function load_data_rate_all_override()
	{
		log_debug("cdr_rate_table", "Executing load_data_rate_all_overrride()");


		/*
			If this is a customer override, we first need to load service-level overrides to ensure
			that the customer gets overrides from both services and customers.
		*/
		if ($this->option_type == "customer")
		{
			// fetch rates
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id, rate_prefix, rate_description, rate_price_sale FROM cdr_rate_tables_overrides WHERE option_type='service' AND option_type_id='". $this->option_type_serviceid ."'";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				$sql_obj->fetch_array();

				foreach ($sql_obj->data as $data_rates)
				{
					$this->data["rates"][ $data_rates["rate_prefix"] ]["id_rate_override"]	= $data_rates["id"];
					$this->data["rates"][ $data_rates["rate_prefix"] ]["rate_prefix"]	= $data_rates["rate_prefix"];
					$this->data["rates"][ $data_rates["rate_prefix"] ]["rate_description"]	= $data_rates["rate_description"];
					$this->data["rates"][ $data_rates["rate_prefix"] ]["rate_price_sale"]	= $data_rates["rate_price_sale"];
					$this->data["rates"][ $data_rates["rate_prefix"] ]["option_type"]	= 'service';
				}
			}
		}


		// fetch rates
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id, rate_prefix, rate_description, rate_price_sale FROM cdr_rate_tables_overrides WHERE option_type='". $this->option_type ."' AND option_type_id='". $this->option_type_id ."'";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$sql_obj->fetch_array();

			foreach ($sql_obj->data as $data_rates)
			{
				$this->data["rates"][ $data_rates["rate_prefix"] ]["id_rate_override"]	= $data_rates["id"];
				$this->data["rates"][ $data_rates["rate_prefix"] ]["rate_prefix"]	= $data_rates["rate_prefix"];
				$this->data["rates"][ $data_rates["rate_prefix"] ]["rate_description"]	= $data_rates["rate_description"];
				$this->data["rates"][ $data_rates["rate_prefix"] ]["rate_price_sale"]	= $data_rates["rate_price_sale"];
				$this->data["rates"][ $data_rates["rate_prefix"] ]["option_type"]	= $this->option_type;
			}

			return 1;
		}

		// failure
		return 0;

	} // end of load_data_rate_all_override



	/*
		load_data_rate_override

		Load a single data rate value into $this->data_rate

		Returns
		0	failure
		1	success
	*/
	function load_data_rate_override()
	{
		log_debug("cdr_rate_table", "Executing load_data_rate_override()");


		// fetch rates
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id, rate_prefix, rate_description, rate_price_sale FROM cdr_rate_tables_overrides WHERE id='". $this->id_rate_override ."' LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$sql_obj->fetch_array();

			$this->data_rate["rate_prefix"]		= $sql_obj->data[0]["rate_prefix"];
			$this->data_rate["rate_description"]	= $sql_obj->data[0]["rate_description"];
			$this->data_rate["rate_price_sale"]	= $sql_obj->data[0]["rate_price_sale"];

			return 1;
		}

		// failure
		return 0;

	} // end of load_data_rate_override

	

	/*
		action_rate_create_override

		Create a new data rate override

		Results
		0	Failure
		#	Success - return ID
	*/
	function action_rate_create_override()
	{
		log_write("debug", "cdr_rate_table_rates", "Executing action_rate_create_override()");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "INSERT INTO `cdr_rate_tables_overrides` (option_type, option_type_id, rate_prefix) VALUES ('". $this->option_type ."', '". $this->option_type_id ."', '". $this->data_rate["rate_prefix"]. "')";
		$sql_obj->execute();

		$this->id_rate_override = $sql_obj->fetch_insert_id();

		return $this->id_rate_override;

	} // end of action_rate_create_override




	/*
		action_rate_update_override

		Update the details for the selected rate based on the data in $this->data_rate. If no ID is provided,
		it will first call the action_rate_create function to add a new rate.

		Returns
		0	failure
		#	success - returns the ID
	*/
	function action_rate_update_override()
	{
		log_write("debug", "cdr_rate_table_rates", "Executing action_rate_update_override()");

		/*
			Start Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();



		/*
			If no ID supplied, create a new rate first
		*/
		if (!$this->id_rate_override)
		{
			$mode = "create";

			if (!$this->action_rate_create_override())
			{
				return 0;
			}
		}
		else
		{
			$mode = "update";
		}



		/*
			Update Rate Details
		*/

		$sql_obj->string	= "UPDATE `cdr_rate_tables_overrides` SET "
						."rate_prefix='". $this->data_rate["rate_prefix"] ."', "
						."rate_description='". $this->data_rate["rate_description"] ."', "
						."rate_price_sale='". $this->data_rate["rate_price_sale"] ."' "
						."WHERE id='". $this->id_rate_override ."' LIMIT 1";
		$sql_obj->execute();

		

		/*
			Commit
		*/

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "cdr_rate_table", "An error occured when updating rate override details.");

			return 0;
		}
		else
		{
			$sql_obj->trans_commit();

			if ($mode == "update")
			{
				log_write("notification", "cdr_rate_table_rates", "Rate override successfully updated.");
			}
			else
			{
				log_write("notification", "cdr_rate_table_rates", "Rate override successfully created.");
			}
			
			return $this->id_rate;
		}

	} // end of action_rate_update_override



	/*
		action_rate_delete_override

		Deletes the selected rate from it's rate table.

		Results
		0	Failure
		1	Success
	*/
	function action_rate_delete_override()
	{
		log_write("debug", "cdr_rate_table_rates", "Executing action_rate_delete_override()");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM `cdr_rate_tables_overrides` WHERE id='". $this->id_rate_override ."' LIMIT 1";
	
		if ($sql_obj->execute())
		{
			log_write("notification", "cdr_rate_table_rates", "Requested rate override has been deleted.");
			return 1;
		}
		else
		{
			log_write("error", "cdr_rate_table_rates", "An error occured whilst attempting to delete the requested rate override (". $this->id_rate_override .").");
			return 0;
		}

	} // end of action_rate_delete_override



} // end of cdr_rate_table_rates_override








/*
	CLASS: cdr_usage

	Functions for querying and calculating call costs for service invoicing.
*/
class cdr_usage
{
	/*
		load_service_information

		Load the provided server information and overrides to fetch the pricing for all the call services.
	*/




	/*
		fetch_usage_data

		Return an array of call items along with their calculated prices from the CDR records
		table for the selected service and period.
	*/

} // end of class cdr_usage




/*
	CLASS: cdr_customer_service_ddi

	Functions for managing DDI values for customers as well as being able to query
	a specific DDI to discover what customer it belongs to.
*/

class cdr_customer_service_ddi
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
		log_debug("cdr_customer_services_ddi", "Executing verify_id_service_ddi()");

		if ($this->id)
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id_service_customer, services_customers.customerid as id_customer FROM `services_customers_ddi` LEFT JOIN services_customers ON services_customers.id = services_customers_ddi.id_service_customer WHERE services_customers_ddi.id='". $this->id ."' LIMIT 1";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				$sql_obj->fetch_array();


				// verify id_service_customer
				if ($this->id_service_customer)
				{
					if ($sql_obj->data[0]["id_service_customer"] == $this->id_service_customer)
					{
						log_write("debug", "customers_services_ddi", "The selected service-customer matches the DDI");
					}
					else
					{
						log_write("error", "customers_services_ddi", "The seleced service-customer (". $this->id_service_customer .") does not match the selected customer (". $this->id .").");
						return 0;
					}
				}
				else
				{
					$this->id_service_customer = $sql_obj->data[0]["id_service_customer"];

					log_write("debug", "customers_services_ddi", "Setting id_service_customer to ". $this->id_service_customer ."");
				}


				// verify customer ID
				if ($this->id_customer)
				{
					if ($sql_obj->data[0]["id_customer"] == $this->id_customer)
					{
						log_write("debug", "customers_services_ddi", "The selected DDI belongs to the correct customer and service-customer mapping");
						return 1;
					}
					else
					{
						log_write("error", "customers_services_ddi", "The seelcted DDI does not belong to the selected customer ". $this->id ."");
						return 0;
					}

				}
				else
				{
					$this->id_customer = $sql_obj->data[0]["id_customer"];

					log_write("error", "Setting id_customer to ". $this->id ."");
					return 1;
				}

			}
		}

		return 0;

	} // end of verify_id_service_ddi



	/*
		verify_unique_ddi

		Verifies that the supplied DDI information is not already used by any other DDI

		Results
		0	Failure - ddi is assigned to another customer
		1	Success - ddi is available
	*/

	function verify_unique_ddi()
	{
		log_debug("cdr_customer_services_ddi", "Executing verify_unique_ddi()");
/*
		TODO: write me

		$sql_obj			= New sql_query;
		$sql_obj->string		= "SELECT id FROM `services_customers_ddi` WHERE r='". $this->data["code_customer"] ."' ";

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

	} // end of verify_unique_ddi



	/*
		load_data

		Load the DDI data

		Results
		0	Failure
		1	Success
	*/
	function load_data()
	{
		log_write("debug", "cdr_customer_services_ddi", "Executing load_data_ddi()");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT ddi_start, ddi_finish, description FROM services_customers_ddi WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$sql_obj->fetch_array();

			$this->data = $sql_obj->data[0];

			return 1;
		}

		return 0;

	} // end of load_data_ddi
	


	/*
		action_create

		Create a new DDI record based on the data in $this->data

		Results
		0	Failure
		#	Success - return ID
	*/
	function action_create()
	{
		log_debug("cdr_customer_services_ddi", "Executing action_create()");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "INSERT INTO `services_customers_ddi` (id_service_customer) VALUES ('". $this->id_service_customer . "')";
		$sql_obj->execute();

		$this->id = $sql_obj->fetch_insert_id();

		return $this->id;

	} // end of action_create




	/*
		action_update

		Updates DDI action

		Returns
		0	failure
		#	success - returns the ID
	*/
	function action_update()
	{
		log_debug("cdr_customer_services_ddi", "Executing action_update()");

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
			Update DDI value
		*/

		$sql_obj->string	= "UPDATE `services_customers_ddi` SET "
						."ddi_start='". $this->data["ddi_start"] ."', "
						."ddi_finish='". $this->data["ddi_finish"] ."', "
						."description='". $this->data["description"] ."' "
						."WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		
		/*
			Commit
		*/

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "cdr_customers_services_ddi", "An error occured when updating customer DDI records.");

			return 0;
		}
		else
		{
			$sql_obj->trans_commit();

			if ($mode == "update")
			{
				log_write("notification", "cdr_customers_services_ddi", "Customer DDI records successfully updated.");
			}
			else
			{
				log_write("notification", "cdr_customers_service_ddi", "Customer DDI records successfully created.");
			}
			
			return $this->id;
		}

	} // end of action_update



	/*
		action_delete

		Deletes a DDI

		Results
		0	failure
		1	success
	*/
	function action_delete()
	{
		log_debug("cdr_customers_services_ddi", "Executing action_delete()");


		/*
			Start Transaction
		*/

		$sql_obj = New sql_query;
		$sql_obj->trans_begin();


		/*
			Delete DDI
		*/
			
		$sql_obj->string	= "DELETE FROM services_customers_ddi WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();


		/*
			Commit
		*/
		
		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "cdr_customers_services_ddi", "An error occured whilst trying to delete the DDI.");

			return 0;
		}
		else
		{
			$sql_obj->trans_commit();

			log_write("notification", "cdr_customers_services_ddi", "DDI has been successfully deleted.");

			return 1;
		}
	}


} // end of class: customer_services_ddi




?>
