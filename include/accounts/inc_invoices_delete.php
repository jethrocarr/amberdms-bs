<?php
/*
	include/accounts/inc_invoices_delete.php

	Provides forms and processing code for deleting unwanted invoices. This is used by both the AR and AP pages.
*/


/*
	FUNCTIONS
*/




/*
	invoice_form_delete_render($type, $id, $processpage)

	This function provides a form for adjusting the basic details of the invoice.


	Values
	type		Either "ar" or "ap"
	id		If editing/viewing an existing invoice, provide the ID
	processpage	Page to submit the form too

	Return Codes
	0	failure
	1	success
*/
function invoice_form_delete_render($type, $id, $processpage)
{
	log_debug("inc_invoices_delete", "Executing invoice_form_delete_render($type, $id, $processpage)");

	
	/*
		Make sure invoice does exist!
	*/
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM account_$type WHERE id='$id'";
	$sql_obj->execute();
		
	if (!$sql_obj->num_rows())
	{
		print "<p><b>Error: The requested invoice does not exist. <a href=\"index.php?page=accounts/$type/$type.php\">Try looking on the invoice/invoice list page.</a></b></p>";
		return 0;
	}


	/*
		Start Form
	*/
	$form = New form_input;
	$form->formname		= $type ."_invoice_delete";
	$form->language		= $_SESSION["user"]["lang"];

	$form->action		= $processpage;
	$form->method		= "POST";
	


	/*
		Define form structure
	*/
	
	// basic details
	$structure = NULL;
	$structure["fieldname"] 	= "code_invoice";
	$structure["type"]		= "text";
	$form->add_input($structure);
	
	$structure = NULL;
	$structure["fieldname"] 	= "delete_confirm";
	$structure["type"]		= "checkbox";
	$structure["options"]["label"]	= "Yes, I wish to delete this invoice and realise that once deleted the data can not be recovered.";
	$form->add_input($structure);



	// hidden date field
	$structure = NULL;
	$structure["fieldname"] 	= "date_create";
	$structure["type"]		= "hidden";
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
	$structure["defaultvalue"]	= "Delete Invoice";
	$form->add_input($structure);


	// load data
	$form->sql_query = "SELECT date_create, code_invoice FROM account_$type WHERE id='$id'";
	$form->load_data();


	$form->subforms[$type ."_invoice_delete"]	= array("code_invoice", "delete_confirm");

	$form->subforms["hidden"]			= array("id_invoice", "date_create");
	$form->subforms["submit"]			= array("submit");
	


	/*
		Verify that the invoice can be deleted, then render delete form.
	*/
	
	$expirydate = sql_get_singlevalue("SELECT value FROM config WHERE name='ACCOUNTS_INVOICE_LOCK'");


	if (time_date_to_timestamp($form->structure["date_create"]["defaultvalue"]) < mktime() - time_date_to_timestamp($expirydate))
	{
		print "<p><b>Sorry, this invoice has now locked and can not be deleted.</b></p>";
	}
	else
	{
		$form->render_form();
	}


}




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
