<?php
/*
	accounts/ar/transactions-edit-process.php

	access: accounts_transactions_write

	Allows existing accounts to be modified or new accounts to be created. This page
	also provides the update feature for re-calculating totals on transactions before they are saved
*/

// includes
require("../../include/config.php");
require("../../include/amberphplib/main.php");

// custom includes
require("../../include/accounts/inc_transactions.php");



if (user_permissions_get('accounts_ar_write'))
{
	/////////////////////////

	/*
		Fetch all form data
	*/

	$id				= security_form_input_predefined("int", "id_transaction", 0, "");

	// action type
	$data["action"]			= security_form_input_predefined("any", "action", 1, "");


	// we only require input when we do a save, for an update we just want to query
	if ($data["action"] == "save")
	{
		$required = 1;
	}
	else
	{
		$required = 0;
	}


	// general details
	$data["customerid"]		= security_form_input_predefined("int", "customerid", $required, "");
	$data["employeeid"]		= security_form_input_predefined("int", "employeeid", $required, "");
	$data["notes"]			= security_form_input_predefined("any", "notes", 0, "");
	
	$data["code_invoice"]		= security_form_input_predefined("any", "code_invoice", 0, "");
	$data["code_ordernumber"]	= security_form_input_predefined("any", "code_ordernumber", 0, "");
	$data["code_ponumber"]		= security_form_input_predefined("any", "code_ponumber", 0, "");
	$data["date_transaction"]	= security_form_input_predefined("date", "date_transaction", $required, "");
	$data["date_due"]		= security_form_input_predefined("date", "date_due", $required, "");

	// transaction(s)
	$data["num_trans"]		= security_form_input_predefined("int", "num_trans", $required, "");

	for ($i = 0; $i < $data["num_trans"]; $i++)
	{
		$data["trans"][$i]["account"]		= security_form_input_predefined("int", "trans_". $i ."_account", 0, "");
		$data["trans"][$i]["amount"]		= security_form_input_predefined("any", "trans_". $i ."_amount", 0, "");
		$data["trans"][$i]["description"]	= security_form_input_predefined("any", "trans_". $i ."_description", 0, "");

		if ($data["trans"][$i]["amount"])
			$_SESSION["error"]["trans_". $i ."_amount"] = sprintf("%0.2f", $data["trans"][$i]["amount"]);

		// make sure both and an amount have been supplied together
		if ($required)
		{
			if ($data["trans"][$i]["account"] && !$data["trans"][$i]["amount"])
			{
				$_SESSION["error"]["message"][] = "You must supply both an amount and select an account for each transaction row";
				$_SESSION["error"]["trans_". $i ."-error"] = 1;
			}

			if ($data["trans"][$i]["amount"] && !$data["trans"][$i]["account"])
			{
				$_SESSION["error"]["message"][] = "You must supply both an amount and select an account for each transaction row";
				$_SESSION["error"]["trans_". $i ."-error"] = 1;
			}
		}
	}

	// tax
	$data["amount_tax"]		= security_form_input_predefined("any", "amount_tax", 0, "");
	$data["amount_tax_orig"]	= security_form_input_predefined("any", "amount_tax_orig", 0, "");
	$data["tax_enable"]		= security_form_input_predefined("any", "tax_enable", 0, "");
	$data["tax_id"]			= security_form_input_predefined("int", "tax_id", 0, "");

	// other
	$data["dest_account"]		= security_form_input_predefined("int", "dest_account", $required, "");



	/*
		Calculate total information
	*/

	// add transactions
	for ($i = 0; $i < $data["num_trans"]; $i++)
	{
		$data["amount"] += $data["trans"][$i]["amount"];
	}

	// apply tax
	if ($data["tax_enable"])
	{
		if ($data["tax_id"])
		{
			/*
				Tax can be calculated in two ways:
				1. Calculate the tax from the taxrate
				2. Let the user over-ride the tax field with their own value

					The amount_tax_orig form field allows us to detect if the user
					has tried to over-write the field with their own values.
			*/
			if ($data["amount_tax_orig"] && ($data["amount_tax"] != $data["amount_tax_orig"]))
			{				
				// user has over-ridden the amount to charge for tax.
				$data["amount_total"] = $data["amount"] + $data["amount_tax"];
			}
			else
			{
				// need to calculate tax value
				$taxrate = sql_get_singlevalue("SELECT taxrate as value FROM account_taxes WHERE id='". $data["tax_id"] ."'");
	
				$data["amount_tax"]	= $data["amount"] * ($taxrate / 100);
				$data["amount_total"]	= $data["amount"] + $data["amount_tax"];

				// set tax_amount_orig value
				$data["amount_tax_orig"] = $data["amount_tax"];
			}
			
			
		}
		else
		{
			$_SESSION["error"]["message"][] = "Tax has been enabled, but no tax type has been selected - please select a valid tax type, or disable tax on this transaction";
		}
	}
	else
	{
		$data["amount_total"] = $data["amount"];
	}
	
	// pad values
	$data["amount_total"]			= sprintf("%0.2f", $data["amount_total"]);
	$data["amount_tax"]			= sprintf("%0.2f", $data["amount_tax"]);
	$data["amount_tax_orig"]		= sprintf("%0.2f", $data["amount_tax_orig"]);
	$data["amount"]				= sprintf("%0.2f", $data["amount"]);

	// set returns
	$_SESSION["error"]["amount_total"]	= $data["amount_total"];
	$_SESSION["error"]["amount_tax"]	= $data["amount_tax"];
	$_SESSION["error"]["amount_tax_orig"]	= $data["amount_tax_orig"];
	$_SESSION["error"]["amount"]		= $data["amount"];



	// are we editing an existing transaction or adding a new one?
	if ($id)
	{
		$mode = "edit";

		// make sure the account actually exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM `account_ar` WHERE id='$id'";
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


	// make sure we don't choose a transaction invoice number that is already in use
	if ($data["code_invoice"])
	{
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM `account_ar` WHERE code_invoice='". $data["code_invoice"] ."'";
		if ($id)
			$sql_obj->string .= " AND id!='$id'";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$_SESSION["error"]["message"][]		= "This invoice number is already in use by another invoice. Please choose a unique number, or leave it blank to recieve an automatically generated number.";
			$_SESSION["error"]["name_chart-error"]	= 1;
		}
	}


	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		if ($mode == "edit")
		{
			$_SESSION["error"]["form"]["ar_transaction_edit"] = "failed";
			header("Location: ../../index.php?page=accounts/ar/transactions-view.php&id=$id");
			exit(0);
		}
		else
		{
			$_SESSION["error"]["form"]["ar_transaction_add"] = "failed";
			header("Location: ../../index.php?page=accounts/ar/transactions-add.php");
			exit(0);
		}
	}
	else
	{
		/*
			PROCESS ACTION

			There are two actions that can be performed:
			* update	Updates the calculations and returns to the main invoice page
			* save		Saves the invoice
		*/


		if ($data["action"] == "update")
		{
			// add 1 more transaction row if the user has filled
			// all the current rows
			$count = 0;
			for ($i = 0; $i < $data["num_trans"]; $i++)
			{
				if ($data["trans"][$i]["amount"])
				{
					$count++;
				}
			}

			if ($count == $data["num_trans"])
			{
				$data["num_trans"]++;
			}
			elseif ($count < $data["num_trans"])
			{
				$data["num_trans"] = $count + 1;
			}

			$_SESSION["error"]["num_trans"] = $data["num_trans"];

			
			// return to the form
			if ($mode == "edit")
			{
				$_SESSION["error"]["form"]["ar_transaction_edit"] = "update";
				header("Location: ../../index.php?page=accounts/ar/transactions-view.php&id=$id");
				exit(0);
			}
			else
			{
				$_SESSION["error"]["form"]["ar_transaction_add"] = "update";
				header("Location: ../../index.php?page=accounts/ar/transactions-add.php");
				exit(0);
			}
		}
		else
		{
		
			// GENERATE INVOICE ID
			// if no invoice ID has been supplied, we now need to generate a unique invoice id
			$data["code_invoice"] = transaction_generate_ar_invoiceid();

		
			// APPLY GENERAL OPTIONS
			if ($mode == "add")
			{
				/*
					Create new transaction
				*/
				
				$sql_obj		= New sql_query;
				$sql_obj->string	= "INSERT INTO `account_ar` (code_invoice) VALUES ('".$data["code_invoice"]."')";
				if (!$sql_obj->execute())
				{
					$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst attempting to create transaction";
				}

				$id = $sql_obj->fetch_insert_id();
			}

			if ($id)
			{
				/*
					Update general transaction details
				*/
				
				$sql_obj = New sql_query;
				
				$sql_obj->string = "UPDATE `account_ar` SET "
							."customerid='". $data["customerid"] ."', "
							."employeeid='". $data["employeeid"] ."', "
							."notes='". $data["notes"] ."', "
							."code_invoice='". $data["code_invoice"] ."', "
							."code_ordernumber='". $data["code_ordernumber"] ."', "
							."code_ponumber='". $data["code_ponumber"] ."', "
							."date_transaction='". $data["date_transaction"] ."', "
							."date_due='". $data["date_due"] ."', "
							."taxid='". $data["tax_id"] ."', "
							."dest_account='". $data["dest_account"] ."', "
							."amount_total='". $data["amount_total"] ."', "
							."amount_tax='". $data["amount_tax"] ."', "
							."amount='". $data["amount"] ."' "
							."WHERE id='$id'";
							
				if (!$sql_obj->execute())
				{
					$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst attempting to save changes";
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




				/*
					Create items for each transaction in the DB
				*/

				// delete the existing transaction items
				if ($id)
				{
					$sql_obj = New sql_query;
					$sql_obj->string = "DELETE FROM account_trans WHERE type='ar' AND customid='$id'";
					$sql_obj->execute();
				}

				// create all the transaction items
				for ($i = 0; $i < $data["num_trans"]; $i++)
				{
					if ($data["trans"][$i]["amount"])
					{
						$sql_obj		= New sql_query;
						$sql_obj->string	= "INSERT "
									."INTO account_trans ("
									."type, "
									."customid, "
									."chartid, "
									."amount, "
									."memo "
									.") VALUES ("
									."'ar', "
									."'$id', "
									."'". $data["trans"][$i]["account"] ."', "
									."'". $data["trans"][$i]["amount"] ."', "
									."'". $data["trans"][$i]["description"] ."' "
									.")";
						$sql_obj->execute();
					}
				}

			}

			// display updated details
			header("Location: ../../index.php?page=accounts/ar/transactions-view.php&id=$id");
			exit(0);
			
		} // end action response

	} // end if passed tests

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
