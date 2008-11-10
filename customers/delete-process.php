<?php
/*
	products/delete-process.php

	access: customers_write

	Deletes a customer provided that the customer has not been added to any invoices or time groups.
*/

// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");


if (user_permissions_get('customers_write'))
{
	/////////////////////////

	$id				= security_form_input_predefined("int", "id_customer", 1, "");

	// these exist to make error handling work right
	$data["name_customer"]		= security_form_input_predefined("any", "name_customer", 0, "");

	// confirm deletion
	$data["delete_confirm"]		= security_form_input_predefined("any", "delete_confirm", 1, "You must confirm the deletion");

	
	// make sure the customer actually exists
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM `customers` WHERE id='$id'";
	$sql_obj->execute();
	
	if (!$sql_obj->num_rows())
	{
		$_SESSION["error"]["message"][] = "The customer you have attempted to edit - $id - does not exist in this system.";
	}


		
	//// ERROR CHECKING ///////////////////////
			

	// make sure customer does not belong to any invoices
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM account_ar WHERE customerid='$id'";
	$sql_obj->execute();

	if ($sql_obj->num_rows())
	{
		$_SESSION["error"]["message"][] = "You are not able to delete this customer because it has been added to an invoice.";
	}

	// make sure customer has no time groups assigned to it
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM time_groups WHERE customerid='$id'";
	$sql_obj->execute();

	if ($sql_obj->num_rows())
	{
		$_SESSION["error"]["message"][] = "You are not able to delete this customer because it has been added to an time group;.";
	}


	

	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"]["customer_delete"] = "failed";
		header("Location: ../index.php?page=customers/delete.php&id=$id");
		exit(0);
		
	}
	else
	{

		/*
			Delete Customer
		*/
			
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM customers WHERE id='$id'";
			
		if (!$sql_obj->execute())
		{
			$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst trying to delete the customer";
		}
		else
		{		
			$_SESSION["notification"]["message"][] = "Customer has been successfully deleted.";
		}



		/*
			Delete Journal
		*/
		journal_delete_entire("customers", $id);



		// return to products list
		header("Location: ../index.php?page=customers/customers.php");
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
