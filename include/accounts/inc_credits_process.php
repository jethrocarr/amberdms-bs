<?php
/*
	include/accounts/inc_credits_process.php

	Provides processing functions for handling input from inc_credits_forms.php based
	form pages.
*/

require("../../include/accounts/inc_invoices.php");
require("../../include/accounts/inc_credits.php");


/*
	FUNCTIONS
*/




/*
	credit_form_details_process($type, $mode, $returnpage_error, $returnpage_success)

	Form for processing credit form results

	Values
	type			"ar_credit" or "ap_credit"
	mode			"edit" or "add" for the action to perform
	returnpage_error	Page to return to in event of errors or updates
	returnpage_success	Page to return to if successful.
*/
function credit_form_details_process($type, $mode, $returnpage_error, $returnpage_success)
{
	log_debug("inc_credits_forms", "Executing credit_form_details_process($type, $mode, $returnpage_error, $returnpage_success)");

	// TODO: it seems this function requests the $mode, but then works it out itself anyway.
	// check out what is going on here.

	/*
		Start the credit
	*/
	$credit		= New credit;
	$credit->type	= $type;
	
	
	/*
		Fetch all form data
	*/


	// get the ID for an edit
	if ($mode == "edit")
	{
		$credit->id = @security_form_input_predefined("int", "id_credit", 1, "");
	}
	

	// general details
	if ($type == "ap_credit")
	{
		$credit->data["vendorid"]	= @security_form_input_predefined("int", "vendorid", 1, "");
	}
	else
	{
		$credit->data["customerid"]	= @security_form_input_predefined("int", "customerid", 1, "");
	}
	
	$credit->data["invoiceid"]		= @security_form_input_predefined("int", "invoiceid", 1, "");
	$credit->data["employeeid"]		= @security_form_input_predefined("int", "employeeid", 1, "");
	$credit->data["notes"]			= @security_form_input_predefined("any", "notes", 0, "");
	
	$credit->data["code_ordernumber"]	= @security_form_input_predefined("any", "code_ordernumber", 0, "");
	$credit->data["code_ponumber"]		= @security_form_input_predefined("any", "code_ponumber", 0, "");
	$credit->data["date_trans"]		= @security_form_input_predefined("date", "date_trans", 1, "");

	// other
	$credit->data["dest_account"]		= @security_form_input_predefined("int", "dest_account", 1, "");


	// are we editing an existing credit or adding a new one?
	if ($credit->id)
	{
		$mode = "edit";

		// make sure the credit actually exists
		if (!$credit->verify_credit())
		{
			log_write("error", "process", "The credit you have attempted to edit - ". $credit->id ." - does not exist in this system.");
		}


		// check if credit is locked or not
		if ($credit->check_lock())
		{
			log_write("error", "process", "The credit can not be edited because it is locked.");
		}

	}
	else
	{
		$mode = "add";
	}


	// credit must be provided by edit page, but not by add credit, since we can just generate a new one
	if ($mode == "add")
	{
		$credit->data["code_credit"]		= @security_form_input_predefined("any", "code_credit", 0, "");
	}
	else
	{
		$credit->data["code_credit"]		= @security_form_input_predefined("any", "code_credit", 1, "");
	}



	//// ERROR CHECKING ///////////////////////


	// make sure we don't choose a credit credit number that is already in use
	if (isset($credit->data["code_credit"]))
	{
		$credit->prepare_code_credit($credit->data["code_credit"]);
	}

	/// if there was an error, go back to the entry page
	if (isset($_SESSION["error"]["message"]))
	{	
		$_SESSION["error"]["form"][$type ."_credit_". $mode] = "failed";
		header("Location: ../../index.php?page=$returnpage_error&id=". $credit->id ."");
		exit(0);
	}
	else
	{
		// GENERATE INVOICE ID

		// if no credit ID has been supplied, we now need to generate a unique credit id
		if (!isset($credit->data["code_credit"]))
		{
			$credit->prepare_code_credit();
			
			config_generate_uniqueid("ACCOUNTS_CREDIT_NUM", "SELECT id FROM account_". $credit->type ." WHERE code_credit='VALUE'");
		}

		// APPLY GENERAL OPTIONS
		if ($mode == "add")
		{
			// create a new credit
			if ($credit->action_create())
			{
				log_write("process", "notification", "Credit note successfully created");
				journal_quickadd_event("account_". $credit->type ."", $credit->id, "Credit Note successfully created");
			}
			else
			{
				log_write("process", "error", "An unexpected fault occured whilst attempting to create the credit note");
			}

			// display items page
			$returnpage_success = str_replace("view", "items", $returnpage_success);
			header("Location: ../../index.php?page=$returnpage_success&id=". $credit->id ."");
		}
		else
		{
			// update an existing credit
			if ($credit->action_update())
			{
				log_write("process", "notification", "Credit note successfully updated.");
				journal_quickadd_event("account_". $credit->type ."", $credit->id, "Credit note successfully updated");
			}
			else
			{
				log_write("process", "error", "An unexpected fault occured whilst attempting to update the credit note");
			}

		
			// display updated details
			header("Location: ../../index.php?page=$returnpage_success&id=". $credit->id ."");
		}

		
		exit(0);
			

	} // end if passed tests


} // end if credit_form_details_process





/*
	credit_form_export_process($type, $mode, $returnpage_error, $returnpage_success)

	Form for processing export form results

	Values
	type			"ar_credit" or "ap_credit"
	returnpage_error	Page to return to in event of errors or updates
	returnpage_success	Page to return to if successful.
*/
function credit_form_export_process($type, $returnpage_error, $returnpage_success)
{
	log_debug("inc_credits_forms", "Executing credit_form_export_process($type, $returnpage_error, $returnpage_success)");

	/*
		Start the credit
	*/
	$credit	= New credit;
	$credit->type	= $type;
	
	
	/*
		Fetch all form data
	*/


	// get the ID for an edit
	$credit->id = @security_form_input_predefined("int", "id_credit", 1, "");
	

	// general details
	$data["formname"] = @security_form_input_predefined("any", "formname", 1, "");

	if ($data["formname"] == "credit_export_email")
	{
		// send email
		$data["sender"]		= @security_form_input_predefined("any", "sender", 1, "");
		$data["subject"]	= @security_form_input_predefined("any", "subject", 1, "");
		$data["email_to"]	= @security_form_input_predefined("multiple_email", "email_to", 1, "");
		$data["email_cc"]	= @security_form_input_predefined("multiple_email", "email_cc", 0, "");
		$data["email_bcc"]	= @security_form_input_predefined("multiple_email", "email_bcc", 0, "");
		$data["message"]	= @security_form_input_predefined("any", "email_message", 1, "");

		// check if email sending is permitted
		if (sql_get_singlevalue("SELECT value FROM config WHERE name='EMAIL_ENABLE'") != "enabled")
		{
			log_write("error", "inc_credits_process", "Sorry, the ability to email credits has been disabled. Please contact your system administrator if you require this feature to be enabled.");
		}
	}
	else
	{
		// PDF download
		$data["credit_mark_as_sent"] = @security_form_input_predefined("any", "credit_mark_as_sent", 0, "");
	}


	// make sure that the credit exists
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM `account_". $credit->type ."` WHERE id='". $credit->id ."'";
	$sql_obj->execute();

	if (!$sql_obj->num_rows())
	{
		$_SESSION["error"]["message"][] = "The credit you have attempted to edit - ". $credit->id ." - does not exist in this system.";
	}




	//// ERROR CHECKING ///////////////////////


	/// if there was an error, go back to the entry page
	if (!empty($_SESSION["error"]["message"]))
	{	
		header("Location: ../../index.php?page=$returnpage_error&id=". $credit->id ."");
		exit(0);
	}
	else
	{
		if ($data["formname"] == "credit_export_email")
		{
			/*
				Generate a PDF of the credit and email it to the customer
			*/


			// stripslashes from the variables - by default all input variables are quoted for security reasons but
			// we don't want this going through to the email.
			$data["subject"] = stripslashes($data["subject"]);
			$data["message"] = stripslashes($data["message"]);


			// send email
			$credit->load_data();
			$credit->email_credit($data["sender"], $data["email_to"], $data["email_cc"], $data["email_bcc"], $data["subject"], $data["message"]);

			$_SESSION["notification"]["message"][] = "Email sent successfully.";
		}
		else
		{

			/*
				Mark credit as being sent if user requests it
			*/
			if ($data["credit_mark_as_sent"])
			{
				$sql_obj		= New sql_query;
				$sql_obj->string	= "UPDATE account_". $credit->type ." SET date_sent='". date("Y-m-d") ."', sentmethod='manual' WHERE id='". $credit->id ."'";
				$sql_obj->execute();
			}


			/*
				Provide PDF to user's browser
			*/
			
			// generate PDF
			$credit->load_data();
			$credit->generate_pdf();


			// PDF headers
			if ($type == "quotes")
			{
				$filename = "/tmp/quote_". $credit->data["code_quote"] .".pdf";
			}
			else
			{
				$filename = "/tmp/credit_". $credit->data["code_credit"] .".pdf";
			}
			
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
			print $credit->obj_pdf->output;
			exit(0);
		}

		
		// display updated details
		header("Location: ../../index.php?page=$returnpage_success&id=". $credit->id ."");
		exit(0);
			

	} // end if passed tests


} // end if credit_form_export_process







/*
	credit_form_delete_process($type, $mode, $returnpage_error, $returnpage_success)

	Form for processing credit deletion form requests.

	Values
	type			"ar_credit" or "ap_credit"
	returnpage_error	Page to return to in event of errors or updates
	returnpage_success	Page to return to if successful.
*/
function credit_form_delete_process($type, $returnpage_error, $returnpage_success)
{
	log_debug("inc_credits_forms", "Executing credit_form_delete_process($type, $returnpage_error, $returnpage_success)");


	$credit	= New credit;
	$credit->type	= $type;

	
	/*
		Import POST Data
	*/
	
	$credit->id			= @security_form_input_predefined("int", "id_credit", 1, "");
	$data["delete_confirm"]		= @security_form_input_predefined("any", "delete_confirm", 1, "You must confirm the deletion");

	// we don't use this value (since we can't trust it) but we need to read it
	// in here to work around a limitation in the Amberphplib framework
	$data["code_credit"]		= @security_form_input_predefined("any", "code_credit", 1, "");


	/*
		Error Handling
	*/

	
	// make sure the credit actually exists
	if (!$credit->verify_credit())
	{
		log_write("error", "process", "The credit note you have attempted to delete - ". $credit->id ." - does not exist in this system.");
	}


	// check if credit is locked or not
	if ($credit->check_delete_lock())
	{
		log_write("error", "process", "The credit note can not be deleted because it is locked.");
	}
		


	// return to input page in event of an error
	if (isset($_SESSION["error"]["message"]))
	{	
		$_SESSION["error"]["form"][$type ."_credit_delete"] = "failed";
		header("Location: ../../index.php?page=$returnpage_error&id=". $credit->id);
		exit(0);
	}


	/*
		Delete Credit Note
	*/

	$credit->load_data();

	if ($credit->action_delete())
	{
		$_SESSION["notification"]["message"] = array("Credit note has been successfully deleted.");
	}
	else
	{
		$_SESSION["error"]["message"][] = "Some problems were experienced while deleting the credit note.";
	}

		
	// display updated details
	header("Location: ../../index.php?page=$returnpage_success&id=$credit->id");
	exit(0);
			

} // end if credit_form_delete_process





/*
	credit_form_lock_process($type, $returnpage_error, $returnpage_success)

	Form for processing credit deletion form requests.

	Values
	type			"ar_credit" or "ap_credit"
	returnpage_error	Page to return to in event of errors or updates
	returnpage_success	Page to return to if successful.
*/
function credit_form_lock_process($type, $returnpage_error, $returnpage_success)
{
	log_debug("inc_credits_forms", "Executing credit_form_lock_process($type, $returnpage_error, $returnpage_success)");


	$credit	= New credit;
	$credit->type	= $type;

	
	/*
		Import POST Data
	*/
	
	$credit->id			= @security_form_input_predefined("int", "id_credit", 1, "");
	$data["lock_credit"]		= @security_form_input_predefined("checkbox", "lock_credit", 0, "");


	/*
		Error Handling
	*/

	
	// make sure the credit actually exists
	if (!$credit->verify_credit())
	{
		log_write("error", "process", "The credit note you have attempted to delete - ". $credit->id ." - does not exist in this system.");
	}


	// check if credit is locked or not
	if ($credit->check_lock())
	{
		log_write("error", "process", "The credit note can not be locked because it is *already* locked.");
	}
		

	// check lock
	if (!$data["lock_credit"])
	{
		log_write("error", "process", "You must check to confirm the credit note lock.");
	}


	// return to input page in event of an error
	if (isset($_SESSION["error"]["message"]))
	{	
		$_SESSION["error"]["form"][$type ."_credit_lock"] = "failed";
		header("Location: ../../index.php?page=$returnpage_error&id=". $credit->id);
		exit(0);
	}



	/*
		Lock Credit Note
	*/

	$credit->load_data();

	if ($credit->action_lock())
	{
		log_write("notification", "process", "The selected credit note has now been locked.");
	}
	else
	{
		log_write("error", "process", "An error occured whilst attempting to lock the credit note.");
	}

	
	// display updated details
	header("Location: ../../index.php?page=$returnpage_success&id=". $credit->id);
	exit(0);
			

} // end if credit_form_delete_process


?>
