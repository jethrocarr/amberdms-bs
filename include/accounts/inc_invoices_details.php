<?php
/*
	include/accounts/inc_invoices_details.php

	Provides forms and processing code for adjusting the basic details of an invoice. This
	is used by both the AR and AP pages.
*/


/*
	FUNCTIONS
*/




/*
	invoice_form_details_render($type, $id, $processpage)

	This function provides a form for adjusting the basic details of the invoice.


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

	$form->action		= $processpage;
	$form->method		= "POST";
	


	/*
		Define form structure
	*/
	
	// basic details
	if ($type == "ap")
	{
		$structure = form_helper_prepare_dropdownfromdb("vendorid", "SELECT id, name_vendor as label FROM vendors");
		$structure["options"]["req"]	= "yes";
		$form->add_input($structure);
	}
	else
	{
		$structure = form_helper_prepare_dropdownfromdb("customerid", "SELECT id, name_customer as label FROM customers");
		$structure["options"]["req"]	= "yes";
		$form->add_input($structure);
	}
		
	$structure = form_helper_prepare_dropdownfromdb("employeeid", "SELECT id, name_staff as label FROM staff");
	$structure["options"]["req"]	= "yes";
	$form->add_input($structure);

	$structure = NULL;
	$structure["fieldname"] 	= "code_invoice";
	$structure["type"]		= "input";

	if ($mode == "edit")
		$structure["options"]["req"] = "yes";

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
	$structure["options"]["height"]	= "100";
	$structure["options"]["width"]	= 500;
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
	if ($type == "ap")
	{
		$structure = charts_form_prepare_acccountdropdown("dest_account", "ap_summary_account");
	}
	else
	{
		$structure = charts_form_prepare_acccountdropdown("dest_account", "ar_summary_account");
	}
		
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
	$structure["fieldname"]		= "id_invoice";
	$structure["type"]		= "hidden";
	$structure["defaultvalue"]	= $id;
	$form->add_input($structure);	


	// submit
	$structure = NULL;
	$structure["fieldname"]		= "submit";
	$structure["type"]		= "submit";
	$structure["defaultvalue"]	= "Save Changes";
	$form->add_input($structure);


	// load data
	if ($type == "ap")
	{
		$form->sql_query = "SELECT vendorid, employeeid, code_invoice, code_ordernumber, code_ponumber, notes, date_trans, date_due FROM account_$type WHERE id='$id'";
	}
	else
	{
		$form->sql_query = "SELECT customerid, employeeid, code_invoice, code_ordernumber, code_ponumber, notes, date_trans, date_due FROM account_$type WHERE id='$id'";
	}
	
	$form->load_data();



	/*
		Display Form
	*/
	
	if ($type == "ap")
	{
		$form->subforms[$type ."_invoice_details"]	= array("vendorid", "employeeid", "code_invoice", "code_ordernumber", "code_ponumber", "date_trans", "date_due");
	}
	else
	{
		$form->subforms[$type ."_invoice_details"]	= array("customerid", "employeeid", "code_invoice", "code_ordernumber", "code_ponumber", "date_trans", "date_due");
	}
	
	$form->subforms[$type ."_invoice_financials"]	= array("dest_account");
	$form->subforms[$type ."_invoice_other"]	= array("notes");

	$form->subforms["hidden"]			= array("id_invoice");
	$form->subforms["submit"]			= array("submit");
	
	$form->render_form();


}




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
	

	// general details
	if ($type == "ap")
	{
		$data["vendorid"]		= security_form_input_predefined("int", "vendorid", 1, "");
	}
	else
	{
		$data["customerid"]		= security_form_input_predefined("int", "customerid", 1, "");
	}
	
	$data["employeeid"]		= security_form_input_predefined("int", "employeeid", 1, "");
	$data["notes"]			= security_form_input_predefined("any", "notes", 0, "");
	
	$data["code_ordernumber"]	= security_form_input_predefined("any", "code_ordernumber", 0, "");
	$data["code_ponumber"]		= security_form_input_predefined("any", "code_ponumber", 0, "");
	$data["date_trans"]		= security_form_input_predefined("date", "date_trans", 1, "");
	$data["date_due"]		= security_form_input_predefined("date", "date_due", 1, "");

	// other
	$data["dest_account"]		= security_form_input_predefined("int", "dest_account", 1, "");


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


	// invoice must be provided by edit page, but not by add invoice, since we can just generate a new one
	if ($mode == "add")
	{
		$data["code_invoice"]		= security_form_input_predefined("any", "code_invoice", 0, "");
	}
	else
	{
		$data["code_invoice"]		= security_form_input_predefined("any", "code_invoice", 1, "");
	}

	//// ERROR CHECKING ///////////////////////


	// make sure we don't choose a invoice invoice number that is already in use
	if ($data["code_invoice"])
	{
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM `account_$type` WHERE code_invoice='". $data["code_invoice"] ."'";
		
		if ($id)
			$sql_obj->string .= " AND id!='$id'";
	
		// for AP invoices, the ID only need to be unique for the particular vendor we are working with, since
		// it's almost guaranteed that different vendors will use the same numbering scheme for their invoices
		if ($type == "ap")
			$sql_obj->string .= " AND vendorid='". $data["vendorid"] ."'";
			
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
		// GENERATE INVOICE ID
		// if no invoice ID has been supplied, we now need to generate a unique invoice id
		if (!$data["code_invoice"])
			$data["code_invoice"] = invoice_generate_invoiceid($type);

		// APPLY GENERAL OPTIONS
		if ($mode == "add")
		{
			/*
				Create new invoice
			*/
			
			$sql_obj		= New sql_query;
			$sql_obj->string	= "INSERT INTO `account_$type` (code_invoice, date_create) VALUES ('".$data["code_invoice"]."', '". date("Y-m-d") ."')";
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
			
			if ($type == "ap")
			{
				$sql_obj->string = "UPDATE `account_$type` SET "
							."vendorid='". $data["vendorid"] ."', "
							."employeeid='". $data["employeeid"] ."', "
							."notes='". $data["notes"] ."', "
							."code_invoice='". $data["code_invoice"] ."', "
							."code_ordernumber='". $data["code_ordernumber"] ."', "
							."code_ponumber='". $data["code_ponumber"] ."', "
							."date_trans='". $data["date_trans"] ."', "
							."date_due='". $data["date_due"] ."', "
							."dest_account='". $data["dest_account"] ."' "
							."WHERE id='$id'";
			}
			else
			{
				$sql_obj->string = "UPDATE `account_$type` SET "
							."customerid='". $data["customerid"] ."', "
							."employeeid='". $data["employeeid"] ."', "
							."notes='". $data["notes"] ."', "
							."code_invoice='". $data["code_invoice"] ."', "
							."code_ordernumber='". $data["code_ordernumber"] ."', "
							."code_ponumber='". $data["code_ponumber"] ."', "
							."date_trans='". $data["date_trans"] ."', "
							."date_due='". $data["date_due"] ."', "
							."dest_account='". $data["dest_account"] ."' "
							."WHERE id='$id'";
			}
			
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

			// display updated details
			header("Location: ../../index.php?page=$returnpage_success&id=$id");
			exit(0);
			
		} // end if ID

	} // end if passed tests


} // end if invoice_form_details_process


?>
