<?php
/*
	include/accounts/inc_transactions.php

	Provides functions for drawing and processing forms for managing payments of invoices
*/


/*
	FUNCTIONS
*/


/*
	transaction_form_payments_render($type, $id, $processpage)

	Displays a form for making payments against AR or AP transactions.
	
	Values
	type		Either "ar" or "ap"
	id		ID of the transaction
	processpage	Page to submit the form too

	Return Codes
	0	failure
	1	success
*/
function transaction_form_payments_render($type, $id, $processpage)
{
	log_debug("inc_payments_forms", "Executing transaction_form_payments_render($type, $id, $processpage)");

	
	/*
		Make sure transaction does exist!
	*/
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM account_$type WHERE id='$id'";
	$sql_obj->execute();
		
	if (!$sql_obj->num_rows())
	{
		print "<p><b>Error: The requested transaction does not exist. <a href=\"index.php?page=accounts/$type/$type.php\">Try looking on the transaction/invoice list page.</a></b></p>";
		return 0;
	}


	/*
		Fetch important values
	*/
	
	$num_trans	= security_script_input('/^[0-9]*$/', $_SESSION["error"]["num_trans"]);

	if (!$num_trans)
		$num_trans = 1;
	
	/*
		Define form structure
	*/
	$form = New form_input;
	$form->formname = "ar_payment";
	$form->language = $_SESSION["user"]["lang"];


	// unless there has been error data returned, fetch all the payments
	// from the DB, and work out the number of transaction rows
	if (!$_SESSION["error"]["form"][$form->formname])
	{
		$sql_trans_obj		= New sql_query;
		$sql_trans_obj->string	= "SELECT date_trans, amount_debit, chartid, source, memo FROM `account_trans` WHERE type='". $type ."_pay' AND customid='$id' AND amount_debit > 0";
		$sql_trans_obj->execute();
		
		if ($sql_trans_obj->num_rows())
		{
			$sql_trans_obj->fetch_array();
			
			$num_trans = $sql_trans_obj->data_num_rows;
		}
	}


	// transaction rows
	for ($i = 0; $i < $num_trans; $i++)
	{
		// date
		$structure = NuLL;
		$structure["fieldname"] 	= "trans_". $i ."_date_trans";
		$structure["type"]		= "date";
		$structure["defaultvalue"]	= date("Y-m-d");

		// we have this entry here, to make sure that the date is added when
		// the user uses the form update ability to add new rows.
		//
		// without this code, when a new row is added, the error data will be blank
		// and the default date value will be erased.
		//
		if (!$_SESSION["error"]["trans_". $i ."_date_trans"])
			$_SESSION["error"]["trans_". $i ."_date_trans"] = $structure["defaultvalue"];
		
		$form->add_input($structure);


		// amount field
		$structure = NULL;
		$structure["fieldname"] 	= "trans_". $i ."_amount";
		$structure["type"]		= "input";
		$structure["options"]["width"]	= "80";
		$form->add_input($structure);
				
		// account
		$structure = charts_form_prepare_acccountdropdown("trans_". $i ."_account", 6);
		$structure["options"]["width"]	= "200";
		$form->add_input($structure);

		
		
		// source
		$structure = NULL;
		$structure["fieldname"] 	= "trans_". $i ."_source";
		$structure["type"]		= "input";
		$structure["options"]["width"]	= "100";
		$form->add_input($structure);
		
		// description
		$structure = NULL;
		$structure["fieldname"] 	= "trans_". $i ."_description";
		$structure["type"]		= "textarea";
		$form->add_input($structure);

		// if we have data from a sql query, load it in
		if ($sql_trans_obj->data_num_rows)
		{
			if ($sql_trans_obj->data[$i]["date_trans"])
			{
				$form->structure["trans_". $i ."_date_trans"]["defaultvalue"]	= $sql_trans_obj->data[$i]["date_trans"];
				$form->structure["trans_". $i ."_amount"]["defaultvalue"]	= $sql_trans_obj->data[$i]["amount_debit"];
				$form->structure["trans_". $i ."_account"]["defaultvalue"]	= $sql_trans_obj->data[$i]["chartid"];
				$form->structure["trans_". $i ."_source"]["defaultvalue"]	= $sql_trans_obj->data[$i]["source"];
				$form->structure["trans_". $i ."_description"]["defaultvalue"]	= $sql_trans_obj->data[$i]["memo"];
			}
		}
	}
	
	// text fields
	$structure = NULL;
	$structure["fieldname"] 	= "amount_total";
	$structure["type"]		= "text";
	$structure["defaultvalue"]	= "---";
	$form->add_input($structure);

	$structure = NULL;
	$structure["fieldname"] 	= "amount_paid";
	$structure["type"]		= "text";
	$structure["defaultvalue"]	= "---";
	$form->add_input($structure);


	// hidden fields
	$structure = NULL;
	$structure["fieldname"] 	= "num_trans";
	$structure["type"]		= "hidden";
	$structure["defaultvalue"]	= $num_trans;
	$form->add_input($structure);


	$structure = NULL;
	$structure["fieldname"] 	= "id_transaction";
	$structure["type"]		= "hidden";
	$structure["defaultvalue"]	= $id;
	$form->add_input($structure);


	// load data
	if ($mode == "add")
	{
		$form->load_data_error();
	}
	else
	{
		// load general information
		$form->sql_query = "SELECT id, amount_total, amount_paid FROM `account_ar` WHERE id='$id' LIMIT 1";
		$form->load_data();
	}


/*
	// debugging
	// dumps the entire form structure. handy when debuging strange issues.
	print "<pre>";
	print_r ($form->structure);
	print "</pre>";
*/		



	/*
		Display the form
	*/

	// start form/table structure
	print "<form method=\"post\" action=\"$processpage\" class=\"form_standard\">";
	print "<table class=\"form_table\" width=\"100%\">";



	/*
		Payment Transactions

		This section is the most complex part of the form, where we add new rows to the form
		for payments.

		To generate totals or new transaction rows, the user needs to click the update button, however
		in future this could be extended with javascript so the user only has to use the update button if
		their browser is not javascript capable.
	*/
	print "<tr class=\"header\">";
	print "<td colspan=\"2\"><b>". language_translate_string($_SESSION["user"]["lang"], "transaction_". $mode ."_payment") ."</b></td>";
	print "</tr>";

		print "<tr>";
		print "<td colspan=\"2\">";

		// header
		print "<table width=\"100%\">";
		print "<tr>";
		print "<td width=\"20%\"><b>Date</b></td>";
		print "<td width=\"10%\"><b>Amount</b></td>";
		print "<td width=\"20%\"><b>Account</b></td>";
		print "<td width=\"15%\"><b>Source</b></td>";
		print "<td width=\"35%\"><b>Description</b></td>";
			
		print "</tr>";


		/*
			Transaction Rows
			
			There can be any number of transactions (minimum/default is 1) that we need
			to display.
		*/
		for ($i = 0; $i < $num_trans; $i++)
		{
			if ($_SESSION["error"]["trans_". $i ."-error"])
			{
				print "<tr class=\"form_error\">";
			}
			else
			{
				print "<tr class=\"table_highlight\">";
			}


			// date field
			print "<td width=\"20%\" valign=\"top\">";
			$form->render_field("trans_". $i ."_date_trans");
			print "</td>";
			
			// amount field
			print "<td width=\"10%\" valign=\"top\">";
			$form->render_field("trans_". $i ."_amount");
			print "</td>";

			// account
			print "<td width=\"20%\" valign=\"top\">";
			$form->render_field("trans_". $i ."_account");
			print "</td>";

			// source
			print "<td width=\"15%\" valign=\"top\">";
			$form->render_field("trans_". $i ."_source");
			print "</td>";
			
			// description
			print "<td width=\"35%\" valign=\"top\">";
			$form->render_field("trans_". $i ."_description");
			print "</td>";

		
			print "</tr>";
		}


		/*
			Totals Display
		*/
		
		print "<tr class=\"table_highlight\">";

		// joining/filler columns
		print "<td width=\"10%\"></td>";
		
		// total amount paid
		print "<td width=\"10%\"><b>$";
		$form->render_field("amount_paid");
		print "</b></td>";
		
		// joining/filler columns
		print "<td width=\"30%\"></td>";
		print "<td width=\"15%\"></td>";
		print "<td width=\"35%\"></td>";
			
		print "</tr>";



		print "</table>";

		print "</td>";
		print "</tr>";



	// hidden fields
	$form->render_field("id_transaction");
	$form->render_field("num_trans");
	$form->render_field("amount_tax_orig");


	// form submit
	print "<tr class=\"header\">";
	print "<td colspan=\"2\"><b>". language_translate_string($_SESSION["user"]["lang"], "transaction_". $mode ."_submit") ."</b></td>";
	print "</tr>";

	print "<tr>";
	print "<td colspan=\"2\">";

	if (user_permissions_get("accounts_". $type ."_write"))
	{
		print "<input type=\"submit\" name=\"action\" value=\"update\"> <i>Will re-calculate totals and allow you to enter additional rows to the payments section.</i><br>";
		print "<br>";
		print "<input type=\"submit\" name=\"action\" value=\"save\"> <i>Will create the payment(s)</i>";
	}
	else
	{
		print "<p><i>You do not have permissions to save changes to this transaction</i></p>";
	}
	
	print "</td>";
	print "</tr>";

	// end table + form
	print "</table>";		
	print "</form>";


	return 1;
	

}




/*
	transaction_form_payments_process($type, $mode, $returnpage_error, $returnpage_success)

	Form for processing payments form results - the payment form will return a number of transactions
	which we need to process and add to the DB.

	Values
	type			"ar" or "ap" transaction
	returnpage_error	Page to return to in event of errors or updates
	returnpage_success	Page to return to if successful.
*/
function transaction_form_details_process($type, $returnpage_error, $returnpage_success)
{
	log_debug("inc_payments_forms", "Executing transaction_form_details_process($type, $mode, $returnpage_error, $returnpage_success)");

	
	/*
		Fetch all form data
	*/


	// get the transaction id
	$id = security_form_input_predefined("int", "id_transaction", 1, "");


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


	// transaction(s)
	$data["num_trans"]		= security_form_input_predefined("int", "num_trans", $required, "");

	for ($i = 0; $i < $data["num_trans"]; $i++)
	{
		$data["trans"][$i]["date"]		= security_form_input_predefined("date", "trans_". $i ."_date_trans", 0, "");
		$data["trans"][$i]["account"]		= security_form_input_predefined("int", "trans_". $i ."_account", 0, "");
		$data["trans"][$i]["amount"]		= security_form_input_predefined("any", "trans_". $i ."_amount", 0, "");
		$data["trans"][$i]["source"]		= security_form_input_predefined("any", "trans_". $i ."_source", 0, "");
		$data["trans"][$i]["description"]	= security_form_input_predefined("any", "trans_". $i ."_description", 0, "");

		if ($data["trans"][$i]["amount"])
			$_SESSION["error"]["trans_". $i ."_amount"] = sprintf("%0.2f", $data["trans"][$i]["amount"]);


		// make sure required data has been supplied
		if ($required)
		{
			// make sure both and an amount have been supplied together
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
			

			// if an amount has been entered, check all the other values we require
			if ($data["trans"][$i]["amount"] && !$data["trans"][$i]["date"])
			{
				$_SESSION["error"]["message"][] = "You must supply a payment date";
				$_SESSION["error"]["trans_". $i ."-error"] = 1;
			}
		}
	}




	/*
		Calculate total information
	*/

	// add transactions
	for ($i = 0; $i < $data["num_trans"]; $i++)
	{
		$data["amount_paid"] += $data["trans"][$i]["amount"];
	}

	// pad values
	$data["amount_paid"]			= sprintf("%0.2f", $data["amount_paid"]);

	// set returns
	$_SESSION["error"]["amount_paid"]	= $data["amount_paid"];



	// make sure the transaction does actually exist
	$sql_trans_obj		= New sql_query;
	$sql_trans_obj->string	= "SELECT id, dest_account FROM `account_$type` WHERE id='$id'";
	$sql_trans_obj->execute();

	if (!$sql_trans_obj->num_rows())
	{
		$_SESSION["error"]["message"][] = "The transaction you have attempted to edit - $id - does not exist in this system.";
	}
	else
	{
		// we need some information from the transaction such as the dest account
		$sql_trans_obj->fetch_array();
	}


	//// ERROR CHECKING ///////////////////////


	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"][$type ."_payment"] = "failed";
		header("Location: ../../index.php?page=$returnpage_error&id=$id");
		exit(0);
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
			$_SESSION["error"]["form"][$type ."_payment"] = "update";
			header("Location: ../../index.php?page=$returnpage_error&id=$id");
			exit(0);
		}
		else
		{

			/*
				Manage Payment Transactions
			*/

			// delete the existing transaction items
			$sql_obj = New sql_query;
			$sql_obj->string = "DELETE FROM account_trans WHERE type='". $type ."_pay' AND customid='$id'";
			$sql_obj->execute();
			

			// create all the transaction items
			for ($i = 0; $i < $data["num_trans"]; $i++)
			{
				if ($data["trans"][$i]["amount"])
				{
					/*
						Double entry accounting requires two entries for any financial transaction
						
						1. Credit the source of the transaction (eg: withdrawl funds from current account)
						2. Debit the destination (eg: pay an expense account)

						For AR/AP transactions, we credit the summary account choosen by the user
						and debit the various accounts for all the items.
					*/

					// TODO: add better error checking of transaction items here

					
					// insert debit transaction
					$sql_obj		= New sql_query;
					$sql_obj->string	= "INSERT "
								."INTO account_trans ("
								."type, "
								."customid, "
								."date_trans, "
								."chartid, "
								."amount_debit, "
								."source, "
								."memo "
								.") VALUES ("
								."'". $type ."_pay', "
								."'$id', "
								."'". $data["trans"][$i]["date"] ."', "
								."'". $data["trans"][$i]["account"] ."', "
								."'". $data["trans"][$i]["amount"] ."', "
								."'". $data["trans"][$i]["source"] ."', "
								."'". $data["trans"][$i]["description"] ."' "
								.")";
					$sql_obj->execute();


					// insert credit transaction
					$sql_obj		= New sql_query;
					$sql_obj->string	= "INSERT "
								."INTO account_trans ("
								."type, "
								."customid, "
								."date_trans, "
								."chartid, "
								."amount_credit, "
								."source, "
								."memo "
								.") VALUES ("
								."'". $type ."_pay', "
								."'$id', "
								."'". $data["trans"][$i]["date"] ."', "
								."'". $sql_trans_obj->data[0]["dest_account"] ."', "
								."'". $data["trans"][$i]["amount"] ."', "
								."'". $data["trans"][$i]["source"] ."', "
								."'". $data["trans"][$i]["description"] ."' "
								.")";
					$sql_obj->execute();


				}

			} // end of transaction item loop



			// update payment total in DB
			$sql_obj		= New sql_query;
			$sql_obj->string	= "UPDATE account_$type "
						."SET "
						."amount_paid='". $data["amount_paid"] ."' "
						."WHERE id='$id'";
						
			if ($sql_obj->execute())
			{
				$_SESSION["notification"]["message"][] = "Payment(s) successfully entered.";
				journal_quickadd_event("account_$type", $id, "Payment(s) entered against invoice");
			}

			
			// display updated details
			header("Location: ../../index.php?page=$returnpage_success&id=$id");
			exit(0);
			
		} // end action response

	} // end if passed tests


} // end if transaction_form_details_process




?>
