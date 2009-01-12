<?php
/*
	accounts/taxes/edit-process.php

	access: accounts_taxes_write

	Allows existing taxes to be adjusted or new taxes to be added.
*/

// includes
require("../../include/config.php");
require("../../include/amberphplib/main.php");

// custom includes
require("../../include/accounts/inc_taxes.php");


if (user_permissions_get('accounts_taxes_write'))
{
	$obj_tax = New tax;


	/*
		Import POST Data
	*/

	$obj_tax->id				= security_form_input_predefined("int", "id_tax", 0, "");
	
	$obj_tax->data["name_tax"]		= security_form_input_predefined("any", "name_tax", 1, "");
	$obj_tax->data["taxrate"]		= security_form_input_predefined("any", "taxrate", 1, "");
	$obj_tax->data["chartid"]		= security_form_input_predefined("int", "chartid", 1, "");
	$obj_tax->data["taxnumber"]		= security_form_input_predefined("any", "taxnumber", 1, "");
	$obj_tax->data["description"]		= security_form_input_predefined("any", "description", 1, "");
	


	/*
		Error Handling
	*/


	if ($obj_tax->id)
	{
		// make sure the tax actually exists
		if (!$obj_tax->verify_id())
		{
			log_write("error", "process", "The tax you have attempted to edit - ". $obj_tax->id ." - does not exist in this system.");
		}
	}


		
	// make sure we don't choose a tax name that is already in use
	if (!$obj_tax->verify_name_tax())
	{
		log_write("error", "process", "Another tax already exists with the same name - please choose a unique name.");
		$_SESSION["error"]["name_tax-error"] = 1;
	}


	// make sure the selected chart exists
	if (!$obj_tax->verify_valid_chart())
	{
		log_write("error", "process", "The requested chart ID does not exist");
		$_SESSION["error"]["chartid-error"] = 1;
	}


	// return to input page in the event of an error
	if ($_SESSION["error"]["message"])
	{
		if ($obj_tax->id)
		{
			$_SESSION["error"]["form"]["tax_view"] = "failed";
			header("Location: ../../index.php?page=accounts/taxes/view.php&id=". $obj_tax->id);
			exit(0);
		}
		else
		{
			$_SESSION["error"]["form"]["tax_add"] = "failed";
			header("Location: ../../index.php?page=accounts/taxes/add.php");
			exit(0);
		}
	}



	/*
		Apply Changes
	*/

	$obj_tax->action_update();


	// display updated details
	header("Location: ../../index.php?page=accounts/taxes/view.php&id=". $obj_tax->id);
	exit(0);
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
