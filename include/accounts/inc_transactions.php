<?php
/*
	include/accounts/inc_transactions.php

	Contains various help, wrapper and useful functions for working with transactions in the database.
*/


/*
	FUNCTIONS
*/



/*
	transaction_calc_duedate($date)

	This function takes the supplied date in YYYY-MM-DD format, and
	adds the number of days for the default payment term in the DB
	and returns a new due date value - this is suitable for the default
	due date on invoices

	Returns the data in YYYY-MM-DD format.
*/
function transaction_calc_duedate($date)
{
	// get the terms
	$terms = sql_get_singlevalue("SELECT value FROM config WHERE name='ACCOUNTS_TERMS_DAYS'");

	// break up the date, and reconfigure
	$date_array	= split("-", $date);
	$timestamp	= mktime(0, 0, 0, $date_array[1], ($date_array[2] + $terms), $date_array[0]);

	// generate the date
	return date("Y-m-d", $timestamp);
}


/*
	transaction_generate_ar_invoiceid()

	This function will generate a unique invoice ID, by taking the current value from
	ACCOUNTS_AR_INVOICENUM and then making sure it has not already been used.

	Once a unique invoiceid has been determined, the system will update the ACCOUNTS_AR_INVOICENUM
	value so that no other invoice will take it.

	Call this function just prior to inserting a new transaction into the database.

	Returns the invoice ID in a string.
*/
function transaction_generate_ar_invoiceid()
{
	$invoiceid	= 0;
	$invoicenum	= sql_get_singlevalue("SELECT value FROM config WHERE name='ACCOUNTS_AR_INVOICENUM'");

	if (!$invoicenum)
		die("Unable to fetch ACCOUNTS_AR_INVOICENUM value from config database");

	while ($invoiceid == 0)
	{
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_ar WHERE code_invoice='$invoicenum'";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			// invoicenumber already taken, increment and rety
			$invoicenum++;
		}
		else
		{
			// found an avaliable invoice number
			$invoiceid = $invoicenum;


			// update the DB
			$invoicenum++;
			$sql_obj		= New sql_query;
			$sql_obj->string	= "UPDATE config SET value='$invoicenum' WHERE name='ACCOUNTS_AR_INVOICENUM'";
			$sql_obj->execute();
		}
	}

	return $invoiceid;
}


/*
	transaction_render_form($type, $id)

	Displays a transaction form. This is used by:
	- adding AR + AP transactions
	- viewing/editing AR + AP transactions

	Values
	type		Either "ar" or "ap"
	id		If editing/viewing an existing transaction, provide the ID

	Return Codes
	0	failure
	1	success
*/
function transaction_render_form($type, $id)
{

	if ($id)
	{
		$mode = "edit";
	}
	else
	{
		$mode = "add";
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
	$form->formname = "ar_transaction_$mode";
	$form->language = $_SESSION["user"]["lang"];


	// basic details
	$structure = form_helper_prepare_dropdownfromdb("customerid", "SELECT id, name_customer as label FROM customers");
	$structure["options"]["width"]	= 300;
	$form->add_input($structure);
		
	$structure = form_helper_prepare_dropdownfromdb("employeeid", "SELECT id, name_staff as label FROM staff");
	$structure["options"]["width"]	= 300;
	$form->add_input($structure);

	$structure = NULL;
	$structure["fieldname"] 	= "code_invoice";
	$structure["type"]		= "input";
	$form->add_input($structure);
	
	$structure = NULL;
	$structure["fieldname"] 	= "code_ordernumber";
	$structure["type"]		= "input";
	$form->add_input($structure);

	$structure = NULL;
	$structure["fieldname"] 	= "code_ponumber";
	$structure["type"]		= "input";
	$form->add_input($structure);
		
	$structure = NULL;
	$structure["fieldname"] 	= "notes";
	$structure["type"]		= "textarea";
	$structure["options"]["height"]	= "60";
	$structure["options"]["width"]	= 300;
	$form->add_input($structure);



	// dates
	$structure = NULL;
	$structure["fieldname"] 	= "date_transaction";
	$structure["type"]		= "date";
	$structure["defaultvalue"]	= date("Y-m-d");
	$form->add_input($structure);

	$structure = NULL;
	$structure["fieldname"] 	= "date_due";
	$structure["type"]		= "date";
	$structure["defaultvalue"]	= transaction_calc_duedate(date("Y-m-d"));
	$form->add_input($structure);


	// unless there has been error data returned, fetch all the transactions
	// from the DB, and work out the number of transaction rows
	if (!$_SESSION["error"]["form"][$form->formname])
	{
		$sql_trans_obj		= New sql_query;
		$sql_trans_obj->string	= "SELECT * FROM `account_trans` WHERE type='$type' AND customid='$id'";
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
		// amount field
		$structure = NULL;
		$structure["fieldname"] 	= "trans_". $i ."_amount";
		$structure["type"]		= "input";
		$structure["options"]["width"]	= "80";
		$form->add_input($structure);
				
		// account
		$structure = charts_form_prepare_acccountdropdown("trans_". $i ."_account", 2);
		$form->add_input($structure);
				
		// description
		$structure = NULL;
		$structure["fieldname"] 	= "trans_". $i ."_description";
		$structure["type"]		= "textarea";
		$form->add_input($structure);

		// if we have data from a sql query, load it in
		if ($sql_trans_obj->data_num_rows)
		{
			$form->structure["trans_". $i ."_amount"]["defaultvalue"]	= $sql_trans_obj->data[$i]["amount"];
			$form->structure["trans_". $i ."_account"]["defaultvalue"]	= $sql_trans_obj->data[$i]["chartid"];
			$form->structure["trans_". $i ."_description"]["defaultvalue"]	= $sql_trans_obj->data[$i]["memo"];
		}
	}
	

	// tax amount
	$structure = NULL;
	$structure["fieldname"] 		= "amount_tax";
	$structure["type"]			= "input";
	$structure["options"]["width"]		= "80";
	$form->add_input($structure);
		
	// tax enable/disable
	$structure = NULL;
	$structure["fieldname"] 		= "tax_enable";
	$structure["type"]			= "checkbox";
	$structure["defaultvalue"]		= "enabled";
	$structure["options"]["label"]		= " ";
	$form->add_input($structure);
		
		
	// tax account dropdown
	$structure = form_helper_prepare_dropdownfromdb("tax_id", "SELECT id, name_tax as label FROM account_taxes");

	if (count(array_keys($structure["values"])) == 1)
	{
		// if there is only 1 tax option avaliable, select it as the default
		$structure["options"]["noselectoption"] = "yes";
	}
	
	$form->add_input($structure);
	

	
	// destination account
	$structure = charts_form_prepare_acccountdropdown("dest_account", 1);

	if (count(array_keys($structure["values"])) == 1)
	{
		// if there is only 1 tax option avaliable, select it as the default
		$structure["options"]["noselectoption"] = "yes";
	}
	
	$form->add_input($structure);


	// text field
	$structure = NULL;
	$structure["fieldname"] 	= "amount_total";
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
	$structure["fieldname"] 	= "amount_tax_orig";
	$structure["type"]		= "hidden";
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
		$form->sql_query = "SELECT * FROM `account_ar` WHERE id='$id' LIMIT 1";
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
	print "<form method=\"post\" action=\"accounts/$type/transactions-$mode-process.php\" class=\"form_standard\">";
	print "<table class=\"form_table\" width=\"100%\">";

	// form header
	print "<tr class=\"header\">";
	print "<td colspan=\"2\"><b>". language_translate_string($_SESSION["user"]["lang"], "transaction_". $mode ."_general") ."</b></td>";
	print "</tr>";


	/*
		Basic Details

		This section is just like any normal form
	*/
	
	// details row
	print "<tr>";
	print "<td width=\"60%\" valign=\"top\">";

		// details table
		print "<table>";
		$form->render_row("customerid");
		$form->render_row("employeeid");
		$form->render_row("notes");
		print "</table>";
		
	print "</td>";
	print "<td width=\"40%\" valign=\"top\">";

		// details table
		print "<table>";
		$form->render_row("code_invoice");
		$form->render_row("code_ordernumber");
		$form->render_row("code_ponumber");
		$form->render_row("date_transaction");
		$form->render_row("date_due");
		print "</table>";
	print "</td>";
	

	print "</tr>";


	/*
		Transactions

		This section of the form is quite complex. We need to display all the transaction entries
		that the user has added, as well as displaying totals and tax figures for the entered transactions.

		To generate totals or new transaction rows, the user needs to click the update button, however
		in future this could be extended with javascript so the user only has to use the update button if
		their browser is not javascript capable.
	*/
	print "<tr class=\"header\">";
	print "<td colspan=\"2\"><b>". language_translate_string($_SESSION["user"]["lang"], "transaction_add_transactions") ."</b></td>";
	print "</tr>";

		print "<tr>";
		print "<td colspan=\"2\">";

		// header
		print "<table width=\"100%\">";
		print "<tr>";
		print "<td width=\"10%\"><b>Amount</b></td>";
			
		print "</tr>";


		/*
			Transaction Rows
			
			There can be any number of transactions (minimum/default is 1) that we need
			to display
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


			// amount field
			print "<td width=\"10%\" valign=\"top\">";
			$form->render_field("trans_". $i ."_amount");
			print "</td>";


			// ignore checkbox column
			print "<td width=\"5%\" valign=\"top\"></td>";


			// account
			print "<td width=\"35%\" valign=\"top\">";
			$form->render_field("trans_". $i ."_account");
			print "</td>";


			// description
			print "<td width=\"50%\" valign=\"top\">";
			$form->render_field("trans_". $i ."_description");
			print "</td>";

		
			print "</tr>";
		}


		/*
			Tax Row

			Display the dropdown to select the tax in use and include
			a space to show the amount of tax added, which can be manually
			edited if required.
		*/


		print "<tr>";
	
	
		// amount of tax
		print "<td width=\"10%\" valign=\"top\">";
		$form->render_field("amount_tax");
		print "</td>";


		// checkbox - enable/disable tax
		print "<td width=\"5%\" valign=\"top\">";
		$form->render_field("tax_enable");
		print "</td>";


		// tax selection dropdown
		print "<td width=\"35%\" valign=\"top\">";
		$form->render_field("tax_id");
		print "</td>";
			
		// description field - filler
		print "<td width=\"50%\"></td>";

		print "</tr>";




		/*
			Totals Display
		*/
		
		print "<tr class=\"table_highlight\">";

		// total amount of transaction
		print "<td width=\"10%\"><b>";
		$form->render_field("amount_total");
		print "</b></td>";
		
		// joining/filler column
		print "<td width=\"5%\">to</td>";
		
		// destination account (usually always accounts recivable)
		print "<td width=\"35%\">";
		$form->render_field("dest_account");
		print "</td>";
		
		// description field - filler
		print "<td width=\"50%\"></td>";
		
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
	print "<input type=\"submit\" name=\"action\" value=\"update\"> <i>Will re-calculate totals and allow you to enter additional rows to the transactions section.</i><br>";
	print "<br>";
	print "<input type=\"submit\" name=\"action\" value=\"save\"> <i>Will create the transaction</i>";
	print "</td>";
	print "</tr>";

	// end table + form
	print "</table>";		
	print "</form>";


	return 1;
	
} // end of transactions_render_form





?>
