<?php
/*
	accounts/charts/delete-process.php

	access: account_charts_write

	Deletes an account provided that the account has not been added to any invoices.
*/

// includes
include_once("../../include/config.php");
include_once("../../include/amberphplib/main.php");


if (user_permissions_get('accounts_charts_write'))
{
	/////////////////////////

	$id				= security_form_input_predefined("int", "id_chart", 1, "");

	// these exist to make error handling work right
	$data["code_chart"]		= security_form_input_predefined("any", "code_chart", 0, "");
	$data["description"]		= security_form_input_predefined("any", "description", 0, "");

	// confirm deletion
	$data["delete_confirm"]		= security_form_input_predefined("any", "delete_confirm", 1, "You must confirm the deletion");

	
	// make sure the chart actually exists
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM `account_charts` WHERE id='$id'";
	$sql_obj->execute();
	
	if (!$sql_obj->num_rows())
	{
		$_SESSION["error"]["message"][] = "The account you have attempted to edit - $id - does not exist in this system.";
	}


		
	//// ERROR CHECKING ///////////////////////

	// make sure chart has no transactions in it
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM account_trans WHERE chartid='$id'";
	$sql_obj->execute();

	if ($sql_obj->num_rows())
	{
		$_SESSION["error"]["message"][] = "This account can not be deleted since it has transactions belonging to it";
	}
			
	// make sure chart has no items belonging to it
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM account_items WHERE chartid='$id'";
	$sql_obj->execute();

	if ($sql_obj->num_rows())
	{
		$_SESSION["error"]["message"][] = "This account can not be deleted since it has invoice items belonging to it";
	}
			


	
	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"]["chart_delete"] = "failed";
		header("Location: ../../index.php?page=accounts/charts/delete.php&id=$id");
		exit(0);
		
	}
	else
	{

		/*
			Delete Account
		*/
			
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM account_charts WHERE id='$id'";
			
		if (!$sql_obj->execute())
		{
			$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst trying to delete the account";
		}
		else
		{		
			$_SESSION["notification"]["message"][] = "Account has been successfully deleted.";
		}


		// return to chart of accounts
		header("Location: ../../index.php?page=accounts/charts/charts.php");
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
