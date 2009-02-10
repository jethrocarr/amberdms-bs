<?php
/*
	accounts/charts/edit-process.php

	access: accounts_charts_write

	Allows existing accounts to be modified or new accounts to be created
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
		Import POST Data
	*/
	
	$obj_chart->id				= security_form_input_predefined("int", "id_chart", 0, "");

	// general details
	$obj_chart->data["code_chart"]		= security_form_input_predefined("int", "code_chart", 0, "A chart code can only consist of numbers.");
	$obj_chart->data["description"]		= security_form_input_predefined("any", "description", 1, "");
	
	// only want the chart type if create a new account
	if (!$obj_chart->id)
	{
		$obj_chart->data["chart_type"]	= security_form_input_predefined("int", "chart_type", 1, "");
	}
	
	// menu selection options
	$sql_obj_menu		= New sql_query;
	$sql_obj_menu->string	= "SELECT id, value FROM `account_chart_menu`";

	$sql_obj_menu->execute();
	$sql_obj_menu->fetch_array();

	foreach ($sql_obj_menu->data as $data_menu)
	{
		$obj_chart->data["menuoptions"][ $data_menu["value"] ] = security_form_input_predefined("any", $data_menu["value"], 0, "Form provided invalid input!");
	}

	unset($sql_obj_menu);

	

	/*
		Error Handling
	*/

	// verify the account exists (if editing an existing one)
	if ($obj_chart->id)
	{
		if (!$obj_chart->verify_id())
		{
			log_write("error", "process", "The account you have attempted to edit - ". $obj_chart->id ." - does not exist in this system.");
		}
	}


		
	// make sure we don't choose a chart code number that is already in use
	if (!$obj_chart->verify_code_chart())
	{
		log_write("error", "process", "This account code has already been used by another account - please enter a unique code.");
		$_SESSION["error"]["name_chart-error"] = 1;
	}


	// return to the input page in the event of an error
	if ($_SESSION["error"]["message"])
	{	
		if ($obj_chart->id)
		{
			$_SESSION["error"]["form"]["chart_view"] = "failed";
			header("Location: ../../index.php?page=accounts/charts/view.php&id=". $obj_chart->id);
			exit(0);
		}
		else
		{
			$_SESSION["error"]["form"]["chart_add"] = "failed";
			header("Location: ../../index.php?page=accounts/charts/add.php");
			exit(0);
		}
	}


	/*
		Process Data
	*/

	$obj_chart->action_update();

	// display updated details
	header("Location: ../../index.php?page=accounts/charts/view.php&id=". $obj_chart->id);
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
