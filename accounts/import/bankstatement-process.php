<?php
/*
	bankstatement-process.php
	
	access: "accounts_import_statement" group members

	Verifies and parses imported file.
*/

//inclues
include_once("include/amberphplib/main.php");


if (user_permissions_get("accounts_import_statement"))
{

    //process upload and verify content and file type
    $file_obj = New file_storage;
    $file_obj->verify_upload_form("BANK_STATEMENT", array("csv", "ofx", "qif"));
   
    
    if (error_check())
    {
	header("Location: index.php?page=accounts/import/bankstatement.php");
    }
    else
    {
	//declare array
	$transactions = array();
	//set file type
	$filetype = format_file_extension($_FILES["BANK_STATEMENT"]["name"]);
	
	if ($filetype == "csv")
	{
	    //check that file can be opened
	    if ($handle = fopen($_FILES["BANK_STATEMENT"]["tmp_name"], "r"))
	    {
		$i = 0;
		while ($data = fgetcsv($handle, 1000, ",")) 
		{
		    //count the number of entries in the row
		    $num_entries = count($data);
		    for ($j=0; $j<$num_entries; $j++)
		    {
			//place the information into a 2 dimensional array
			$transactions[$i][$j] = $data[$j];
		    }
		    $i++;
		}
		fclose($handle);
		$_SESSION["csv_array"] = $transactions;
		
		header("Location: index.php?page=accounts/import/bankstatement-csv.php");
	    }
	  
	    //if file cannot be opened, create an error
	    else
	    {
		print "Error!";
	    }
	}
    }
}



?>