<?php
/*
	admin/config_application-process.php
	
	Access: admin only

	Applies changes to the application configuration
*/


// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");


if (user_permissions_get("admin"))
{
	/*
		Load Data
	*/

	$data["ACCOUNTS_AP_INVOICENUM"]		= @security_form_input("/^[A-Za-z0-9_\-]*[0-9]*$/", "ACCOUNTS_AP_INVOICENUM", 1, "");
	$data["ACCOUNTS_AR_INVOICENUM"]		= @security_form_input("/^[A-Za-z0-9_\-]*[0-9]*$/", "ACCOUNTS_AR_INVOICENUM", 1, "");
	$data["ACCOUNTS_GL_TRANSNUM"]		= @security_form_input("/^[A-Za-z0-9_\-]*[0-9]*$/", "ACCOUNTS_GL_TRANSNUM", 1, "");
	$data["ACCOUNTS_QUOTES_NUM"]		= @security_form_input("/^[A-Za-z0-9_\-]*[0-9]*$/", "ACCOUNTS_QUOTES_NUM", 1, "");
	$data["CODE_ACCOUNT"]			= @security_form_input("/^[A-Za-z0-9_\-]*[0-9]*$/", "CODE_ACCOUNT", 1, "");
	$data["CODE_CUSTOMER"]			= @security_form_input("/^[A-Za-z0-9_\-]*[0-9]*$/", "CODE_CUSTOMER", 1, "");
	$data["CODE_VENDOR"]			= @security_form_input("/^[A-Za-z0-9_\-]*[0-9]*$/", "CODE_VENDOR", 1, "");
	$data["CODE_PRODUCT"]			= @security_form_input("/^[A-Za-z0-9_\-]*[0-9]*$/", "CODE_PRODUCT", 1, "");
	$data["CODE_PROJECT"]			= @security_form_input("/^[A-Za-z0-9_\-]*[0-9]*$/", "CODE_PROJECT", 1, "");
	$data["CODE_STAFF"]			= @security_form_input("/^[A-Za-z0-9_\-]*[0-9]*$/", "CODE_STAFF", 1, "");

	$data["ACCOUNTS_SERVICES_ADVANCEBILLING"]	= @security_form_input_predefined("any", "ACCOUNTS_SERVICES_ADVANCEBILLING", 1, "");
	$data["ACCOUNTS_TERMS_DAYS"]			= @security_form_input_predefined("int", "ACCOUNTS_TERMS_DAYS", 0, "");
	$data["ACCOUNTS_INVOICE_AUTOEMAIL"]		= @security_form_input_predefined("any", "ACCOUNTS_INVOICE_AUTOEMAIL", 0, "");
	
	$data["TIMESHEET_BOOKTOFUTURE"]		= @security_form_input_predefined("any", "TIMESHEET_BOOKTOFUTURE", 0, "");
	
	$data["ACCOUNTS_INVOICE_LOCK"]		= @security_form_input_predefined("int", "ACCOUNTS_INVOICE_LOCK", 0, "");
	$data["ACCOUNTS_GL_LOCK"]		= @security_form_input_predefined("int", "ACCOUNTS_GL_LOCK", 0, "");
	$data["JOURNAL_LOCK"]			= @security_form_input_predefined("int", "JOURNAL_LOCK", 0, "");
	$data["TIMESHEET_LOCK"]			= @security_form_input_predefined("int", "TIMESHEET_LOCK", 0, "");
	
	$data["BLACKLIST_ENABLE"]		= @security_form_input_predefined("any", "BLACKLIST_ENABLE", 0, "");
	$data["BLACKLIST_LIMIT"]		= @security_form_input_predefined("int", "BLACKLIST_LIMIT", 1, "");
	
	$data["UPLOAD_MAXBYTES"]		= @security_form_input_predefined("int", "UPLOAD_MAXBYTES", 1, "");

	$data["PHONE_HOME"]			= @security_form_input_predefined("any", "PHONE_HOME", 0, "");
	

	// only fetch dangerous options if support for it is enabled
	if ($GLOBALS["config"]["dangerous_conf_options"] == "enabled")
	{
		$data["EMAIL_ENABLE"]		= @security_form_input_predefined("any", "EMAIL_ENABLE", 0, "");
		$data["PATH_TMPDIR"]		= @security_form_input_predefined("any", "PATH_TMPDIR", 1, "");
		$data["DATA_STORAGE_LOCATION"]	= @security_form_input_predefined("any", "DATA_STORAGE_LOCATION", 1, "");
		$data["DATA_STORAGE_METHOD"]	= @security_form_input_predefined("any", "DATA_STORAGE_METHOD", 1, "");
		$data["APP_PDFLATEX"]		= @security_form_input_predefined("any", "APP_PDFLATEX", 1, "");
		$data["APP_WKHTMLTOPDF"]	= @security_form_input_predefined("any", "APP_WKHTMLTOPDF", 1, "");
		$data["APP_MYSQL_DUMP"]		= @security_form_input_predefined("any", "APP_MYSQL_DUMP", 1, "");

		if ($data["EMAIL_ENABLE"] == "on")
		{
			$data["EMAIL_ENABLE"] = "enabled";
		}
		else
		{
			$data["EMAIL_ENABLE"] = "disabled";
		}
	}


	// modifiy checkbox values
	if ($data["TIMESHEET_BOOKTOFUTURE"] == "on")
	{
		$data["TIMESHEET_BOOKTOFUTURE"] = "enabled";
	}
	else
	{
		$data["TIMESHEET_BOOKTOFUTURE"] = "disabled";
	}
		
	if ($data["ACCOUNTS_INVOICE_AUTOEMAIL"] == "on")
	{
		$data["ACCOUNTS_INVOICE_AUTOEMAIL"] = "enabled";
	}
	else
	{
		$data["ACCOUNTS_INVOICE_AUTOEMAIL"] = "disabled";
	}
	
	if ($data["BLACKLIST_ENABLE"] == "on")
	{
		$data["BLACKLIST_ENABLE"] = "enabled";
	}
	else
	{
		$data["BLACKLIST_ENABLE"] = "disabled";
	}

	if ($data["PHONE_HOME"] == "on")
	{
		$data["PHONE_HOME"] = "enabled";
	}
	else
	{
		$data["PHONE_HOME"] = "disabled";
	}



	// check max upload size
	$system_upload_max_filesize = format_size_bytes(ini_get('upload_max_filesize'));

	if ($data["UPLOAD_MAXBYTES"] > $system_upload_max_filesize)
	{
		// adjust the value to the max possible and add notification about it.
		$data["UPLOAD_MAXBYTES"] = $system_upload_max_filesize;

		log_write("notification", "process", "The maximum upload is ". format_size_human($system_upload_max_filesize) ." due to server limits, the maximum upload value for this application has been adjusted to suit.");
	}




	/*
		Error Handling
	*/
	if (error_check())
	{
		$_SESSION["error"]["form"]["config_application"] = "failed";
		header("Location: ../index.php?page=admin/config_application.php");
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

			log_write("notification", "process", "Application configuration updated successfully");
		}

		header("Location: ../index.php?page=admin/config_application.php");
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
