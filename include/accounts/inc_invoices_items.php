<?php
/*
	include/accounts/inc_invoices_items.php

	Provides forms and processing code for listing and adjusting the items belonging
	to an invoice. This is used by both the AR and AP pages.
*/


// includes
include("inc_ledger.php");



/*
	FUNCTIONS
*/


/*
	invoice_list_items($type, $id, $viewpage, $deletepage);

	This function lists all the items belonging to the invoice and creates links to view/edit/delete them.

	Values
	type		Either "ar" or "ap"
	id		If editing/viewing an existing invoice, provide the ID
	viewpage	Page for viewing/editing invoice items
	deletepage	Processing page for deleting invoice items
	
	Return Codes
	0	failure
	1	success
*/
function invoice_list_items($type, $id, $viewpage, $deletepage)
{
	log_debug("inc_invoice_items", "Executing invoice_list_items($type, $id, $viewpage, $deletepage)");

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
	else
	{
		/*
			Standard invoice items
		*/
		print "<b>Standard Items:</b>";

		// establish a new table object
		$item_list = New table;

		$item_list->language	= $_SESSION["user"]["lang"];
		$item_list->tablename	= "item_list";

		// define all the columns and structure
		$item_list->add_column("money", "price", "account_items.price");
		$item_list->add_column("standard", "account", "CONCAT_WS(' -- ',account_charts.code_chart,account_charts.description)");
		$item_list->add_column("standard", "description", "account_items.description");

		// defaults
		$item_list->columns		= array("price", "account", "description");
		$item_list->columns_order	= array("account");

		// totals
		$item_list->total_columns	= array("price");

		// define SQL structure
		$item_list->sql_obj->prepare_sql_settable("account_items");
		$item_list->sql_obj->prepare_sql_addfield("id", "account_items.id");
		$item_list->sql_obj->prepare_sql_addjoin("LEFT JOIN account_charts ON account_charts.id = account_items.chartid");
		$item_list->sql_obj->prepare_sql_addwhere("invoiceid='$id'");
		$item_list->sql_obj->prepare_sql_addwhere("type!='tax'");

		// run SQL query
		$item_list->generate_sql();
		$item_list->load_data_sql();

		if (!$item_list->data_num_rows)
		{
			print "<p><i>There are currently no items on this invoice.</i></p>";
		}
		else
		{
			// edit link
			$structure = NULL;
			$structure["id"]["value"]	= "$id";
			$structure["id"]["action"]	= "edit";
			$structure["itemid"]["column"]	= "id";
			
			$item_list->add_link("edit", $viewpage, $structure);

			
			// delete link
			$structure = NULL;
			$structure["id"]["value"]	= "$id";
			$structure["itemid"]["column"]	= "id";
			$structure["full_link"]		= "yes";
			
			$item_list->add_link("delete", $deletepage, $structure);

			// display the table
			$item_list->render_table();	
		}
		
		print "<p><b><a href=\"index.php?page=$viewpage&id=$id&type=standard\">Add standard transaction item</a></b></p>";



		/* 
			Tax Items
		*/
		print "<br><br>";
		print "<b>Taxes:</b>";

		// establish a new table object
		$item_list = New table;

		$item_list->language	= $_SESSION["user"]["lang"];
		$item_list->tablename	= "item_list";

		// define all the columns and structure
		$item_list->add_column("money", "price", "account_items.price");
		$item_list->add_column("standard", "name_tax", "CONCAT_WS(' -- ',account_taxes.name_tax,account_taxes.description)");
		$item_list->add_column("standard", "description", "account_items.description");

		// defaults
		$item_list->columns		= array("price", "name_tax", "description");
		$item_list->columns_order	= array("name_tax");

		// totals
		$item_list->total_columns	= array("price");

		// define SQL structure
		$item_list->sql_obj->prepare_sql_settable("account_items");
		$item_list->sql_obj->prepare_sql_addfield("id", "account_items.id");
		$item_list->sql_obj->prepare_sql_addjoin("LEFT JOIN account_taxes ON account_taxes.id = account_items.customid");
		$item_list->sql_obj->prepare_sql_addwhere("invoiceid='$id'");
		$item_list->sql_obj->prepare_sql_addwhere("type='tax'");
		

		// run SQL query
		$item_list->generate_sql();
		$item_list->load_data_sql();

		if (!$item_list->data_num_rows)
		{
			print "<p><i>There are currently no taxes on this invoice.</i></p>";
		}
		else
		{
			// edit link
			$structure = NULL;
			$structure["id"]["value"]	= "$id";
			$structure["itemid"]["column"]	= "id";
			
			$item_list->add_link("edit", $viewpage, $structure);
			
			// delete link
			$structure = NULL;
			$structure["id"]["value"]	= "$id";
			$structure["itemid"]["column"]	= "id";
			$structure["full_link"]		= "yes";

			$item_list->add_link("delete", $deletepage, $structure);

		
			// display the table
			$item_list->render_table();
	
		}
		
		print "<p><b><a href=\"index.php?page=$viewpage&id=$id&type=tax\">Add new tax to Invoice</a></b></p>";


	} // end if invoice exists

	return 1;
	
} // end of invoice_list_items()



/*
	invoice_form_items_render($type, $id, $processpage)

	This function provides a form for creating or editing invoice items.

	Values
	type		Either "ar" or "ap"
	id		If editing/viewing an existing invoice, provide the ID
	processpage	Page to submit the form too

	Return Codes
	0	failure
	1	success
*/
function invoice_form_items_render($type, $id, $processpage)
{
	log_debug("inc_invoices_details", "Executing invoice_form_items_render($type, $id, $processpage)");

	
	// fetch the item ID
	$itemid		= security_script_input('/^[0-9]*$/', $_GET["itemid"]);

	if ($itemid)
	{
		$mode = "edit";
	}
	else
	{
		$mode		= "add";
		$item_type	= security_script_input('/^[a-z]*$/', $_GET["type"]);
	}



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
		Make sure invoice item does exist, that it belongs to the correct invoice
		and fetch the item ID at the same time.
	*/
	if ($mode == "edit")
	{
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id, type FROM account_items WHERE id='$itemid' AND invoiceid='$id' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			print "<p><b>Error: The requested item/invoice combination does not exist. Are you trying to use a link to a deleted invoice?</b></p>";
			return 0;
		}
		else
		{
			$sql_obj->fetch_array();

			$item_type = $sql_obj->data[0]["type"];
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
		Define form structure, depending on the type of the item
	*/

	switch ($item_type)
	{
		case "standard":
		
			/*
				STANDARD
				
				simple transaction item which allows the user to specifiy a value only.
			*/
			
			// basic details
			$structure = NULL;
			$structure["fieldname"] 	= "price";
			$structure["type"]		= "input";
			$form->add_input($structure);

			$structure = NULL;
			$structure = charts_form_prepare_acccountdropdown("chartid", 5);
			$form->add_input($structure);
				
			$structure = NULL;
			$structure["fieldname"] 	= "description";
			$structure["type"]		= "textarea";
			$structure["options"]["height"]	= "50";
			$structure["options"]["width"]	= 500;
			$form->add_input($structure);

	
			// define form layout
			$form->subforms[$type ."_invoice_item"]		= array("price", "chartid", "description");

			// SQL query
			$form->sql_query = "SELECT price, description, chartid FROM account_items WHERE id='$itemid'";

		break;


		case "tax":
		
			/*
				TAX

				Tax items are quite flexible items - they allow the user to select a different
				tax code, and then set the item to either automatically calculate the tax
				based on the total items, or to set a manual value.

				The tax will then auto-recalculate if required when other items are changed.

				Other accounting systems looked at seem to only allow 1 tax to be added to invoices - by having
				tax as an item, we can do nifty stuff like have different tax items to be added - for example if you
				add a product, the product could be configured to add a specific tax item of a specific amount to the
				invoice.
			*/

			// tax selection
			$structure = form_helper_prepare_dropdownfromdb("tax_id", "SELECT id, name_tax as label FROM account_taxes");

			if (count(array_keys($structure["values"])) == 1)
			{
				// if there is only 1 tax option avaliable, select it as the default
				$structure["options"]["noselectoption"] = "yes";
			}
	
			$form->add_input($structure);
	

			// auto or manual
			$structure = NULL;
			$structure["fieldname"] 	= "manual_option";
			$structure["type"]		= "checkbox";
			$structure["options"]["label"]	= "Do not auto-calculate this tax, instead specify the amount charged for this tax in the field below.";
			$form->add_input($structure);

			// manual value input field
			$structure = NULL;
			$structure["fieldname"] 	= "manual_amount";
			$structure["type"]		= "input";
			$form->add_input($structure);


			// define form layout
			$form->subforms[$type ."_invoice_item"]		= array("tax_id", "manual_option", "manual_amount");

			// SQL query
			$form->sql_query = "SELECT price as manual_amount FROM account_items WHERE id='$itemid'";

		break;


		default:
			print "<p><b>Error: Unknown type passed to render form.</b></p>";
		break;
	}



	// IDs
	$structure = NULL;
	$structure["fieldname"]		= "id_invoice";
	$structure["type"]		= "hidden";
	$structure["defaultvalue"]	= $id;
	$form->add_input($structure);	
	
	$structure = NULL;
	$structure["fieldname"]		= "id_item";
	$structure["type"]		= "hidden";
	$structure["defaultvalue"]	= $itemid;
	$form->add_input($structure);	
	
	$structure = NULL;
	$structure["fieldname"]		= "item_type";
	$structure["type"]		= "hidden";
	$structure["defaultvalue"]	= $item_type;
	$form->add_input($structure);	



	// submit
	$structure = NULL;
	$structure["fieldname"]		= "submit";
	$structure["type"]		= "submit";
	$structure["defaultvalue"]	= "Save Changes";
	$form->add_input($structure);


	// load data
	$form->load_data();

	// custom loads for tax items
	if ($item_type == "tax" && $itemid)
	{
		// check if the tax is to be calculated manually or calculated
		// automatically.
		
		$mode = sql_get_singlevalue("SELECT option_value AS value FROM account_items_options WHERE itemid='$itemid' AND option_name='TAX_CALC_MODE' LIMIT 1");
		
		if ($mode == "manual")
		{
			$form->structure["manual_option"]["defaultvalue"] = "on";
		}
		else
		{
			$form->structure["manual_amount"]["defaultvalue"] = "";
		}
	}


	/*
		Display Form
	*/
	
	$form->subforms["hidden"]			= array("id_invoice", "id_item", "item_type");
	$form->subforms["submit"]			= array("submit");
	
	$form->render_form();


	return 1;
}




/*
	invoice_form_items_process($type, $returnpage_error, $returnpage_success)

	Form for processing invoice form results

	Values
	type			"ar" or "ap" invoice
	returnpage_error	Page to return to in event of errors or updates
	returnpage_success	Page to return to if successful.
*/
function invoice_form_items_process($type,  $returnpage_error, $returnpage_success)
{
	log_debug("inc_invoices_items", "Executing invoice_form_items_process($type, $returnpage_error, $returnpage_success)");

	
	/*
		Fetch all form data
	*/

	// invoice + item IDs
	$id		= security_form_input_predefined("int", "id_invoice", 1, "");
	$item_type	= security_form_input_predefined("any", "item_type", 1, "");
	$itemid		= security_form_input_predefined("int", "id_item", 0, "");

	if ($itemid)
	{
		$mode = "edit";
	}
	else
	{
		$mode = "add";
	}
	
	
	/*
		Verify that the invoice exists, and fetch some required information from it.
	*/
	$sql_inv_obj		= New sql_query;
	$sql_inv_obj->string	= "SELECT id, dest_account, date_trans FROM account_$type WHERE id='$id' LIMIT 1";
	$sql_inv_obj->execute();
	
	if (!$sql_inv_obj->num_rows())
	{
		$_SESSION["error"]["message"][] = "<p><b>Error: The requested invoice does not exist. <a href=\"index.php?page=accounts/$type/$type.php\">Try looking on the invoice/invoice list page.</a></b></p>";
	}
	else
	{
		$sql_inv_obj->fetch_array();
	}


	/*
		Make sure invoice item does exist, that it belongs to the correct invoice
		and fetch the item ID at the same time.
	*/
	if ($mode == "edit")
	{
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_items WHERE id='$itemid' AND invoiceid='$id' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			$_SESSION["error"]["message"][] = "<p><b>Error: The requested item/invoice combination does not exist. Are you trying to use a link to a deleted invoice?</b></p>";
		}
	}


	// fetch form data and process into suitable fields.
	switch($item_type)
	{
		case "standard":
			/*
				STANDARD ITEMS
			*/

			// configure the type label to use for the account_trans entry
			$trans_type = $type;

			// fetch information from form
			$data["price"]		= security_form_input_predefined("money", "price", 1, "");
			$data["chartid"]	= security_form_input_predefined("int", "chartid", 1, "");
			$data["description"]	= security_form_input_predefined("any", "description", 0, "");
			
		break;


		case "tax":
			/*
				TAX ITEMS

				We need to either use the manual amounts provided, or calculate the tax amount.
			*/

			// configure the type label to use for the account_trans entry
			$trans_type = $type ."_tax";

			// fetch key information from form
			$data["customid"]	= security_form_input_predefined("int", "tax_id", 1, "");
			$data["manual_option"]	= security_form_input_predefined("any", "manual_option", 0, "");


			// fetch information about the tax - we need to know the account and taxrate
			$sql_tax_obj		= New sql_query;
			$sql_tax_obj->string	= "SELECT chartid, taxrate FROM account_taxes WHERE id='". $data["customid"] ."' LIMIT 1";
			$sql_tax_obj->execute();

			if (!$sql_tax_obj->num_rows())
			{
				$_SESSION["error"]["message"][] = "Unknown tax requested!";
			}
			else
			{
				$sql_tax_obj->fetch_array();
				
				$data["chartid"] = $sql_tax_obj->data[0]["chartid"];
				$data["taxrate"] = $sql_tax_obj->data[0]["taxrate"];
			}



			// calculate tax, either:
			//	1. manual amount provided
			//	2. automatic based on the percentage provided
			if ($data["manual_option"])
			{
				// fetch manual value from the form
				$data["price"]	= security_form_input_predefined("money", "manual_amount", 1, "You must enter a value if you choose to calculate the tax amount manually.");

				// label it for the ledgers
				$data["description"] = "Manual tax calculation";
			}
			else
			{
				// fetch total of billable items
				$amount	= sql_get_singlevalue("SELECT sum(price) as value FROM `account_items` WHERE invoiceid='$id' AND type!='tax'");

				// calculate taxable amount
				$data["price"] = $amount * ($data["taxrate"] / 100);
				
				$data["price"] = sprintf("%0.2f", $data["price"]);

				
				// label it for the ledgers
				$data["description"] = "Automatic tax calculation at rate of ". $data["taxrate"] ."%";
			}
			
		break;


		default:
			$_SESSION["error"]["message"][] = "Unknown item type passed to processing form.";
		break;
	}
	
	

	//// ERROR CHECKING ///////////////////////


	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"][$type ."_invoice_". $mode] = "failed";
		header("Location: ../../index.php?page=$returnpage_error&id=$id&type=$item_type");
		exit(0);
	}
	else
	{

		/*
			APPLY ITEM CHANGES
		*/
	
		if ($mode == "add")
		{
			/*
				Create new item
			*/
	
			$sql_obj		= New sql_query;
			$sql_obj->string	= "INSERT INTO `account_items` (invoiceid) VALUES ('$id')";
			if (!$sql_obj->execute())
			{
				$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst attempting to create item";
			}

			$itemid = $sql_obj->fetch_insert_id();
		}


		if ($itemid)
		{
			/*
				Update Item Details
			*/
			
			$sql_obj = New sql_query;
			
			$sql_obj->string = "UPDATE `account_items` SET "
						."type='$item_type', "
						."price='". $data["price"] ."', "
						."chartid='". $data["chartid"] ."', "
						."customid='". $data["customid"] ."', "
						."description='". $data["description"] ."' "
						."WHERE id='$itemid'";
						
			if (!$sql_obj->execute())
			{
				$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst attempting to save changes";
				header("Location: ../../index.php?page=$returnpage_error&id=$id&type=$item_type");
				exit(0);
			}



			/*
				Update Item Options
			*/

			// remove all existing options
			$sql_obj		= New sql_query;
			$sql_obj->string	= "DELETE FROM account_items_options WHERE itemid='$itemid'";
			$sql_obj->execute();


			// flag tax item as manual if required
			if ($item_type == "tax" && $data["manual_option"] == "on")
			{
				$sql_obj		= New sql_query;
				$sql_obj->string	= "INSERT INTO account_items_options (itemid, option_name, option_value) VALUES ('$itemid', 'TAX_CALC_MODE', 'manual')";
				$sql_obj->execute();
			}



			/*
				Generate ledger entries.

			*/

			// remove all the old ledger entries belonging to this item
			// (items are identified using the values in the source field)
			$sql_obj		= New sql_query;
			$sql_obj->string	= "DELETE FROM `account_trans` WHERE type='$type' AND customid='$id' AND source='$itemid'";
			$sql_obj->execute();

			// re-generate the tax calculations for this invoice
			//
			// (unless we are adding a tax item, in which case this would be
			//  pointless, since the taxable totals have not changed)
			if ($item_type != "tax")
			{
				invoice_items_update_tax($id, $type);
			}
			
			// We need to debit the AR account and then credit the item's account.
			ledger_trans_add("debit", $trans_type, $id, $sql_inv_obj->data[0]["date_trans"], $sql_inv_obj->data[0]["dest_account"], $price, $data["description"], "$itemid");
			ledger_trans_add("credit", $trans_type, $id, $sql_inv_obj->data[0]["date_trans"], $data["chartid"], $price, $data["description"], "$itemid");


			// update the total on the invoice.
			invoice_items_update_total($id, $type);



			/*
				Return to success page
			*/
			if ($mode == "add")
			{
				$_SESSION["notification"]["message"][] = "Item successfully created.";
				journal_quickadd_event("account_$type", $id, "Item successfully created");
			}
			else
			{
				$_SESSION["notification"]["message"][] = "Item successfully updated.";
				journal_quickadd_event("account_$type", $id, "Item successfully updated");
			}
				
		} // end if ID


		// display updated details
		header("Location: ../../index.php?page=$returnpage_success&id=$id");
		exit(0);


	} // end if passed tests


} // end of invoice_form_items_process




/*
	invoice_form_items_delete_process($type, $returnpage_error, $returnpage_success)

	Processing page to delete invoice items.

	Values
	type			"ar" or "ap" invoice
	returnpage_error	Page to return to in event of errors or updates
	returnpage_success	Page to return to if successful.
*/
function invoice_form_items_delete_process($type,  $returnpage_error, $returnpage_success)
{
	log_debug("inc_invoices_items", "Executing invoice_form_items_delete_process($type, $returnpage_error, $returnpage_success)");

	
	/*
		Fetch all form data
	*/

	// invoice + item IDs
	$id		= security_script_input("/^[0-9]*$/", $_GET["id"]);
	$itemid		= security_script_input("/^[0-9]*$/", $_GET["itemid"]);
	
	if (!$id || !$itemid)
	{
		$_SESSION["error"]["message"][] = "Incorrect URL passed to function invoice_forms_items_delete_process";
	}
	
	//// ERROR CHECKING ///////////////////////
	
	/*
		Verify that the invoice exists, and fetch some required information from it.
	*/
	$sql_inv_obj		= New sql_query;
	$sql_inv_obj->string	= "SELECT id, dest_account, date_trans FROM account_$type WHERE id='$id' LIMIT 1";
	$sql_inv_obj->execute();
	
	if (!$sql_inv_obj->num_rows())
	{
		$_SESSION["error"]["message"][] = "<p><b>Error: The requested invoice does not exist. <a href=\"index.php?page=accounts/$type/$type.php\">Try looking on the invoice/invoice list page.</a></b></p>";
	}
	else
	{
		$sql_inv_obj->fetch_array();
	}


	/*
		Make sure invoice item does exist, that it belongs to the correct invoice
		and fetch the item ID at the same time.
	*/
	if ($mode == "edit")
	{
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_items WHERE id='$itemid' AND invoiceid='$id' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			$_SESSION["error"]["message"][] = "<p><b>Error: The requested item/invoice combination does not exist. Are you trying to use a link to a deleted invoice?</b></p>";
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
			Delete the invoice item
		*/


		// delete item
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM account_items WHERE id='$itemid'";
		
		if (!$sql_obj->execute())
		{
			$_SESSION["error"]["message"][] = "Error: Unable to delete invoice item $itemid";
		}


		// remove the ledger entries belonging to this item
		// (items are identified using the values in the source field)
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM `account_trans` WHERE type='$type' AND customid='$id' AND source='$itemid'";
		
		if (!$sql_obj->execute())
		{
			$_SESSION["error"]["message"][] = "Error: Unable to delete ledger entries belonging to deleted item $itemid";
		}


		// update taxes and totals
		//
		// TODO: add some smarts here to not bother to regenerate taxes when
		// the user has only deleted a tax item
		//
		invoice_items_update_tax($id, $type);	
		invoice_items_update_total($id, $type);


		// return with success
		if (!$_SESSION["error"]["message"])
		{
			$_SESSION["notification"]["message"][] = "Invoice item deleted successfully";
			journal_quickadd_event("account_$type", $id, "Item successfully deleted");
		}
		
		header("Location: ../../index.php?page=$returnpage_error&id=$id");
		exit(0);
	}
	
} // end of invoice_form_items_delete_process





/*
	invoice_items_update_total

	This function totals up all the items on the invoice and updates the totals on the invoice itself.

	Values
	id		ID of the invoice to update
	type		Type of invoice - AR or AP

	Return Codes
	0		failure
	1		success
*/
function invoice_items_update_total($id, $type)
{
	log_debug("inc_invoices_items", "Executing invoice_items_update_total($id, $type)");


	// default values
	$amount		= "0";
	$amount_tax	= "0";
	$amount_total	= "0";

	/*
		Total up all the items, and all the tax
	*/


	// calculate totals from the DB
	$amount		= sql_get_singlevalue("SELECT sum(price) as value FROM `account_items` WHERE invoiceid='$id' AND type!='tax'");
	$amount_tax	= sql_get_singlevalue("SELECT sum(price) as value FROM `account_items` WHERE invoiceid='$id' AND type='tax'");

	// final totals
	$amount_total	= $amount + $amount_tax;

	$amount		= sprintf("%0.2f", $amount);
	$amount_tax	= sprintf("%0.2f", $amount_tax);
	$amount_total	= sprintf("%0.2f", $amount_total);


	/*
		Update the invoice
	*/
	$sql_obj = New sql_query;
			
	$sql_obj->string = "UPDATE `account_$type` SET "
				."amount='". $amount ."', "
				."amount_tax='". $amount_tax ."', "
				."amount_total='". $amount_total ."' "
				."WHERE id='$id'";
	
	if (!$sql_obj->execute())
	{
		log_debug("inc_invoices_items", "A fatal SQL error occured whilst attempting to update invoice totals");
		return 0;
	}


	return 1;

} // end of invoice_items_update_total





/*
	invoice_items_update_tax

	This function regenerates the taxes for any auto-matically calculated tax items on this invoice.

	Note that it does NOT update the tax totals on the invoice itself, so invoice_items_update_totals should
	always be run after running this function.
	

	Values
	id		ID of the invoice to update
	type		Type of invoice - AR or AP

	Return Codes
	0		failure
	1		success
*/
function invoice_items_update_tax($id, $type)
{
	log_debug("inc_invoices_items", "Executing invoice_items_update_tax($id, $type)");


	// fetch taxable amount
	$amount		= sql_get_singlevalue("SELECT sum(price) as value FROM `account_items` WHERE invoiceid='$id' AND type!='tax'");


	/*
		Run though all the tax items on this invoice
	*/
	$sql_items_obj		= New sql_query;
	$sql_items_obj->string	= "SELECT id, customid, price FROM account_items WHERE invoiceid='$id' AND type='tax'";
	$sql_items_obj->execute();

	if ($sql_items_obj->num_rows())
	{
		$sql_items_obj->fetch_array();

		foreach ($sql_items_obj->data as $data)
		{
			// determine if we need to calculate tax for this item
			$mode = sql_get_singlevalue("SELECT option_value AS value FROM account_items_options WHERE itemid='". $data["id"] ."' AND option_name='TAX_CALC_MODE' LIMIT 1");

			if (!$mode || $mode != "manual")
			{
				/*
					This item is an automatically calculated tax item.
					
					Fetch taxrate information, calculate new tax amount and update the item
				*/
			
				// fetch taxrate
				$taxrate = sql_get_singlevalue("SELECT taxrate AS value FROM account_taxes WHERE id='". $data["customid"] ."' LIMIT 1");
			
				// calculate taxable amount
				$price = $amount * ($taxrate / 100);
				$price = sprintf("%0.2f", $price);

				// update the item with the new amount
				$sql_obj		= New sql_query;
				$sql_obj->string	= "UPDATE account_items SET price='$price' WHERE id='". $data["id"] ."'";
				$sql_obj->execute();
			}
		}
	}
	else
	{
		log_debug("inc_invoices_items", "No tax items to re-calculate tax totals for.");
		return 0;
	}


	return 1;

} // end of invoice_items_update_tax








?>
