<?php
/*
	admin/config_company-process.php
	
	Access: admin only

	Updates the company details and contact details.
*/


// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");


if (user_permissions_get("admin"))
{
	////// INPUT PROCESSING ////////////////////////


	// fetch all the data
	$data["COMPANY_NAME"]			= @security_form_input_predefined("any", "COMPANY_NAME", 1, "");
	$data["COMPANY_CONTACT_EMAIL"]		= @security_form_input_predefined("email", "COMPANY_CONTACT_EMAIL", 1, "");
	$data["COMPANY_CONTACT_PHONE"]		= @security_form_input_predefined("any", "COMPANY_CONTACT_PHONE", 1, "");
	$data["COMPANY_CONTACT_FAX"]		= @security_form_input_predefined("any", "COMPANY_CONTACT_FAX", 0, "");
	$data["COMPANY_ADDRESS1_STREET"]	= @security_form_input_predefined("any", "COMPANY_ADDRESS1_STREET", 1, "");
	$data["COMPANY_ADDRESS1_CITY"]		= @security_form_input_predefined("any", "COMPANY_ADDRESS1_CITY", 1, "");
	$data["COMPANY_ADDRESS1_STATE"]		= @security_form_input_predefined("any", "COMPANY_ADDRESS1_STATE", 0, "");
	$data["COMPANY_ADDRESS1_COUNTRY"]	= @security_form_input_predefined("any", "COMPANY_ADDRESS1_COUNTRY", 1, "");
	$data["COMPANY_ADDRESS1_ZIPCODE"]	= @security_form_input_predefined("any", "COMPANY_ADDRESS1_ZIPCODE", 0, "");
	$data["COMPANY_PAYMENT_DETAILS"]	= @security_form_input_predefined("any", "COMPANY_PAYMENT_DETAILS", 1, "");
	$data["COMPANY_TAX_NUMBER"]		= @security_form_input_predefined("any", "COMPANY_TAX_NUMBER", 0, "");
	$data["COMPANY_REG_NUMBER"]		= @security_form_input_predefined("any", "COMPANY_REG_NUMBER", 0, "");
	$data["COMPANY_ADDRESS2_STREET"]	= @security_form_input_predefined("any", "COMPANY_ADDRESS2_STREET", 1, "");
	$data["COMPANY_ADDRESS2_CITY"]		= @security_form_input_predefined("any", "COMPANY_ADDRESS2_CITY", 1, "");
	$data["COMPANY_ADDRESS2_STATE"]		= @security_form_input_predefined("any", "COMPANY_ADDRESS2_STATE", 0, "");
	$data["COMPANY_ADDRESS2_COUNTRY"]	= @security_form_input_predefined("any", "COMPANY_ADDRESS2_COUNTRY", 1, "");
	$data["COMPANY_ADDRESS2_ZIPCODE"]	= @security_form_input_predefined("any", "COMPANY_ADDRESS2_ZIPCODE", 0, "");
	$data["COMPANY_B2C_TERMS"]		= @security_form_input_predefined("html", "COMPANY_B2C_TERMS", 0,"");
	$data["COMPANY_B2B_TERMS"]		= @security_form_input_predefined("html", "COMPANY_B2B_TERMS", 0,"");
	/*
		Process company logo upload and verify content 
		if any has been supplied. Enforce png only
	*/
	$file_obj				= New file_storage;

	if ($_FILES["COMPANY_LOGO"]["size"] > 1)
	{
		$file_obj->verify_upload_form("COMPANY_LOGO", array("png"));
	}



	/*
		Error Handling
	*/
	if (error_check())
	{
		$_SESSION["error"]["form"]["config_company"] = "failed";
		header("Location: ../index.php?page=admin/config_company.php");
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

		if ($_FILES["COMPANY_LOGO"]["size"] > 1)
		{
			// set file variables	
			$file_obj->data["type"]			= "COMPANY_LOGO";
			$file_obj->data["customid"]		= "0";
	
			// see if a file already exists
			if ($file_obj->load_data_bytype())
			{
				log_debug("process", "Old file exists, will overwrite.");
			}
			else
			{
				log_debug("process", "No previous file exists, performing clean upload.");
			}

			// force the filename
			$file_obj->data["file_name"]	= "company_logo.png";
			
			// call the upload function
			if (!$file_obj->action_update_form("COMPANY_LOGO"))
			{
				log_write("error", "process", "Unable to upload company logo");
			}
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

			log_write("notification", "process", "Company configuration updated successfully");
		}

		header("Location: ../index.php?page=admin/config_company.php");
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
