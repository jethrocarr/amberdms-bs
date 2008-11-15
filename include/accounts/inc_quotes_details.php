<?php
/*
	include/accounts/inc_quotes_details.php

	Provides forms and processing code for adjusting the basic details of an quote. This
	code is simular to the inc_quotes_details.php page, but due to the number of variations
	it was decided to split it into a seporate function.
*/


/*
	FUNCTIONS
*/




/*
	quotes_form_details_render($type, $id, $processpage)

	This function provides a form for adjusting the basic details of the quote.


	Values
	id		If editing/viewing an existing quote, provide the ID
	processpage	Page to submit the form too

	Return Codes
	0	failure
	1	success
*/
function quotes_form_details_render($id, $processpage)
{
	log_debug("inc_quotes_details", "Executing quotes_form_details_render($id, $processpage)");

	if ($id)
	{
		$mode = "edit";
	}
	else
	{
		$mode = "add";
	}

	
	/*
		Make sure quote does exist!
	*/
	if ($mode == "edit")
	{
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_quotes WHERE id='$id'";
		$sql_obj->execute();
		
		if (!$sql_obj->num_rows())
		{
			print "<p><b>Error: The requested quote does not exist. <a href=\"index.php?page=accounts/quotes/quotes.php\">Try looking on the quotes page.</a></b></p>";
			return 0;
		}
	}


	/*
		Start Form
	*/
	$form = New form_input;
	$form->formname		= "quote_". $mode;
	$form->language		= $_SESSION["user"]["lang"];

	$form->action		= $processpage;
	$form->method		= "POST";
	


	/*
		Define form structure
	*/
	
	// basic details
	$structure = form_helper_prepare_dropdownfromdb("customerid", "SELECT id, name_customer as label FROM customers");
	$structure["options"]["req"]	= "yes";
	$form->add_input($structure);
	
		
	$structure = form_helper_prepare_dropdownfromdb("employeeid", "SELECT id, name_staff as label FROM staff");
	$structure["options"]["req"]	= "yes";
	$form->add_input($structure);

	$structure = NULL;
	$structure["fieldname"] 	= "code_quote";
	$structure["type"]		= "input";

	if ($mode == "edit")
		$structure["options"]["req"] = "yes";

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
	$structure["fieldname"] 	= "date_validtill";
	$structure["type"]		= "date";
	$structure["defaultvalue"]	= quotes_calc_duedate(date("Y-m-d"));
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
	$structure["defaultvalue"]	= "Save Changes";
	$form->add_input($structure);


	// load data
	$form->sql_query = "SELECT customerid, employeeid, code_quote, notes, date_trans, date_validtill FROM account_quotes WHERE id='$id'";
	$form->load_data();



	/*
		Display Form
	*/
	
	$form->subforms["quote_details"]	= array("customerid", "employeeid", "code_quote", "date_trans", "date_validtill");
	$form->subforms["quote_other"]		= array("notes");

	$form->subforms["hidden"]		= array("id_quote");
	$form->subforms["submit"]		= array("submit");
	
	$form->render_form();


}




/*
	quotes_form_details_process($mode, $returnpage_error, $returnpage_success)

	Form for processing quote form results

	Values
	mode			"edit" or "add" for the action to perform
	returnpage_error	Page to return to in event of errors or updates
	returnpage_success	Page to return to if successful.
*/
function quotes_form_details_process($mode, $returnpage_error, $returnpage_success)
{
	log_debug("inc_quotes_details", "Executing quotes_form_details_process($mode, $returnpage_error, $returnpage_success)");

	
	/*
		Fetch all form data
	*/


	// get the ID for an edit
	if ($mode == "edit")
	{
		$id = security_form_input_predefined("int", "id_quote", 1, "");
	}
	else
	{
		$id = NULL;
	}
	

	// general details
	$data["customerid"]		= security_form_input_predefined("int", "customerid", 1, "");
	$data["employeeid"]		= security_form_input_predefined("int", "employeeid", 1, "");
	$data["notes"]			= security_form_input_predefined("any", "notes", 0, "");
	
	$data["date_trans"]		= security_form_input_predefined("date", "date_trans", 1, "");
	$data["date_validtill"]		= security_form_input_predefined("date", "date_validtill", 1, "");


	// are we editing an existing quote or adding a new one?
	if ($id)
	{
		$mode = "edit";

		// make sure the account actually exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM `account_quotes` WHERE id='$id'";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			$_SESSION["error"]["message"][] = "The quote you have attempted to edit - $id - does not exist in this system.";
		}
	}
	else
	{
		$mode = "add";
	}


	// quote must be provided by edit page, but not by add quote, since we can just generate a new one
	if ($mode == "add")
	{
		$data["code_quote"]		= security_form_input_predefined("any", "code_quote", 0, "");
	}
	else
	{
		$data["code_quote"]		= security_form_input_predefined("any", "code_quote", 1, "");
	}

	//// ERROR CHECKING ///////////////////////


	// make sure we don't choose a quote number that is already in use
	if ($data["code_quote"])
	{
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM `account_quotes` WHERE code_quote='". $data["code_quote"] ."'";
		
		if ($id)
			$sql_obj->string .= " AND id!='$id'";
	
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$_SESSION["error"]["message"][]		= "This quote number is already in use by another quote. Please choose a unique number, or leave it blank to recieve an automatically generated number.";
			$_SESSION["error"]["code_quote-error"]	= 1;
		}
	}


	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"]["quote_". $mode] = "failed";
		header("Location: ../../index.php?page=$returnpage_error&id=$id");
		exit(0);
	}
	else
	{
		// GENERATE INVOICE ID
		// if no quote ID has been supplied, we now need to generate a unique quote id
		if (!$data["code_quote"])
			$data["code_quote"] = config_generate_uniqueid("ACCOUNTS_QUOTES_NUM", "SELECT id FROM account_quotes WHERE code_quote='VALUE'");


		// APPLY GENERAL OPTIONS
		if ($mode == "add")
		{
			/*
				Create new quote
			*/
			
			$sql_obj		= New sql_query;
			$sql_obj->string	= "INSERT INTO `account_quotes` (code_quote, date_create) VALUES ('".$data["code_quote"]."', '". date("Y-m-d") ."')";
			if (!$sql_obj->execute())
			{
				$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst attempting to create quote";
			}

			$id = $sql_obj->fetch_insert_id();
		}

		if ($id)
		{
			/*
				Update general quote details
			*/
			
			$sql_obj = New sql_query;
			
			$sql_obj->string = "UPDATE `account_quotes` SET "
						."customerid='". $data["customerid"] ."', "
						."employeeid='". $data["employeeid"] ."', "
						."notes='". $data["notes"] ."', "
						."code_quote='". $data["code_quote"] ."', "
						."date_trans='". $data["date_trans"] ."', "
						."date_validtill='". $data["date_validtill"] ."' "
						."WHERE id='$id'";
		
			if (!$sql_obj->execute())
			{
				$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst attempting to save changes";
			}
			else
			{
				if ($mode == "add")
				{
					$_SESSION["notification"]["message"][] = "Quote successfully created.";
					journal_quickadd_event("account_quotes", $id, "Quote successfully created");
				}
				else
				{
					$_SESSION["notification"]["message"][] = "Quote successfully updated.";
					journal_quickadd_event("account_quotes", $id, "Quote successfully updated");
				}
				
			}

			// display updated details
			header("Location: ../../index.php?page=$returnpage_success&id=$id");
			exit(0);
			
		} // end if ID

	} // end if passed tests


} // end if quote_form_details_process


?>
