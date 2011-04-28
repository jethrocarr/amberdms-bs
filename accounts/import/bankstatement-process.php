<?php
/*
	bankstatement-process.php
	
	access: "accounts_import_statement" group members

	Validates the uploaded statement file as being a supported format
	and reads in the data into session information to pass to the column assignment
	and then the record matching pages.
*/

//inclues
require("../../include/config.php");
require("../../include/amberphplib/main.php");


if (user_permissions_get("accounts_import_statement"))
{
	/*
		Process Uploaded File
	*/

	$file_obj = New file_storage;
	$file_obj->verify_upload_form("BANK_STATEMENT", array("csv"));
	
	$dest_account	= @security_form_input_predefined("int", "dest_account", 1, "");
	$employeeid	= @security_form_input_predefined("any", "employeeid", 1, "");


	/*
		Check for obvious errors
	*/
	if (error_check())
	{
		header("Location: ../../index.php?page=accounts/import/bankstatement.php");
		exit(0);
	}



	/*
		Import File Contents
	*/

	// declare array
	$transactions = array();

	// set file type
	$filetype = format_file_extension($_FILES["BANK_STATEMENT"]["name"]);

	// process CSV file
	if ($filetype == "csv")
	{
		// check that file can be opened
		if ($handle = fopen($_FILES["BANK_STATEMENT"]["tmp_name"], "r"))
		{
			$i = 0;

			while ($data = fgetcsv($handle, 1000, ","))
			{
				// count the number of entries in the row
				$num_entries = count($data);

				for ($j=0; $j<$num_entries; $j++)
				{
					// place the information into a 2 dimensional array
					$transactions[$i][$j] = $data[$j];
				}


				$i++;

			} // end of loop
	
			fclose($handle);


			// assign to session variable
			$_SESSION["csv_array"]		= $transactions;
			$_SESSION["dest_account"]	= $dest_account;
			$_SESSION["employeeid"]		= $employeeid;
	
			// take user to column assignment page
			header("Location: ../../index.php?page=accounts/import/bankstatement-csv.php");
			exit(0);

		} // end if can be opend

	} // end of file type

	
	/*
		an unexpected error occured
	*/

	if (error_check())
	{
		header("Location: ../../index.php?page=accounts/import/bankstatement.php");
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
