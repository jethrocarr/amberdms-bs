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
