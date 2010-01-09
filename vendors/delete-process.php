<?php
/*
	vendors/delete-process.php

	access: vendors_write

	Deletes a vendor provided that the vendor has not been assigned to any products or invoices.
*/

// includes
require("../include/config.php");
require("../include/amberphplib/main.php");

// custom includes
require("../include/vendors/inc_vendors.php");


if (user_permissions_get('vendors_write'))
{
	$obj_vendor = New vendor;


	/*
		Load POST Data
	*/

	$obj_vendor->id			= @security_form_input_predefined("int", "id_vendor", 1, "");

	// these exist to make error handling work right
	$data["name_vendor"]		= @security_form_input_predefined("any", "name_vendor", 0, "");

	// confirm deletion
	$data["delete_confirm"]		= @security_form_input_predefined("any", "delete_confirm", 1, "You must confirm the deletion");



	/*
		Error Handling
	*/
	
	// make sure the vendor actually exists
	if (!$obj_vendor->verify_id())
	{
		log_write("error", "process", "The vendor you have attempted to edit - ". $obj_vendor->id ." - does not exist in this system.");
	}


	// make sure vendor can be safely deleted
	if ($obj_vendor->check_delete_lock())
	{
		log_write("error", "process", "The vendor can not be deleted since there are invoices or products assigned to the vendor.");
	}


	// return to the entry page in the event of an error
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"]["vendor_delete"] = "failed";
		header("Location: ../index.php?page=vendors/delete.php&id=". $obj_vendor->id);
		exit(0);
	}



	/*
		Delete Vendor
	*/

	// perform delete action
	$obj_vendor->action_delete();

	// return to products list
	header("Location: ../index.php?page=vendors/vendors.php");
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
