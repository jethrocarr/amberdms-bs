<?php
/*
	admin/config_services-process.php
	
	Access: admin only
	
	Options and configuration for service billing and other options.
*/


// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");


if (user_permissions_get("admin"))
{
	/*
		Load Data
	*/

	$data["SERVICE_CDR_LOCAL"]				= @security_form_input_predefined("any", "SERVICE_CDR_LOCAL", 1, "");
	$data["SERVICE_PARTPERIOD_MODE"]			= @security_form_input_predefined("any", "SERVICE_PARTPERIOD_MODE", 1, "");
	$data["SERVICE_MIGRATION_MODE"]				= @security_form_input_predefined("checkbox", "SERVICE_MIGRATION_MODE", 0, "");

	if ($GLOBALS["config"]["dangerous_conf_options"] == "enabled")
	{
		$data["SERVICE_TRAFFIC_MODE"]			= @security_form_input_predefined("any", "SERVICE_TRAFFIC_MODE", 1, "");
		$data["SERVICE_CDR_MODE"]			= @security_form_input_predefined("any", "SERVICE_CDR_MODE", 1, "");

		if ($data["SERVICE_TRAFFIC_MODE"] == "external")
		{
			$data["SERVICE_TRAFFIC_DB_TYPE"]	= @security_form_input_predefined("any", "SERVICE_TRAFFIC_DB_TYPE", 1, "");
			$data["SERVICE_TRAFFIC_DB_HOST"]	= @security_form_input_predefined("any", "SERVICE_TRAFFIC_DB_HOST", 0, "");
			$data["SERVICE_TRAFFIC_DB_NAME"]	= @security_form_input_predefined("any", "SERVICE_TRAFFIC_DB_NAME", 1, "");
			$data["SERVICE_TRAFFIC_DB_USERNAME"]	= @security_form_input_predefined("any", "SERVICE_TRAFFIC_DB_USERNAME", 1, "");
			$data["SERVICE_TRAFFIC_DB_PASSWORD"]	= @security_form_input_predefined("any", "SERVICE_TRAFFIC_DB_PASSWORD", 0, "");
		}

		if ($data["SERVICE_CDR_MODE"] == "external")
		{
			$data["SERVICE_CDR_DB_TYPE"]		= @security_form_input_predefined("any", "SERVICE_CDR_DB_TYPE", 1, "");
			$data["SERVICE_CDR_DB_HOST"]		= @security_form_input_predefined("any", "SERVICE_CDR_DB_HOST", 0, "");
			$data["SERVICE_CDR_DB_NAME"]		= @security_form_input_predefined("any", "SERVICE_CDR_DB_NAME", 1, "");
			$data["SERVICE_CDR_DB_USERNAME"]	= @security_form_input_predefined("any", "SERVICE_CDR_DB_USERNAME", 1, "");
			$data["SERVICE_CDR_DB_PASSWORD"]	= @security_form_input_predefined("any", "SERVICE_CDR_DB_PASSWORD", 0, "");
		}
	}


	/*
		Service Usage Unit Options
	*/
	$data_units		= array();

	$obj_sql		= New sql_query;
	$obj_sql->string	= "SELECT id, name FROM service_units ORDER BY typeid, name";
	$obj_sql->execute();

	if ($obj_sql->num_rows())
	{
		$obj_sql->fetch_array();

		foreach ($obj_sql->data as $data_row)
		{
			$data_units[ $data_row["id"] ]			= @security_form_input_predefined("checkbox", "service_unit_". $data_row["id"], 0, "");

			// if marked inactive, check if it's inuse
			if ($data_units [ $data_row["id"] ] == 0)
			{
				// we need to make sure this usage unit is not in use, since if it is, disabling
				// it would cause weird borkage.
			
				$obj_sql_check		= New sql_query;
				$obj_sql_check->string	= "SELECT name_service FROM services WHERE units='". $data_row["id"] ."'";
				$obj_sql_check->execute();

				if ($obj_sql_check->num_rows())
				{
					$obj_sql_check->fetch_array();

					$service_list = array();

					foreach ($obj_sql_check->data as $data_check)
					{
						$service_list[] = $data_check["name_service"];
					}

					// unit is in use
					log_write("error", "process", "Unable to disable unit ". $data_row["name"] ." due to it being used by service \"". format_arraytocommastring($service_list) ."\".");
					error_flag_field("service_unit_". $data_row["id"]);

				} // end if service unit in use

			} // end if disabled

		} // end of service unit loop

	} // end of if service units



	/*
		Service Type Options
	*/
	$data_types		= array();

	$obj_sql		= New sql_query;
	$obj_sql->string	= "SELECT id, name FROM service_types ORDER BY name";
	$obj_sql->execute();

	if ($obj_sql->num_rows())
	{
		$obj_sql->fetch_array();

		foreach ($obj_sql->data as $data_row)
		{
			$data_types[ $data_row["id"] ]["active"]		= @security_form_input_predefined("checkbox", "service_type_". $data_row["id"] ."_enable", 0, "");
			$data_types[ $data_row["id"] ]["description"]		= @security_form_input_predefined("any", "service_type_". $data_row["id"] ."_description", 0, "");

			// if marked inactive, check if it's inuse
			if ($data_types [ $data_row["id"] ]["active"] == 0)
			{
				// we need to make sure this usage type is not in use, since if it is, disabling
				// it would cause weird borkage.
			
				$obj_sql_check		= New sql_query;
				$obj_sql_check->string	= "SELECT name_service FROM services WHERE typeid='". $data_row["id"] ."'";
				$obj_sql_check->execute();

				if ($obj_sql_check->num_rows())
				{
					$obj_sql_check->fetch_array();

					$service_list = array();

					foreach ($obj_sql_check->data as $data_check)
					{
						$service_list[] = $data_check["name_service"];
					}

					// type is in use
					log_write("error", "process", "Unable to disable type ". $data_row["name"] ." due to it being used by service \"". format_arraytocommastring($service_list) ."\".");
					error_flag_field("service_type_". $data_row["id"] ."_enable");

				} // end if service type in use

			} // end if disabled

		} // end of service type loop

	} // end of if service types


	/*
		Billing Cycle Configuration
	*/
	$data_cycles		= array();

	$obj_sql		= New sql_query;
	$obj_sql->string	= "SELECT id, name FROM billing_cycles ORDER BY priority";
	$obj_sql->execute();

	if ($obj_sql->num_rows())
	{
		$obj_sql->fetch_array();

		foreach ($obj_sql->data as $data_row)
		{
			$data_cycles[ $data_row["id"] ]			= @security_form_input_predefined("checkbox", "billing_cycle_". $data_row["id"], 0, "");

			// if marked inactive, check if it's inuse
			if ($data_cycles [ $data_row["id"] ] == 0)
			{
				// we need to make sure this usage cycle is not in use, since if it is, disabling
				// it would cause weird borkage.
			
				$obj_sql_check		= New sql_query;
				$obj_sql_check->string	= "SELECT name_service FROM services WHERE billing_cycle='". $data_row["id"] ."'";
				$obj_sql_check->execute();

				if ($obj_sql_check->num_rows())
				{
					$obj_sql_check->fetch_array();

					$service_list = array();

					foreach ($obj_sql_check->data as $data_check)
					{
						$service_list[] = $data_check["name_service"];
					}

					// cycle is in use
					log_write("error", "process", "Unable to disable cycle ". $data_row["name"] ." due to it being used by service \"". format_arraytocommastring($service_list) ."\".");
					error_flag_field("billing_cycle_". $data_row["id"]);

				} // end if service cycle in use

			} // end if disabled

		} // end of service cycle loop

	} // end of if service cycles



	/*
		Billing Mode Configuration
	*/
	$data_modes		= array();

	$obj_sql		= New sql_query;
	$obj_sql->string	= "SELECT id, name FROM billing_modes";
	$obj_sql->execute();

	if ($obj_sql->num_rows())
	{
		$obj_sql->fetch_array();

		foreach ($obj_sql->data as $data_row)
		{
			$data_modes[ $data_row["id"] ]			= @security_form_input_predefined("checkbox", "billing_mode_". $data_row["id"], 0, "");

			// if marked inactive, check if it's inuse
			if ($data_modes [ $data_row["id"] ] == 0)
			{
				// we need to make sure this usage mode is not in use, since if it is, disabling
				// it would cause weird borkage.
			
				$obj_sql_check		= New sql_query;
				$obj_sql_check->string	= "SELECT name_service FROM services WHERE billing_mode='". $data_row["id"] ."'";
				$obj_sql_check->execute();

				if ($obj_sql_check->num_rows())
				{
					$obj_sql_check->fetch_array();

					$service_list = array();

					foreach ($obj_sql_check->data as $data_check)
					{
						$service_list[] = $data_check["name_service"];
					}

					// mode is in use
					log_write("error", "process", "Unable to disable mode ". $data_row["name"] ." due to it being used by service \"". format_arraytocommastring($service_list) ."\".");
					error_flag_field("billing_mode_". $data_row["id"]);

				} // end if service mode in use

			} // end if disabled

		} // end of service mode loop

	} // end of if service modes




	/*
		Test Traffic Database
	*/

	if ($data["SERVICE_TRAFFIC_DB_TYPE"] == "mysql_netflow_daily")
	{
		$obj_sql = New sql_query;

		if (!$obj_sql->session_init("mysql", $data["SERVICE_TRAFFIC_DB_HOST"], $data["SERVICE_TRAFFIC_DB_NAME"], $data["SERVICE_TRAFFIC_DB_USERNAME"], $data["SERVICE_TRAFFIC_DB_PASSWORD"]))
		{
			log_write("error", "sql_query", "Unable to connect to traffic service usage database!");

			error_flag_field("SERVICE_TRAFFIC_DB_HOST");
			error_flag_field("SERVICE_TRAFFIC_DB_NAME");
			error_flag_field("SERVICE_TRAFFIC_DB_USERNAME");
			error_flag_field("SERVICE_TRAFFIC_DB_PASSWORD");
		}
		else
		{
			log_write("notification", "sql_query", "Tested successful connection to traffic usage database");

			$obj_sql->session_terminate();
		}

	}



	/*
		Test CDR Database
	*/

	if ($data["SERVICE_CDR_DB_TYPE"] == "mysql_cdr_daily")
	{
		$obj_sql = New sql_query;

		if (!$obj_sql->session_init("mysql", $data["SERVICE_CDR_DB_HOST"], $data["SERVICE_CDR_DB_NAME"], $data["SERVICE_CDR_DB_USERNAME"], $data["SERVICE_CDR_DB_PASSWORD"]))
		{
			log_write("error", "sql_query", "Unable to connect to CDR service usage database!");

			error_flag_field("SERVICE_CDR_DB_HOST");
			error_flag_field("SERVICE_CDR_DB_NAME");
			error_flag_field("SERVICE_CDR_DB_USERNAME");
			error_flag_field("SERVICE_CDR_DB_PASSWORD");
		}
		else
		{
			log_write("notification", "sql_query", "Tested successful connection to CDR usage database");

			$obj_sql->session_terminate();
		}
	}


	/*
		Process Errors
	*/

	if (error_check())
	{
		$_SESSION["error"]["form"]["config_services"] = "failed";
		header("Location: ../index.php?page=admin/config_services.php");
		exit(0);
	}
	else
	{
		$_SESSION["error"] = array();


		/*
			Start Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();

	
		/*
			Update all the config fields

			We have already loaded the data for all the fields, so simply need to go and set all the values
			based on the naming of the $data array.
		*/

		foreach (array_keys($data) as $data_key)
		{
			$sql_obj->string = "UPDATE config SET value='". $data[$data_key] ."' WHERE name='$data_key' LIMIT 1";
			$sql_obj->execute();
		}


		/*
			Update service units
		*/

		foreach (array_keys($data_units) as $id_unit)
		{
			$sql_obj->string = "UPDATE service_units SET active='". $data_units[ $id_unit ] ."' WHERE id='". $id_unit ."' LIMIT 1";
			$sql_obj->execute();
		}


		/*
			Update service types
		*/

		foreach (array_keys($data_types) as $id_type)
		{
			$sql_obj->string = "UPDATE service_types SET active='". $data_types[ $id_type ]["active"] ."', description='". $data_types[ $id_type ]["description"] ."' WHERE id='". $id_type ."' LIMIT 1";
			$sql_obj->execute();
		}


		/*
			Update billing cycles
		*/

		foreach (array_keys($data_cycles) as $id_cycle)
		{
			$sql_obj->string = "UPDATE billing_cycles SET active='". $data_cycles[ $id_cycle ] ."' WHERE id='". $id_cycle ."' LIMIT 1";
			$sql_obj->execute();
		}


		/*
			Update billing mode
		*/

		foreach (array_keys($data_modes) as $id_mode)
		{
			$sql_obj->string = "UPDATE billing_modes SET active='". $data_modes[ $id_mode ] ."' WHERE id='". $id_mode ."' LIMIT 1";
			$sql_obj->execute();
		}




		/*
			Commit
		*/
		
		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "process", "An error occured whilst updating configuration, no changes have been applied.");
		}
		else
		{
			$sql_obj->trans_commit();

			log_write("notification", "process", "Service billing configuration updated successfully");
		}

		header("Location: ../index.php?page=admin/config_services.php");
		exit(0);


	} // if valid data input
	
	
} // end of "is user logged in?"
else
{
	// user does not have permissions to access this page.
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
