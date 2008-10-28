<?php
/*
	include/accounts/inc_invoices_forms.php

	Provides functions for drawing and processing forms for processing invoices.
*/


/*
	FUNCTIONS
*/


/*
	invoice_form_render($type, $id, $processpage)

	Determines the correct form to display.
	- adding AR + AP invoices
	- viewing/editing AR + AP invoices

	Values
	type		Either "ar" or "ap"
	id		If editing/viewing an existing invoice, provide the ID
	processpage	Page to submit the form too

	Return Codes
	0	failure
	1	success
*/
function invoice_form_render($type, $id, $processpage)
{
	log_debug("inc_invoices_forms", "Executing invoice_form_details_render($type, $id, $processpage)");

	if ($id)
	{
		$mode = "edit";
	}
	else
	{
		$mode = "add";
	}

	// debugging
	print "<pre>";
	print_r($_SESSION["form"][$type ."_invoice"]);
	print "</pre>";


	if (!$_SESSION["form"][$type ."_invoice"]["action"])
		$_SESSION["form"][$type ."_invoice"]["action"] = "main";

	
	switch ($_SESSION["form"][$type ."_invoice"]["action"])
	{
		case "main":
			return invoice_form_details_render($type, $id, $processpage);
		break;
		
		case "add_item_simple":
		case "edit_item_simple":
			return invoice_form_item_render($type, $id, $processpage, "simple", "");
		break;
		
		default:
			log_debug("inc_invoices_forms", "Error: Unrecognised form action");
			return 0;
		break;
	}

}




/*
	invoice_form_details_render($type, $id, $processpage)

	Displays the main invoice form. This is used by:
	- adding AR + AP invoices
	- viewing/editing AR + AP invoices

	Values
	type		Either "ar" or "ap"
	id		If editing/viewing an existing invoice, provide the ID
	processpage	Page to submit the form too

	Return Codes
	0	failure
	1	success
*/
function invoice_form_details_render($type, $id, $processpage)
{
	log_debug("inc_invoices_forms", "Executing invoice_form_details_render($type, $id, $processpage)");

	if ($id)
	{
		$mode = "edit";
	}
	else
	{
		$mode = "add";
	}

	
	/*
		Make sure invoice does exist!
	*/
	if ($mode == "edit")
	{
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_$type WHERE id='$id'";
		$sql_obj->execute();
		
		if (!$sql_obj->num_rows())
		{
			print "<p><b>Error: The requested invoice does not exist. <a href=\"index.php?page=accounts/$type/$type.php\">Try looking on the invoice/invoice list page.</a></b></p>";
			return 0;
		}
	}


	/*
		Start Form
	*/
	$form = New form_input;
	$form->formname		= $type ."_invoice_". $mode;
	$form->language		= $_SESSION["user"]["lang"];
	$form->sessionform	= $type ."_invoice";


	/*
		Define form structure
	*/

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
	$structure["fieldname"] 	= "date_invoice";
	$structure["type"]		= "date";
	$structure["defaultvalue"]	= date("Y-m-d");
	$form->add_input($structure);

	$structure = NULL;
	$structure["fieldname"] 	= "date_due";
	$structure["type"]		= "date";
	$structure["defaultvalue"]	= invoice_calc_duedate(date("Y-m-d"));
	$form->add_input($structure);

/*
obsolete?
	// unless there has been error data returned, fetch all the invoices
	// from the DB, and work out the number of invoice rows
	if (!$_SESSION["error"]["form"][$form->formname])
	{
		$sql_trans_obj		= New sql_query;
		$sql_trans_obj->string	= "SELECT amount_credit, chartid, memo FROM `account_trans` WHERE type='$type' AND customid='$id' AND amount_credit > 0";
		$sql_trans_obj->execute();
		
		if ($sql_trans_obj->num_rows())
		{
			$sql_trans_obj->fetch_array();
			$num_trans = $sql_trans_obj->data_num_rows;
		}
	}
*/

	/*
		Define invoice structure and load data

		There are different types of invoices, so we need to define accordingly
	*/

/*
	if ($_SESSION["error"]["num_trans"])
	{
		$num_trans = count(array_keys($_SESSION["error"]["num_trans"]));
	}

	for ($i = 0; $i < $num_trans; $i++)
	{
		/// all invoices have both the amount and account fields
		
		// amount field
		$structure = NULL;
		$structure["fieldname"] 	= "trans_". $i ."_amount";
		$structure["type"]		= "input";
		$structure["options"]["width"]	= "80";
		$form->add_input($structure);
						
		// account
		$structure = charts_form_prepare_acccountdropdown("trans_". $i ."_account", 2);
		$form->add_input($structure);


		/// now depending on the invoice type, we add suitable options
		if (!$_SESSION["error"]["invoices"][$i]["type"])
			$_SESSION["error"]["invoices"][$i]["type"] = $sql_trans_obj->data[$i]["type"];
		
		switch ($_SESSION["error"]["invoices"][$i]["type"])
		{
			case "simple":
			
				// description
				$structure = NULL;
				$structure["fieldname"] 	= "trans_". $i ."_description";
				$structure["type"]		= "textarea";
				$form->add_input($structure);

				// if we have data from a sql query, load it in
				if ($sql_trans_obj->data_num_rows)
				{
					$form->structure["trans_". $i ."_amount"]["defaultvalue"]	= $sql_trans_obj->data[$i]["amount_credit"];
					$form->structure["trans_". $i ."_account"]["defaultvalue"]	= $sql_trans_obj->data[$i]["chartid"];
					$form->structure["trans_". $i ."_description"]["defaultvalue"]	= $sql_trans_obj->data[$i]["memo"];
				}
			break;
		}
	}
*/
	

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


	// total fields
	$structure = NULL;
	$structure["fieldname"] 	= "amount";
	$structure["type"]		= "text";
	$structure["defaultvalue"]	= "---";
	$form->add_input($structure);
	
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
	$structure["fieldname"] 	= "id_invoice";
	$structure["type"]		= "hidden";
	$structure["defaultvalue"]	= $id;
	$form->add_input($structure);


	// load data
	if ($mode == "edit")
	{
		$form->sql_query = "SELECT * FROM `account_ar` WHERE id='$id' LIMIT 1";
	}

	// the load_data function will fetch data from error array, session array or SQL.
	$form->load_data();


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

	// start form
	print "<form method=\"post\" action=\"$processpage\" class=\"form_standard\">";

	// unsaved data box
	$form->render_sessionform_messagebox();
	
	// start table
	print "<table class=\"form_table\" width=\"100%\">";

	// form header
	print "<tr class=\"header\">";
	print "<td colspan=\"2\"><b>". language_translate_string($_SESSION["user"]["lang"], "invoice_". $mode ."_general") ."</b></td>";
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
		$form->render_row("date_invoice");
		$form->render_row("date_due");
		print "</table>";
	print "</td>";
	

	print "</tr>";





	/*
		Transactions

		This section of the form is quite complex. We need to display all the invoice entries
		that the user has added, as well as displaying totals and tax figures for the entered invoices.

		To generate totals or new invoice rows, the user needs to click the update button, however
		in future this could be extended with javascript so the user only has to use the update button if
		their browser is not javascript capable.
	*/
	print "<tr class=\"header\">";
	print "<td colspan=\"2\"><b>". language_translate_string($_SESSION["user"]["lang"], "invoice_add_invoices") ."</b></td>";
	print "</tr>";

		print "<tr>";
		print "<td colspan=\"2\">";
		
		// add item buttons
		print "<table>";
		
		print "<tr>";
			print "<td><a href=\"$processpage?id=$id&action=add_item_simple\">Add Simple Item</a></td>";
			print "<td><i>Simple item entry for adding charges to an invoice.</i></td>";
		print "</tr>";

		print "</table>";



		if (!$_SESSION["form"][$form->sessionform]["trans"])
		{
			print "<p><i>This invoice currently has no items attached to it.</i></p>";
		}
		else
		{
			// header
			print "<table width=\"100%\">";
			print "<tr>";
			print "<td width=\"10%\"><b>Amount</b></td>";
			print "<td width=\"5%\"></td>";
			print "<td width=\"35%\"><b>Account</b></td>";
			print "<td width=\"40%\"><b>Description</b></td>";
			print "<td width=\"10%\"></td>";
			print "</tr>";


			// render invoice rows
			$i = 0;
			foreach ($_SESSION["form"][$form->sessionform]["trans"] as $transaction)
			{
				print "<tr class=\"table_highlight\">";

				// amount field
				print "<td width=\"10%\" valign=\"top\">$". $transaction["trans_amount"] ."</td>";

				// ignore checkbox column
				print "<td width=\"5%\" valign=\"top\"></td>";

				// account
				$sql_trans_obj		= New sql_query;
				$sql_trans_obj->string	= "SELECT code_chart, description FROM account_charts WHERE id='". $transaction["trans_account"] ."'";
				$sql_trans_obj->execute();
				$sql_trans_obj->fetch_array();
				
				print "<td width=\"35%\" valign=\"top\">". $sql_trans_obj->data[0]["code_chart"] ." -- ". $sql_trans_obj->data[0]["description"]  ."</td>";

				// description
				print "<td width=\"40%\" valign=\"top\">". $transaction["trans_description"] ."</td>";


				// edit & delete links
				print "<td width=\"10%\" valign=\"top\"><a href=\"$processpage?id=$id&action=edit_item&transid=$i\">edit</a> || <a href=\"$processpage?id=$id&action=del_item&transid=$i\">del</a></td>";
			
				print "</tr>";

				// count so we know the ID of the transaction row for deleting or editing
				$i++;
			}

	
			// render sub-total field		
			print "<tr></tr>";
			
			print "<tr>";

			// total amount of invoice
			print "<td width=\"100%\" colspan=\"5\"><i>Total Items: $";
			$form->render_field("amount");
			print "</i></td>";
			
			print "</tr>";


				
			
			print "</table>";
		}

		print "<br>";



	print "</td>";
	print "</tr>";




	/*
		Tax + Totals
	*/
	print "<tr class=\"header\">";
	print "<td colspan=\"2\"><b>". language_translate_string($_SESSION["user"]["lang"], "invoice_add_tax_totals") ."</b></td>";
	print "</tr>";

		print "<tr>";
		print "<td colspan=\"2\">";

		// header
		print "<table width=\"100%\">";
		print "<tr>";
		print "<td width=\"10%\"><b>Amount</b></td>";
		print "<td width=\"5%\"></td>";
		print "<td width=\"35%\"><b>Account</b></td>";
			
		print "</tr>";



		/*
			Tax Row

			Display the dropdown to select the tax in use and include
			a space to show the amount of tax added, which can be manually
			edited if required.
		*/


		print "<tr class=\"table_highlight\">";
	
	
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

		// total amount of invoice
		print "<td width=\"10%\"><b>$";
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
	$form->render_field("id_invoice");
	$form->render_field("num_trans");
	$form->render_field("amount_tax_orig");


	// form submit
	print "<tr class=\"header\">";
	print "<td colspan=\"2\"><b>". language_translate_string($_SESSION["user"]["lang"], "invoice_". $mode ."_submit") ."</b></td>";
	print "</tr>";

	print "<tr>";
	print "<td colspan=\"2\">";

	if (user_permissions_get("accounts_". $type ."_write"))
	{
		print "<input type=\"submit\" name=\"action\" value=\"update\"> <i>Will re-calculate totals and allow you to enter additional rows to the invoices section.</i><br>";
		print "<br>";
		print "<input type=\"submit\" name=\"action\" value=\"save\"> <i>Will create the invoice</i>";
	}
	else
	{
		print "<p><i>You do not have permissions to save changes to this invoice</i></p>";
	}
	
	print "</td>";
	print "</tr>";

	// end table + form
	print "</table>";		
	print "</form>";


	return 1;
	
} // end of invoices_from_details_render



/*
	invoice_form_item_render($type, $id, $processpage, $transid )

	This function provides a form for adding or adjusting simple items on the invoice.
	- adding AR + AP invoices
	- viewing/editing AR + AP invoices

	Values
	type		Either "ar" or "ap"
	id		If editing/viewing an existing invoice, provide the ID
	processpage	Page to submit the form too
	itemtype	Type of item
				"simple"	Simple invoice item
	transid		ID of the invoice to edit - ID is of the session array, *not* of the database.

	Return Codes
	0	failure
	1	success
*/
function invoice_form_item_render($type, $id, $processpage, $itemtype, $transid)
{
	log_debug("inc_invoices_forms", "Executing invoice_form_item_simple_render($type, $id, $processpage, $itemtype, $transid)");

	if ($id)
	{
		$mode = "edit";
	}
	else
	{
		$mode = "add";
	}

	
	/*
		Make sure invoice does exist!
	*/
	if ($mode == "edit")
	{
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_$type WHERE id='$id'";
		$sql_obj->execute();
		
		if (!$sql_obj->num_rows())
		{
			print "<p><b>Error: The requested invoice does not exist. <a href=\"index.php?page=accounts/$type/$type.php\">Try looking on the invoice/invoice list page.</a></b></p>";
			return 0;
		}
	}


	/*
		Start Form
	*/
	$form = New form_input;
	$form->formname 	= $type ."_invoice_". $mode ."_item";
	$form->language 	= $_SESSION["user"]["lang"];

	$form->action		= $processpage;
	$form->method		= "post";

	$form->sessionform	= $type ."_invoice";


	
	/*
		Define form structure
	*/


	// amount field
	$structure = NULL;
	$structure["fieldname"] 	= "trans_amount";
	$structure["type"]		= "input";
	$structure["options"]["width"]	= "80";
	$form->add_input($structure);
						
	// account
	$structure = charts_form_prepare_acccountdropdown("trans_account", 2);
	$form->add_input($structure);


	/// now depending on the invoice type, we add suitable options
	switch ($itemtype)
	{
		case "simple":
		
			// description
			$structure = NULL;
			$structure["fieldname"] 	= "trans_description";
			$structure["type"]		= "textarea";
			$form->add_input($structure);


			// define fields for item type
			$form->subforms["invoice_item"] = array("trans_amount", "trans_account", "trans_description");
		break;
	}



	// hidden fields
	$structure = NULL;
	$structure["fieldname"] 	= "id_invoice";
	$structure["type"]		= "hidden";
	$structure["defaultvalue"]	= $id;
	$form->add_input($structure);

	$structure = NULL;
	$structure["fieldname"]		= "formname";
	$structure["type"]		= "hidden";
	$structure["defaultvalue"]	= "invoice_item";
	$form->add_input($structure);

	$structure = NULL;
	$structure["fieldname"]		= "itemtype";
	$structure["type"]		= "hidden";
	$structure["defaultvalue"]	= $itemtype;
	$form->add_input($structure);

	$structure = NULL;
	$structure["fieldname"] 	= "id_trans";
	$structure["type"]		= "hidden";
	$structure["defaultvalue"]	= $transid;
	$form->add_input($structure);


	// submit
	$structure = NULL;
	$structure["fieldname"] 	= "action";
	$structure["type"]		= "submit";
	$structure["defaultvalue"]	= "update";
	$form->add_input($structure);
	
	$structure = NULL;
	$structure["fieldname"] 	= "cancel";
	$structure["type"]		= "submit";
	$structure["defaultvalue"]	= "cancel";
	$form->add_input($structure);


	// load data
	$form->load_data();

	// import data from session variable


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

	// base subforms
	$form->subforms["hidden"]	= array("id_invoice", "id_trans", "formname", "itemtype");
	$form->subforms["submit"]	= array("action", "cancel");

	// display form
	$form->render_form();

	return 1;
	
} // end of invoices_form_item_render





/*
	invoice_form_process($type, $mode, $returnpage_error, $returnpage_success)

	Reads the information provided by the form, then calls the relevent function
	for processing that particular form type.

	Values
	type			"ar" or "ap" invoice
	mode			"edit" or "add" for the action to perform
	returnpage_error	Page to return to in event of errors or updates
	returnpage_success	Page to return to if successful.
*/
function invoice_form_process($type, $mode, $returnpage_error, $returnpage_success)
{
	log_debug("inc_invoices_forms", "Executing invoice_form_process($type, $mode, $returnpage_error, $returnpage_success)");

	if ($_GET["action"])
	{
		// get the action value from GET
		$id			= security_script_input("/^[0-9]*$/", $_GET["id"]);
		$data["action"]		= security_script_input("/^\S*$/", $_GET["action"]);
	}
	else
	{
		// use data from POST
		$id			= security_form_input_predefined("int", "id", 0, "");
		$data["action"]		= security_form_input_predefined("any", "action", 0, "");
		$data["formname"]	= security_form_input_predefined("any", "formname", 0, "");

		if ($_POST["cancel"])
		{
			$data["action"] = "cancel";
		}
	}



	switch ($data["action"])
	{
		case "cancel":
			// return user to main form
			$_SESSION["form"][$type ."_invoice"]["action"] = "main";

			header("Location: ../../index.php?page=$returnpage_error&id=$id");
			exit(0);
		break;
		
		case "Clear Form":
			// user wishes to clear all the session data of the form
			$_SESSION["form"][$type ."_invoice"] = NULL;

			header("Location: ../../index.php?page=$returnpage_error&id=$id");
			exit(0);
		break;

		case "add_item_simple":
			// display the simple item form
			$_SESSION["form"][$type ."_invoice"]["action"] = $data["action"];
		
			header("Location: ../../index.php?page=$returnpage_error&id=$id");
			exit(0);
		break;

		case "edit_item":
			// display the simple item form
			$_SESSION["form"][$type ."_invoice"]["action"] = $data["action"];
			
			header("Location: ../../index.php?page=$returnpage_error&id=$id");
			exit(0);
		break;
		
		case "del_item":
			// delete the item
			return invoice_form_item_delete($type, $mode, $returnpage_error, $returnpage_success);
			
		break;


		
		case "update":

			// call the process function
			if ($data["formname"] == "invoice_item")
			{
				return invoice_form_item_process($type, $mode, $returnpage_error, $returnpage_success);
			}
			else
			{
				return invoice_form_details_process($type, $mode, $returnpage_error, $returnpage_success);
			}
		break;

		default:
			die("Invalid action provided to invoice_form_process");
		break;
	}
}



/*
	invoice_util_totals_process($type)
	
	Calculates the updated total and tax information for the form and saves
	the information to the session array.

	This fucntion is called by the various form processing functions such as invoice_forms_items_process.

	Values
	type	ar or ap
*/
function invoice_util_totals_process($type)
{
	log_debug("inc_invoices_forms", "Executing invoice_utils_totals_process($type)");
	
	$sessionform = $type ."_invoice";

	// reset counter so we don't accidently grow thr total!
	$_SESSION["form"][$sessionform]["amount"] = 0;
	

	// total up all the transactions
	foreach ($_SESSION["form"][$sessionform]["trans"] as $transaction)
	{
		$_SESSION["form"][$sessionform]["amount"] += $transaction["trans_amount"];
	}

	// apply tax
	if ($_SESSION["form"][$sessionform]["tax_enable"] == "on")
	{
		if ($_SESSION["form"][$sessionform]["tax_id"])
		{
			/*
				Tax can be calculated in two ways:
				1. Calculate the tax from the taxrate
				2. Let the user over-ride the tax field with their own value

					The amount_tax_orig form field allows us to detect if the user
					has tried to over-write the field with their own values.
			*/
			if ($_SESSION["form"][$sessionform]["amount_tax_orig"] && ($_SESSION["form"][$sessionform]["amount_tax"] != $_SESSION["form"][$sessionform]["amount_tax_orig"]))
			{				
				// user has over-ridden the amount to charge for tax.
				$_SESSION["form"][$sessionform]["amount_total"] = $_SESSION["form"][$sessionform]["amount"] + $_SESSION["form"][$sessionform]["amount_tax"];
			}
			else
			{
				// need to calculate tax value
				$taxrate = sql_get_singlevalue("SELECT taxrate as value FROM account_taxes WHERE id='". $_SESSION["form"][$sessionform]["tax_id"] ."'");
	
				$_SESSION["form"][$sessionform]["amount_tax"]	= $_SESSION["form"][$sessionform]["amount"] * ($taxrate / 100);
				$_SESSION["form"][$sessionform]["amount_total"]	= $_SESSION["form"][$sessionform]["amount"] + $_SESSION["form"][$sessionform]["amount_tax"];

				// set tax_amount_orig value
				$_SESSION["form"][$sessionform]["amount_tax_orig"] = $_SESSION["form"][$sessionform]["amount_tax"];
			}
			
			
		}
		else
		{
			$_SESSION["error"]["message"][] = "Tax has been enabled, but no tax type has been selected - please select a valid tax type, or disable tax on this invoice";
		}
	}
	else
	{
		$_SESSION["form"][$sessionform]["amount_total"]		= $_SESSION["form"][$sessionform]["amount"];
		$_SESSION["form"][$sessionform]["amount_tax"]		= "0";
		$_SESSION["form"][$sessionform]["amount_tax_orig"]	= "0";
	}
	
	// pad values
	$_SESSION["form"][$sessionform]["amount_total"]			= sprintf("%0.2f", $_SESSION["form"][$sessionform]["amount_total"]);
	$_SESSION["form"][$sessionform]["amount_tax"]			= sprintf("%0.2f", $_SESSION["form"][$sessionform]["amount_tax"]);
	$_SESSION["form"][$sessionform]["amount_tax_orig"]		= sprintf("%0.2f", $_SESSION["form"][$sessionform]["amount_tax_orig"]);
	$_SESSION["form"][$sessionform]["amount"]			= sprintf("%0.2f", $_SESSION["form"][$sessionform]["amount"]);

	// add to error array as well
	$_SESSION["error"]["amount"]		= $_SESSION["form"][$sessionform]["amount"];
	$_SESSION["error"]["amount_total"]	= $_SESSION["form"][$sessionform]["amount_total"];
	$_SESSION["error"]["amount_tax"]	= $_SESSION["form"][$sessionform]["amount_tax"];
	$_SESSION["error"]["amount_tax_orig"]	= $_SESSION["form"][$sessionform]["amount_tax_orig"];

	
	return 0;
}



/*
	invoice_form_item_process($type, $mode, $returnpage_error, $returnpage_success)

	Form for processing invoice form results

	Values
	type			"ar" or "ap" invoice
	mode			"edit" or "add" for the action to perform
	returnpage_error	Page to return to in event of errors or updates
	returnpage_success	Page to return to if successful.
*/
function invoice_form_item_process($type, $mode, $returnpage_error, $returnpage_success)
{
	log_debug("inc_invoices_forms", "Executing invoice_form_item_process($type, $mode, $returnpage_error, $returnpage_success)");

	
	/*
		Fetch all form data
	*/


	// general data
	$transid			= security_form_input_predefined("int", "id_trans", 0, "");
	$itemtype			= security_form_input_predefined("any", "itemtype", 1, "");

	$data["trans_amount"]		= security_form_input_predefined("money", "trans_amount", 1, "");
	$data["trans_account"]		= security_form_input_predefined("int", "trans_account", 1, "");


	// custom item type data
	switch ($itemtype)
	{
		case "simple":
			$data["trans_description"]	= security_form_input_predefined("any", "trans_description", 0, "");
		break;
	}


	//// ERROR CHECKING ///////////////////////


	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"][$type ."_invoice_". $mode ."_item"] = "failed";
		header("Location: ../../index.php?page=$returnpage_error&id=$id");
		exit(0);
	}
	else
	{
		// find an avaliable transaction ID if it's a new addition
		if (!$transid)
		{
			$count		= 0;
			$complete	= 0;
			
			while ($complete == 0)
			{
				if (!$_SESSION["form"][$type ."_invoice"]["trans"][$count])
				{
					$transid	= $count;
					$complete	= 1;
				}
				
				$count++;
			}
		}


		// save the transaction to the session aray
		$_SESSION["form"][$type ."_invoice"]["trans"][$transid]["trans_amount"]		= $data["trans_amount"];
		$_SESSION["form"][$type ."_invoice"]["trans"][$transid]["trans_account"]	= $data["trans_account"];
		$_SESSION["form"][$type ."_invoice"]["trans"][$transid]["trans_description"]	= $data["trans_description"];
		

		// update the totals + tax
		invoice_util_totals_process($type);
		
		// return to the main form
		$_SESSION["error"]["form"][$type ."_invoice"]	= "update";
		$_SESSION["form"][$type ."_invoice"]["action"]	= "main";

		header("Location: ../../index.php?page=$returnpage_error&id=$id");
		exit(0);
	}


} // end if invoice_form_item_process




/*
	invoice_form_details_process($type, $mode, $returnpage_error, $returnpage_success)

	Form for processing invoice form results

	Values
	type			"ar" or "ap" invoice
	mode			"edit" or "add" for the action to perform
	returnpage_error	Page to return to in event of errors or updates
	returnpage_success	Page to return to if successful.
*/
function invoice_form_details_process($type, $mode, $returnpage_error, $returnpage_success)
{
	log_debug("inc_invoices_forms", "Executing invoice_form_details_process($type, $mode, $returnpage_error, $returnpage_success)");

	
	/*
		Fetch all form data
	*/


	// get the ID for an edit
	if ($mode == "edit")
	{
		$id = security_form_input_predefined("int", "id_invoice", 1, "");
	}
	else
	{
		$id = NULL;
	}
	

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
	$data["date_invoice"]		= security_form_input_predefined("date", "date_invoice", $required, "");
	$data["date_due"]		= security_form_input_predefined("date", "date_due", $required, "");

	// tax
	$data["amount_tax"]		= security_form_input_predefined("money", "amount_tax", 0, "");
	$data["amount_tax_orig"]	= security_form_input_predefined("money", "amount_tax_orig", 0, "");
	$data["tax_enable"]		= security_form_input_predefined("any", "tax_enable", 0, "");
	$data["tax_id"]			= security_form_input_predefined("int", "tax_id", 0, "");

	// other
	$data["dest_account"]		= security_form_input_predefined("int", "dest_account", $required, "");


	/*
		Load all the data into the session array
	*/
	$_SESSION["form"][$type ."_invoice"]["customerid"]		= $data["customerid"];
	$_SESSION["form"][$type ."_invoice"]["employeeid"]		= $data["employeeid"];
	$_SESSION["form"][$type ."_invoice"]["notes"]			= $data["notes"];
	$_SESSION["form"][$type ."_invoice"]["code_invoice"]		= $data["code_invoice"];
	$_SESSION["form"][$type ."_invoice"]["code_ordernumber"]	= $data["code_ordernumber"];
	$_SESSION["form"][$type ."_invoice"]["code_ponumber"]		= $data["code_ponumber"];
	$_SESSION["form"][$type ."_invoice"]["date_invoice"]		= $data["date_invoice"];
	$_SESSION["form"][$type ."_invoice"]["date_due"]		= $data["date_due"];

	$_SESSION["form"][$type ."_invoice"]["amount_tax"]		= $data["amount_tax"];
	$_SESSION["form"][$type ."_invoice"]["amount_tax_orig"]		= $data["amount_tax_orig"];
	$_SESSION["form"][$type ."_invoice"]["tax_enable"]		= $data["tax_enable"];
	$_SESSION["form"][$type ."_invoice"]["tax_id"]			= $data["tax_id"];
	
	$_SESSION["form"][$type ."_invoice"]["dest_account"]		= $data["dest_account"];
	
	// update the totals + tax
	invoice_util_totals_process($type);



	// are we editing an existing invoice or adding a new one?
	if ($id)
	{
		$mode = "edit";

		// make sure the account actually exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM `account_$type` WHERE id='$id'";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			$_SESSION["error"]["message"][] = "The invoice you have attempted to edit - $id - does not exist in this system.";
		}
	}
	else
	{
		$mode = "add";
	}


	//// ERROR CHECKING ///////////////////////


	// make sure we don't choose a invoice invoice number that is already in use
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
		$_SESSION["error"]["form"][$type ."_invoice_". $mode] = "failed";
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

			Any other action types should be assigned to "update".
		*/


		if ($data["action"] != "save")
		{
			// add 1 more invoice row if the user has filled
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
			$_SESSION["error"]["form"][$type ."_invoice_". $mode] = "update";
			header("Location: ../../index.php?page=$returnpage_error&id=$id");
			exit(0);
		}
		else
		{
		
			// GENERATE INVOICE ID
			// if no invoice ID has been supplied, we now need to generate a unique invoice id
			if (!$data["code_invoice"])
				$data["code_invoice"] = invoice_generate_ar_invoiceid();

		
			// APPLY GENERAL OPTIONS
			if ($mode == "add")
			{
				/*
					Create new invoice
				*/
				
				$sql_obj		= New sql_query;
				$sql_obj->string	= "INSERT INTO `account_$type` (code_invoice) VALUES ('".$data["code_invoice"]."')";
				if (!$sql_obj->execute())
				{
					$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst attempting to create invoice";
				}

				$id = $sql_obj->fetch_insert_id();
			}

			if ($id)
			{
				/*
					Update general invoice details
				*/
				
				$sql_obj = New sql_query;
				
				$sql_obj->string = "UPDATE `account_$type` SET "
							."customerid='". $data["customerid"] ."', "
							."employeeid='". $data["employeeid"] ."', "
							."notes='". $data["notes"] ."', "
							."code_invoice='". $data["code_invoice"] ."', "
							."code_ordernumber='". $data["code_ordernumber"] ."', "
							."code_ponumber='". $data["code_ponumber"] ."', "
							."date_invoice='". $data["date_invoice"] ."', "
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
						journal_quickadd_event("account_$type", $id, "Transaction successfully created");
					}
					else
					{
						$_SESSION["notification"]["message"][] = "Transaction successfully updated.";
						journal_quickadd_event("account_$type", $id, "Transaction successfully updated");
					}
					
				}




				/*
					Create items for each invoice in the DB
				*/

				// delete the existing invoice items
				if ($mode == "edit")
				{
					$sql_obj = New sql_query;
					$sql_obj->string = "DELETE FROM account_trans WHERE type='$type' AND customid='$id'";
					$sql_obj->execute();
				}

				// create all the invoice items
				for ($i = 0; $i < $data["num_trans"]; $i++)
				{
					if ($data["trans"][$i]["amount"])
					{
						/*
							Double entry accounting requires two entries for any financial invoice
							1. Credit the source of the invoice (eg: withdrawl funds from current account)
							2. Debit the destination (eg: pay an expense account)

							For AR/AP invoices, we credit the summary account choosen by the user
							and debit the various accounts for all the items.
						*/

						
						// insert debit invoice
						$sql_obj		= New sql_query;
						$sql_obj->string	= "INSERT "
									."INTO account_trans ("
									."type, "
									."customid, "
									."date_trans, "
									."chartid, "
									."amount_debit, "
									."memo "
									.") VALUES ("
									."'$type', "
									."'$id', "
									."'". $data["date_invoice"] ."', "
									."'". $data["dest_account"] ."', "
									."'". $data["trans"][$i]["amount"] ."', "
									."'". $data["trans"][$i]["description"] ."' "
									.")";
						$sql_obj->execute();


						// insert credit invoice
						$sql_obj		= New sql_query;
						$sql_obj->string	= "INSERT "
									."INTO account_trans ("
									."type, "
									."customid, "
									."date_trans, "
									."chartid, "
									."amount_credit, "
									."memo "
									.") VALUES ("
									."'$type', "
									."'$id', "
									."'". $data["date_invoice"] ."', "
									."'". $data["trans"][$i]["account"] ."', "
									."'". $data["trans"][$i]["amount"] ."', "
									."'". $data["trans"][$i]["description"] ."' "
									.")";
						$sql_obj->execute();


					}
				}


				/*
					Create invoice entries for tax purposes
					credit
				*/

				// delete the existing invoice items
				if ($mode == "edit")
				{
					$sql_obj = New sql_query;
					$sql_obj->string = "DELETE FROM account_trans WHERE type='". $type ."_tax' AND customid='$id'";
					$sql_obj->execute();
				}


				if ($data["tax_enable"])
				{
					// get the account used by the tax
					$data["tax_chartid"] = sql_get_singlevalue("SELECT chartid as value FROM account_taxes WHERE id='". $data["tax_id"] ."'");

					if (!$data["tax_chartid"])
					{
						$_SESSION["error"]["message"][] = "Unable to determine chart ID for tax, tax entries may be incorrect or missing. Please report this error as a bug.";
					}
					else
					{
						// insert debit invoice
						$sql_obj		= New sql_query;
						$sql_obj->string	= "INSERT "
									."INTO account_trans ("
									."type, "
									."customid, "
									."date_trans, "
									."chartid, "
									."amount_debit "
									.") VALUES ("
									."'". $type ."_tax', "
									."'$id', "
									."'". $data["date_invoice"] ."', "
									."'". $data["dest_account"] ."', "
									."'". $data["amount_tax"] ."' "
									.")";
						$sql_obj->execute();


						// insert credit invoice
						$sql_obj		= New sql_query;
						$sql_obj->string	= "INSERT "
									."INTO account_trans ("
									."type, "
									."customid, "
									."date_trans, "
									."chartid, "
									."amount_credit "
									.") VALUES ("
									."'". $type ."_tax', "
									."'$id', "
									."'". $data["date_invoice"] ."', "
									."'". $data["tax_chartid"] ."', "
									."'". $data["amount_tax"] ."' "
									.")";
						$sql_obj->execute();
					}
					
				} // end if tax enabled

			}

			// display updated details
			header("Location: ../../index.php?page=$returnpage_success&id=$id");
			exit(0);
			
		} // end action response

	} // end if passed tests


} // end if invoice_form_details_process


?>
