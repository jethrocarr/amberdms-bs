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
