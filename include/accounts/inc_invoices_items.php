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

	var $locked;
	var $mode;
	
	var $obj_table_standard;
	var $obj_table_taxes;



	function execute()
	{
		log_debug("invoice_list_items", "Executing execute()");

		// check lock status
		if ($this->type != "quotes")
		{
			$this->locked = sql_get_singlevalue("SELECT locked as value FROM account_". $this->type ." WHERE id='". $this->invoiceid ."'");
		}



		/*
			Create table of standard object data
		*/

		$_SESSION["notification"]["message"] = array("Updated tax value with custom input");
		// establish a new table object
		$this->obj_table_standard		= New table;

		$this->obj_table_standard->language	= $_SESSION["user"]["lang"];
		$this->obj_table_standard->tablename	= "item_list";

		// define all the columns and structure
		$this->obj_table_standard->add_column("standard", "item_info", "NONE");
		$this->obj_table_standard->add_column("text", "description", "");
		$this->obj_table_standard->add_column("standard", "qnty", "quantity");
		$this->obj_table_standard->add_column("standard", "units", "");
		$this->obj_table_standard->add_column("money", "price", "");
		$this->obj_table_standard->add_column("money", "amount", "");

		// defaults
		$this->obj_table_standard->columns		= array("item_info", "description", "qnty", "units", "price", "amount");

		// totals
		$this->obj_table_standard->total_columns	= array("amount");

		// define SQL structure
		$this->obj_table_standard->sql_obj->prepare_sql_settable("account_items");
		
		$this->obj_table_standard->sql_obj->prepare_sql_addfield("id", "");
		$this->obj_table_standard->sql_obj->prepare_sql_addfield("type", "");
		$this->obj_table_standard->sql_obj->prepare_sql_addfield("customid", "");
		$this->obj_table_standard->sql_obj->prepare_sql_addfield("chartid", "");

		$this->obj_table_standard->sql_obj->prepare_sql_addwhere("invoiceid='". $this->invoiceid ."'");
		$this->obj_table_standard->sql_obj->prepare_sql_addwhere("invoicetype='". $this->type ."'");
		$this->obj_table_standard->sql_obj->prepare_sql_addwhere("type!='tax'");
		$this->obj_table_standard->sql_obj->prepare_sql_addwhere("type!='payment'");
		
		$this->obj_table_standard->sql_obj->prepare_sql_addorderby("type");

		// run SQL query
		$this->obj_table_standard->generate_sql();
		$this->obj_table_standard->load_data_sql();

		if ($this->obj_table_standard->data_num_rows)
		{
			/*
				Perform custom processing for item types
			*/
			for ($i=0; $i < $this->obj_table_standard->data_num_rows; $i++)
			{
				switch ($this->obj_table_standard->data[$i]["type"])
				{
					case "product":
						/*
							Fetch product name
						*/
						$sql_obj		= New sql_query;
						$sql_obj->string	= "SELECT name_product FROM products WHERE id='". $this->obj_table_standard->data[$i]["customid"] ."' LIMIT 1";
						$sql_obj->execute();

						$sql_obj->fetch_array();
						$this->obj_table_standard->data[$i]["item_info"] = $sql_obj->data[0]["name_product"];
					break;


					case "time":
						/*
							Fetch time group ID
						*/

						$groupid = sql_get_singlevalue("SELECT option_value as value FROM account_items_options WHERE itemid='". $this->obj_table_standard->data[$i]["id"] ."' AND option_name='TIMEGROUPID'");

						$this->obj_table_standard->data[$i]["item_info"] = sql_get_singlevalue("SELECT CONCAT_WS(' -- ', projects.code_project, time_groups.name_group) as value FROM time_groups LEFT JOIN projects ON projects.id = time_groups.projectid WHERE time_groups.id='$groupid' LIMIT 1");
					break;


					case "standard":
						/*
							Fetch account name and blank a few fields
						*/

						$sql_obj		= New sql_query;
						$sql_obj->string	= "SELECT CONCAT_WS(' -- ',code_chart,description) as name_account FROM account_charts WHERE id='". $this->obj_table_standard->data[$i]["chartid"] ."' LIMIT 1";
						$sql_obj->execute();

						$sql_obj->fetch_array();
						$this->obj_table_standard->data[$i]["item_info"] = $sql_obj->data[0]["name_account"];
					
						$this->obj_table_standard->data[$i]["qnty"] = "";
					break;
				}
			}


			if (user_permissions_get("accounts_". $this->type ."_write") && !$this->locked)
			{
				// edit link
				$structure = NULL;
				$structure["id"]["value"]	= $this->invoiceid;
				$structure["id"]["action"]	= "edit";
				$structure["itemid"]["column"]	= "id";
			
				$this->obj_table_standard->add_link("edit", $this->page_view, $structure);

			
				// delete link
				$structure = NULL;
				$structure["id"]["value"]	= $this->invoiceid;
				$structure["itemid"]["column"]	= "id";
				$structure["full_link"]		= "yes";
			
				$this->obj_table_standard->add_link("delete", $this->page_delete, $structure);
			}


			// process table data & generate totals
			$this->obj_table_standard->render_table_prepare();



			/* 
				Create table of tax data
			*/

			// establish a new table object
			$this->obj_table_taxes = New table;

			$this->obj_table_taxes->language = $_SESSION["user"]["lang"];
			$this->obj_table_taxes->tablename = "item_list";

			// define all the columns and structure
			$this->obj_table_taxes->add_column("money", "amount", "account_items.amount");
			$this->obj_table_taxes->add_column("standard", "name_tax", "account_taxes.name_tax");

			// defaults
			$this->obj_table_taxes->columns		= array("amount", "name_tax");
			$this->obj_table_taxes->columns_order	= array("name_tax");

			// totals
			$this->obj_table_taxes->total_columns	= array("amount");

			// define SQL structure
			$this->obj_table_taxes->sql_obj->prepare_sql_settable("account_items");
			$this->obj_table_taxes->sql_obj->prepare_sql_addfield("id", "account_items.id");
			$this->obj_table_taxes->sql_obj->prepare_sql_addjoin("LEFT JOIN account_taxes ON account_taxes.id = account_items.customid");
			$this->obj_table_taxes->sql_obj->prepare_sql_addwhere("invoiceid='". $this->invoiceid ."'");
			$this->obj_table_taxes->sql_obj->prepare_sql_addwhere("invoicetype='". $this->type ."'");
			$this->obj_table_taxes->sql_obj->prepare_sql_addwhere("type='tax'");
			

			// run SQL query
			$this->obj_table_taxes->generate_sql();
			$this->obj_table_taxes->load_data_sql();

			// prepare the data for display
			$this->obj_table_taxes->render_table_prepare();


		} // end if items exist

		
		return 1;
	}



	function render_html()
	{
		log_debug("invoice_list_items", "Executing render_html()");


		/*
			Display custom table, combining the table data from the standard items and adding tax data

			The following code is based off the tables class.
		*/

		if (!$this->obj_table_standard->data_num_rows)
		{
			format_msgbox("info", "<p>There are no items in this invoice</p>");
		}
		else
		{

			print "<table width=\"100%\" class=\"table_content\" style=\"border-bottom: 0px;\">";


			/*
				Display standard invoice items
			*/

			print "<tr>";

			// heading
			foreach ($this->obj_table_standard->columns as $column)
			{
				print "<td class=\"header\"><b>". $this->obj_table_standard->render_columns[$column] ."</b></td>";
			}

			// filler for optional link column
			print "<td class=\"header\"></td>";

			print "</tr>";


			// display invoice items
			for ($i=0; $i < $this->obj_table_standard->data_num_rows; $i++)
			{
				print "<tr>";

				// content for columns
				foreach ($this->obj_table_standard->columns as $columns)
				{
					$content = $this->obj_table_standard->data_render[$i][$columns];

					// display
					print "<td valign=\"top\">$content</td>";
				}


				// links	
				print "<td align=\"right\">";

				$links		= array_keys($this->obj_table_standard->links);
				$links_count	= count($links);
				$count		= 0;

				foreach ($links as $link)
				{
					$count++;
					
					$linkname = language_translate_string($this->obj_table_standard->language, $link);

					// link to page
					// There are two ways:
					// 1. (default) Link to index.php
					// 2. Set the ["options]["full_link"] value to yes to force a full link

					if ($this->obj_table_standard->links[$link]["options"]["full_link"] == "yes")
					{
						print "<a href=\"". $this->obj_table_standard->links[$link]["page"] ."?libfiller=n";
					}
					else
					{
						print "<a href=\"index.php?page=". $this->obj_table_standard->links[$link]["page"] ."";
					}

					// add each option
					foreach (array_keys($this->obj_table_standard->links[$link]["options"]) as $getfield)
					{
						/*
							There are two methods for setting the value of the variable:
							1. The value has been passed.
							2. The name of a column to take the value from has been passed
						*/
						if ($this->obj_table_standard->links[$link]["options"][$getfield]["value"])
						{
							print "&$getfield=". $this->obj_table_standard->links[$link]["options"][$getfield]["value"];
						}
						else
						{
							print "&$getfield=". $this->obj_table_standard->data[$i][ $this->obj_table_standard->links[$link]["options"][$getfield]["column"] ];
						}
					}

					// finish link
					print "\">$linkname</a>";

					// if required, add seporator
					if ($count < $links_count)
					{
						print " || ";
					}
				}

				print "</tr>";
			}


			/*
				Subtotal

				Display total of all items without tax
			*/
			print "<tr>";
				print "<td class=\"blank\" colspan=\"3\"></td>";
				print "<td class=\"footer\" valign=\"top\" colspan=\"2\"><b>Subtotal:</b></td>";
				print "<td class=\"footer\" valign=\"top\"><b>". $this->obj_table_standard->data_render["total"]["amount"] ."</b></td>";
				print "<td class=\"footer\"></td>";
			print "</tr>";



			/*
				Display taxes

				For AR invoices and quotes we only display a read-only total, but for AP
				invoices we create a form for each tax, which users can use to override the
				automatically calculate tax amount.

				This override function is provided to deal with vendors who send invoices with
				incorrect rounding - a common example is $0.01 rounding errors.
			*/
			for ($i=0; $i < $this->obj_table_taxes->data_num_rows; $i++)
			{
				print "<tr>";

				// padding
				print "<td class=\"blank\" colspan=\"3\"></td>";

				// tax name
				print "<td valign=\"center\" colspan=\"2\">". $this->obj_table_taxes->data_render[$i]["name_tax"] ."</td>";


				if ($this->type == "ap" && user_permissions_get("accounts_". $this->type ."_write") && !$this->locked)
				{
					// amount
					print "<td valign=\"top\">";
					
					print "<form method=\"post\" action=\"accounts/ap/invoice-items-tax-override-process.php\">";

					print "<input type=\"hidden\" name=\"invoiceid\" value=\"". $this->invoiceid ."\">";
					print "<input type=\"hidden\" name=\"itemid\" value=\"". $this->obj_table_taxes->data[$i]["id"] ."\">";

					print sql_get_singlevalue("SELECT value FROM config WHERE name='CURRENCY_DEFAULT_SYMBOL'");
					print "<input name=\"amount\" value=\"". $this->obj_table_taxes->data[$i]["amount"] ."\" style=\"width: 100px; font-size: 10px;\">";
					
					print "</td>";
					

					// links
					print "<td align=\"right\">";

					print "<input type=\"submit\" value=\"correct\" style=\"font-size: 10px\">";

					print "</form>";

					print "</td>";			
				}
				else
				{
					// amount
					print "<td valign=\"top\">". $this->obj_table_taxes->data_render[$i]["amount"] ."</td>";

					// links
					print "<td></td>";
				}

				print "</tr>";
			}


			/*
				Invoice Total

				Items + Taxes totaled together
			*/

			$invoice_total = $this->obj_table_standard->data["total"]["amount"] + $this->obj_table_taxes->data["total"]["amount"];

			$invoice_total = format_money($invoice_total);

			print "<tr>";
				print "<td class=\"blank\" colspan=\"3\"></td>";
				print "<td class=\"footer\" valign=\"top\" colspan=\"2\"><b>Invoice Total:</b></td>";
				print "<td class=\"footer\" valign=\"top\"><b>$invoice_total</b></td>";
				print "<td class=\"footer\"></td>";
			print "</tr>";


			print "</table>";



		} // end if items exist




		if (user_permissions_get("accounts_". $this->type ."_write") && !$this->locked)
		{
			/*
				Display the new item form
			*/


			$form = New form_input;
			$form->formname		= $this->type ."_invoice_". $this->mode;
			$form->language		= $_SESSION["user"]["lang"];
	
			$form->action		= ereg_replace("edit", "add-process", $this->page_view);
			$form->method		= "POST";

			// basic details
			$structure = NULL;
			$structure["fieldname"] 	= "id";
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= $this->invoiceid;
			$form->add_input($structure);


			// item dropdown
			$structure = NULL;
			$structure["fieldname"] 	= "item";
			$structure["type"]		= "dropdown";

			$structure["values"][]			= "standard";
			$structure["translations"]["standard"]	= "Basic Transaction";
			
			if ($this->type == "ar")
			{
				$structure["values"][]			= "time";
				$structure["translations"]["time"]	= "Time Item";
			}

			// fetch all the products for the drop down
			$sql_products_obj		= New sql_query;
			$sql_products_obj->string	= "SELECT id, code_product, name_product FROM products ORDER BY name_product";
			$sql_products_obj->execute();
			
			if ($sql_products_obj->num_rows())
			{
				$sql_products_obj->fetch_array();

				foreach ($sql_products_obj->data as $data)
				{
					$structure["values"][]				= $data["id"];
					$structure["translations"][ $data["id"] ]	= $data["code_product"] ."--". $data["name_product"];
				}
			}


			$form->add_input($structure);


			// submit support
			$structure = NULL;
			$structure["fieldname"] 	= "submit";
			$structure["type"]		= "submit";
			$structure["defaultvalue"]	= "Add";
			$form->add_input($structure);



			// display the form
			print "<br><table class=\"table_highlight_info\" width=\"100%\"><tr><td>";
			print "<p>Add new items to invoice:</p>";

			print "<form method=\"". $form->method ."\" action=\"". $form->action ."\">";
			print "<table><tr>";

				print "<td>";
				$form->render_field("item");
				print "</td>";

				print "<td>";
				$form->render_field("submit");
				print "</td>";

			print "</tr></table>";
			
			$form->render_field("id");

			print "</form>";

			print "</td></tr></table>";
		
		} // end if items

		return 1;

	} // end of render_html

	
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
	var $locked;
	
//	var $obj_table_standard;
//	var $obj_table_taxes;



	function execute()
	{
		log_debug("invoice_list_payments", "Executing execute()");
		
		// TODO: fix up this class to comply with the standard coding style of the rest of the application
	
		$this->locked = sql_get_singlevalue("SELECT locked as value FROM account_". $this->type ." WHERE id='". $this->invoiceid ."'");

		
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
		$item_list->sql_obj->prepare_sql_addwhere("invoicetype='". $this->type ."'");
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



			if (user_permissions_get("accounts_". $this->type ."_write") && !$this->locked)
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

		if (!$this->locked)
		{
			print "<p><b><a href=\"index.php?page=". $this->page_view ."&id=". $this->invoiceid ."&type=payment\">Add Payment</a></b></p>";
		}


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
	var $productid;		// ID of the product for seeding the form

	var $mode;
	
	var $obj_form;
	

	function execute()
	{
		log_debug("invoice_form_item", "Executing execute()");

		// TODO: fix up this class to comply with the standard coding style of the rest of the application
	
		// do nothing
		return 1;
	}


	function render_html()
	{
		log_debug("invoice_form_item", "Executing render_html()");


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
				if ($this->itemid)
				{
					$form->sql_query = "SELECT amount, description, chartid FROM account_items WHERE id='". $this->itemid ."'";
				}



				/*
					List all the taxes, so that the user can select the tax(es) that apply for the transaction.
				*/

				$sql_tax_obj		= New sql_query;
				$sql_tax_obj->string	= "SELECT id, name_tax, description FROM account_taxes ORDER BY name_tax";
				$sql_tax_obj->execute();

				if ($sql_tax_obj->num_rows())
				{
					// user note
					$structure = NULL;
					$structure["fieldname"] 		= "tax_message";
					$structure["type"]			= "message";
					$structure["defaultvalue"]		= "<p>Check all taxes that apply to this transaction below. If you want more advanced tax control (eg: fixed amounts of tax) then define a product and add it to the invoice.</p>";
					$form->add_input($structure);
				
					$form->subforms[$this->type ."_invoice_item_tax"][] = "tax_message";


					// fetch customer/vendor tax defaults
					if ($this->type == "ap")
					{
						$vendorid	= sql_get_singlevalue("SELECT vendorid as value FROM account_ap WHERE id='". $this->invoiceid ."'");
						$defaulttax	= sql_get_singlevalue("SELECT tax_default as value FROM vendors WHERE id='". $vendorid."'");
					}
					else
					{
						$customerid	= sql_get_singlevalue("SELECT customerid as value FROM account_ar WHERE id='". $this->invoiceid ."'");
						$defaulttax	= sql_get_singlevalue("SELECT tax_default as value FROM customers WHERE id='". $customerid ."'");
					}



					// run through all the taxes
					$sql_tax_obj->fetch_array();

					foreach ($sql_tax_obj->data as $data_tax)
					{
						// define tax checkbox
						$structure = NULL;
						$structure["fieldname"] 		= "tax_". $data_tax["id"];
						$structure["type"]			= "checkbox";
						$structure["options"]["label"]		= $data_tax["name_tax"] ." -- ". $data_tax["description"];
						$structure["options"]["no_fieldname"]	= "enable";

						if ($this->itemid)
						{
							// see if this tax is currently inuse for the item
							$sql_obj		= New sql_query;
							$sql_obj->string	= "SELECT id FROM account_items_options WHERE itemid='". $this->itemid ."' AND option_name='TAX_CHECKED' AND option_value='". $data_tax["id"] ."'";
							$sql_obj->execute();

							if ($sql_obj->num_rows())
							{
								$structure["defaultvalue"] = "on";
							}
						}
						else
						{
							// is this tax a customer/vendor default? If so, it should be checked automatically.
							if ($data_tax["id"] == $defaulttax)
							{
								$structure["defaultvalue"] = "on";
							}

						}

						// add to form
						$form->add_input($structure);
						$form->subforms[$this->type ."_invoice_item_tax"][] = "tax_". $data_tax["id"];
					}
				}

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

				// fetch data
				//
				// if the item is new, use the this->item field to fetch the default product details, otherwise
				// fetch the details for the existing item
				//
				if ($this->itemid)
				{
					$form->sql_query = "SELECT price, description, customid as productid, quantity, units FROM account_items WHERE id='". $this->itemid ."'";
				}
				else
				{
					if ($this->type_invoice == "ar")
					{
						$form->sql_query = "SELECT id as productid, price_sale as price, units, details as description FROM products WHERE id='". $this->productid ."'";
					}
					else
					{
						$form->sql_query = "SELECT id as productid, price_cost as price, units, details as description FROM products WHERE id='". $this->productid ."'";
					}

					$form->structure["quantity"]["defaultvalue"] = 1;
				}


			
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
					$customerid = sql_get_singlevalue("SELECT customerid as value FROM account_". $this->type ." WHERE id='". $this->invoiceid ."' LIMIT 1");
					
					// list of avaliable time groups
					$structure = form_helper_prepare_dropdownfromdb("timegroupid", "SELECT time_groups.id, projects.name_project as label, time_groups.name_group as label1 FROM time_groups LEFT JOIN projects ON projects.id = time_groups.projectid WHERE customerid='$customerid' AND (invoiceitemid='0' OR invoiceitemid='". $this->itemid ."') ORDER BY name_group");
					$structure["options"]["width"]		= "400";
					$structure["options"]["autoselect"]	= "yes";
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


		if ($this->item_type == "time" && count($form->structure["timegroupid"]["values"]) == 0)
		{
			$form->subforms["submit"]			= array();
			$form->render_form();
			format_msgbox("important", "<p>There are currently no unprocessed time groups belonging to this customer - you must add time to a timegroup before you can create a time item.</p>");
			
		}
		else
		{
			$form->subforms["submit"]			= array("submit");
			$form->render_form();
		}
		


		return 1;
	}
} // end of invoice_form_item




/*
	FUNCTIONS
*/




/*
	invoice_form_items_add_process($type, $returnpage_error, $returnpage_success)

	Wrapper page - this page takes data in from the main invoice items page when creating
	new items, extracts the details and then redirects the user to the item creation page.

	Eg:
	invoice-items.php -> invoice-items-add-process.php -> invoice-items-edit.php

	Values
	type			"ar" or "ap" invoice
	returnpage_error	Page to return to in event of errors or updates
	returnpage_success	Page to return to if successful.
*/
function invoice_form_items_add_process($type,  $returnpage_error, $returnpage_success)
{
	log_debug("inc_invoices_items", "Executing invoice_form_items_add_process($type, $returnpage_error, $returnpage_success)");

	
	/*
		Import POST data
	*/
	
	$item		= security_form_input_predefined("any", "item", 1, "You must select the type of item to add to the invoice");
	$invoiceid	= security_form_input_predefined("any", "id", 1, "You must select an invoice before accessing this page");


	/*
		Process item value
	*/
	if ($item)
	{

		if ($item == "standard")
		{
			$item_type = "standard";
		}
		elseif ($item == "time")
		{
			$item_type = "time";
		}
		else
		{
			// must be a product - check that the product exists
			$sql_product_obj		= New sql_query;
			$sql_product_obj->string	= "SELECT id FROM products WHERE id='". $item ."'";
			$sql_product_obj->execute();

			if (!$sql_product_obj->num_rows())
			{
				log_write("error", "invoice_form_item", "The requested item does not exist");
			}

			$item_type	= "product";
			$productid	= $item;
		}
	}



	/*
		Error Handling
	*/

	if ($_SESSION["error"]["message"])
	{
		header("Location: ../../index.php?page=$returnpage_error&id=$invoiceid");
		exit(0);
	}



	/*
		Success
	*/

	header("Location: ../../index.php?page=$returnpage_success&id=$invoiceid&type=$item_type&productid=$productid");
	exit(0);




} // end of invoice_form_items_add_process



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
			$data["amount"]		= security_form_input_predefined("money", "amount", 0, "");
			$data["chartid"]	= security_form_input_predefined("int", "chartid", 1, "");
			$data["description"]	= security_form_input_predefined("any", "description", 0, "");

			// fetch information from all tax checkboxes from form
			$sql_tax_obj		= New sql_query;
			$sql_tax_obj->string	= "SELECT id FROM account_taxes";
			$sql_tax_obj->execute();

			if ($sql_tax_obj->num_rows())
			{
				$sql_tax_obj->fetch_array();

				foreach ($sql_tax_obj->data as $data_tax)
				{
					$data["tax_". $data_tax["id"] ]	= security_form_input_predefined("any", "tax_". $data_tax["id"], 0, "");
				}

			} // end of loop through taxes
			
		break;


		case "product":
			/*
				PRODUCT ITEMS
			*/
			
			// fetch information from form
			$data["price"]		= security_form_input_predefined("money", "price", 0, "");
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
			$data["price"]		= security_form_input_predefined("money", "price", 0, "");
			$data["customid"]	= security_form_input_predefined("int", "productid", 1, "");
			$data["timegroupid"]	= security_form_input_predefined("int", "timegroupid", 1, "");
			$data["description"]	= security_form_input_predefined("any", "description", 0, "");
			$data["units"]		= "hours";
			
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

		$item->action_update_tax();
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
			Update invoice summary
		*/
	
		$item->action_update_total();



		/*
			Update ledger

			(No need to do this for quotes, since they do not impact the ledger)
		*/
		
		if ($item->type_invoice != "quotes")
		{
			$item->action_update_ledger();
		}


		// success
		$_SESSION["notification"]["message"][] = "Item deleted successfully";
		header("Location: ../../index.php?page=$returnpage_success&id=". $item->id_invoice ."");
		exit(0);
	}
	
} // end of invoice_form_items_delete_process




/*
	invoice_form_tax_override_process($type, $returnpage_error, $returnpage_success)

	This function overwrites the selected tax with the specified value - this is only used for AP
	transactions where the taxes sometimes need to be manual adjusted due to bad vendor rounding.

	Values
	$returnpage		Page to return to.
*/
function invoice_form_tax_override_process($returnpage)
{
	log_debug("inc_invoices_items", "Executing invoice_form_tax_override_process($returnpage)");



	/*
		Start invoice_items object
	*/
	$item			= New invoice_items;
	
	$item->id_invoice	= security_form_input_predefined("int", "invoiceid", 1, "");
	$item->id_item		= security_form_input_predefined("int", "itemid", 1, "");

	$item->type_invoice	= "ap"; // only AP invoices can have taxes overridden
	
	
	/*
		Fetch all form data
	*/
	
	$data["amount"]		= security_form_input_predefined("money", "amount", 0, "");


	
	//// ERROR CHECKING ///////////////////////


	/*
		Verify invoice/form data
	*/
	if ($item->verify_invoice())
	{
		if (!$item->verify_item())
		{
			$_SESSION["error"]["message"][] = "The provided tax does not exist.";
		}
	}
	else
	{
		$_SESSION["error"]["message"][] = "The provided invoice does not exist.";
	}



	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"]["ap_invoice_". $mode ."_override"] = "failed";
		header("Location: ../../index.php?page=$returnpage&id=". $item->id_invoice);
		exit(0);
	}
	else
	{
		/*
			Depending on the amount, we either delete the tax item (if the amount is 0) or we
			adjust the tax item.
		*/


		if ($data["amount"] == 0)
		{
			// delete item
			$item->action_delete();
		
			// done
			$_SESSION["notification"]["message"] = array("Deleted unwanted tax.");
		}
		else
		{
			// load & update the tax item
			$item->load_data();

			$item->data["amount"] = $data["amount"];

			$item->action_update();

			// done
			$_SESSION["notification"]["message"] = array("Updated tax value with custom input");
		}


		// update invoice summary
		$item->action_update_total();

		// update ledger
		$item->action_update_ledger();


		// done
		header("Location: ../../index.php?page=$returnpage&id=". $item->id_invoice);
		exit(0);
	
	}


} // end of invoice_form_tax_override_process





?>
