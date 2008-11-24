<?php
/*
	customers/edit-process.php

	access: customers_write

	Allows existing customers to be adjusted, or new customers to be added.
*/

// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");


if (user_permissions_get('customers_write'))
{
	/////////////////////////

	$id				= security_form_input_predefined("int", "id_customer", 0, "");
	
	$data["name_customer"]		= security_form_input_predefined("any", "name_customer", 1, "You must set a customer name");
	$data["name_contact"]		= security_form_input_predefined("any", "name_contact", 0, "");
	
	$data["contact_phone"]		= security_form_input_predefined("any", "contact_phone", 0, "");
	$data["contact_fax"]		= security_form_input_predefined("any", "contact_fax", 0, "");
	$data["contact_email"]		= security_form_input_predefined("email", "contact_email", 0, "There is a mistake in the supplied email address, please correct.");
	$data["date_start"]		= security_form_input_predefined("date", "date_start", 0, "");
	$data["date_end"]		= security_form_input_predefined("date", "date_end", 0, "");

	$data["address1_street"]	= security_form_input_predefined("any", "address1_street", 0, "");
	$data["address1_city"]		= security_form_input_predefined("any", "address1_city", 0, "");
	$data["address1_state"]		= security_form_input_predefined("any", "address1_state", 0, "");
	$data["address1_country"]	= security_form_input_predefined("any", "address1_country", 0, "");
	$data["address1_zipcode"]	= security_form_input_predefined("any", "address1_zipcode", 0, "");
	
	$data["pobox"]			= security_form_input_predefined("any", "pobox", 0, "");
	
	$data["address2_street"]	= security_form_input_predefined("any", "address2_street", 0, "");
	$data["address2_city"]		= security_form_input_predefined("any", "address2_city", 0, "");
	$data["address2_state"]		= security_form_input_predefined("any", "address2_state", 0, "");
	$data["address2_country"]	= security_form_input_predefined("any", "address2_country", 0, "");
	$data["address2_zipcode"]	= security_form_input_predefined("any", "address2_zipcode", 0, "");
	
	$data["tax_number"]		= security_form_input_predefined("any", "tax_number", 0, "");
	$data["tax_default"]		= security_form_input_predefined("int", "tax_default", 0, "");


	// are we editing an existing customer or adding a new one?
	if ($id)
	{
		$mode = "edit";

		// make sure the customer actually exists
		$mysql_string		= "SELECT id FROM `customers` WHERE id='$id'";
		$mysql_result		= mysql_query($mysql_string);
		$mysql_num_rows		= mysql_num_rows($mysql_result);

		if (!$mysql_num_rows)
		{
			$_SESSION["error"]["message"][] = "The customer you have attempted to edit - $id - does not exist in this system.";
		}
	}
	else
	{
		$mode = "add";
	}


		
	//// ERROR CHECKING ///////////////////////


	// make sure we don't choose a customer name that has already been taken
	$mysql_string	= "SELECT id FROM `customers` WHERE name_customer='". $data["name_customer"] ."'";
	if ($id)
		$mysql_string .= " AND id!='$id'";
	$mysql_result	= mysql_query($mysql_string);
	$mysql_num_rows	= mysql_num_rows($mysql_result);

	if ($mysql_num_rows)
	{
		$_SESSION["error"]["message"][] = "This customer name is already used for another customer - please choose a unique name.";
		$_SESSION["error"]["name_customer-error"] = 1;
	}


	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		if ($mode == "edit")
		{
			$_SESSION["error"]["form"]["customer_view"] = "failed";
			header("Location: ../index.php?page=customers/view.php&id=$id");
			exit(0);
		}
		else
		{
			$_SESSION["error"]["form"]["customer_add"] = "failed";
			header("Location: ../index.php?page=customers/add.php");
			exit(0);
		}
	}
	else
	{
		if ($mode == "add")
		{
			// create a new entry in the DB
			$mysql_string = "INSERT INTO `customers` (name_customer) VALUES ('".$data["name_customer"]."')";
			if (!mysql_query($mysql_string))
			{
				$_SESSION["error"]["message"][] = "A fatal SQL error occured: ". $mysql_error();
			}

			$id = mysql_insert_id();
		}

		if ($id)
		{
			// update customer details
			$mysql_string = "UPDATE `customers` SET "
						."name_customer='". $data["name_customer"] ."', "
						."name_contact='". $data["name_contact"] ."', "
						."contact_phone='". $data["contact_phone"] ."', "
						."contact_email='". $data["contact_email"] ."', "
						."contact_fax='". $data["contact_fax"] ."', "
						."date_start='". $data["date_start"] ."', "
						."date_end='". $data["date_end"] ."', "
						."tax_number='". $data["tax_number"] ."', "
						."tax_default='". $data["tax_default"] ."', "
						."address1_street='". $data["address1_street"] ."', "
						."address1_city='". $data["address1_city"] ."', "
						."address1_state='". $data["address1_state"] ."', "
						."address1_country='". $data["address1_country"] ."', "
						."address1_zipcode='". $data["address1_zipcode"] ."', "
						."pobox='". $data["pobox"] ."', "
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
					// message + journal entry
					$_SESSION["notification"]["message"][] = "Customer successfully created.";
					journal_quickadd_event("customers", $id, "Customer account created");
				}
				else
				{
					// message + journal entry
					$_SESSION["notification"]["message"][] = "Customer successfully updated.";
					journal_quickadd_event("customers", $id, "Customer's details updated");
				}
				
			}
		}


		// display updated details
		header("Location: ../index.php?page=customers/view.php&id=$id");
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
