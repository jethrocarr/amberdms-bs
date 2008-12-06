<?php
/*
	include/accounts/inc_invoices_items.php

	Provides forms and processing code for listing and adjusting the items belonging
	to an invoice or quote. These functions are used by the AR, AP and quotes sections
	of this program.
*/


// includes
include("inc_ledger.php");


/*
	class: invoice_list_items

	This function lists all the items belonging to the invoice and creates links to view/edit/delete them.
*/
class invoice_list_items
{
	var $type;		// Either "ar", "ap" or "quotes"
	var $invoiceid;		// If editing/viewing an existing invoice, provide the ID
	
	var $page_view;		// Page for viewing/editing the invoice item
	var $page_delete;	// Page for deleting the invoice item

	var $mode;
	
//	var $obj_table_standard;
//	var $obj_table_taxes;



	function execute()
	{
		log_debug("invoice_list_items", "Executing execute()");
		
		// TODO: fix up this class to comply with the standard coding style of the rest of the application
	
		// do nothing
		return 1;
	}

	function render_html()
	{
		log_debug("invoice_list_items", "Executing render_html()");

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
		$item_list->add_column("text", "description", "");

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
		
		$item_list->sql_obj->prepare_sql_addwhere("invoiceid='". $this->invoiceid ."'");
		$item_list->sql_obj->prepare_sql_addwhere("invoicetype='". $this->type ."'");
		$item_list->sql_obj->prepare_sql_addwhere("type!='tax'");
		$item_list->sql_obj->prepare_sql_addwhere("type!='payment'");
		
		$item_list->sql_obj->prepare_sql_addorderby("type");

		// run SQL query
		$item_list->generate_sql();
		$item_list->load_data_sql();

		if (!$item_list->data_num_rows)
		{
			print "<p><i>There are currently no items.</i></p>";
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


			if (user_permissions_get("accounts_". $this->type ."_write"))
			{
				// edit link
				$structure = NULL;
				$structure["id"]["value"]	= $this->invoiceid;
				$structure["id"]["action"]	= "edit";
				$structure["itemid"]["column"]	= "id";
			
				$item_list->add_link("edit", $this->page_view, $structure);

			
				// delete link
				$structure = NULL;
				$structure["id"]["value"]	= $this->invoiceid;
				$structure["itemid"]["column"]	= "id";
				$structure["full_link"]		= "yes";
			
				$item_list->add_link("delete", $this->page_delete, $structure);
			}


			// display the table
			$item_list->render_table_html();
		}
		
		print "<p><b><a href=\"index.php?page=". $this->page_view ."&id=". $this->invoiceid ."&type=standard\">Add standard transaction item</a></b></p>";
		print "<p><b><a href=\"index.php?page=". $this->page_view ."&id=". $this->invoiceid ."&type=product\">Add product item</a></b></p>";

		if ($this->type == "ar")
		{
			print "<p><b><a href=\"index.php?page=". $this->page_view ."&id=". $this->invoiceid ."&type=time\">Add time item</a></b></p>";
		}



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
		$item_list->add_column("text", "description", "account_items.description");

		// defaults
		$item_list->columns		= array("amount", "name_tax", "description");
		$item_list->columns_order	= array("name_tax");

		// totals
		$item_list->total_columns	= array("amount");

		// define SQL structure
		$item_list->sql_obj->prepare_sql_settable("account_items");
		$item_list->sql_obj->prepare_sql_addfield("id", "account_items.id");
		$item_list->sql_obj->prepare_sql_addjoin("LEFT JOIN account_taxes ON account_taxes.id = account_items.customid");
		$item_list->sql_obj->prepare_sql_addwhere("invoiceid='". $this->invoiceid ."'");
		$item_list->sql_obj->prepare_sql_addwhere("invoicetype='". $this->type ."'");
		$item_list->sql_obj->prepare_sql_addwhere("type='tax'");
		

		// run SQL query
		$item_list->generate_sql();
		$item_list->load_data_sql();

		if (!$item_list->data_num_rows)
		{
			print "<p><i>There are currently no taxes items.</i></p>";
		}
		else
		{
			
			if (user_permissions_get("accounts_". $this->type ."_write"))
			{
				// edit link
				$structure = NULL;
				$structure["id"]["value"]	= $this->invoiceid;
				$structure["itemid"]["column"]	= "id";
			
				$item_list->add_link("edit", $this->page_view, $structure);
			
				// delete link
				$structure = NULL;
				$structure["id"]["value"]	= $this->invoiceid;
				$structure["itemid"]["column"]	= "id";
				$structure["full_link"]		= "yes";

				$item_list->add_link("delete", $this->page_delete, $structure);
			}

		
			// display the table
			$item_list->render_table_html();
	
		}
		
		print "<p><b><a href=\"index.php?page=". $this->page_view ."&id=". $this->invoiceid ."&type=tax\">Add tax item</a></b></p>";

		return 1;
	}

	
} // end of class invoice_list_items





/*
	class: invoice_list_payments

	This function lists all the payments belonging to the invoice and creates links to view/edit/delete them.
*/
class invoice_list_payments
{
	var $type;		// Either "ar" or "ap"
	var $invoiceid;		// If editing/viewing an existing invoice, provide the ID
	
	var $page_view;		// Page for viewing/editing the invoice item
	var $page_delete;	// Page for deleting the invoice item

	var $mode;
	
//	var $obj_table_standard;
//	var $obj_table_taxes;



	function execute()
	{
		log_debug("invoice_list_payments", "Executing execute()");
		
		// TODO: fix up this class to comply with the standard coding style of the rest of the application
	
		// do nothing
		return 1;
	}


	function render_html()
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
		$item_list->sql_obj->prepare_sql_addwhere("invoiceid='". $this->invoiceid ."'");
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



			if (user_permissions_get("accounts_". $this->type ."_write"))
			{
				// edit link
				$structure = NULL;
				$structure["id"]["value"]	= $this->invoiceid;
				$structure["id"]["action"]	= "edit";
				$structure["itemid"]["column"]	= "id";
				
				$item_list->add_link("edit", $this->page_view, $structure);

				
				// delete link
				$structure = NULL;
				$structure["id"]["value"]	= $this->invoiceid;
				$structure["itemid"]["column"]	= "id";
				$structure["full_link"]		= "yes";
				
				$item_list->add_link("delete", $this->page_delete, $structure);
			}
			

			// display the table
			$item_list->render_table_html();
		}
		
		print "<p><b><a href=\"index.php?page=". $this->page_view ."&id=". $this->invoiceid ."&type=payment\">Add Payment</a></b></p>";


		return 1;
	}
	
} // end of invoice_list_payments()






/*
	class: invoice_form_item

	Provides a form for creating or editing invoice items/payments
*/
class invoice_form_item
{
	var $type;		// Either "ar" or "ap"
	var $invoiceid;		// If editing/viewing an existing invoice, provide the ID
	var $itemid;		// If editing/viewing an existing item, provide the ID
	
	var $processpage;	// Page to process the submitted form
	var $item_type;		// Type of item

	var $mode;
	
	var $obj_form;
	

	function execute()
	{
		log_debug("invoice_form_items", "Executing execute()");
		
		// TODO: fix up this class to comply with the standard coding style of the rest of the application
	
		// do nothing
		return 1;
	}


	function render_html()
	{
		log_debug("inc_invoices_details", "Executing invoice_form_items_render($type, $id, $processpage)");


		// determine the mode
		if ($this->itemid)
		{
			$this->mode = "edit";
		}
		else
		{
			$this->mode		= "add";
		}


		/*
			Start Form
		*/
		$form = New form_input;
		$form->formname		= $this->type ."_invoice_". $this->mode;
		$form->language		= $_SESSION["user"]["lang"];

		$form->action		= $this->processpage;
		$form->method		= "POST";
		


		/*
			Define form structure, depending on the type of the item
		*/

		switch ($this->item_type)
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

				if ($this->type == "ap")
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
				$form->subforms[$this->type ."_invoice_item"] = array("amount", "chartid", "description");

				// SQL query
				$form->sql_query = "SELECT amount, description, chartid FROM account_items WHERE id='". $this->itemid ."'";

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
				$form->subforms[$this->type ."_invoice_item"]		= array("productid", "price", "quantity", "units", "description");

				// SQL query
				$form->sql_query = "SELECT price, description, customid as productid, quantity, units FROM account_items WHERE id='". $this->itemid ."'";


			
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

				if ($this->type == "ar")
				{
					// fetch the customer ID for this invoice, so we can create
					// a list of the time groups can be added.
					$customerid = sql_get_singlevalue("SELECT customerid as value FROM account_". $this->type ." WHERE id='". $this->id ."' LIMIT 1");
					
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
					$form->subforms[$this->type ."_invoice_item"]		= array("timegroupid", "productid", "price", "description");

					// SQL query
					$form->sql_query = "SELECT price, description, customid as productid, quantity, units FROM account_items WHERE id='". $this->itemid ."'";
					
				
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
				$form->subforms[$this->type ."_invoice_item"]		= array("tax_id", "manual_option", "manual_amount");

				// SQL query
				$form->sql_query = "SELECT customid as tax_id, amount as manual_amount FROM account_items WHERE id='". $this->itemid ."'";

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
				$form->subforms[$this->type ."_invoice_item"]		= array("date_trans", "amount", "chartid", "source", "description");

				// SQL query
				$form->sql_query = "SELECT amount as amount, description, chartid FROM account_items WHERE id='". $this->itemid ."'";

			break;


			default:
				print "<p><b>Error: Unknown type passed to render form.</b></p>";
			break;
		}



		// IDs
		$structure = NULL;
		$structure["fieldname"]		= "id_invoice";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->invoiceid;
		$form->add_input($structure);	
		
		$structure = NULL;
		$structure["fieldname"]		= "id_item";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->itemid;
		$form->add_input($structure);	
		
		$structure = NULL;
		$structure["fieldname"]		= "item_type";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->item_type;
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
		if ($this->itemid)
		{
			switch ($this->item_type)
			{
				case "tax":

					// check if the tax is to be calculated manually or calculated
					// automatically.
					
					$sql_obj		= New sql_query;
					$sql_obj->string	= "SELECT option_value AS value FROM account_items_options WHERE itemid='". $this->itemid ."' AND option_name='TAX_CALC_MODE' LIMIT 1";
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
					$form->structure["timegroupid"]["defaultvalue"]	= sql_get_singlevalue("SELECT option_value AS value FROM account_items_options WHERE itemid='". $this->itemid ."' AND option_name='TIMEGROUPID' LIMIT 1");
					
				break;


				case "payment":

					// fetch payment date_trans and source fields.
					$form->structure["date_trans"]["defaultvalue"]	= sql_get_singlevalue("SELECT option_value AS value FROM account_items_options WHERE itemid='". $this->itemid ."' AND option_name='DATE_TRANS' LIMIT 1");
					$form->structure["source"]["defaultvalue"]	= sql_get_singlevalue("SELECT option_value AS value FROM account_items_options WHERE itemid='". $this->itemid ."' AND option_name='SOURCE' LIMIT 1");
				
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
} // end of invoice_form_item




/*
	FUNCTIONS
*/





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
		Start invoice_items class
	*/
	$item			= New invoice_items;
	
	$item->id_invoice	= security_form_input_predefined("int", "id_invoice", 1, "");
	$item->id_item		= security_form_input_predefined("int", "id_item", 0, "");
	
	$item->type_invoice	= $type;
	$item->type_item	= security_form_input_predefined("any", "item_type", 1, "");
	

	/*
		Fetch all form data
	*/

	if ($item->id_item)
	{
		$mode = "edit";
	}
	else
	{
		$mode = "add";
	}
	
	
	
	//// ERROR CHECKING ///////////////////////
	

	/*
		Check that invoice and item are valid
	*/
	
	if ($item->verify_invoice())
	{
		if ($mode == "edit")
		{
			if (!$item->verify_item())
			{
				$_SESSION["error"]["message"][] = "<p><b>Error: The requested item/invoice combination does not exist. Are you trying to use a link to a deleted invoice?</b></p>";
			}
		}
	}
	else
	{
		$_SESSION["error"]["message"][] = "<p><b>Error: The requested invoice does not exist.</b></p>";
	}

	

	// fetch form data
	switch($item->type_item)
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

		break;


		case "time":
			/*
				TIME ITEMS
			*/
		
			// fetch information from form
			$data["price"]		= security_form_input_predefined("money", "price", 1, "");
			$data["customid"]	= security_form_input_predefined("int", "productid", 1, "");
			$data["timegroupid"]	= security_form_input_predefined("int", "timegroupid", 1, "");
			$data["description"]	= security_form_input_predefined("any", "description", 0, "");
			$data["units"]		= "hours";
			
		break;




		case "tax":
			/*
				TAX ITEMS
			*/

			// fetch key information from form
			$data["customid"]	= security_form_input_predefined("int", "tax_id", 1, "");
			$data["manual_option"]	= security_form_input_predefined("any", "manual_option", 0, "");

			if ($data["manual_option"])
			{
				$data["manual_amount"]	= security_form_input_predefined("money", "manual_amount", 1, "You must enter a value if you choose to calculate the tax amount manually.");
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
	


	/*
		Process data
	*/
	if (!$item->prepare_data($data))
	{
		$_SESSION["error"]["message"] = "An error was encountered whilst processing supplied data.";
	}



	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"][$item->type_invoice ."_invoice_". $mode] = "failed";
		header("Location: ../../index.php?page=$returnpage_error&id=". $item->id_invoice ."&type=". $item->type_item ."");
		exit(0);
	}
	else
	{

		/*
			APPLY ITEM CHANGES
		*/
	
		if ($mode == "add")
		{
			if (!$item->action_create())
			{
				$_SESSION["error"]["message"][] = "Unexpected problem occured whilst attempting to create new invoice item.";
			}
		}
		else
		{
			if (!$item->action_update())
			{
				$_SESSION["error"]["message"][] = "Unexpected problem occured whilst attempting to update invoice item.";
			}
		}



		/*
			Re-calculate taxes, totals and ledgers as required
		*/
		
		if ($item->type_item != "tax" && $item->type_item != "payment")
		{
			$item->action_update_tax();
		}

		$item->action_update_total();



		/*
			Generate ledger entries.

			(Note that for quotes, we do NOT generate ledger entries, since a quote
			should have no impact on the accounts)
		*/

		if ($item->type_invoice != "quotes")
		{
			$item->action_update_ledger();
		}



		/*
			Return to success page
		*/

		if (!$_SESSION["error"]["messages"])
		{
			if ($mode == "add")
			{
				$_SESSION["notification"]["message"][] = "Item successfully created.";
				journal_quickadd_event("account_". $item->type_invoice ."", $item->id_invoice, "Item successfully created");
			}
			else
			{
				$_SESSION["notification"]["message"][] = "Item successfully updated.";
				journal_quickadd_event("account_". $item->type_invoice ."", $item->id_invoice, "Item successfully updated");
			}
		}
				

		// display updated details
		header("Location: ../../index.php?page=$returnpage_success&id=". $item->id_invoice."");
		exit(0);


	} // end if passed tests


} // end of invoice_form_items_process




/*
	invoice_form_items_delete_process($type, $returnpage_error, $returnpage_success)

	Processing page to delete invoice items.

	Values
	type			"ar", "ap" or "quotes"
	returnpage_error	Page to return to in event of errors or updates
	returnpage_success	Page to return to if successful.
*/
function invoice_form_items_delete_process($type,  $returnpage_error, $returnpage_success)
{
	log_debug("inc_invoices_items", "Executing invoice_form_items_delete_process($type, $returnpage_error, $returnpage_success)");


	/*
		Start invoice_items object
	*/
	$item			= New invoice_items;
	
	$item->id_invoice	= security_script_input("/^[0-9]*$/", $_GET["id"]);
	$item->id_item		= security_script_input("/^[0-9]*$/", $_GET["itemid"]);

	$item->type_invoice	= $type;
	
	
	/*
		Fetch all form data
	*/

	// invoice + item IDs
	if (!$item->id_invoice || !$item->id_item)
	{
		$_SESSION["error"]["message"][] = "Incorrect URL passed to function invoice_forms_items_delete_process";
	}
	

	
	//// ERROR CHECKING ///////////////////////


	/*
		Verify invoice/form data
	*/
	if ($item->verify_invoice())
	{
		if (!$item->verify_item())
		{
			$_SESSION["error"]["message"][] = "<p><b>Error: The requested item/invoice combination does not exist. Are you trying to use a link to a deleted invoice?</b></p>";
		}
	}
	else
	{
		$_SESSION["error"]["message"][] = "<p><b>Error: The requested invoice does not exist.</b></p>";
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
			Delete the item
		*/
		$item->action_delete();


		/*
			Update taxes
		*/
		
		$item->action_update_tax();



		/*
			Update ledger

			(No need to do this for quotes, since they do not impact the ledger)
		*/
		
		if ($item->type_invoice != "quotes")
		{
			$item->action_update_ledger();
		}



		/*
			Update invoice summary
		*/
		
		$item->action_update_total();


		// return with success
		if (!$_SESSION["error"]["message"])
		{
			$_SESSION["notification"]["message"][] = "Item deleted successfully";
			journal_quickadd_event("account_". $item->type_invoice ."", $item->id_invoice, "Item successfully deleted");
		}
		
		header("Location: ../../index.php?page=$returnpage_success&id=". $item->id_invoice ."");
		exit(0);
	}
	
} // end of invoice_form_items_delete_process




?>
