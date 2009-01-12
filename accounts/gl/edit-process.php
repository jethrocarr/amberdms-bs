<?php
/*
	accounts/transactions/edit-process.php

	access: accounts_gl_write

	Allows existing accounts to be modified or new accounts to be created
*/

// includes
require("../../include/config.php");
require("../../include/amberphplib/main.php");

// custom includes
require("../../include/accounts/inc_ledger.php");
require("../../include/accounts/inc_gl.php");



if (user_permissions_get('accounts_gl_write'))
{
	$obj_gl = New gl_transaction;



	/*
		Import POST Data
	*/

	$obj_gl->id				= security_form_input_predefined("int", "id_transaction", 0, "");

	// general details
	$obj_gl->data["code_gl"]		= security_form_input_predefined("any", "code_gl", 0, "");
	$obj_gl->data["date_trans"]		= security_form_input_predefined("date", "date_trans", 1, "");
	$obj_gl->data["employeeid"]		= security_form_input_predefined("any", "employeeid", 1, "");
	$obj_gl->data["description"]		= security_form_input_predefined("any", "description", 1, "");
	$obj_gl->data["description_useall"]	= security_form_input_predefined("any", "description_useall", 0, "");
	$obj_gl->data["notes"]			= security_form_input_predefined("any", "notes", 0, "");
	

	// transaction rows
	$obj_gl->data["num_trans"]		= security_form_input_predefined("int", "num_trans", $required, "");

	for ($i = 0; $i < $obj_gl->data["num_trans"]; $i++)
	{
		$obj_gl->data["trans"][$i]["account"]		= security_form_input_predefined("int", "trans_". $i ."_account", 0, "");
		$obj_gl->data["trans"][$i]["debit"]		= security_form_input_predefined("money", "trans_". $i ."_debit", 0, "");
		$obj_gl->data["trans"][$i]["credit"]		= security_form_input_predefined("money", "trans_". $i ."_credit", 0, "");
		$obj_gl->data["trans"][$i]["source"]		= security_form_input_predefined("any", "trans_". $i ."_source", 0, "");
		$obj_gl->data["trans"][$i]["description"]	= security_form_input_predefined("any", "trans_". $i ."_description", 0, "");


		// make sure both account and an amount have been supplied together
		if ($obj_gl->data["trans"][$i]["account"] && !$obj_gl->data["trans"][$i]["debit"] && !$obj_gl->data["trans"][$i]["credit"] )
		{
			$_SESSION["error"]["message"][] = "You must supply both either a debit or credit value along with the account";
			$_SESSION["error"]["trans_". $i ."-error"] = 1;
		}


		// make sure an account has been supplied
		if ($obj_gl->data["trans"][$i]["debit"] != "0.00" || $obj_gl->data["trans"][$i]["credit"] != "0.00")
		{

			if (!$obj_gl->data["trans"][$i]["account"])
			{
				$_SESSION["error"]["message"][] = "You must supply both an amount and select an account for each transaction row";
				$_SESSION["error"]["trans_". $i ."-error"] = 1;
			}
		}


		// make sure that both debit and credit are not set for one transaction
		if ($obj_gl->data["trans"][$i]["debit"] > 0 && $obj_gl->data["trans"][$i]["credit"] > 0)
		{
			$_SESSION["error"]["message"][] = "One transaction row can not have both credit and debit amounts";
			$_SESSION["error"]["trans_". $i ."-error"] = 1;
		}
	}


	



	/*
		Error Handling
	*/


	// verify transaction exists (when editing an existing transaction)
	if ($obj_gl->id)
	{
		if (!$obj_gl->verify_id())
		{
			log_write("error", "process", "The transaction you have attempted to edit - ". $obj_gl->id ." - does not exist in this system.");
		}

		// check if transaction is locked
		if ($obj_gl->check_lock())
		{
			log_write("error", "process", "The transaction you have attempted to edit is locked and can not be modified.");
		}
	}



	// make sure we don't choose a transaction code number that is already in use
	if ($obj_gl->data["code_gl"])
	{
		if (!$obj_gl->verify_code_gl())
		{
			log_write("error", "process", "This transaction ID/code has already been used by another transaction - please enter a unique code or leave blank to recieve an automaticly assigned one.");
			$_SESSION["error"]["code_gl-error"] = 1;
		}
	}


	// verify transaction data
	if ($obj_gl->data["num_trans"])
	{
		if (!$obj_gl->verify_valid_trans())
		{
			// set returns
			$_SESSION["error"]["total_credit"]	= $obj_gl->data["total_credit"];
			$_SESSION["error"]["total_debit"]	= $obj_gl->data["total_debit"];

			// highlight the first two transaction rows
			$_SESSION["error"]["trans_0-error"] = 1;
			$_SESSION["error"]["trans_1-error"] = 1;
		}
	}


	// return to input page in event of an error
	if ($_SESSION["error"]["message"])
	{
		if ($obj_gl->id)
		{
			$_SESSION["error"]["form"]["transaction_view"] = "failed";
			header("Location: ../../index.php?page=accounts/gl/view.php&id=". $obj_gl->id);
			exit(0);
		}
		else
		{
			$_SESSION["error"]["form"]["transaction_add"] = "failed";
			header("Location: ../../index.php?page=accounts/gl/add.php");
			exit(0);
		}
	}



	/*
		Update Database
	*/

	$obj_gl->action_update();


	// display updated details
	header("Location: ../../index.php?page=accounts/gl/view.php&id=". $obj_gl->id);
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
