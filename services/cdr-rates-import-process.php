<?php
/*
	services/cdr-rates-import-process.php

	access: services_write

	Verifies and parses imported CSV file.
*/

//inclues
require("../include/config.php");
require("../include/amberphplib/main.php");
require("../include/services/inc_services.php");
require("../include/services/inc_services_cdr.php");


if (user_permissions_get("services_write"))
{
	/*
		Load Data
	*/

	$obj_rate_table						= New cdr_rate_table;
	$obj_rate_table->id					= @security_form_input_predefined("int", "id_rate_table", 1, "");




	/*
		Verify valid rate table
	*/
	if (!$obj_rate_table->verify_id())
	{
		log_write("error", "process", "The CDR rate table you have attempted to edit - ". $obj_rate_table->id ." - does not exist in this system.");
	}



	/*
		Verify File Upload
	*/

	$file_obj = New file_storage;
	$file_obj->verify_upload_form("cdr_rate_import_file", array("csv"));
   



	/*
		Handle Errors
	*/
    
	if (error_check())
	{
		header("Location: ../index.php?page=services/cdr-rates-import.php&id=". $obj_rate_table->id );
		exit(0);
	}
	else
	{
		/*
			Load the file
		*/

		$rate_table = array();

		// check that file can be opened
		if ($handle = fopen($_FILES["cdr_rate_import_file"]["tmp_name"], "r"))
		{
			// read data into array
			$i = 0;
			while ($data = fgetcsv($handle, 1000, ",")) 
			{
				// count the number of entries in the row
				$num_entries = count($data);

				for ($j=0; $j<$num_entries; $j++)
				{
					// place the information into a 2 dimensional array
					$rate_table[$i][$j] = $data[$j];
				}

				$i++;
			}


			fclose($handle);
		}
		else
		{
			log_write("error", "process", "An unexpected error occured whilst trying to import the supplied file");
		}

		// save to session
		$_SESSION["csv_array"] = $rate_table;
		
		header("Location: ../index.php?page=services/cdr-rates-import-csv.php&id=". $obj_rate_table->id );
		exit(0);
	}
}
else
{
	// user does not have permissions to access this page.
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
