<?php
/*
	services/inc_services.php

	Provides classes for managing services
*/




/*
	CLASS: service

	Provides functions for querying and managing services.

	TODO: We are slowly moving code out of the process and forms include pages into their new
		OO class.
*/

class service
{
	var $id;		// holds service ID
	var $data;		// holds values of record fields

	var $option_type;	// option type category
	var $option_type_id;	// option_type id


	/*
		verify_id

		Checks that the provided ID is a valid service

		Results
		0	Failure to find the ID
		1	Success - service exists
	*/

	function verify_id()
	{
		log_debug("inc_services", "Executing verify_id()");

		if ($this->id)
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM `services` WHERE id='". $this->id ."' LIMIT 1";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				return 1;
			}
		}

		return 0;

	} // end of verify_id



	/*
		verify_id_options

		Checks that the provided options IDs are valid and either verifies that they
		apply to the selected service or fetches the ID for the service if it doesn't
		already exist.

		Results
		0	Failure to verify
		1	Success
	*/
	function verify_id_options()
	{
		log_write("debug", "inc_services", "Executing verify_id_options()");



		/*
			Fetch service ID from DB to verify valid options information
		*/

		if ($this->option_type == "bundle")
		{
			$obj_sql		= New sql_query;
			$obj_sql->string	= "SELECT id_service FROM services_bundles WHERE id='". $this->option_type_id ."' LIMIT 1";
			$obj_sql->execute();

			if ($obj_sql->num_rows())
			{
				$obj_sql->fetch_array();

				$service_id = $obj_sql->data[0]["id_service"];

			}
			else
			{
				log_write("error", "inc_services", "Unable to find ID $option_type_id in services_bundles");
				return 0;
			}
		}
		elseif ($this->option_type == "customer")
		{
			$obj_sql		= New sql_query;
			$obj_sql->string	= "SELECT serviceid as id_service FROM services_customers WHERE id='". $this->option_type_id ."' LIMIT 1";
			$obj_sql->execute();

			if ($obj_sql->num_rows())
			{
				$obj_sql->fetch_array();

				$service_id = $obj_sql->data[0]["id_service"];

			}
			else
			{
				log_write("error", "inc_services", "Unable to find ID $option_type_id in services_customers");
				return 0;
			}
		
		}
		else
		{
			log_write("warning", "inc_services", "No such option type $option_type");
			return 0;
		}



		/*
			Verify or select service ID
		*/

		if (!$this->id)
		{
			// no service selected, select the service that belongs to the option ID.
			$this->id = $service_id;

			return 1;
		}
		else
		{
			// verify the service ID against the currently selected service
			if ($this->id != $service_id)
			{
				log_write("error", "inc_services", "Service options returned id_service of $service_id but currently selected service is ". $this->id ."");
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
		load_data

		Load the service's information into the $this->data array.

		Returns
		0	failure
		1	success
	*/
	function load_data()
	{
		log_debug("inc_services", "Executing load_data()");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT * FROM services WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			// fetch basic service data
			$sql_obj->fetch_array();
			$this->data = $sql_obj->data[0];

			// fetch labels of various attributes
			//
			// It's probably better to use these multiple queries rather than a join, since when quering a number of services in one load, these queries
			// will be cached automatically for performance.
			//
			$this->data["typeid_string"]		= sql_get_singlevalue("SELECT name as value FROM service_types WHERE id='". $this->data["typeid"] ."' LIMIT 1");
			$this->data["billing_cycle_string"]	= sql_get_singlevalue("SELECT name as value FROM billing_cycles WHERE id='". $this->data["billing_cycle"] ."' LIMIT 1");
			$this->data["billing_mode_string"]	= sql_get_singlevalue("SELECT name as value FROM billing_modes WHERE id='". $this->data["billing_mode"] ."' LIMIT 1");
			$this->data["usage_mode_string"]	= sql_get_singlevalue("SELECT name as value FROM service_usage_modes WHERE id='". $this->data["usage_mode"] ."' LIMIT 1");
		

			// fetch additional service attributes stored in the options table
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT option_name, option_value FROM services_options WHERE option_type='service' AND option_type_id='". $this->id ."'";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				$sql_obj->fetch_array();

				foreach ($sql_obj->data as $data_options)
				{
					$this->data[ $data_options["option_name"] ] 	= $data_options["option_value"];
				}
			}

			return 1;
		}

		// failure
		return 0;

	} // end of load_data



	/*
		load_data_options

		Loads service options. If there is already data from load_data it will overwrite certain values as needed.

		Returns
		0			Failure
		1			Success
	*/
	function load_data_options()
	{
		log_write("debug", "inc_services", "Executing load_data_options()");



		/*
			If this is a customer service and belongs to a bundle, we need to load bundle options
			before we load the customer options, to ensure the service gets options from both.
		*/
		if ($this->option_type == "customer")
		{
			// check - is this a bundle service?
			$obj_component_sql		= New sql_query;
			$obj_component_sql->string	= "SELECT bundleid_component as id_bundle_component FROM `services_customers` WHERE id='". $this->option_type_id ."' LIMIT 1";
			$obj_component_sql->execute();
			$obj_component_sql->fetch_array();

			if ($obj_component_sql->data[0]["id_bundle_component"])
			{
				// load options for the bundle item (if any)
				$obj_sql		= New sql_query;
				$obj_sql->string	= "SELECT option_name, option_value FROM `services_options` WHERE option_type='bundle' AND option_type_id='". $obj_component_sql->data[0]["id_bundle_component"] ."'";
				$obj_sql->execute();

				if ($obj_sql->num_rows())
				{
					$obj_sql->fetch_array();

					foreach ($obj_sql->data as $data_options)
					{
						// add options to data array, overwriting values if they already exist
						$this->data[ $data_options["option_name"] ] = $data_options["option_value"];
					}
				}

			}
			
		}



		/*
			Load Options for the selected type of service
		*/

		$obj_sql		= New sql_query;
		$obj_sql->string	= "SELECT option_name, option_value FROM `services_options` WHERE option_type='". $this->option_type ."' AND option_type_id='". $this->option_type_id ."'";
		$obj_sql->execute();

		if ($obj_sql->num_rows())
		{
			$obj_sql->fetch_array();

			foreach ($obj_sql->data as $data_options)
			{
				// add options to data array, overwriting values if they already exist
				$this->data[ $data_options["option_name"] ] = $data_options["option_value"];
			}

			return 1;
		}

		return 0;
	}




	/*
		action_update_options

		Update the options for the particular service component. Note that you should NOT call this function
		if you have used load_data, otherwise it would results in every service value becoming an option.

		Returns
		0	failure
		#	success - returns the ID
	*/
	function action_update_options()
	{
		log_debug("inc_services", "Executing action_update_options()");


		/*
			Start Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();



		/*
			Delete the existing options
		*/

		$sql_obj->string = "DELETE FROM services_options WHERE option_type='". $this->option_type ."' AND option_type_id='". $this->option_type_id ."'";
		$sql_obj->execute();


		/*
			Add the new options, IF they are different to the standard service values - ie we don't want to turn the normal values
			into options.
		*/

		// fetch a copy of the base service and (if a customer bundle item) the bundle options data
		$data_changed	= $this->data;
		$this->data	= array();

		$this->load_data();
		$this->load_data_options();

		// compare and change
		foreach (array_keys($data_changed) as $data_option)
		{
			if ($this->data[$data_option] != $data_changed[$data_option])
			{
				$sql_obj->string = "INSERT INTO services_options (option_type, option_type_id, option_name, option_value) VALUES ('". $this->option_type ."', '". $this->option_type_id ."', '". $data_option ."', '". $data_changed[ $data_option ] ."')";
				$sql_obj->execute();
			}
		}



		/*
			Process any special options

			Some options require manipulations to additional databases
		*/

		// set a single DDI
		if ($data_changed["phone_ddi_single"])
		{
			// delete existing value (if any)
			$sql_obj->string	= "DELETE FROM services_customers_ddi WHERE id_service_customer='". $this->option_type_id ."'";
			$sql_obj->execute();

			// set new value
			$sql_obj->string	= "INSERT INTO services_customers_ddi (id_service_customer, ddi_start, ddi_finish, description) VALUES ('". $this->option_type_id ."', '". $data_changed["phone_ddi_single"] ."', '". $data_changed["phone_ddi_single"] ."', 'Automatically Created')";
			$sql_obj->execute();
		}





		/*
			Commit
		*/

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "inc_services", "An error occured when updating service options.");

			return 0;
		}
		else
		{
			$sql_obj->trans_commit();
			log_write("notification", "inc_services", "Updated service options successfully");
			
			return $this->id;
		}

	} // end of action_update



} // end of class:services





/*
	CLASS: service_bundle

	Additional functions for handling service bundles
*/
class service_bundle extends service
{

	/*
		verify_is_bundle

		Checks that the provided service ID is actually a bundle

		Results
		-1	Failure - Unable to find the service or unknown failure
		0	Failure - Not a bundle service
		1	Success - service is a bundle
	*/

	function verify_is_bundle()
	{
		log_debug("inc_services", "Executing verify_is_bundle()");

		if ($this->id)
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT services.id, service_types.name as service_type FROM services LEFT JOIN service_types ON service_types.id = services.typeid WHERE services.id='". $this->id ."' LIMIT 1";
			$sql_obj->execute();

			if (!$sql_obj->num_rows())
			{
				return -1;
			}
			else
			{
				$sql_obj->fetch_array();

				if ($sql_obj->data[0]["service_type"] == "bundle")
				{
					return 1;
				}
				else
				{
					return 0;
				}
			}
		}

		return -1;

	} // end of verify_is_bundle



	/*
		bundle_service_create

		Fields
		id_service	ID of service to add.

		Returns
		0	Failure
		#	ID of service-bundle maping

		Adds the provided service to the bundle.
	*/
	function bundle_service_create($id_service)
	{
		log_debug("debug", "inc_services", "Executing bundle_services_create($id_service)");


		/*
			Begin Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();

		
		/*
			Apply Changes
		*/

		$sql_obj->string = "INSERT INTO `services_bundles` (id_bundle, id_service) VALUES ('". $this->id ."', '$id_service')";
		$sql_obj->execute();


		/*
			Update the Journal
		*/
		journal_quickadd_event("services", $this->id, "New service component added to bundle.");



		/*
			Commit
		*/
		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "process", "An error occured whilst attempting to add service to the bundle. No changes have been made.");
		}
		else
		{
			$sql_obj->trans_commit();

			log_write("notification", "process", "Service successfully added to bundle.");
		}


		return 0;
	}


	/*
		bundle_service_list

		Returns an array containing the IDs of all the service components belonging to a bundle.

		Returns
		0		Failure
		array		Array of bundle component IDs.
	*/
	function bundle_service_list()
	{
		log_write("debug", "service_bundle", "Executing bundle_service_list()");
	

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM services_bundles WHERE id_bundle='". $this->id ."'";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$sql_obj->fetch_array();

			$result = array();

			foreach ($sql_obj->data as $data)
			{
				$result[] = $data["id"];
			}

			return $result;
		}
		else
		{
			log_write("warning", "service_bundle", "There are no component services in bundle ". $this->id ."");
			return 0;
		}
	}




	/*
		bundle_service_delete()

		Deletes/removes a service from a bundle.

		Fields
		id_service

		Returns
		0		Unexpected Failure
		1		Success
	*/
	function bundle_service_delete($id_service)
	{
		log_write("debug", "inc_services", "Executing bundle_service_delete($id_service))");


		/*
			Begin Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();

		
		/*
			Apply Changes
		*/

		$sql_obj->string = "SELECT id FROM `services_bundles` WHERE id_bundle='". $this->id ."' AND id_service='$id_service' LIMIT 1";
		$sql_obj->execute();
		$sql_obj->fetch_array();

		$option_id = $sql_obj->data[0]["id"];



		$sql_obj->string = "DELETE FROM `services_bundles` WHERE id='$option_id' LIMIT 1";
		$sql_obj->execute();

		$sql_obj->string = "DELETE FROM `services_options` WHERE option_type='bundle' AND option_type_id='$option_id'";
		$sql_obj->execute();


		/*
			Update the Journal
		*/
		journal_quickadd_event("services", $this->id, "Service component removed from bundle.");



		/*
			Commit
		*/
		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "process", "An error occured whilst attempting to remove a service from the bundle. No changes have been made.");
		}
		else
		{
			$sql_obj->trans_commit();

			log_write("notification", "process", "Service successfully removed from bundle.");
		}


		return 0;
	}


} // end of class: service_bundle





/*
	CLASS: service_usage

	Base class providing general functions for fetching service usage information.

	This class is not used directly, but is rather extended by other usage calculating functions
	to avoid repeating the design of the same basic information each time.
*/

class service_usage
{
	var $id_service_customer;			// ID of the service-customer mapping

	var $obj_service;				// holds service data

	var $date_start;				// start of usage period
	var $date_end;					// end of usage period

	var $data;					// usage information is saved here.


	/*
		Constructor
	*/
	function service_usage()
	{
		log_write("debug", "service_usage", "Executing service_usage()");

		$this->obj_service = New service;
	}



	/*
		load_data_service

		Loads the service object and it's associated data

		Results
		0	Failure
		1	Success
	*/
	function load_data_service()
	{
		log_write("debug", "service_usage", "Executing load_data_service()");


		$this->obj_service->option_type		= "customer";
		$this->obj_service->option_type_id	= $this->id_service_customer;

		if (!$this->obj_service->verify_id_options())
		{
			log_write("error", "service_usage", "Unable to verify service ID of ". $this->id_service_customer ." as being valid, no changes have been made to service configuration.");
			return 0;
		}
		
		$this->obj_service->load_data();
		$this->obj_service->load_data_options();

		return 1;
	}
	

} // end of class: service_usage



?>
