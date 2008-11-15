<?php
/*
	include/accounts/inc_quotes_convert.php

	Provides forms and processing code to convert a quotation into an AR invoice.
*/




/*
	FUNCTIONS
*/




/*
	quotes_form_convert_render($id, $processpage)

	Provides a form for converting the quote into an invoice - includes some input fields needed
	to get further information before conversion can be performed.

	Values
	id		ID of quote to convert.
	processpage	Page to submit the form too

	Return Codes
	0	failure
	1	success
*/
function quotes_form_convert_render($id, $processpage)
{
	log_debug("inc_quotes_convert", "Executing quotes_form_convert_render($id, $processpage)");

	
	/*
		Make sure quote does exist!
	*/
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM account_quotes WHERE id='$id'";
	$sql_obj->execute();
		
	if (!$sql_obj->num_rows())
	{
		print "<p><b>Error: The requested quote does not exist. <a href=\"index.php?page=accounts/quotes/quotes.php\">Try looking on the quotes page.</a></b></p>";
		return 0;
	}


	/*
		Start Form
	*/
	$form = New form_input;
	$form->formname		= "_quote_convert";
	$form->language		= $_SESSION["user"]["lang"];

	$form->action		= $processpage;
	$form->method		= "POST";
	


	/*
		Define form structure
	*/
	
	// basic details
	$structure = NULL;
	$structure["fieldname"] 	= "code_quote";
	$structure["type"]		= "text";
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
		


	// dates
	$structure = NULL;
	$structure["fieldname"] 	= "date_trans";
	$structure["type"]		= "date";
	$structure["defaultvalue"]	= date("Y-m-d");
	$form->add_input($structure);

	$structure = NULL;
	$structure["fieldname"] 	= "date_due";
	$structure["type"]		= "date";
	$structure["defaultvalue"]	= invoice_calc_duedate(date("Y-m-d"));
	$form->add_input($structure);


	// destination account
	$structure = charts_form_prepare_acccountdropdown("dest_account", "ar_summary_account");
		
	if ($structure["values"])
	{
		if (count(array_keys($structure["values"])) == 1)
		{
			// if there is only 1 tax option avaliable, select it as the default
			$structure["options"]["noselectoption"] = "yes";
		}
	}
	
	$structure["options"]["req"]	= "yes";
	$form->add_input($structure);


	



	// ID
	$structure = NULL;
	$structure["fieldname"]		= "id_quote";
	$structure["type"]		= "hidden";
	$structure["defaultvalue"]	= $id;
	$form->add_input($structure);	


	// submit
	$structure = NULL;
	$structure["fieldname"]		= "submit";
	$structure["type"]		= "submit";
	$structure["defaultvalue"]	= "Convert to Invoice";
	$form->add_input($structure);


	// load data
	$form->sql_query = "SELECT date_create, code_quote FROM account_quotes WHERE id='$id'";
	$form->load_data();


	/*
		Display Form
	*/
	$form->subforms["quote_convert_details"]	= array("code_quote", "code_invoice", "code_ordernumber", "code_ponumber", "date_trans", "date_due");
	$form->subforms["quote_convert_financials"]	= array("dest_account");
	$form->subforms["hidden"]			= array("id_quote");
	$form->subforms["submit"]			= array("submit");
	
	$form->render_form();
}




/*
	quotes_form_convert_process($mode, $returnpage_error, $returnpage_success)

	Processes form data and converts a quote into an AR invoice.

	Values
	returnpage_error	Page to return to in event of errors or updates
	returnpage_success	Page to return to if successful.
*/
function quotes_form_convert_process($returnpage_error, $returnpage_success)
{
	log_debug("inc_quotes_forms", "Executing quotes_form_convert_process($mode, $returnpage_error, $returnpage_success)");

	
	/*
		Fetch all form data
	*/

	$id				= security_form_input_predefined("int", "id_quote", 1, "");


	// general data
	$data["code_invoice"]		= security_form_input_predefined("any", "code_invoice", 0, "");
	$data["code_ordernumber"]	= security_form_input_predefined("any", "code_ordernumber", 0, "");
	$data["code_ponumber"]		= security_form_input_predefined("any", "code_ponumber", 0, "");
	$data["date_trans"]		= security_form_input_predefined("date", "date_trans", 1, "");
	$data["date_due"]		= security_form_input_predefined("date", "date_due", 1, "");

	// other
	$data["dest_account"]		= security_form_input_predefined("int", "dest_account", 1, "");




	//// ERROR CHECKING ///////////////////////
	
	// make sure the quote actually exists, and fetch various fields that we need to create the invoice.
	$sql_quote_obj		= New sql_query;
	$sql_quote_obj->string	= "SELECT id, employeeid, customerid, amount_total, amount_tax, amount, notes FROM `account_quotes` WHERE id='$id' LIMIT 1";
	$sql_quote_obj->execute();

	if (!$sql_quote_obj->num_rows())
	{
		$_SESSION["error"]["message"][] = "The quote you have attempted to edit - $id - does not exist in this system.";
	}
	else
	{
		$sql_quote_obj->fetch_array();
	}
		


	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"]["quote_convert"] = "failed";
		header("Location: ../../index.php?page=$returnpage_error&id=$id");
		exit(0);
	}
	else
	{
		// make an invoice ID if one is not supplied by the user
		if (!$data["code_invoice"])
			$data["code_invoice"] = config_generate_uniqueid("ACCOUNTS_AR_INVOICENUM", "SELECT id FROM account_ar WHERE code_invoice='VALUE'");
		
		
		/*
			Create new invoice
		*/
			
		$sql_obj		= New sql_query;
		$sql_obj->string	= "INSERT INTO `account_ar` (code_invoice, date_create) VALUES ('".$data["code_invoice"]."', '". date("Y-m-d") ."')";
		if (!$sql_obj->execute())
		{
			$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst attempting to create invoice";
		}

		$invoiceid = $sql_obj->fetch_insert_id();
		

		if ($invoiceid)
		{
			/*
				Update general invoice details
			*/
			
			$sql_obj = New sql_query;
			
			$sql_obj->string = "UPDATE `account_ar` SET "
						."customerid='". $sql_quote_obj->data[0]["customerid"] ."', "
						."employeeid='". $sql_quote_obj->data[0]["employeeid"] ."', "
						."notes='". $sql_quote_obj->data[0]["notes"] ."', "
						."code_invoice='". $data["code_invoice"] ."', "
						."code_ordernumber='". $data["code_ordernumber"] ."', "
						."code_ponumber='". $data["code_ponumber"] ."', "
						."date_trans='". $data["date_trans"] ."', "
						."date_due='". $data["date_due"] ."', "
						."dest_account='". $data["dest_account"] ."', "
						."amount='". $sql_quote_obj->data[0]["amount"] ."', "
						."amount_tax='". $sql_quote_obj->data[0]["amount_tax"] ."', "
						."amount_total='". $sql_quote_obj->data[0]["amount_total"] ."' "
						."WHERE id='$invoiceid'";

			if (!$sql_obj->execute())
			{
				$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst attempting to fill in invoice details.";
			}



			/*
				Migrate all the items from the quote to the invoice
			*/
			
			$sql_obj		= New sql_query;
			$sql_obj->string	= "UPDATE account_items SET invoiceid='$invoiceid', invoicetype='ar' WHERE invoiceid='$id' AND invoicetype='quotes'";
			
			if (!$sql_obj->execute())
			{
				$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst attempting to migrate quote items to invoice.";
			}


			/*
				Call functions to create transaction entries for all the items. (remember that the quote had nothing in account_trans for the items)
			*/
			
			invoice_items_update_ledger($invoiceid, "ar");



			/*
				Migrate the journal
			*/
			
			$sql_obj		= New sql_query;
			$sql_obj->string	= "UPDATE journal SET customid='$invoiceid', journalname='account_ar' WHERE customid='$id' AND journalname='account_quotes'";
			
			if (!$sql_obj->execute())
			{
				$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst attempting to migrate quote journal to invoice.";
			}




			/*
				Delete the quote
			*/
			
			$sql_obj		= New sql_query;
			$sql_obj->string	= "DELETE FROM account_quotes WHERE id='$id'";
			
			if (!$sql_obj->execute())
			{
				$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst attempting to delete the quote.";
			}
		}



		if (!$_SESSION["error"]["message"])
		{
			$_SESSION["notification"]["message"][] = "Quotation has been converted to an invoice successfully.";

			journal_quickadd_event("account_ar", $invoiceid, "Converted quotation into invoice");
		}
	

		// display updated details
		header("Location: ../../index.php?page=$returnpage_success&id=$invoiceid");
		exit(0);
			
	} // end if passed tests


} // end if quotes_form_convert_process


?>
