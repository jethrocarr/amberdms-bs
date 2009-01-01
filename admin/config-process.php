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


	// fetch all the option field names and then get the processed field data
	$sql_config_obj		= New sql_query;
	$sql_config_obj->string	= "SELECT name FROM config";
	$sql_config_obj->execute();

	if ($sql_config_obj->num_rows())
	{
		$sql_config_obj->fetch_array();
		
		// structure the results into a form we can then use to fill the fields in the form
		foreach ($sql_config_obj->data as $data_config)
		{
			$data[ $data_config["name"] ] = security_form_input_predefined("any", $data_config["name"], 0, "");
		}
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
			Update all the config fields
		*/

		foreach ($sql_config_obj->data as $data_config)
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "UPDATE config SET value='". $data[ $data_config["name"] ] ."' WHERE name='". $data_config["name"] ."'";
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
			Complete
		*/
		
		$_SESSION["notification"]["message"][] = "Configuration Updated";
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
