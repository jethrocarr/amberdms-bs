<?php
/*
	products/delete-process.php

	access: vendors_write

	Deletes a vendor provided that the vendor has not been added to any invoices.
*/

// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");


if (user_permissions_get('vendors_write'))
{
	/////////////////////////

	$id				= security_form_input_predefined("int", "id_vendor", 1, "");

	// these exist to make error handling work right
	$data["name_vendor"]		= security_form_input_predefined("any", "name_vendor", 0, "");

	// confirm deletion
	$data["delete_confirm"]		= security_form_input_predefined("any", "delete_confirm", 1, "You must confirm the deletion");

	
	// make sure the vendor actually exists
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM `vendors` WHERE id='$id'";
	$sql_obj->execute();
	
	if (!$sql_obj->num_rows())
	{
		$_SESSION["error"]["message"][] = "The vendor you have attempted to edit - $id - does not exist in this system.";
	}


		
	//// ERROR CHECKING ///////////////////////
			

	// make sure vendor does not belong to any AP invoices
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM account_ap WHERE vendorid='$id'";
	$sql_obj->execute();

	if ($sql_obj->num_rows())
	{
		$_SESSION["error"]["message"][] = "You are not able to delete this vendor because it has been added to an invoice.";
	}
	

	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"]["vendor_delete"] = "failed";
		header("Location: ../index.php?page=vendors/delete.php&id=$id");
		exit(0);
		
	}
	else
	{

		/*
			Delete Customer
		*/
			
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM vendors WHERE id='$id'";
			
		if (!$sql_obj->execute())
		{
			$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst trying to delete the vendor";
		}
		else
		{		
			$_SESSION["notification"]["message"][] = "Vendor has been successfully deleted.";
		}



		/*
			Delete Journal
		*/
		journal_delete_entire("vendors", $id);



		// return to products list
		header("Location: ../index.php?page=vendors/vendors.php");
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
