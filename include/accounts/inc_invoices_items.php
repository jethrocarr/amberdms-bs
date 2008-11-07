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
		$item_list->add_column("money", "amount", "");
		$item_list->add_column("standard", "item_info", "NONE");
		$item_list->add_column("money", "price", "");
		$item_list->add_column("standard", "units", "");
		$item_list->add_column("standard", "qnty", "quantity");
		$item_list->add_column("standard", "description", "");

		// defaults
		$item_list->columns		= array("amount", "item_info", "price", "qnty", "units", "description");

		// totals
		$item_list->total_columns	= array("amount");

		// define SQL structure
		$item_list->sql_obj->prepare_sql_settable("account_items");
		
		$item_list->sql_obj->prepare_sql_addfield("id", "");
		$item_list->sql_obj->prepare_sql_addfield("type", "");
		$item_list->sql_obj->prepare_sql_addfield("customid", "");
		$item_list->sql_obj->prepare_sql_addfield("chartid", "");
		
		$item_list->sql_obj->prepare_sql_addwhere("invoiceid='$id'");
		$item_list->sql_obj->prepare_sql_addwhere("type!='tax'");
		$item_list->sql_obj->prepare_sql_addwhere("type!='payment'");
		
		$item_list->sql_obj->prepare_sql_addorderby("type");

		// run SQL query
		$item_list->generate_sql();
		$item_list->load_data_sql();

		if (!$item_list->data_num_rows)
		{
			print "<p><i>There are currently no items on this invoice.</i></p>";
		}
		else
		{
			/*
				Perform custom processing for item types
			*/
			for ($i=0; $i < $item_list->data_num_rows; $i++)
			{
				switch ($item_list->data[$i]["type"])
				{
					case "product":
						/*
							Fetch product name
						*/
						$sql_obj		= New sql_query;
						$sql_obj->string	= "SELECT name_product FROM products WHERE id='". $item_list->data[$i]["customid"] ."' LIMIT 1";
						$sql_obj->execute();

						$sql_obj->fetch_array();
						$item_list->data[$i]["item_info"] = $sql_obj->data[0]["name_product"];
					break;


					case "time":
						/*
							Fetch time group ID
						*/

						$groupid = sql_get_singlevalue("SELECT option_value as value FROM account_items_options WHERE itemid='". $item_list->data[$i]["id"] ."' AND option_name='TIMEGROUPID'");

						$item_list->data[$i]["item_info"] = sql_get_singlevalue("SELECT CONCAT_WS(' -- ', projects.code_project, time_groups.name_group) as value FROM time_groups LEFT JOIN projects ON projects.id = time_groups.projectid WHERE time_groups.id='$groupid' LIMIT 1");
					break;


					case "standard":
						/*
							Fetch account name and blank a few fields
						*/

						$sql_obj		= New sql_query;
						$sql_obj->string	= "SELECT CONCAT_WS(' -- ',code_chart,description) as name_account FROM account_charts WHERE id='". $item_list->data[$i]["chartid"] ."' LIMIT 1";
						$sql_obj->execute();

						$sql_obj->fetch_array();
						$item_list->data[$i]["item_info"] = $sql_obj->data[0]["name_account"];
					
						$item_list->data[$i]["qnty"] = "";
					break;
				}
			}

		
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
		print "<p><b><a href=\"index.php?page=$viewpage&id=$id&type=product\">Add product item</a></b></p>";
		print "<p><b><a href=\"index.php?page=$viewpage&id=$id&type=time\">Add time item</a></b></p>";



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
		$item_list->add_column("money", "amount", "account_items.amount");
		$item_list->add_column("standard", "name_tax", "CONCAT_WS(' -- ',account_taxes.name_tax,account_taxes.description)");
		$item_list->add_column("standard", "description", "account_items.description");

		// defaults
		$item_list->columns		= array("amount", "name_tax", "description");
		$item_list->columns_order	= array("name_tax");

		// totals
		$item_list->total_columns	= array("amount");

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
	invoice_list_items_payments($type, $id, $viewpage, $deletepage);

	This function lists all payments items and provided links to add/edit/delete them.

	Values
	type		Either "ar" or "ap"
	id		If editing/viewing an existing invoice, provide the ID
	viewpage	Page for viewing/editing invoice items
	deletepage	Processing page for deleting invoice items
	
	Return Codes
	0	failure
	1	success
*/
function invoice_list_items_payments($type, $id, $viewpage, $deletepage)
{
	log_debug("inc_invoice_items", "Executing invoice_list_items_payments($type, $id, $viewpage, $deletepage)");

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
			Generate table of all the items
		*/

		// establish a new table object
		$item_list = New table;

		$item_list->language	= $_SESSION["user"]["lang"];
		$item_list->tablename	= "item_list";

		// define all the columns and structure
		$item_list->add_column("date", "date_trans", "NONE");
		$item_list->add_column("money", "amount", "account_items.amount");
		$item_list->add_column("standard", "account", "CONCAT_WS(' -- ',account_charts.code_chart,account_charts.description)");
		$item_list->add_column("standard", "source", "NONE");
		$item_list->add_column("standard", "description", "account_items.description");

		// defaults
		$item_list->columns		= array("date_trans", "amount", "account", "source", "description");
		$item_list->columns_order	= array("account");

		// totals
		$item_list->total_columns	= array("amount");

		// define SQL structure
		$item_list->sql_obj->prepare_sql_settable("account_items");
		$item_list->sql_obj->prepare_sql_addfield("id", "account_items.id");
		$item_list->sql_obj->prepare_sql_addjoin("LEFT JOIN account_charts ON account_charts.id = account_items.chartid");
		$item_list->sql_obj->prepare_sql_addwhere("invoiceid='$id'");
		$item_list->sql_obj->prepare_sql_addwhere("type='payment'");

		// run SQL query
		$item_list->generate_sql();
		$item_list->load_data_sql();

		if (!$item_list->data_num_rows)
		{
			print "<p><i>No payments have been made against this invoice.</i></p>";
		}
		else
		{
			// fetch date_trans and source values from DB
			for ($i=0; $i < $item_list->data_num_rows; $i++)
			{
				$sql_obj		= New sql_query;
				$sql_obj->string	= "SELECT option_name, option_value FROM account_items_options WHERE itemid='". $item_list->data[$i]["id"] ."'";
				$sql_obj->execute();

				$sql_obj->fetch_array();

				foreach ($sql_obj->data as $data)
				{
					if ($data["option_name"] == "SOURCE")
						$item_list->data[$i]["source"] = $data["option_value"];


					if ($data["option_name"] == "DATE_TRANS")
						$item_list->data[$i]["date_trans"] = $data["option_value"];
				}
			
			}


		
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
		
		print "<p><b><a href=\"index.php?page=$viewpage&id=$id&type=payment\">Add Payment</a></b></p>";


	} // end if invoice exists

	return 1;
	
} // end of invoice_list_items_payments()







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
			$structure["fieldname"] 	= "amount";
			$structure["type"]		= "input";
			$form->add_input($structure);

			$structure = NULL;

			if ($type == "ap")
			{
				$structure = charts_form_prepare_acccountdropdown("chartid", "ap_expense");
			}
			else
			{
				$structure = charts_form_prepare_acccountdropdown("chartid", "ar_income");
			}
			$form->add_input($structure);
				
			$structure = NULL;
			$structure["fieldname"] 	= "description";
			$structure["type"]		= "textarea";
			$structure["options"]["height"]	= "50";
			$structure["options"]["width"]	= 500;
			$form->add_input($structure);

	
			// define form layout
			$form->subforms[$type ."_invoice_item"]		= array("amount", "chartid", "description");

			// SQL query
			$form->sql_query = "SELECT amount, description, chartid FROM account_items WHERE id='$itemid'";

		break;


		/*
			PRODUCT

			Product item - selection of a product from the DB, and specify quantity, unit and amount.
		*/

		case "product":

			// basic details
			$structure = NULL;
			$structure["fieldname"] 	= "price";
			$structure["type"]		= "input";
			$form->add_input($structure);

			// quantity
			$structure = NULL;
			$structure["fieldname"] 	= "quantity";
			$structure["type"]		= "input";
			$structure["options"]["width"]	= 50;
			$form->add_input($structure);


			// units
			$structure = NULL;
			$structure["fieldname"] 		= "units";
			$structure["type"]			= "input";
			$structure["options"]["width"]		= 50;
			$structure["options"]["max_length"]	= 10;
			$form->add_input($structure);



			// product id
			$structure = form_helper_prepare_dropdownfromdb("productid", "SELECT id, code_product as label, name_product as label1 FROM products");
			$form->add_input($structure);


			// description
			$structure = NULL;
			$structure["fieldname"] 	= "description";
			$structure["type"]		= "textarea";
			$structure["options"]["height"]	= "50";
			$structure["options"]["width"]	= 500;
			$form->add_input($structure);

	
			// define form layout
			$form->subforms[$type ."_invoice_item"]		= array("productid", "price", "quantity", "units", "description");

			// SQL query
			$form->sql_query = "SELECT price, description, customid as productid, quantity, units FROM account_items WHERE id='$itemid'";


		
		break;


		/*
			TIME (AR only)

			Before time can be added to an invoice, the time entries need to be grouped together
			using the form under projects.

			The user can then select a group of time below to add to the invoice. This methods makes
			it easier to add time to invoices, and also means that the time grouping could be done
			by someone without access to invoicing itself.
		*/
		case "time":

			if ($type == "ar")
			{
				// fetch the customer ID for this invoice, so we can create
				// a list of the time groups can be added.
				$customerid = sql_get_singlevalue("SELECT customerid as value FROM account_$type WHERE id='$id' LIMIT 1");
				
				// list of avaliable time groups
				$structure = form_helper_prepare_dropdownfromdb("timegroupid", "SELECT time_groups.id, projects.name_project as label, time_groups.name_group as label1 FROM time_groups LEFT JOIN projects ON projects.id = time_groups.projectid WHERE customerid='$customerid' AND locked='0' ORDER BY name_group");
				$structure["options"]["width"] = "400";
	
				if ($structure["values"])
				{
					if (count(array_keys($structure["values"])) == 1)
					{
						// if there is only 1 time group avaliable, select it by default
						$structure["options"]["noselectoption"] = "yes";
					}
				}
				
				$form->add_input($structure);

			
				// price field
				// TODO: this should auto-update from the product price
				$structure = NULL;
				$structure["fieldname"] 	= "price";
				$structure["type"]		= "input";
				$form->add_input($structure);

				// product id
				$structure = form_helper_prepare_dropdownfromdb("productid", "SELECT id, code_product as label, name_product as label1 FROM products");
				$structure["options"]["width"] = "400";
				$form->add_input($structure);


				// description
				$structure = NULL;
				$structure["fieldname"] 	= "description";
				$structure["type"]		= "textarea";
				$structure["options"]["height"]	= "50";
				$structure["options"]["width"]	= 500;
				$form->add_input($structure);

		
				// define form layout
				$form->subforms[$type ."_invoice_item"]		= array("timegroupid", "productid", "price", "description");

				// SQL query
				$form->sql_query = "SELECT price, description, customid as productid, quantity, units FROM account_items WHERE id='$itemid'";
				
			
			}
			else
			{
				print "<p><i>Error: Time items are only avaliable for AR invoices.</i></p>";
			}

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
			$form->sql_query = "SELECT customid as tax_id, amount as manual_amount FROM account_items WHERE id='$itemid'";

		break;


		case "payment":
			/*
				PAYMENT

				Payments against invoices are also items
			*/
			
			$structure = NULL;
			$structure["fieldname"] 	= "date_trans";
			$structure["type"]		= "date";
			$structure["defaultvalue"]	= date("Y-m-d");
			$form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"] 	= "amount";
			$structure["type"]		= "input";
			$form->add_input($structure);

			$structure = NULL;
			$structure = charts_form_prepare_acccountdropdown("chartid", 6);
			
			if (count(array_keys($structure["values"])) == 1)
			{
				// if there is only 1 account avaliable, select it as the default
				$structure["options"]["noselectoption"] = "yes";
			}
			
			$form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"] 	= "source";
			$structure["type"]		= "input";
			$form->add_input($structure);
				
			$structure = NULL;
			$structure["fieldname"] 	= "description";
			$structure["type"]		= "textarea";
			$structure["options"]["height"]	= "50";
			$structure["options"]["width"]	= 500;
			$form->add_input($structure);
			
	
			// define form layout
			$form->subforms[$type ."_invoice_item"]		= array("date_trans", "amount", "chartid", "source", "description");

			// SQL query
			$form->sql_query = "SELECT amount as amount, description, chartid FROM account_items WHERE id='$itemid'";


			

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

	// custom loads for different item type
	if ($itemid)
	{
		switch ($item_type)
		{
			case "tax":

				// check if the tax is to be calculated manually or calculated
				// automatically.
				
				$sql_obj		= New sql_query;
				$sql_obj->string	= "SELECT option_value AS value FROM account_items_options WHERE itemid='$itemid' AND option_name='TAX_CALC_MODE' LIMIT 1";
				$sql_obj->execute();

				if ($sql_obj->num_rows())
				{
					$form->structure["manual_option"]["defaultvalue"] = "on";
					$form->structure["manual_amount"]["defaultvalue"] = "";
				}
				else
				{
					$form->structure["manual_option"]["defaultvalue"] = "";
					$form->structure["manual_amount"]["defaultvalue"] = "";
				}

			break;


			case "time":

				// fetch the time group ID
				$form->structure["timegroupid"]["defaultvalue"]	= sql_get_singlevalue("SELECT option_value AS value FROM account_items_options WHERE itemid='$itemid' AND option_name='TIMEGROUPID' LIMIT 1");
				
			break;


			case "payment":

				// fetch payment date_trans and source fields.
				$form->structure["date_trans"]["defaultvalue"]	= sql_get_singlevalue("SELECT option_value AS value FROM account_items_options WHERE itemid='$itemid' AND option_name='DATE_TRANS' LIMIT 1");
				$form->structure["source"]["defaultvalue"]	= sql_get_singlevalue("SELECT option_value AS value FROM account_items_options WHERE itemid='$itemid' AND option_name='SOURCE' LIMIT 1");
			
			break;
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
	
	
	
	//// ERROR CHECKING ///////////////////////
	
	
	/*
		Verify that the invoice exists
	*/
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM account_$type WHERE id='$id' LIMIT 1";
	$sql_obj->execute();
	
	if (!$sql_obj->num_rows())
	{
		$_SESSION["error"]["message"][] = "<p><b>Error: The requested invoice does not exist. <a href=\"index.php?page=accounts/$type/$type.php\">Try looking on the invoice/invoice list page.</a></b></p>";
	}
	else
	{
		$sql_obj->fetch_array();
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

			// fetch information from form
			$data["amount"]		= security_form_input_predefined("money", "amount", 1, "");
			$data["chartid"]	= security_form_input_predefined("int", "chartid", 1, "");
			$data["description"]	= security_form_input_predefined("any", "description", 0, "");
			
		break;


		case "product":
			/*
				PRODUCT ITEMS
			*/
			
			// fetch information from form
			$data["price"]		= security_form_input_predefined("money", "price", 1, "");
			$data["quantity"]	= security_form_input_predefined("int", "quantity", 1, "");
			$data["units"]		= security_form_input_predefined("any", "units", 0, "");
			$data["customid"]	= security_form_input_predefined("int", "productid", 1, "");
			$data["description"]	= security_form_input_predefined("any", "description", 0, "");

			// calculate the total amount
			$data["amount"] = $data["price"] * $data["quantity"];

			// get the chart for the product
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT account_sales FROM products WHERE id='". $data["customid"] ."' LIMIT 1";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				$sql_obj->fetch_array();

				$data["chartid"] = $sql_obj->data[0]["account_sales"];
			}
			else
			{
				if (!$_SESSION["error"]["productid-error"])
				{
					$_SESSION["error"]["message"][] = "The requested product does not exist!";
					$_SESSION["error"]["productid-error"] = 1;
				}
			}
		break;


		case "time":
			/*
				TIME ITEMS

				We need to get the number of billable hours, then calculate
				the total charge for the item.

				The supplied price is the cost per hour, and the supplied productid
				provides the information for where the time should be billed to.
			*/
		
			// a time item can only be added to an AR transactions
			if ($type != "ar")
			{
				$_SESSION["error"]["message"][] = "You can only add time invoice items to AR invoices.";
			}

		
			// fetch information from form
			$data["price"]		= security_form_input_predefined("money", "price", 1, "");
			$data["customid"]	= security_form_input_predefined("int", "productid", 1, "");
			$data["timegroupid"]	= security_form_input_predefined("int", "timegroupid", 1, "");
			$data["description"]	= security_form_input_predefined("any", "description", 0, "");
			$data["units"]		= "hours";

			// fetch the number of billable hours for the supplied timegroupid
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT SUM(time_booked) as time_billable FROM timereg WHERE groupid='". $data["timegroupid"] ."'";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				// work out the number of hours and excess minutes
				$sql_obj->fetch_array();
	
			 	$minutes	= $sql_obj->data[0]["time_billable"] / 60;
				$hours		= sprintf("%d",$minutes / 60);
				
				$excess_minutes = sprintf("%02d", $minutes - ($hours * 60));
				
				// convert minutes to base-10 numbering systems
				// eg: 15mins becomes 0.25
				$excess_minutes = $excess_minutes / 60;
				
				// set the quantity
				$data["quantity"] = $hours + $excess_minutes;
			}
			else
			{
				$_SESSION["error"]["message"][] = "Invalid time group supplied!";
			}
			
			
			// calculate the total amount
			$data["amount"] = $data["price"] * $data["quantity"];

			// get the chart for the product
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT account_sales FROM products WHERE id='". $data["customid"] ."' LIMIT 1";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				$sql_obj->fetch_array();

				$data["chartid"] = $sql_obj->data[0]["account_sales"];
			}
			else
			{
				if (!$_SESSION["error"]["productid-error"])
				{
					$_SESSION["error"]["message"][] = "The requested product does not exist!";
					$_SESSION["error"]["productid-error"] = 1;
				}
			}
		break;




		case "tax":
			/*
				TAX ITEMS

				We need to either use the manual amounts provided, or calculate the tax amount.
			*/

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
				$data["amount"]	= security_form_input_predefined("money", "manual_amount", 1, "You must enter a value if you choose to calculate the tax amount manually.");

				// label it for the ledgers
				$data["description"] = "Manual tax calculation";
			}
			else
			{
				// fetch total of billable items
				$amount	= sql_get_singlevalue("SELECT sum(amount) as value FROM `account_items` WHERE invoiceid='$id' AND type!='tax'");

				// calculate taxable amount
				$data["amount"] = $amount * ($data["taxrate"] / 100);
				
				$data["amount"] = sprintf("%0.2f", $data["amount"]);

				
				// label it for the ledgers
				$data["description"] = "Automatic tax calculation at rate of ". $data["taxrate"] ."%";
			}
			
		break;


		case "payment":
			/*
				PAYMENT ITEM
			*/

			// fetch information from form
			$data["date_trans"]	= security_form_input_predefined("date", "date_trans", 1, "");
			$data["amount"]		= security_form_input_predefined("money", "amount", 1, "");
			$data["chartid"]	= security_form_input_predefined("int", "chartid", 1, "");
			$data["source"]		= security_form_input_predefined("any", "source", 1, "");
			$data["description"]	= security_form_input_predefined("any", "description", 0, "");
			
		break;


		default:
			$_SESSION["error"]["message"][] = "Unknown item type passed to processing form.";
		break;
	}
	
	



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
						."amount='". $data["amount"] ."', "
						."price='". $data["price"] ."', "
						."chartid='". $data["chartid"] ."', "
						."customid='". $data["customid"] ."', "
						."quantity='". $data["quantity"] ."', "
						."units='". $data["units"] ."', "
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

			// create options for payments
			if ($item_type == "payment")
			{
				// source
				$sql_obj		= New sql_query;
				$sql_obj->string	= "INSERT INTO account_items_options (itemid, option_name, option_value) VALUES ('$itemid', 'SOURCE', '". $data["source"] ."')";
				$sql_obj->execute();

				// date_trans
				$sql_obj		= New sql_query;
				$sql_obj->string	= "INSERT INTO account_items_options (itemid, option_name, option_value) VALUES ('$itemid', 'DATE_TRANS', '". $data["date_trans"] ."')";
				$sql_obj->execute();
			}


			// options for time items
			if ($item_type == "time")
			{
				// create options entry for the timegroupid
				$sql_obj		= New sql_query;
				$sql_obj->string	= "INSERT INTO account_items_options (itemid, option_name, option_value) VALUES ('$itemid', 'TIMEGROUPID', '". $data["timegroupid"] ."')";
				$sql_obj->execute();

				// update the time_group with the status, invoiceid and itemid
				$sql_obj		= New sql_query;
				$sql_obj->string	= "UPDATE time_groups SET invoiceid='$id', invoiceitemid='$itemid', locked='1' WHERE id='". $data["timegroupid"] ."'";
				$sql_obj->execute();
			}

		
			
			/*
				Update Tax Items

				re-generate the tax calculations for this invoice
				(exclude tax and payment items since they don't affect the taxable totals)
			*/
			
			if ($item_type != "tax" && $item != "payment")
			{
				invoice_items_update_tax($id, $type);
			}


			/*
				Update Invoice Totals

				Update the summary totals on the invoice.
			*/

			invoice_items_update_total($id, $type);



			/*
				Generate ledger entries.

			*/

			invoice_items_update_ledger($id, $type);

		

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
		$sql_obj->string	= "SELECT id, type FROM account_items WHERE id='$itemid' AND invoiceid='$id' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			$_SESSION["error"]["message"][] = "<p><b>Error: The requested item/invoice combination does not exist. Are you trying to use a link to a deleted invoice?</b></p>";
		}
		else
		{
			$sql_obj->fetch_array();

			$item_type = $sql_obj->data[0]["type"];
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
			Unlock time_groups if required
		*/
		if ($item_type == "time")
		{
			$groupid = sql_get_singlevalue("SELECT option_value as value FROM account_items_options WHERE itemid='$itemid' AND option_name='TIMEGROUPID'");
		
			$sql_obj		= New sql_query;
			$sql_obj->string	= "UPDATE time_groups SET invoiceid='0', invoiceitemid='0', locked='0' WHERE id='$groupid'";
			$sql_obj->execute();
		}
	
	
		/*
			Delete the invoice item options
		*/

		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM account_items_options WHERE itemid='$itemid'";
		$sql_obj->execute();

	
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



		/*
			Update taxes
		*/
		
		invoice_items_update_tax($id, $type);	



		/*
			Update ledger
		*/
		
		invoice_items_update_ledger($id, $type);



		/*
			Update invoice summary
		*/
		
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
	$amount		= sql_get_singlevalue("SELECT sum(amount) as value FROM `account_items` WHERE invoiceid='$id' AND type!='tax' AND type!='payment'");
	$amount_tax	= sql_get_singlevalue("SELECT sum(amount) as value FROM `account_items` WHERE invoiceid='$id' AND type='tax'");
	$amount_paid	= sql_get_singlevalue("SELECT sum(amount) as value FROM `account_items` WHERE invoiceid='$id' AND type='payment'");

	// final totals
	$amount_total	= $amount + $amount_tax;

	$amount		= sprintf("%0.2f", $amount);
	$amount_tax	= sprintf("%0.2f", $amount_tax);
	$amount_total	= sprintf("%0.2f", $amount_total);
	$amount_paid	= sprintf("%0.2f", $amount_paid);


	/*
		Update the invoice
	*/
	$sql_obj = New sql_query;
			
	$sql_obj->string = "UPDATE `account_$type` SET "
				."amount='". $amount ."', "
				."amount_tax='". $amount_tax ."', "
				."amount_total='". $amount_total ."', "
				."amount_paid='". $amount_paid ."' "
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

	Note that it does NOT update the tax totals on the invoice itself or the ledger, so you MUST run
	the following functions afterwards:
	* invoice_items_update_totals
	* invoice_items_update_ledger
	

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
	$amount		= sql_get_singlevalue("SELECT sum(amount) as value FROM `account_items` WHERE invoiceid='$id' AND type!='tax' AND type!='payment'");


	/*
		Run though all the tax items on this invoice
	*/
	$sql_items_obj		= New sql_query;
	$sql_items_obj->string	= "SELECT id, customid, amount FROM account_items WHERE invoiceid='$id' AND type='tax'";
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
			
				// fetch required information
				$sql_tax_obj		= New sql_query;
				$sql_tax_obj->string	= "SELECT taxrate, chartid FROM account_taxes WHERE id='". $data["customid"] ."' LIMIT 1";
				$sql_tax_obj->execute();

				if ($sql_tax_obj->num_rows())
				{
					$sql_tax_obj->fetch_array();
				}

			
				// calculate taxable amount
				$amount = $amount * ($sql_tax_obj->data[0]["taxrate"] / 100);
				$amount = sprintf("%0.2f", $amount);

				// update the item with the new amount
				$sql_obj		= New sql_query;
				$sql_obj->string	= "UPDATE account_items SET amount='$amount' WHERE id='". $data["id"] ."'";
				$sql_obj->execute();


				// note - the invoice_items_update ledger function should now be called to update the ledger

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




/*
	invoice_items_update_ledger

	This function updates the ledger based on the data in the account_items table. This function needs to be
	run after making any changes to any item on the invoice, including payments.

	Values
	id		ID of the invoice to update
	type		Type of invoice - AR or AP

	Return Codes
	0		failure
	1		success
*/
function invoice_items_update_ledger($id, $type)
{
	log_debug("inc_invoices_items", "Executing invoice_items_update_ledger($id, $type)");


	// fetch key information from invoice
	$sql_inv_obj		= New sql_query;
	$sql_inv_obj->string	= "SELECT id, dest_account, date_trans FROM account_$type WHERE id='$id' LIMIT 1";
	$sql_inv_obj->execute();
	$sql_inv_obj->fetch_array();


	// remove all the old ledger entries belonging to this invoice
	$sql_obj		= New sql_query;
	$sql_obj->string	= "DELETE FROM `account_trans` WHERE customid='$id'";
	$sql_obj->execute();


	/*
		PROCESS NON-PAYMENT ITEMS

		For all normal items, we want to aggregate the totals per chart then add ledger entries
		per-invoice, not per-item.

		Then we create the following in the ledger:

			AR INVOICES
			* A single debit from the AR account
			* A single credit to each different account for the items.

			AP INVOICES
			* A single credit to the AP account
			* A single debit to each different account for the items

		Payment items need to be handled differently - see code further down.
	*/
	
	// add up the total for the AR entry
	$amount = 0;

	// Fetch totals per chart from the items table.
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT chartid, type, SUM(amount) as amount FROM `account_items` WHERE invoiceid='$id' AND type!='payment' GROUP BY chartid";
	$sql_obj->execute();

	if ($sql_obj->num_rows())
	{
		$sql_obj->fetch_array();

		foreach ($sql_obj->data as $item_data)
		{
			// set trans type
			if ($item_data["type"] == "tax")
			{
				$trans_type = $type ."_tax";
			}
			else
			{
				$trans_type = $type;
			}
		
			// create ledger entry for this account
			if ($type == "ap")
			{
				ledger_trans_add("debit", $trans_type, $id, $sql_inv_obj->data[0]["date_trans"], $item_data["chartid"], $item_data["amount"], "", "");
			}
			else
			{
				ledger_trans_add("credit", $trans_type, $id, $sql_inv_obj->data[0]["date_trans"], $item_data["chartid"], $item_data["amount"], "", "");
			}

			// add up the total for the AR entry.
			$amount += $item_data["amount"];
		}

		if ($type == "ap")
		{
			// create credit from AP account
			ledger_trans_add("credit", $type, $id, $sql_inv_obj->data[0]["date_trans"], $sql_inv_obj->data[0]["dest_account"], $amount, "", "");
		}
		else
		{
			// create debit to AR account
			ledger_trans_add("debit", $type, $id, $sql_inv_obj->data[0]["date_trans"], $sql_inv_obj->data[0]["dest_account"], $amount, "", "");
		}
	}




	/*
		PROCESS PAYMENT ITEMS

		Payment entries are different to other items, in that we need to add stand alone
		entries for each payment item, since payments can be made on different dates, so therefore
		can not be aggregated.
	*/

	// run though each payment item
	$sql_item_obj		= New sql_query;
	$sql_item_obj->string	= "SELECT id, chartid, amount, description FROM `account_items` WHERE invoiceid='$id' AND type='payment'";
	$sql_item_obj->execute();

	if ($sql_item_obj->num_rows())
	{
		$sql_item_obj->fetch_array();

		foreach ($sql_item_obj->data as $data)
		{
			// fetch information from options
			$sql_option_obj		= New sql_query;
			$sql_option_obj->string	= "SELECT option_name, option_value FROM account_items_options WHERE itemid='". $data["id"] ."'";
			$sql_option_obj->execute();

			$sql_option_obj->fetch_array();

			foreach ($sql_option_obj->data as $option_data)
			{
				if ($option_data["option_name"] == "SOURCE")
					$data["source"] = $option_data["option_value"];

				if ($option_data["option_name"] == "DATE_TRANS")
					$data["date_trans"] = $option_data["option_value"];
			}
			

			if ($type == "ap")
			{
				// we need to credit the destination account for the payment to come from and debit the AP account
				ledger_trans_add("credit", $type ."_pay", $id, $data["date_trans"], $data["chartid"], $data["amount"], $data["source"], $data["description"]);
				ledger_trans_add("debit", $type ."_pay", $id, $data["date_trans"], $sql_inv_obj->data[0]["dest_account"], $data["amount"], $data["source"], $data["description"]);
			}
			else
			{
				// we need to debit the destination account for the payment to go into and credit the AR account
				ledger_trans_add("debit", $type ."_pay", $id, $data["date_trans"], $data["chartid"], $data["amount"], $data["source"], $data["description"]);
				ledger_trans_add("credit", $type ."_pay", $id, $data["date_trans"], $sql_inv_obj->data[0]["dest_account"], $data["amount"], $data["source"], $data["description"]);
			}
		}
	}


	return 1;

} // end of invoice_items_update_ledger










?>
