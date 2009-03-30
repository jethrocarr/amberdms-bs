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

		/*
			Start SQL Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();


		/*
			Generate Quote ID
		
			If no quote ID has been supplied, we now need to generate a unique quote id
		*/
		if (!$data["code_quote"])
			$data["code_quote"] = config_generate_uniqueid("ACCOUNTS_QUOTES_NUM", "SELECT id FROM account_quotes WHERE code_quote='VALUE'");


		// APPLY GENERAL OPTIONS
		if ($mode == "add")
		{
			/*
				Create new quote
			*/
			
			$sql_obj->string	= "INSERT INTO `account_quotes` (code_quote, date_create) VALUES ('".$data["code_quote"]."', '". date("Y-m-d") ."')";
			$sql_obj->execute();

			$id = $sql_obj->fetch_insert_id();
		}

		if ($id)
		{
			/*
				Update general quote details
			*/
			
			$sql_obj->string = "UPDATE `account_quotes` SET "
						."customerid='". $data["customerid"] ."', "
						."employeeid='". $data["employeeid"] ."', "
						."notes='". $data["notes"] ."', "
						."code_quote='". $data["code_quote"] ."', "
						."date_trans='". $data["date_trans"] ."', "
						."date_validtill='". $data["date_validtill"] ."' "
						."WHERE id='$id' LIMIT 1";
		
			$sql_obj->execute();
		}



		/*
			Update the Journal
		*/

		if ($mode == "add")
		{
			journal_quickadd_event("account_quotes", $id, "Quote successfully created");
		}
		else
		{
			journal_quickadd_event("account_quotes", $id, "Quote successfully updated");
		}



		/*
			Commit
		*/

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "inc_quotes_details", "An error occured whilst attempting to update the quote details");
		}
		else
		{
			$sql_obj->trans_commit();


			if ($mode == "add")
			{
				log_write("notification", "inc_quotes_details", "Quote successfully created.");
			}
			else
			{
				log_write("notification", "inc_quotes_details", "Quote successfully updated.");
			}

			// display updated details
			header("Location: ../../index.php?page=$returnpage_success&id=$id");
			exit(0);
			
		}

	} // end if passed tests


} // end if quote_form_details_process


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
		/*
			Start SQL Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();
	

		// make an invoice ID if one is not supplied by the user
		if (!$data["code_invoice"])
			$data["code_invoice"] = config_generate_uniqueid("ACCOUNTS_AR_INVOICENUM", "SELECT id FROM account_ar WHERE code_invoice='VALUE'");
		
		
		/*
			Create new invoice
		*/
			
		$sql_obj->string	= "INSERT INTO `account_ar` (code_invoice, date_create) VALUES ('".$data["code_invoice"]."', '". date("Y-m-d") ."')";
		$sql_obj->execute();

		$invoiceid = $sql_obj->fetch_insert_id();
		

		if ($invoiceid)
		{
			/*
				Update general invoice details
			*/
			
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
						."WHERE id='$invoiceid' LIMIT 1";

			$sql_obj->execute();



			/*
				Migrate all the items from the quote to the invoice
			*/
			
			$sql_obj->string	= "UPDATE account_items SET invoiceid='$invoiceid', invoicetype='ar' WHERE invoiceid='$id' AND invoicetype='quotes'";
			$sql_obj->execute();



			/*
				Call functions to create transaction entries for all the items.
				(remember that the quote had nothing in account_trans for the items)
			*/
			
			$invoice_item = New invoice_items;
					
			$invoice_item->id_invoice	= $invoiceid;
			$invoice_item->type_invoice	= "ar";

			$invoice_item->action_update_ledger();

			unset($invoice_item);



			/*
				Migrate the journal
			*/
			
			$sql_obj->string	= "UPDATE journal SET customid='$invoiceid', journalname='account_ar' WHERE customid='$id' AND journalname='account_quotes'";
			$sql_obj->execute();



			/*
				Delete the quote
			*/
			
			$sql_obj->string	= "DELETE FROM account_quotes WHERE id='$id' LIMIT 1";
			$sql_obj->execute();
		}


		/*
			Update the Journal
		*/

		journal_quickadd_event("account_ar", $invoiceid, "Converted quotation into invoice");


		/*
			Commit
		*/

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "inc_quotes_forms", "An error occured whilst attempting to convert the quote into an invoice. No changes have been made.");

			$_SESSION["error"]["form"]["quote_convert"] = "failed";
			header("Location: ../../index.php?page=$returnpage_error&id=$id");
			exit(0);
		}
		else
		{
			$sql_obj->trans_commit();

			log_write("notification", "inc_quotes_forms"], "Quotation has been converted to an invoice successfully.");

			header("Location: ../../index.php?page=$returnpage_success&id=$invoiceid");
			exit(0);
		}
			
	} // end if passed tests


} // end if quotes_form_convert_process





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
		/*
			Start SQL Transaction
		*/

		$sql_obj = New sql_query;
		$sql_obj->trans_begin();



		/*
			delete quote itself
		*/

		$sql_obj->string	= "DELETE FROM account_quotes WHERE id='$id' LIMIT 1";
		$sql_obj->execute();


		/*
			delete all the item options
		*/

		$sql_item_obj		= New sql_query;
		$sql_item_obj->string	= "SELECT id FROM account_items WHERE invoicetype='quotes' AND invoiceid='$id'";
		$sql_item_obj->execute();

		if ($sql_item_obj->num_rows())
		{
			$sql_item_obj->fetch_array();

			foreach ($sql_item_obj->data as $data)
			{
				$sql_obj->string	= "DELETE FROM account_items_options WHERE itemid='". $data["id"] ."'";
				$sql_obj->execute();
			}
		}



		/*
			delete all the quote items
		*/

		$sql_obj->string	= "DELETE FROM account_items WHERE invoicetype='quotes' AND invoiceid='$id'";
		$sql_obj->execute();
		

		/*
			delete quote journal entries
		*/

		journal_delete_entire("account_quotes", $id);



		/*
			Commit
		*/
		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "inc_quotes_forms", "An error occured whilst attempting to delete the quote. No changes have been made.");

			$_SESSION["error"]["form"]["quote_delete"] = "failed";
			header("Location: ../../index.php?page=$returnpage_error&id=$id");
			exit(0);
		}
		else
		{
			$sql_obj->trans_commit();

			log_write("notification", "inc_quotes_forms", "The quotation has been successfully deleted.");
		
			header("Location: ../../index.php?page=$returnpage_success");
			exit(0);
		}
			
	} // end if passed tests


} // end if quotes_form_delete_process




?>
