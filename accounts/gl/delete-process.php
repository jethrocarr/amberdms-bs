<?php
/*
	accounts/gl/delete-process.php

	access: account_gl_write

	Deletes a transaction, provided that it has not been locked.
*/

// includes
include_once("../../include/config.php");
include_once("../../include/amberphplib/main.php");


if (user_permissions_get('accounts_gl_write'))
{
	/////////////////////////

	$id				= security_form_input_predefined("int", "id_transaction", 1, "");

	// these exist to make error handling work right
	$data["code_gl"]		= security_form_input_predefined("any", "code_gl", 0, "");
	$data["description"]		= security_form_input_predefined("any", "description", 0, "");

	// confirm deletion
	$data["delete_confirm"]		= security_form_input_predefined("any", "delete_confirm", 1, "You must confirm the deletion");



	//// ERROR CHECKING ///////////////////////


	// make sure the transaction actually exists
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id, locked FROM `account_gl` WHERE id='$id' LIMIT 1";
	$sql_obj->execute();
	
	if (!$sql_obj->num_rows())
	{
		$_SESSION["error"]["message"][] = "The transaction you have attempted to edit - $id - does not exist in this system.";
	}
	else
	{
		$sql_obj->fetch_array();

		if ($sql_obj->data[0]["locked"])
		{
			$_SESSION["error"]["message"][] = "This transaction can not be deleted, because it is now locked";
		}
	}

	
	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"]["transaction_delete"] = "failed";
		header("Location: ../../index.php?page=accounts/gl/delete.php&id=$id");
		exit(0);
		
	}
	else
	{
		/*
			Delete general ledger details
		*/
			
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM account_gl WHERE id='$id'";
			
		if (!$sql_obj->execute())
		{
			$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst trying to delete the transaction";
		}


		/*
			Delete transaction items
		*/
		
		$sql_obj		= New sql_query();
		$sql_obj->string	= "DELETE FROM account_trans WHERE type='gl' AND customid='$id'";
		$sql_obj->execute();
		
		if (!$sql_obj->execute())
		{
			$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst trying to delete the transaction items.";
		}




		/*
			Complete
		*/
		
		if (!$_SESSION["error"]["message"])
		{
			$_SESSION["notification"]["message"][] = "Transaction has been successfully deleted.";
		}
		
		header("Location: ../../index.php?page=accounts/gl/gl.php");
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
