<?php
/*
	accounts/taxes/edit-process.php

	access: accounts_taxes_write

	Allows existing taxes to be adjusted or new taxes to be added.
*/

// includes
include_once("../../include/config.php");
include_once("../../include/amberphplib/main.php");


if (user_permissions_get('accounts_taxes_write'))
{
	/////////////////////////

	$id				= security_form_input_predefined("int", "id_tax", 0, "");
	
	$data["name_tax"]		= security_form_input_predefined("any", "name_tax", 1, "");
	$data["taxrate"]		= security_form_input_predefined("any", "taxrate", 1, "");
	$data["chartid"]		= security_form_input_predefined("int", "chartid", 1, "");
	$data["description"]		= security_form_input_predefined("any", "description", 1, "");
	

	// are we editing an existing account or adding a new one?
	if ($id)
	{
		$mode = "edit";

		// make sure the tax actually exists
		$mysql_string		= "SELECT id FROM `account_taxes` WHERE id='$id'";
		$mysql_result		= mysql_query($mysql_string);
		$mysql_num_rows		= mysql_num_rows($mysql_result);

		if (!$mysql_num_rows)
		{
			$_SESSION["error"]["message"][] = "The tax you have attempted to edit - $id - does not exist in this system.";
		}
	}
	else
	{
		$mode = "add";
	}


		
	//// ERROR CHECKING ///////////////////////


	// make sure we don't choose a tax code number that is already in use
	$mysql_string	= "SELECT id FROM `account_taxes` WHERE name_tax='". $data["name_tax"] ."'";
	if ($id)
		$mysql_string .= " AND id!='$id'";
	$mysql_result	= mysql_query($mysql_string);
	$mysql_num_rows	= mysql_num_rows($mysql_result);

	if ($mysql_num_rows)
	{
		$_SESSION["error"]["message"][] = "Another tax already exists with the same name - please choose a unique name.";
		$_SESSION["error"]["name_tax-error"] = 1;
	}


	// make sure the selected chart exists
	$sql_obj = New sql_query;
	$sql_obj->string = "SELECT id FROM account_charts WHERE id='". $data["chartid"] ."'";
	$sql_obj->execute();

	if (!$sql_obj->num_rows())
	{
		$_SESSION["error"]["message"][] = "The requested chart ID does not exist";
		$_SESSION["error"]["chartid-error"] = 1;
	}


	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		if ($mode == "edit")
		{
			$_SESSION["error"]["form"]["tax_view"] = "failed";
			header("Location: ../../index.php?page=accounts/taxes/view.php&id=$id");
			exit(0);
		}
		else
		{
			$_SESSION["error"]["form"]["tax_add"] = "failed";
			header("Location: ../../index.php?page=accounts/taxes/add.php");
			exit(0);
		}
	}
	else
	{
		if ($mode == "add")
		{
			// create a new entry in the DB
			$mysql_string = "INSERT INTO `account_taxes` (name_tax) VALUES ('".$data["name_tax"]."')";
			if (!mysql_query($mysql_string))
			{
				$_SESSION["error"]["message"][] = "A fatal SQL error occured: ". $mysql_error();
			}

			$id = mysql_insert_id();
		}

		if ($id)
		{
			// update tax details
			$mysql_string = "UPDATE `account_taxes` SET "
						."name_tax='". $data["name_tax"] ."', "
						."taxrate='". $data["taxrate"] ."', "
						."chartid='". $data["chartid"] ."', "
						."description='". $data["description"] ."' "
						."WHERE id='$id'";
						
			if (!mysql_query($mysql_string))
			{
				$_SESSION["error"]["message"][] = "A fatal SQL error occured: ". mysql_error();
			}
			else
			{
				if ($mode == "add")
				{
					$_SESSION["notification"]["message"][] = "Tax successfully created.";
				}
				else
				{
					$_SESSION["notification"]["message"][] = "Tax successfully updated.";
				}
				
			}
		}


		// display updated details
		header("Location: ../../index.php?page=accounts/taxes/view.php&id=$id");
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
