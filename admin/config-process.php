<?php
/*
	admin/config-process.php
	
	Access: admin only

	Updates the system configuration.
*/


// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");


if (user_permissions_get("admin"))
{
	////// INPUT PROCESSING ////////////////////////


	// fetch all the data
	$data["COMPANY_NAME"]			= security_form_input_predefined("any", "COMPANY_NAME", 1, "");
	$data["COMPANY_CONTACT_EMAIL"]		= security_form_input_predefined("email", "COMPANY_CONTACT_EMAIL", 1, "");
	$data["COMPANY_CONTACT_PHONE"]		= security_form_input_predefined("any", "COMPANY_CONTACT_PHONE", 1, "");
	$data["COMPANY_CONTACT_FAX"]		= security_form_input_predefined("any", "COMPANY_CONTACT_FAX", 1, "");
	$data["COMPANY_ADDRESS1_STREET"]	= security_form_input_predefined("any", "COMPANY_ADDRESS1_STREET", 1, "");
	$data["COMPANY_ADDRESS1_CITY"]		= security_form_input_predefined("any", "COMPANY_ADDRESS1_CITY", 1, "");
	$data["COMPANY_ADDRESS1_STATE"]		= security_form_input_predefined("any", "COMPANY_ADDRESS1_STATE", 0, "");
	$data["COMPANY_ADDRESS1_COUNTRY"]	= security_form_input_predefined("any", "COMPANY_ADDRESS1_COUNTRY", 1, "");
	$data["COMPANY_ADDRESS1_ZIPCODE"]	= security_form_input_predefined("any", "COMPANY_ADDRESS1_ZIPCODE", 0, "");
	$data["COMPANY_PAYMENT_DETAILS"]	= security_form_input_predefined("any", "COMPANY_PAYMENT_DETAILS", 1, "");
	
	$data["ACCOUNTS_AP_INVOICENUM"]		= security_form_input_predefined("int", "ACCOUNTS_AP_INVOICENUM", 1, "");
	$data["ACCOUNTS_AR_INVOICENUM"]		= security_form_input_predefined("int", "ACCOUNTS_AR_INVOICENUM", 1, "");
	$data["ACCOUNTS_GL_TRANSNUM"]		= security_form_input_predefined("int", "ACCOUNTS_GL_TRANSNUM", 1, "");
	$data["ACCOUNTS_QUOTES_NUM"]		= security_form_input_predefined("int", "ACCOUNTS_QUOTES_NUM", 1, "");
	$data["CODE_ACCOUNT"]			= security_form_input_predefined("int", "CODE_ACCOUNT", 1, "");
	$data["CODE_CUSTOMER"]			= security_form_input_predefined("int", "CODE_CUSTOMER", 1, "");
	$data["CODE_VENDOR"]			= security_form_input_predefined("int", "CODE_VENDOR", 1, "");
	$data["CODE_PRODUCT"]			= security_form_input_predefined("int", "CODE_PRODUCT", 1, "");
	$data["CODE_PROJECT"]			= security_form_input_predefined("int", "CODE_PROJECT", 1, "");
	$data["CODE_STAFF"]			= security_form_input_predefined("int", "CODE_STAFF", 1, "");

	$data["ACCOUNTS_SERVICES_ADVANCEBILLING"]	= security_form_input_predefined("any", "ACCOUNTS_SERVICES_ADVANCEBILLING", 1, "");
	$data["ACCOUNTS_TERMS_DAYS"]			= security_form_input_predefined("int", "ACCOUNTS_TERMS_DAYS", 0, "");
	$data["ACCOUNTS_INVOICE_AUTOEMAIL"]		= security_form_input_predefined("any", "ACCOUNTS_INVOICE_AUTOEMAIL", 0, "");
	
	$data["TIMESHEET_BOOKTOFUTURE"]		= security_form_input_predefined("any", "TIMESHEET_BOOKTOFUTURE", 0, "");
	
	$data["CURRENCY_DEFAULT_NAME"]		= security_form_input_predefined("any", "CURRENCY_DEFAULT_NAME", 1, "");
	$data["CURRENCY_DEFAULT_SYMBOL"]	= security_form_input_predefined("any", "CURRENCY_DEFAULT_SYMBOL", 1, "");
	
	$data["ACCOUNTS_INVOICE_LOCK"]		= security_form_input_predefined("int", "ACCOUNTS_INVOICE_LOCK", 0, "");
	$data["ACCOUNTS_GL_LOCK"]		= security_form_input_predefined("int", "ACCOUNTS_GL_LOCK", 0, "");
	$data["JOURNAL_LOCK"]			= security_form_input_predefined("int", "JOURNAL_LOCK", 0, "");
	$data["TIMESHEET_LOCK"]			= security_form_input_predefined("int", "TIMESHEET_LOCK", 0, "");
	
	$data["BLACKLIST_ENABLE"]		= security_form_input_predefined("any", "BLACKLIST_ENABLE", 0, "");
	$data["BLACKLIST_LIMIT"]		= security_form_input_predefined("int", "BLACKLIST_LIMIT", 1, "");
	
	$data["UPLOAD_MAXBYTES"]		= security_form_input_predefined("int", "UPLOAD_MAXBYTES", 1, "");
	$data["DATEFORMAT"]			= security_form_input_predefined("any", "DATEFORMAT", 1, "");
	$data["TIMEZONE_DEFAULT"]		= security_form_input_predefined("any", "TIMEZONE_DEFAULT", 1, "");
	
	// only fetch dangerous options if support for it is enabled
	if ($GLOBALS["config"]["dangerous_conf_options"] == "enabled")
	{
		$data["EMAIL_ENABLE"]		= security_form_input_predefined("any", "EMAIL_ENABLE", 0, "");
		$data["DATA_STORAGE_LOCATION"]	= security_form_input_predefined("any", "DATA_STORAGE_LOCATION", 1, "");
		$data["DATA_STORAGE_METHOD"]	= security_form_input_predefined("any", "DATA_STORAGE_METHOD", 1, "");
		$data["APP_PDFLATEX"]		= security_form_input_predefined("any", "APP_PDFLATEX", 1, "");

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




	// Process file upload data
	//
	if ($_FILES["COMPANY_LOGO"]["size"] > 1)
	{
		// check the filesize is less than or equal to the max upload size
		$filesize_max = sql_get_singlevalue("SELECT value FROM config WHERE name='UPLOAD_MAXBYTES'");

		if ($_FILES['COMPANY_LOGO']['size'] >= $filesize_max)
		{
			$filesize_max_human	= format_size_human($filesize_max);
			$filesize_upload_human	= format_size_human($_FILES['COMPANY_LOGO']['size']);	
	
			$_SESSION["error"]["message"][] = "Files must be no larger than $filesize_max_human. You attempted to upload a $filesize_upload_human file.";
			$_SESSION["error"]["COMPANY_LOGO-error"] = 1;
		}


		// we can only accept PNG files
		if (!preg_match("/.png$/", $_FILES["COMPANY_LOGO"]["name"]))
		{
			$_SESSION["error"]["message"][] = "Only PNG files are acceptable for company logo uploads.";
			$_SESSION["error"]["COMPANY_LOGO-error"] = 1;
		}
	}





	//// PROCESS DATA ////////////////////////////


	if ($_SESSION["error"]["message"])
	{
		$_SESSION["error"]["form"]["config"] = "failed";
		header("Location: ../index.php?page=admin/config.php");
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
			Update the logo file if required
		*/


		if ($_FILES["COMPANY_LOGO"]["size"] > 1 && !$_SESSION["error"]["message"])
		{

			$file_obj = New file_process;
				
			// see if a file already exists
			if ($file_obj->fetch_information_by_type("COMPANY_LOGO", 0))
			{
				log_debug("config_process", "Old file exists, will overwrite.");
			}
			else
			{
				log_debug("config_process", "No previous file exists, performing clean upload.");
			}
			
			// set file variables	
			$file_obj->data["type"]			= "COMPANY_LOGO";
			$file_obj->data["customid"]		= "0";
			$file_obj->data["file_name"]		= "company_logo.png";
			$file_obj->data["file_size"]		= $_FILES['COMPANY_LOGO']['size'];

			// call the upload function
			if (!$file_obj->process_upload_from_form("COMPANY_LOGO"))
			{
				log_write("error", "config_process", "Unable to upload company logo");
			}
		}

	
		/*
			Commit
		*/
		
		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "config_process", "An error occured whilst updating configuration, no changes have been applied.");
		}
		else
		{
			$sql_obj->trans_commit();

			log_write("notification", "config_process", "Configuration Updated Successfully");
		}

		header("Location: ../index.php?page=admin/config.php");
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
