<?php
/*
	include/accounts/inc_invoices_process.php

	Provides forms and processing code for adjusting the basic details of an invoice. This
	is used by both the AR and AP pages.
*/

require("../../include/accounts/inc_ledger.php");
require("../../include/accounts/inc_invoices.php");


/*
	FUNCTIONS
*/





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

	// TODO: it seems this function requests the $mode, but then works it out itself anyway.
	// check out what is going on here.

	/*
		Start the invoice
	*/
	$invoice	= New invoice;
	$invoice->type	= $type;
	
	
	/*
		Fetch all form data
	*/


	// get the ID for an edit
	if ($mode == "edit")
	{
		$invoice->id = security_form_input_predefined("int", "id_invoice", 1, "");
	}
	

	// general details
	if ($type == "ap")
	{
		$invoice->data["vendorid"]	= security_form_input_predefined("int", "vendorid", 1, "");
	}
	else
	{
		$invoice->data["customerid"]	= security_form_input_predefined("int", "customerid", 1, "");
	}
	
	$invoice->data["employeeid"]		= security_form_input_predefined("int", "employeeid", 1, "");
	$invoice->data["notes"]			= security_form_input_predefined("any", "notes", 0, "");
	
	$invoice->data["code_ordernumber"]	= security_form_input_predefined("any", "code_ordernumber", 0, "");
	$invoice->data["code_ponumber"]		= security_form_input_predefined("any", "code_ponumber", 0, "");
	$invoice->data["date_trans"]		= security_form_input_predefined("date", "date_trans", 1, "");
	$invoice->data["date_due"]		= security_form_input_predefined("date", "date_due", 1, "");

	// other
	$invoice->data["dest_account"]		= security_form_input_predefined("int", "dest_account", 1, "");


	// are we editing an existing invoice or adding a new one?
	if ($invoice->id)
	{
		$mode = "edit";

		// make sure the account actually exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM `account_". $invoice->type ."` WHERE id='". $invoice->id ."'";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			$_SESSION["error"]["message"][] = "The invoice you have attempted to edit - ". $invoice->id ." - does not exist in this system.";
		}
	}
	else
	{
		$mode = "add";
	}


	// invoice must be provided by edit page, but not by add invoice, since we can just generate a new one
	if ($mode == "add")
	{
		$this->data["code_invoice"]		= security_form_input_predefined("any", "code_invoice", 0, "");
	}
	else
	{
		$this->data["code_invoice"]		= security_form_input_predefined("any", "code_invoice", 1, "");
	}



	//// ERROR CHECKING ///////////////////////


	// make sure we don't choose a invoice invoice number that is already in use
	if ($invoice->data["code_invoice"])
	{
		$invoice->prepare_code_invoice($invoice->data["code_invoice"]);
	}


	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"][$type ."_invoice_". $mode] = "failed";
		header("Location: ../../index.php?page=$returnpage_error&id=". $invoice->id ."");
		exit(0);
	}
	else
	{
		// GENERATE INVOICE ID
		// if no invoice ID has been supplied, we now need to generate a unique invoice id
		if (!$invoice->data["code_invoice"])
		{
			$invoice->prepare_code_invoice();
		}

		// APPLY GENERAL OPTIONS
		if ($mode == "add")
		{
			// create a new invoice
			if ($invoice->action_create())
			{
				$_SESSION["notification"]["message"][] = "Invoice successfully created.";
				journal_quickadd_event("account_". $invoice->type ."", $invoice->id, "Invoice successfully created");
			}
			else
			{
				$_SESSION["error"]["message"] = "An error occured whilst attempting to create the invoice";
			}
		}
		else
		{
			// update an existing invoice
			if ($invoice->action_update())
			{
				$_SESSION["notification"]["message"][] = "Invoice successfully updated.";
				journal_quickadd_event("account_". $invoice->type ."", $invoice->id, "Invoice successfully updated");
			}
			else
			{
				$_SESSION["error"]["message"] = "An error occured whilst attempting to update the invoice";
			}
		}

		
		// display updated details
		header("Location: ../../index.php?page=$returnpage_success&id=". $invoice->id ."");
		exit(0);
			

	} // end if passed tests


} // end if invoice_form_details_process





/*
	invoice_form_export_process($type, $mode, $returnpage_error, $returnpage_success)

	Form for processing export form results

	Values
	type			"ar" or "ap" invoice
	returnpage_error	Page to return to in event of errors or updates
	returnpage_success	Page to return to if successful.
*/
function invoice_form_export_process($type, $returnpage_error, $returnpage_success)
{
	log_debug("inc_invoices_forms", "Executing invoice_form_export_process($type, $returnpage_error, $returnpage_success)");

	/*
		Start the invoice
	*/
	$invoice	= New invoice;
	$invoice->type	= $type;
	
	
	/*
		Fetch all form data
	*/


	// get the ID for an edit
	$invoice->id = security_form_input_predefined("int", "id_invoice", 1, "");
	

	// general details
	$data["formname"] = security_form_input_predefined("any", "formname", 1, "");

	if ($data["formname"] == "invoice_export_email")
	{
		// send email
		$data["sender"]		= security_form_input_predefined("any", "sender", 1, "");
		$data["subject"]	= security_form_input_predefined("any", "subject", 1, "");
		$data["email_to"]	= security_form_input_predefined("any", "email_to", 1, "");
		$data["email_cc"]	= security_form_input_predefined("any", "email_cc", 0, "");
		$data["email_bcc"]	= security_form_input_predefined("any", "email_bcc", 0, "");
		$data["message"]	= security_form_input_predefined("any", "email_message", 1, "");
	

		// check if email sending is permitted
		if (sql_get_singlevalue("SELECT value FROM config WHERE name='EMAIL_ENABLE'") != "enabled")
		{
			log_write("error", "inc_invoices_process", "Sorry, the ability to email invoices has been disabled. Please contact your system administrator if you require this feature to be enabled.");
		}
	}
	else
	{
		// PDF download
		$data["invoice_mark_as_sent"] = security_form_input_predefined("any", "invoice_mark_as_sent", 0, "");
	}


	// make sure that the invoice exists
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM `account_". $invoice->type ."` WHERE id='". $invoice->id ."'";
	$sql_obj->execute();

	if (!$sql_obj->num_rows())
	{
		$_SESSION["error"]["message"][] = "The invoice you have attempted to edit - ". $invoice->id ." - does not exist in this system.";
	}




	//// ERROR CHECKING ///////////////////////


	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		header("Location: ../../index.php?page=$returnpage_error&id=". $invoice->id ."");
		exit(0);
	}
	else
	{
		if ($data["formname"] == "invoice_export_email")
		{
			/*
				Generate a PDF of the invoice and email it to the customer
			*/

			$invoice->load_data();
			$invoice->email_invoice($data["sender"], $data["email_to"], $data["email_cc"], $data["email_bcc"], $data["subject"], $data["message"]);

			$_SESSION["notification"]["message"][] = "Email sent successfully.";
		}
		else
		{

			/*
				Mark invoice as being sent if user requests it
			*/
			if ($data["invoice_mark_as_sent"])
			{
				$sql_obj		= New sql_query;
				$sql_obj->string	= "UPDATE account_". $invoice->type ." SET date_sent='". date("Y-m-d") ."', sentmethod='manual' WHERE id='". $invoice->id ."'";
				$sql_obj->execute();
			}


			/*
				Provide PDF to user's browser
			*/
			
			// generate PDF
			$invoice->load_data();
			$invoice->generate_pdf();


			// PDF headers
			$filename = "/tmp/invoice_". $invoice->data["code_invoice"] .".pdf";
			
			// required for IE, otherwise Content-disposition is ignored
			if (ini_get('zlib.output_compression'))
				ini_set('zlib.output_compression', 'Off');

			header("Pragma: public"); // required
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Cache-Control: private",false); // required for certain browsers 
			header("Content-Type: application/pdf");
			
			header("Content-Disposition: attachment; filename=\"".basename($filename)."\";" );
			header("Content-Transfer-Encoding: binary");


			// output the PDF
			print $invoice->obj_pdf->output;
			exit(0);
		}

		
		// display updated details
		header("Location: ../../index.php?page=$returnpage_success&id=". $invoice->id ."");
		exit(0);
			

	} // end if passed tests


} // end if invoice_form_export_process







/*
	invoice_form_delete_process($type, $mode, $returnpage_error, $returnpage_success)

	Form for processing invoice deletion form requests.

	Values
	type			"ar" or "ap" invoice
	returnpage_error	Page to return to in event of errors or updates
	returnpage_success	Page to return to if successful.
*/
function invoice_form_delete_process($type, $returnpage_error, $returnpage_success)
{
	log_debug("inc_invoices_forms", "Executing invoice_form_delete_process($type, $mode, $returnpage_error, $returnpage_success)");

	
	/*
		Fetch all form data
	*/


	// get form data
	$id				= security_form_input_predefined("int", "id_invoice", 1, "");
	$data["delete_confirm"]		= security_form_input_predefined("any", "delete_confirm", 1, "You must confirm the deletion");

	// we don't use this value (since we can't trust it) but we need to read it
	// in here to work around a limitation in the Amberphplib framework
	$data["date_create"]		= security_form_input_predefined("any", "date_create", 1, "");

	//// ERROR CHECKING ///////////////////////
	
	// make sure the invoice actually exists
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id, date_create FROM `account_$type` WHERE id='$id' LIMIT 1";
	$sql_obj->execute();

	if (!$sql_obj->num_rows())
	{
		$_SESSION["error"]["message"][] = "The invoice you have attempted to edit - $id - does not exist in this system.";
	}
	else
	{
		$sql_obj->fetch_array();

		// make sure the invoice can actually be deleted
		$expirydate = sql_get_singlevalue("SELECT value FROM config WHERE name='ACCOUNTS_INVOICE_LOCK'");

		if (time_date_to_timestamp($sql_obj->data[0]["date_create"]) < mktime() - time_date_to_timestamp($expirydate))
		{
			$_SESSION["error"]["message"][] = "The invoice requested can not be deleted, it is now older than $expirydate days and is therefore locked";
		}
	}
		


	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"][$type ."_invoice_delete"] = "failed";
		header("Location: ../../index.php?page=$returnpage_error&id=$id");
		exit(0);
	}
	else
	{

		/*
			Delete Invoice
		*/
		$invoice	= New invoice;
		$invoice->id	= $id;
		$invoice->type	= $type;

		if ($invoice->action_delete())
		{
			$_SESSION["notification"]["message"][] = "Invoice has been successfully deleted.";
		}
		else
		{
			$_SESSION["error"]["message"][] = "Some problems were experienced while deleting the invoice.";
		}

		
		// display updated details
		header("Location: ../../index.php?page=$returnpage_success&id=$id");
		exit(0);
			
	} // end if passed tests


} // end if invoice_form_delete_process


?>
