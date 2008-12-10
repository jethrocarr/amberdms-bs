<?php
/*
	vendors/edit-process.php

	access: vendors_write

	Allows existing vendors to be adjusted, or new vendors to be added.
*/

// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");


if (user_permissions_get('vendors_write'))
{
	/////////////////////////

	$id				= security_form_input_predefined("int", "id_vendor", 0, "");
	
	$data["name_vendor"]		= security_form_input_predefined("any", "name_vendor", 1, "You must set a vendor name");
	$data["name_contact"]		= security_form_input_predefined("any", "name_contact", 0, "");
	
	$data["contact_phone"]		= security_form_input_predefined("any", "contact_phone", 0, "");
	$data["contact_fax"]		= security_form_input_predefined("any", "contact_fax", 0, "");
	$data["contact_email"]		= security_form_input_predefined("email", "contact_email", 0, "There is a mistake in the supplied email address, please correct.");
	$data["date_start"]		= security_form_input_predefined("date", "date_start", 1, "");
	$data["date_end"]		= security_form_input_predefined("date", "date_end", 0, "");

	$data["address1_street"]	= security_form_input_predefined("any", "address1_street", 0, "");
	$data["address1_city"]		= security_form_input_predefined("any", "address1_city", 0, "");
	$data["address1_state"]		= security_form_input_predefined("any", "address1_state", 0, "");
	$data["address1_country"]	= security_form_input_predefined("any", "address1_country", 0, "");
	$data["address1_zipcode"]	= security_form_input_predefined("any", "address1_zipcode", 0, "");
	
	$data["address2_street"]	= security_form_input_predefined("any", "address2_street", 0, "");
	$data["address2_city"]		= security_form_input_predefined("any", "address2_city", 0, "");
	$data["address2_state"]		= security_form_input_predefined("any", "address2_state", 0, "");
	$data["address2_country"]	= security_form_input_predefined("any", "address2_country", 0, "");
	$data["address2_zipcode"]	= security_form_input_predefined("any", "address2_zipcode", 0, "");
	
	$data["tax_default"]		= security_form_input_predefined("int", "tax_default", 0, "");
	$data["tax_number"]		= security_form_input_predefined("any", "tax_number", 0, "");


	// are we editing an existing vendor or adding a new one?
	if ($id)
	{
		$mode = "edit";

		// make sure the vendor actually exists
		$mysql_string		= "SELECT id FROM `vendors` WHERE id='$id'";
		$mysql_result		= mysql_query($mysql_string);
		$mysql_num_rows		= mysql_num_rows($mysql_result);

		if (!$mysql_num_rows)
		{
			$_SESSION["error"]["message"][] = "The vendor you have attempted to edit - $id - does not exist in this system.";
		}
	}
	else
	{
		$mode = "add";
	}


		
	//// ERROR CHECKING ///////////////////////


	// make sure we don't choose a vendor name that has already been taken
	$mysql_string	= "SELECT id FROM `vendors` WHERE name_vendor='". $data["name_vendor"] ."'";
	if ($id)
		$mysql_string .= " AND id!='$id'";
	$mysql_result	= mysql_query($mysql_string);
	$mysql_num_rows	= mysql_num_rows($mysql_result);

	if ($mysql_num_rows)
	{
		$_SESSION["error"]["message"][] = "This vendor name is already used for another vendor - please choose a unique name.";
		$_SESSION["error"]["name_vendor-error"] = 1;
	}


	// make sure we don't choose a vendor code that has already been taken
	if ($data["code_vendor"])
	{
		$mysql_string	= "SELECT id FROM `vendors` WHERE code_vendor='". $data["code_vendor"] ."'";
		if ($id)
			$mysql_string .= " AND id!='$id'";
		$mysql_result	= mysql_query($mysql_string);
		$mysql_num_rows	= mysql_num_rows($mysql_result);

		if ($mysql_num_rows)
		{
			$_SESSION["error"]["message"][] = "This vendor code is already used for another vendor - please choose a unique code, or leave it blank to recieve an auto-generated value.";
			$_SESSION["error"]["code_vendor-error"] = 1;
		}
	}
	




	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		if ($mode == "edit")
		{
			$_SESSION["error"]["form"]["vendor_view"] = "failed";
			header("Location: ../index.php?page=vendors/view.php&id=$id");
			exit(0);
		}
		else
		{
			$_SESSION["error"]["form"]["vendor_add"] = "failed";
			header("Location: ../index.php?page=vendors/add.php");
			exit(0);
		}
	}
	else
	{

		// generate a unique vendor code
		if (!$data["code_vendor"])
		{
			$data["code_vendor"] = config_generate_uniqueid("CODE_VENDOR", "SELECT id FROM vendors WHERE code_vendor='VALUE'");
		}

	
		if ($mode == "add")
		{
			// create a new entry in the DB
			$mysql_string = "INSERT INTO `vendors` (name_vendor) VALUES ('".$data["name_vendor"]."')";
			if (!mysql_query($mysql_string))
			{
				$_SESSION["error"]["message"][] = "A fatal SQL error occured: ". $mysql_error();
			}

			$id = mysql_insert_id();
		}

		if ($id)
		{
			// update vendor details
			$mysql_string = "UPDATE `vendors` SET "
						."code_vendor='". $data["code_vendor"] ."', "
						."name_vendor='". $data["name_vendor"] ."', "
						."name_contact='". $data["name_contact"] ."', "
						."contact_phone='". $data["contact_phone"] ."', "
						."contact_email='". $data["contact_email"] ."', "
						."contact_fax='". $data["contact_fax"] ."', "
						."date_start='". $data["date_start"] ."', "
						."date_end='". $data["date_end"] ."', "
						."tax_default='". $data["tax_default"] ."', "
						."tax_number='". $data["tax_number"] ."', "
						."address1_street='". $data["address1_street"] ."', "
						."address1_city='". $data["address1_city"] ."', "
						."address1_state='". $data["address1_state"] ."', "
						."address1_country='". $data["address1_country"] ."', "
						."address1_zipcode='". $data["address1_zipcode"] ."', "
						."address2_street='". $data["address2_street"] ."', "
						."address2_city='". $data["address2_city"] ."', "
						."address2_state='". $data["address2_state"] ."', "
						."address2_country='". $data["address2_country"] ."', "
						."address2_zipcode='". $data["address2_zipcode"] ."' "
						."WHERE id='$id'";
						
			if (!mysql_query($mysql_string))
			{
				$_SESSION["error"]["message"][] = "A fatal SQL error occured: ". mysql_error();
			}
			else
			{
				if ($mode == "add")
				{
					$_SESSION["notification"]["message"][] = "Vendor successfully created.";
					journal_quickadd_event("vendors", $id, "Vendor's successfully created");
				}
				else
				{
					$_SESSION["notification"]["message"][] = "Vendor successfully updated.";
					journal_quickadd_event("vendors", $id, "Vendor's details updated");
				}
			}
		}

		// display updated details
		header("Location: ../index.php?page=vendors/view.php&id=$id");
		exit(0);
	}

	/////////////////////////
	
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
