<?php
/*
	accounts/charts/delete-process.php

	access: account_charts_write

	Deletes an account provided that the account has not been added to any invoices.
*/

// includes
require("../../include/config.php");
require("../../include/amberphplib/main.php");

// custom includes
require("../../include/accounts/inc_charts.php");


if (user_permissions_get('accounts_charts_write'))
{
	$obj_chart = New chart;


	/*
		Load POST Data
	*/

	$obj_chart->id			= security_form_input_predefined("int", "id_chart", 1, "");

	// these exist to make error handling work right
	$data["code_chart"]		= security_form_input_predefined("any", "code_chart", 0, "");
	$data["description"]		= security_form_input_predefined("any", "description", 0, "");

	// confirm deletion
	$data["delete_confirm"]		= security_form_input_predefined("any", "delete_confirm", 1, "You must confirm the deletion");



	/*
	
		Error Handling
	*/
	
	// make sure the chart actually exists
	if (!$obj_chart->verify_id())
	{
		log_write("error", "process", "The account you have attempted to edit - ". $obj_chart->id ." - does not exist in this system.");
	}


	// make sure chart is safe to delete
	if ($obj_chart->check_delete_lock())
	{
		log_write("error", "process", "The account can not be deleted because it is locked.");
	}



	// return to the input page in event of an error
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"]["chart_delete"] = "failed";
		header("Location: ../../index.php?page=accounts/charts/delete.php&id=". $obj_chart->id);
		exit(0);
	}



	/*
		Delete Account
	*/

	$obj_chart->action_delete();
			
	// return to chart of accounts
	header("Location: ../../index.php?page=accounts/charts/charts.php");
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
