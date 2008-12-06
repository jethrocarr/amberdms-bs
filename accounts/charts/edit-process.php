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

	// general details
	$data["code_chart"]		= security_form_input_predefined("int", "code_chart", 0, "A chart code can only consist of numbers.");
	$data["description"]		= security_form_input_predefined("any", "description", 1, "");
	$data["chart_type"]		= security_form_input_predefined("int", "chart_type", 1, "");
	
	// menu selection options
	$menu_options = array();
	$sql_obj_menu = New sql_query;
	$sql_obj_menu->string = "SELECT id, value FROM `account_chart_menu`";
	$sql_obj_menu->execute();
	$sql_obj_menu->fetch_array();

	foreach ($sql_obj_menu->data as $data_menu)
	{
		$menu_options[ $data_menu["value"] ] = security_form_input_predefined("any", $data_menu["value"], 0, "Form provided invalid input!");
	}

	
	

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
	if ($data["code_chart"])
	{
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
		if (!$data["code_chart"])
		{
			// generate a unique chart code
			$data["code_chart"] = config_generate_uniqueid("CODE_ACCOUNT", "SELECT id FROM account_charts WHERE code_chart='VALUE'");
		}

	
		// APPLY GENERAL OPTIONS
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
						."chart_type='". $data["chart_type"] ."' "
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

			/*
				APPLY MENU SELECTION OPTIONS
		
				This takes quite a few mysql calls, as we need to remove old permissions
				and add new ones on a one-by-one basis.

				TODO: This code could be optimised to be a bit more efficent with it's SQL queries.
			*/

			foreach ($sql_obj_menu->data as $data_menu)
			{
				// check if any current settings exist
				$sql_obj = New sql_query;
				$sql_obj->string = "SELECT id FROM account_charts_menus WHERE chartid='$id' AND menuid='". $data_menu["id"] ."'";
				$sql_obj->execute();

				
				if ($sql_obj->num_rows())
				{
					// chart has this menu option set

					// if the new setting is "off", delete the current setting.
					if ($menu_options[ $data_menu["value"] ] != "on")
					{
						$sql_obj = New sql_query;
						$sql_obj->string = "DELETE FROM account_charts_menus WHERE chartid='$id' AND menuid='". $data_menu["id"] ."'";
						$sql_obj->execute();
					}

					// if new setting is "on", we don't need todo anything.

				}
				else
				{	// no current option exists

					// if the new option is "on", insert a new entry
					if ($menu_options[ $data_menu["value"] ] == "on")
					{
						$sql_obj = New sql_query;
						$sql_obj->string = "INSERT INTO account_charts_menus (chartid, menuid) VALUES ('$id', '". $data_menu["id"] ."')";
						$sql_obj->execute();
					}

					// if new option is "off", we don't need todo anything.
				}
				
			} // end of loop through menu items

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
