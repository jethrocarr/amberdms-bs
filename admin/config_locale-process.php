<?php
/*
	admin/config_locale-process.php
	
	Access: admin only

	Updates locale settings, like timezone, date formatting, currency, number formatting, etc.
*/


// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");


if (user_permissions_get("admin"))
{
	/*
		Load Data
	*/

	$data["THEME_DEFAULT"]				= @security_form_input_predefined("any", "THEME_DEFAULT", 1, "");

	$data["DATEFORMAT"]				= @security_form_input_predefined("any", "DATEFORMAT", 1, "");
	$data["TIMEZONE_DEFAULT"]			= @security_form_input_predefined("any", "TIMEZONE_DEFAULT", 1, "");

	$data["CURRENCY_DEFAULT_NAME"]			= @security_form_input_predefined("any", "CURRENCY_DEFAULT_NAME", 1, "");
	$data["CURRENCY_DEFAULT_SYMBOL"]		= @security_form_input_predefined("any", "CURRENCY_DEFAULT_SYMBOL", 1, "");
	$data["CURRENCY_DEFAULT_SYMBOL_POSITION"]	= @security_form_input_predefined("any", "CURRENCY_DEFAULT_SYMBOL_POSITION", 1, "");
	




	/*
		Process Errors
	*/

	if (error_check())
	{
		$_SESSION["error"]["form"]["config_locale"] = "failed";
		header("Location: ../index.php?page=admin/config_locale.php");
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

			log_write("notification", "process", "Locale configuration updated successfully");
		}

		header("Location: ../index.php?page=admin/config_locale.php");
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
