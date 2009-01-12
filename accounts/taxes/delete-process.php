<?php
/*
	taxes/delete-process.php

	access: account_taxes_write

	Deletes a tax provided that the tax has not been added to any invoices.
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

	$obj_tax->id				= security_form_input_predefined("int", "id_tax", 1, "");

	// these exist to make error handling work right
	$obj_tax->data["name_tax"]		= security_form_input_predefined("any", "name_tax", 0, "");

	// confirm deletion
	$obj_tax->data["delete_confirm"]	= security_form_input_predefined("any", "delete_confirm", 1, "You must confirm the deletion");



	/*
		Error Handling
	*/

	// make sure the tax actually exists
	if (!$obj_tax->verify_id())
	{
		log_write("error", "process", "The tax you have attempted to edit - ". $obj_tax->id ." - does not exist in this system.");
	}


	// make sure tax is safe to delete
	if ($obj_tax->check_delete_lock())
	{
		log_write("error", "process", "This tax can not be deleted due to it being used by invoices.");
	}


	// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"]["tax_delete"] = "failed";
		header("Location: ../../index.php?page=accounts/taxes/delete.php&id=". $obj_tax->id);
		exit(0);
		
	}



	/*
		Apply Changes
	*/


	$obj_tax->action_delete();


	// return to taxes list
	header("Location: ../../index.php?page=accounts/taxes/taxes.php");
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
