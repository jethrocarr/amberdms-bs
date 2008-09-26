<?php
/*
	accounts/charts/edit-process.php

	access: accounts_charts_write

	Allows existing accounts to be modified or new accounts to be created
*/

// includes
include_once("../../include/config.php");
include_once("../../include/amberphplib/main.php");


if (user_permissions_get('accounts_charts_write'))
{
	/////////////////////////

	$id				= security_form_input_predefined("int", "id_chart", 0, "");
	
	$data["code_chart"]		= security_form_input_predefined("int", "code_chart", 1, "A chart code must be supplied and can only consist of numbers.");
	$data["description"]		= security_form_input_predefined("any", "description", 1, "");
	$data["chart_type"]		= security_form_input_predefined("int", "chart_type", 1, "");
	$data["chart_category"]		= security_form_input_predefined("any", "chart_category", 1, "");
	

	// are we editing an existing account or adding a new one?
	if ($id)
	{
		$mode = "edit";

		// make sure the account actually exists
		$mysql_string		= "SELECT id FROM `account_charts` WHERE id='$id'";
		$mysql_result		= mysql_query($mysql_string);
		$mysql_num_rows		= mysql_num_rows($mysql_result);

		if (!$mysql_num_rows)
		{
			$_SESSION["error"]["message"][] = "The account you have attempted to edit - $id - does not exist in this system.";
		}
	}
	else
	{
		$mode = "add";
	}


		
	//// ERROR CHECKING ///////////////////////


	// make sure we don't choose a chart code number that is already in use
	$mysql_string	= "SELECT id FROM `account_charts` WHERE code_chart='". $data["code_chart"] ."'";
	if ($id)
		$mysql_string .= " AND id!='$id'";
	$mysql_result	= mysql_query($mysql_string);
	$mysql_num_rows	= mysql_num_rows($mysql_result);

	if ($mysql_num_rows)
	{
		$_SESSION["error"]["message"][] = "This account code has already been used by another account - please enter a unique code.";
		$_SESSION["error"]["name_chart-error"] = 1;
	}


	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		if ($mode == "edit")
		{
			$_SESSION["error"]["form"]["chart_view"] = "failed";
			header("Location: ../../index.php?page=accounts/charts/view.php&id=$id");
			exit(0);
		}
		else
		{
			$_SESSION["error"]["form"]["chart_add"] = "failed";
			header("Location: ../../index.php?page=accounts/charts/add.php");
			exit(0);
		}
	}
	else
	{
		if ($mode == "add")
		{
			// create a new entry in the DB
			$mysql_string = "INSERT INTO `account_charts` (code_chart) VALUES ('".$data["code_chart"]."')";
			if (!mysql_query($mysql_string))
			{
				$_SESSION["error"]["message"][] = "A fatal SQL error occured: ". $mysql_error();
			}

			$id = mysql_insert_id();
		}

		if ($id)
		{
			// update chart details
			$mysql_string = "UPDATE `account_charts` SET "
						."code_chart='". $data["code_chart"] ."', "
						."description='". $data["description"] ."', "
						."chart_type='". $data["chart_type"] ."', "
						."chart_category='". $data["chart_category"] ."' "
						."WHERE id='$id'";
						
			if (!mysql_query($mysql_string))
			{
				$_SESSION["error"]["message"][] = "A fatal SQL error occured: ". mysql_error();
			}
			else
			{
				if ($mode == "add")
				{
					$_SESSION["notification"]["message"][] = "Account successfully created.";
				}
				else
				{
					$_SESSION["notification"]["message"][] = "Account successfully updated.";
				}
				
			}
		}


		// display updated details
		header("Location: ../../index.php?page=accounts/charts/view.php&id=$id");
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
