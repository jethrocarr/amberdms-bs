<?php
/*
	taxes/delete-process.php

	access: account_taxes_write

	Deletes a tax provided that the tax has not been added to any invoices.
*/

// includes
include_once("../../include/config.php");
include_once("../../include/amberphplib/main.php");


if (user_permissions_get('accounts_taxes_write'))
{
	/////////////////////////

	$id				= security_form_input_predefined("int", "id_tax", 1, "");

	// these exist to make error handling work right
	$data["name_tax"]		= security_form_input_predefined("any", "name_tax", 0, "");

	// confirm deletion
	$data["delete_confirm"]		= security_form_input_predefined("any", "delete_confirm", 1, "You must confirm the deletion");

	
	// make sure the tax actually exists
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM `account_taxes` WHERE id='$id'";
	$sql_obj->execute();
	
	if (!$sql_obj->num_rows())
	{
		$_SESSION["error"]["message"][] = "The tax you have attempted to edit - $id - does not exist in this system.";
	}


		
	//// ERROR CHECKING ///////////////////////


	// make sure tax does not belong to any invoices
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM account_items WHERE type='tax' AND customid='$id'";
	$sql_obj->execute();

	if ($sql_obj->num_rows())
	{
		$_SESSION["error"]["message"][] = "You are not able to delete this tax because it has been added to an invoice.";
	}


	
	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"]["tax_delete"] = "failed";
		header("Location: ../../index.php?page=accounts/taxes/delete.php&id=$id");
		exit(0);
		
	}
	else
	{

		/*
			Delete Customer
		*/
			
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM account_taxes WHERE id='$id'";
			
		if (!$sql_obj->execute())
		{
			$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst trying to delete the tax";
		}
		else
		{		
			$_SESSION["notification"]["message"][] = "Tax has been successfully deleted.";
		}


		// return to taxes list
		header("Location: ../../index.php?page=accounts/taxes/taxes.php");
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
