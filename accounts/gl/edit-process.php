<?php
/*
	accounts/transactions/edit-process.php

	access: accounts_gl_write

	Allows existing accounts to be modified or new accounts to be created
*/

// includes
include_once("../../include/config.php");
include_once("../../include/amberphplib/main.php");

// ledger functions for managing transactions
include_once("../../include/accounts/inc_ledger.php");



if (user_permissions_get('accounts_gl_write'))
{
	/////////////////////////


	$id				= security_form_input_predefined("int", "id_transaction", 0, "");

	// general details
	$data["code_gl"]		= security_form_input_predefined("any", "code_gl", 0, "");
	$data["date_trans"]		= security_form_input_predefined("date", "date_trans", 1, "");
	$data["employeeid"]		= security_form_input_predefined("any", "employeeid", 1, "");
	$data["description"]		= security_form_input_predefined("any", "description", 1, "");
	$data["description_useall"]	= security_form_input_predefined("any", "description_useall", 0, "");
	$data["notes"]			= security_form_input_predefined("any", "notes", 0, "");
	

	// transaction rows
	$data["num_trans"]		= security_form_input_predefined("int", "num_trans", $required, "");

	for ($i = 0; $i < $data["num_trans"]; $i++)
	{
		$data["trans"][$i]["account"]		= security_form_input_predefined("int", "trans_". $i ."_account", 0, "");
		$data["trans"][$i]["debit"]		= security_form_input_predefined("money", "trans_". $i ."_debit", 0, "");
		$data["trans"][$i]["credit"]		= security_form_input_predefined("money", "trans_". $i ."_credit", 0, "");
		$data["trans"][$i]["source"]		= security_form_input_predefined("any", "trans_". $i ."_source", 0, "");
		$data["trans"][$i]["description"]	= security_form_input_predefined("any", "trans_". $i ."_description", 0, "");


		// if enabled, overwrite any description fields of transactions with the master one
		if ($data["description_useall"] == "on")
		{
			$data["trans"][$i]["description"] = $data["description"];
		}


		// make sure both account and an amount have been supplied together
		if ($data["trans"][$i]["account"] && !$data["trans"][$i]["debit"] && !$data["trans"][$i]["credit"] )
		{
			$_SESSION["error"]["message"][] = "You must supply both either a debit or credit value along with the account";
			$_SESSION["error"]["trans_". $i ."-error"] = 1;
		}

		if ($data["trans"][$i]["debit"] != "0.00" || $data["trans"][$i]["credit"] != "0.00")
		{
//			print "debug -- debit: ". $data["trans"][$i]["debit"] .", credit: ". $data["trans"][$i]["credit"];

			if (!$data["trans"][$i]["account"])
			{
				$_SESSION["error"]["message"][] = "You must supply both an amount and select an account for each transaction row";
				$_SESSION["error"]["trans_". $i ."-error"] = 1;
			}
		}
	}



	// are we editing an existing transaction or adding a new one?
	if ($id)
	{
		$mode = "edit";

		// make sure the transaction actually exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM `account_gl` WHERE id='$id'";
		$sql_obj->execute();
		
		if (!$sql_obj->num_rows())
		{
			$_SESSION["error"]["message"][] = "The transaction you have attempted to edit - $id - does not exist in this system.";
		}
	}
	else
	{
		$mode = "add";
	}


		
	//// ERROR CHECKING ///////////////////////


	// make sure we don't choose a transaction code number that is already in use
	if ($data["code_gl"])
	{
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM `account_gl` WHERE code_gl='". $data["code_gl"] ."'";
		
		if ($id)
			$sql_obj->string .= " AND id!='$id'";
			
		$sql_obj->execute();
		
		if ($sql_obj->num_rows())
		{
			$_SESSION["error"]["message"][] = "This transaction ID/code has already been used by another transaction - please enter a unique code or leave blank to recieve an automaticly assigned one.";
			$_SESSION["error"]["code_gl-error"] = 1;
		}
	}


	// add transaction rows to total credits and debits
	for ($i = 0; $i < $data["num_trans"]; $i++)
	{
		$data["total_credit"]	+= $data["trans"][$i]["credit"];
		$data["total_debit"]	+= $data["trans"][$i]["debit"];
	}

	// total credit and debits need to match up
	if ($data["total_credit"] != $data["total_debit"])
	{
		$_SESSION["error"]["message"][] = "The total credits and total debits need to be equal before the transaction can be saved.";
	}

	// make sure some values have been supplied
	if ($data["total_credit"] == "0.00" && $data["total_debit"] == "0.00")
	{
		$_SESSION["error"]["message"][] = "You must enter some transaction information before you can save this transaction.";
		$_SESSION["error"]["trans_0-error"] = 1;
		$_SESSION["error"]["trans_1-error"] = 1;
	}

	// pad values
	$data["total_credit"]			= sprintf("%0.2f", $data["total_credit"]);
	$data["total_debit"]			= sprintf("%0.2f", $data["total_debit"]);

	// set returns
	$_SESSION["error"]["total_credit"]	= $data["total_credit"];
	$_SESSION["error"]["total_debit"]	= $data["total_debit"];


	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		if ($mode == "edit")
		{
			$_SESSION["error"]["form"]["transaction_view"] = "failed";
			header("Location: ../../index.php?page=accounts/gl/view.php&id=$id");
			exit(0);
		}
		else
		{
			$_SESSION["error"]["form"]["transaction_add"] = "failed";
			header("Location: ../../index.php?page=accounts/gl/add.php");
			exit(0);
		}
	}
	else
	{
		// if required, generate a new unique ID
		if (!$date["code_gl"])
		{
			$data["code_gl"] = config_generate_uniqueid("ACCOUNTS_GL_TRANSNUM", "SELECT id FROM account_gl WHERE code_gl='VALUE'");
		}



		/*
			Save general fields
		*/
		if ($mode == "add")
		{
			// create a new entry in the DB
			$sql_obj		= New sql_query;
			$sql_obj->string	= "INSERT INTO `account_gl` (code_gl) VALUES ('".$data["code_gl"]."')";

			
			if (!$sql_obj->execute())
			{
				$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst attempting to create the new transaction.";
			}

			$id = $sql_obj->fetch_insert_id();
		}

		if ($id)
		{
			// update transaction details
			$sql_obj		= New sql_query;
			$sql_obj->string	= "UPDATE `account_gl` SET "
							."code_gl='". $data["code_gl"] ."', "
							."date_trans='". $data["date_trans"] ."', "
							."employeeid='". $data["employeeid"] ."', "
							."description='". $data["description"] ."', "
							."notes='". $data["notes"] ."' "
							."WHERE id='$id'";
						
			if (!$sql_obj->execute())
			{
				$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst updating the transaction.";
			}
			else
			{
				if ($mode == "add")
				{
					$_SESSION["notification"]["message"][] = "Transaction successfully created.";
				}
				else
				{
					$_SESSION["notification"]["message"][] = "Transaction successfully updated.";
				}
				
			}

		}


		/*
			Create transaction items/rows
		*/

		// delete existing transactions
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM account_trans WHERE type='gl' AND customid='$id'";
		$sql_obj->execute();
	

		// add all valid transactions
		for ($i = 0; $i < $data["num_trans"]; $i++)
		{
			if ($data["trans"][$i]["account"])
			{
				if ($data["trans"][$i]["debit"] != "0.00")
				{
					ledger_trans_add("debit", "gl", $id, $data["date_trans"], $data["trans"][$i]["account"], $data["trans"][$i]["debit"], $data["trans"][$i]["source"], $data["trans"][$i]["description"]);
				}
				else
				{
					ledger_trans_add("credit", "gl", $id, $data["date_trans"], $data["trans"][$i]["account"], $data["trans"][$i]["credit"], $data["trans"][$i]["source"], $data["trans"][$i]["description"]);
				}
			}
		}

		
		

		// display updated details
		header("Location: ../../index.php?page=accounts/gl/view.php&id=$id");
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
