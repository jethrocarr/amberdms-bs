<?php
/*
	include/accounts/inc_quotes_delete.php

	Provides forms and processing code for deleting unwanted quotes.
*/


/*
	FUNCTIONS
*/




/*
	quotes_form_delete_render($id, $processpage)

	Provides form for deleting the selected quote.

	Values
	id		ID of quote to delete.
	processpage	Page to submit the form too

	Return Codes
	0	failure
	1	success
*/
function quotes_form_delete_render($id, $processpage)
{
	log_debug("inc_quotes_delete", "Executing quotes_form_delete_render($id, $processpage)");

	
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
	$form->formname		= "_quote_delete";
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
	$structure["fieldname"] 	= "delete_confirm";
	$structure["type"]		= "checkbox";
	$structure["options"]["label"]	= "Yes, I wish to delete this quote and realise that once deleted the data can not be recovered.";
	$form->add_input($structure);



	// hidden date field
	$structure = NULL;
	$structure["fieldname"] 	= "date_create";
	$structure["type"]		= "hidden";
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
	$structure["defaultvalue"]	= "Delete Quote";
	$form->add_input($structure);


	// load data
	$form->sql_query = "SELECT date_create, code_quote FROM account_quotes WHERE id='$id'";
	$form->load_data();


	/*
		Display Form
	*/
	$form->subforms["quote_delete"]			= array("code_quote");
	$form->subforms["hidden"]			= array("id_quote", "date_create");
	$form->subforms["submit"]			= array("delete_confirm", "submit");
	
	$form->render_form();
}




/*
	quotes_form_delete_process($mode, $returnpage_error, $returnpage_success)

	Form for processing quote deletion form requests.

	Values
	returnpage_error	Page to return to in event of errors or updates
	returnpage_success	Page to return to if successful.
*/
function quotes_form_delete_process($returnpage_error, $returnpage_success)
{
	log_debug("inc_quotes_forms", "Executing quotes_form_delete_process($mode, $returnpage_error, $returnpage_success)");

	
	/*
		Fetch all form data
	*/


	// get form data
	$id				= security_form_input_predefined("int", "id_quote", 1, "");
	$data["delete_confirm"]		= security_form_input_predefined("any", "delete_confirm", 1, "You must confirm the deletion");

	// we don't use this value (since we can't trust it) but we need to read it
	// in here to work around a limitation in the Amberphplib framework
	$data["date_create"]		= security_form_input_predefined("any", "date_create", 1, "");


	//// ERROR CHECKING ///////////////////////
	
	// make sure the quote actually exists
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id, date_create FROM `account_quotes` WHERE id='$id' LIMIT 1";
	$sql_obj->execute();

	if (!$sql_obj->num_rows())
	{
		$_SESSION["error"]["message"][] = "The quote you have attempted to edit - $id - does not exist in this system.";
	}
		


	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"]["quote_delete"] = "failed";
		header("Location: ../../index.php?page=$returnpage_error&id=$id");
		exit(0);
	}
	else
	{

		// delete quote itself
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM account_quotes WHERE id='$id'";
		$sql_obj->execute();

		// delete all the item options
		$sql_item_obj		= New sql_query;
		$sql_item_obj->string	= "SELECT id FROM account_items WHERE invoicetype='quotes' AND invoiceid='$id'";
		$sql_item_obj->execute();

		if ($sql_item_obj->num_rows())
		{
			$sql_item_obj->fetch_array();

			foreach ($sql_item_obj->data as $data)
			{
				$sql_obj		= New sql_query;
				$sql_obj->string	= "DELETE FROM account_items_options WHERE itemid='". $data["id"] ."'";
				$sql_obj->execute();
			}
		}


		// delete all the quote items
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM account_items WHERE invoicetype='quotes' AND invoiceid='$id'";
		$sql_obj->execute();
				
		// delete quote journal entries
		journal_delete_entire("account_quotes", $id);

		// display updated details
		header("Location: ../../index.php?page=$returnpage_success&id=$id");
		exit(0);
			
	} // end if passed tests


} // end if quotes_form_delete_process


?>
