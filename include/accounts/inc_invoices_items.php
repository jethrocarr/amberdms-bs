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
		$this->obj_table_standard->add_column("standard", "discount", "NONE");
		$this->obj_table_standard->add_column("money", "amount", "");

		// defaults
		$this->obj_table_standard->columns		= array("item_info", "description", "qnty", "units", "price", "discount", "amount");

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
		$this->obj_table_standard->sql_obj->prepare_sql_addorderby("chartid");
		$this->obj_table_standard->sql_obj->prepare_sql_addorderby("description");

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
							Fetch product code
						*/
						$sql_obj		= New sql_query;
						$sql_obj->string	= "SELECT code_product, name_product FROM products WHERE id='". $this->obj_table_standard->data[$i]["customid"] ."' LIMIT 1";
						$sql_obj->execute();

						$sql_obj->fetch_array();
						$this->obj_table_standard->data[$i]["item_info"] = $sql_obj->data[0]["code_product"] ." -- ".$sql_obj->data[0]["name_product"];



						/*
							Fetch discount (if any)
						*/

						$discount = sql_get_singlevalue("SELECT option_value as value FROM account_items_options WHERE itemid='". $this->obj_table_standard->data[$i]["id"] ."' AND option_name='DISCOUNT'");

						if ($discount)
						{
							$this->obj_table_standard->data[$i]["discount"] = $discount ."%";
						}

					break;


					case "time":
						/*
							Fetch time group ID
						*/

						$groupid = sql_get_singlevalue("SELECT option_value as value FROM account_items_options WHERE itemid='". $this->obj_table_standard->data[$i]["id"] ."' AND option_name='TIMEGROUPID'");

						$this->obj_table_standard->data[$i]["item_info"] = sql_get_singlevalue("SELECT CONCAT_WS(' -- ', projects.code_project, time_groups.name_group) as value FROM time_groups LEFT JOIN projects ON projects.id = time_groups.projectid WHERE time_groups.id='$groupid' LIMIT 1");


						/*
							Fetch discount (if any)
						*/

						$discount = sql_get_singlevalue("SELECT option_value as value FROM account_items_options WHERE itemid='". $this->obj_table_standard->data[$i]["id"] ."' AND option_name='DISCOUNT'");

						if ($discount)
						{
							$this->obj_table_standard->data[$i]["discount"] = $discount ."%";
						}


					break;


					case "service":
					case "service_usage":

						/*
							Fetch service group
						*/

						$sql_obj		= New sql_query;
					
						if ($this->obj_table_standard->data[$i]["type"] == "service_usage")
						{
							$sql_obj->string	= "SELECT service_groups.group_name as group_name FROM services LEFT JOIN service_groups ON service_groups.id = id_service_group_usage WHERE services.id='". $this->obj_table_standard->data[$i]["customid"] ."' LIMIT 1";
						}
						else
						{
							$sql_obj->string	= "SELECT service_groups.group_name as group_name FROM services LEFT JOIN service_groups ON service_groups.id = id_service_group WHERE services.id='". $this->obj_table_standard->data[$i]["customid"] ."' LIMIT 1";
						}

						$sql_obj->execute();
						$sql_obj->fetch_array();
						
						$this->obj_table_standard->data[$i]["item_info"] = $sql_obj->data[0]["group_name"];


						/*
							Fetch discount (if any)
						*/

						$discount = sql_get_singlevalue("SELECT option_value as value FROM account_items_options WHERE itemid='". $this->obj_table_standard->data[$i]["id"] ."' AND option_name='DISCOUNT'");

						if ($discount)
						{
							$this->obj_table_standard->data[$i]["discount"] = $discount ."%";
						}

						
						unset($sql_obj);
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

					case "credit":
						/*
							Adjust Fields
						*/

						$this->obj_table_standard->data[$i]["item_info"]	= "CREDIT";
						$this->obj_table_standard->data[$i]["qnty"]		= "";
					break;
				}
			}


			$authtype = "none";

			switch ($this->type)
			{
				case "ar":
				case "ar_credit":
					$authtype = "ar";
				break;

				case "ap":
				case "ap_credit":
					$authtype = "ap";
				break;

				case "quote":
				case "quotes":
					$authtype = "quotes";
				break;
			}

			if (user_permissions_get("accounts_". $authtype ."_write") && !$this->locked)
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
			if ($this->type == "ar" || $this->type == "ap" || $this->type == "quotes")
			{
				// regular invoice item
				print "<table class=\"table_highlight_info\" width=\"100%\">";
				print "<tr><td width=\"100%\">";

					print "<p>This invoice has no items and is currently empty.</p>";

					print "<div class=\"invoice_button_area\">";
						print "<a href=\"index.php?page=". $this->page_view ."&id=".$this->invoiceid."&type=standard\">
							<img src=\"images/icons/plus.gif\" height=\"15\" width=\"15\"/>&nbsp;&nbsp;<strong>Basic Transaction</strong></a>
							<br />";

						if ($this->type == "ar")
						{
							print "<a href=\"index.php?page=". $this->page_view ."&id=".$this->invoiceid."&type=time\">
								<img src=\"images/icons/plus.gif\" height=\"15\" width=\"15\"/>&nbsp;&nbsp;<strong>Time Item</strong></a><br />";
						}

						print "<a href=\"index.php?page=". $this->page_view ."&id=".$this->invoiceid."&type=product\">
							<img src=\"images/icons/plus.gif\" height=\"15\" width=\"15\"/>&nbsp;&nbsp;<strong>Product</strong></a>";					
					print "</div>";

				print "</td></tr>";
				print "</table>";
			}
			elseif ($this->type == "ar_credit" || $this->type == "ap_credit")
			{
				// credit

				// nothing todo
			}
		}
		else
		{

			print "<table width=\"100%\" class=\"table_content\" style=\"border-bottom: 0px;\" cellspacing=\"0\">";


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
			print "<td class=\"header\">&nbsp;</td>";

			print "</tr>";


			// display invoice items
			for ($i=0; $i < $this->obj_table_standard->data_num_rows; $i++)
			{
				print "<tr>";

				// content for columns
				foreach ($this->obj_table_standard->columns as $columns)
				{
					$content = $this->obj_table_standard->data_render[$i][$columns];

					if (!$content)
					{
						$content = "&nbsp;";
					}

					// display
					print "<td valign=\"top\">$content</td>";
				}


				// links
				if (!empty($this->obj_table_standard->links))
				{
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

						if (isset($this->obj_table_standard->links[$link]["options"]["full_link"]) && $this->obj_table_standard->links[$link]["options"]["full_link"] == "yes")
						{
							print "<a class=\"button_small\" href=\"". $this->obj_table_standard->links[$link]["page"] ."?libfiller=n";
						}
						else
						{
							print "<a class=\"button_small\" href=\"index.php?page=". $this->obj_table_standard->links[$link]["page"] ."";
						}

						// add each option
						foreach (array_keys($this->obj_table_standard->links[$link]["options"]) as $getfield)
						{
							/*
								There are two methods for setting the value of the variable:
								1. The value has been passed.
								2. The name of a column to take the value from has been passed
							*/
							if (isset($this->obj_table_standard->links[$link]["options"][$getfield]["value"]))
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
							print " ";
						}
					}

					print "</tr>";
				}
			}
			
			/*
			 * Add buttons
			 */

			//calculate number of rows buttons can cover
			$footer_rows = $this->obj_table_taxes->data_num_rows + 2;
			
			print "<tr>";
				
				print "<td class=\"blank\" colspan=\"4\" rowspan=\"$footer_rows\">";
				
				if (user_permissions_get("accounts_". $this->type ."_write") && !$this->locked)
				{
					print "<p><strong>Add new items to invoice:<strong></p>";
					print "<div class=\"invoice_button_area\">";
						print "<a href=\"index.php?page=". $this->page_view ."&id=".$this->invoiceid."&type=standard\">
							<img src=\"images/icons/plus.gif\" height=\"15\" width=\"15\"/>&nbsp;&nbsp;<strong>Basic Transaction</strong></a>
							<br />";

						if ($this->type == "ar")
						{
							print "<a href=\"index.php?page=". $this->page_view ."&id=".$this->invoiceid."&type=time\">
								<img src=\"images/icons/plus.gif\" height=\"15\" width=\"15\"/>&nbsp;&nbsp;<strong>Time Item</strong></a><br />";
						}

						print "<a href=\"index.php?page=". $this->page_view ."&id=".$this->invoiceid."&type=product\">
							<img src=\"images/icons/plus.gif\" height=\"15\" width=\"15\"/>&nbsp;&nbsp;<strong>Product</strong></a>";					
					print "</div>";
				}
				print "</td>";
			
			/*
				Subtotal

				Display total of all items without tax
			*/
//			print "<tr>";
//				print "<td class=\"blank\" colspan=\"4\"></td>";
				print "<td class=\"footer\" valign=\"top\" colspan=\"2\"><b>Subtotal:</b></td>";
				print "<td class=\"footer\" valign=\"top\"><b>". $this->obj_table_standard->data_render["total"]["amount"] ."</b></td>";
				print "<td class=\"footer\">&nbsp;</td>";
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
//				print "<td class=\"blank\" colspan=\"4\"></td>";

				// tax name
				print "<td valign=\"center\" colspan=\"2\">". $this->obj_table_taxes->data_render[$i]["name_tax"] ."</td>";


				if ($this->type == "ap" && user_permissions_get("accounts_". $this->type ."_write") && !$this->locked)
				{
					// amount
					print "<td valign=\"top\">";
					
					print "<form method=\"post\" action=\"accounts/ap/invoice-items-tax-override-process.php\" class=\"form_standard\">";

					print "<input type=\"hidden\" name=\"invoiceid\" value=\"". $this->invoiceid ."\">";
					print "<input type=\"hidden\" name=\"itemid\" value=\"". $this->obj_table_taxes->data[$i]["id"] ."\">";


					$position = sql_get_singlevalue("SELECT value FROM config WHERE name='CURRENCY_DEFAULT_SYMBOL_POSITION'");
	
					if ($position == "after")
					{
						print "<input name=\"amount\" value=\"". $this->obj_table_taxes->data[$i]["amount"] ."\" style=\"width: 100px; font-size: 10px;\"> ";
						print sql_get_singlevalue("SELECT value FROM config WHERE name='CURRENCY_DEFAULT_SYMBOL'");
					}
					else
					{
						print sql_get_singlevalue("SELECT value FROM config WHERE name='CURRENCY_DEFAULT_SYMBOL'");
						print "<input name=\"amount\" value=\"". $this->obj_table_taxes->data[$i]["amount"] ."\" style=\"width: 100px; font-size: 10px;\">";
					}


					
					print "</td>";
					

					// links
					print "<td align=\"right\">";

					// use specifc inline CSS here to override default large button sizes
					print "<input type=\"submit\" value=\"adjust\" style=\"
						font-size:		8px !important;
						line-height:		10px;
						padding: 		2px 10px;
						cursor:			pointer;

						color:			#ffffff;
						font-style:		normal;
						font-weight:		normal;

						border-width:		0px;
						border-style:		solid;

						-moz-border-radius:	5px;
						-khtml-border-radius:	5px;
						-webkit-border-radius:	5px;
						border-radius:		5px;
					\">";

					print "</form>";

					print "</td>";			
				}
				else
				{
					// amount
					print "<td valign=\"top\">". $this->obj_table_taxes->data_render[$i]["amount"] ."</td>";

					// links
					print "<td>&nbsp;</td>";
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
//				print "<td class=\"blank\" colspan=\"4\"></td>";
				print "<td class=\"footer\" valign=\"top\" colspan=\"2\"><b>Invoice Total:</b></td>";
				print "<td class=\"footer\" valign=\"top\"><b>$invoice_total</b></td>";
				print "<td class=\"footer\">&nbsp;</td>";
			print "</tr>";


			print "</table>";



		} // end if items exist




//		if (user_permissions_get("accounts_". $this->type ."_write") && !$this->locked)
//		{
//			/*
//				Display the new item form
//			*/
//
//
//			$form = New form_input;
//			$form->formname		= $this->type ."_invoice_". $this->mode;
//			$form->language		= $_SESSION["user"]["lang"];
//	
//			$form->action		= str_replace("edit", "add-process", $this->page_view);
//			$form->method		= "POST";
//
//			// basic details
//			$structure = NULL;
//			$structure["fieldname"] 	= "id";
//			$structure["type"]		= "hidden";
//			$structure["defaultvalue"]	= $this->invoiceid;
//			$form->add_input($structure);
//
//
//			// item dropdown
//			$structure = NULL;
//			$structure["fieldname"] 	= "item";
//			$structure["type"]		= "dropdown";
//			$structure["options"]["width"]	= "600";
//
//			$structure["values"][]			= "standard";
//			$structure["translations"]["standard"]	= "Basic Transaction";
//			
//			if ($this->type == "ar")
//			{
//				$structure["values"][]			= "time";
//				$structure["translations"]["time"]	= "Time Item";
//			}
//
//			// fetch all the products for the drop down
//			$sql_products_obj		= New sql_query;
//			$sql_products_obj->string	= "SELECT id, code_product, name_product FROM products ORDER BY name_product";
//			$sql_products_obj->execute();
//			
//			if ($sql_products_obj->num_rows())
//			{
//				$sql_products_obj->fetch_array();
//
//				foreach ($sql_products_obj->data as $data)
//				{
//					$structure["values"][]				= $data["id"];
//					$structure["translations"][ $data["id"] ]	= $data["code_product"] ."--". $data["name_product"];
//				}
//			}
//
//
//			$form->add_input($structure);
//
//
//			// submit support
//			$structure = NULL;
//			$structure["fieldname"] 	= "submit";
//			$structure["type"]		= "submit";
//			$structure["defaultvalue"]	= "Add";
//			$form->add_input($structure);
//
//
//
//			// display the form
//			print "<br><table class=\"table_highlight_info\" width=\"100%\"><tr><td>";
//			print "<p>Add new items to invoice:</p>";
//
//			print "<form method=\"". $form->method ."\" action=\"". $form->action ."\">";
//			print "<table><tr>";
//
//				print "<td>";
//				$form->render_field("item");
//				print "</td>";
//
//				print "<td>";
//				$form->render_field("submit");
//				print "</td>";
//
//			print "</tr></table>";
//			
//			$form->render_field("id");
//
//			print "</form>";
//
//			print "</td></tr></table>";
//		
//		} // end if items

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

					if ($data["option_name"] == "CREDIT")
						$item_list->data[$i]["account"] = $data["option_value"];
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
			print "<p><a class=\"button\" href=\"index.php?page=". $this->page_view ."&id=". $this->invoiceid ."&type=payment\">Add Payment</a></p>";
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
		$this->obj_form = New form_input;
		$this->obj_form->formname		= $this->type ."_invoice_". $this->mode;
		$this->obj_form->language		= $_SESSION["user"]["lang"];

		$this->obj_form->action		= $this->processpage;
		$this->obj_form->method		= "POST";



		/*
			Fetch customer ID
		*/

		if ($this->type == "ap")
		{
			// fetch the vendorid for this invoice
			$orgid = sql_get_singlevalue("SELECT vendorid as value FROM account_". $this->type ." WHERE id='". $this->invoiceid ."' LIMIT 1");
		}
		else
		{
			// fetch the customer ID for this invoice
			$orgid = sql_get_singlevalue("SELECT customerid as value FROM account_". $this->type ." WHERE id='". $this->invoiceid ."' LIMIT 1");
		}
					

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
				$structure["type"]		= "money";
				$this->obj_form->add_input($structure);

				$structure = NULL;

				if ($this->type == "ap")
				{
					$structure = charts_form_prepare_acccountdropdown("chartid", "ap_expense");
				}
				else
				{
					$structure = charts_form_prepare_acccountdropdown("chartid", "ar_income");
				}
			
				$structure["options"]["search_filter"]	= "enabled";
				$structure["options"]["width"]		= "500";

				$this->obj_form->add_input($structure);
					
				$structure = NULL;
				$structure["fieldname"] 	= "description";
				$structure["type"]		= "textarea";
				$structure["options"]["height"]	= "50";
				$structure["options"]["width"]	= 500;
				$this->obj_form->add_input($structure);

		
				// define form layout
				$this->obj_form->subforms[$this->type ."_invoice_item"] = array("amount", "chartid", "description");

				// SQL query
				if ($this->itemid)
				{
					$this->obj_form->sql_query = "SELECT amount, description, chartid FROM account_items WHERE id='". $this->itemid ."'";
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
					$this->obj_form->add_input($structure);
				
					$this->obj_form->subforms[$this->type ."_invoice_item_tax"][] = "tax_message";


					// fetch customer/vendor tax defaults
					if ($this->type == "ap")
					{
						$defaulttax	= sql_get_singlevalue("SELECT tax_default as value FROM vendors WHERE id='". $orgid."'");
					}
					else
					{
						$defaulttax	= sql_get_singlevalue("SELECT tax_default as value FROM customers WHERE id='". $orgid ."'");
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
						$this->obj_form->add_input($structure);
						$this->obj_form->subforms[$this->type ."_invoice_item_tax"][] = "tax_". $data_tax["id"];
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
				$structure["type"]		= "money";
				$this->obj_form->add_input($structure);

				// quantity
				$structure = NULL;
				$structure["fieldname"] 	= "quantity";
				$structure["type"]		= "input";
				$structure["options"]["width"]	= 50;
				$this->obj_form->add_input($structure);


				// units
				$structure = NULL;
				$structure["fieldname"] 		= "units";
				$structure["type"]			= "input";
				$structure["options"]["width"]		= 50;
				$structure["options"]["max_length"]	= 10;
				$this->obj_form->add_input($structure);



				// product id
				$sql_struct_obj	= New sql_query;
				$sql_struct_obj->prepare_sql_settable("products");
				$sql_struct_obj->prepare_sql_addfield("id", "products.id");
				$sql_struct_obj->prepare_sql_addfield("label", "products.code_product");
				$sql_struct_obj->prepare_sql_addfield("label1", "products.name_product");
				$sql_struct_obj->prepare_sql_addorderby("code_product");
				$sql_struct_obj->prepare_sql_addwhere("id = 'CURRENTID' OR date_end = '0000-00-00'");
				
				$structure = form_helper_prepare_dropdownfromobj("productid", $sql_struct_obj);
				$structure["options"]["search_filter"]	= "enabled";
				$structure["options"]["width"]		= "600";
				$this->obj_form->add_input($structure);


				// description
				$structure = NULL;
				$structure["fieldname"] 		= "description";
				$structure["type"]			= "textarea";
				$structure["options"]["height"]		= "50";
				$structure["options"]["width"]		= 500;
				$this->obj_form->add_input($structure);

				// discount
				$structure = NULL;
				$structure["fieldname"] 		= "discount";
				$structure["type"]			= "input";
				$structure["options"]["width"]		= 50;
				$structure["options"]["label"]		= " %";
				$structure["options"]["max_length"]	= "2";
				$this->obj_form->add_input($structure);


				// define form layout
				$this->obj_form->subforms[$this->type ."_invoice_item"]		= array("productid", "price", "quantity", "units", "description", "discount");


				// fetch data
				//
				// if the item is new, use the this->item field to fetch the default product details, otherwise
				// fetch the details for the existing item
				//
				if ($this->itemid)
				{
					$this->obj_form->sql_query = "SELECT price, description, customid as productid, quantity, units FROM account_items WHERE id='". $this->itemid ."'";
				}
				else
				{
					if ($this->type == "ar" || $this->type == "quotes")
					{
						$this->obj_form->sql_query = "SELECT id as productid, price_sale as price, units, details as description FROM products WHERE id='". $this->productid ."'";
					}
					else
					{
						$this->obj_form->sql_query = "SELECT id as productid, price_cost as price, units, details as description FROM products WHERE id='". $this->productid ."'";
					}

					$this->obj_form->structure["quantity"]["defaultvalue"] = 1;
				}



				// fetch discount (if any) from customer/vendor
				if ($this->type == "ap")
				{
					$discount_org = sql_get_singlevalue("SELECT discount as value FROM vendors WHERE id='". $orgid ."' LIMIT 1");
				}
				else
				{
					$discount_org = sql_get_singlevalue("SELECT discount as value FROM customers WHERE id='". $orgid ."' LIMIT 1");
				}


				// fetch discount (if any) from product
				$discount_product = sql_get_singlevalue("SELECT discount as value FROM products WHERE id='". $this->productid ."' LIMIT 1");


				// choose the largest discount
				if ($discount_org || $discount_product)
				{
					if ($discount_org > $discount_product)
					{
						$this->obj_form->structure["discount"]["defaultvalue"] = $discount_org;
					}
					else
					{
						$this->obj_form->structure["discount"]["defaultvalue"] = $discount_product;
					}
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
					// list of avaliable time groups
					$structure = form_helper_prepare_dropdownfromdb("timegroupid", "SELECT time_groups.id, projects.name_project as label, time_groups.name_group as label1 FROM time_groups LEFT JOIN projects ON projects.id = time_groups.projectid WHERE customerid='$orgid' AND (invoiceitemid='0' OR invoiceitemid='". $this->itemid ."') ORDER BY name_group");
					$structure["options"]["width"]		= "600";
					$structure["options"]["autoselect"]	= "yes";
					$structure["options"]["search_filter"]	= "enabled";
					$this->obj_form->add_input($structure);

				
					// price field
					// TODO: this should auto-update from the product price
					$structure = NULL;
					$structure["fieldname"] 	= "price";
					$structure["type"]		= "money";
					$this->obj_form->add_input($structure);

					// product id
					$sql_struct_obj	= New sql_query;
					$sql_struct_obj->prepare_sql_settable("products");
					$sql_struct_obj->prepare_sql_addfield("id", "products.id");
					$sql_struct_obj->prepare_sql_addfield("label", "products.code_product");
					$sql_struct_obj->prepare_sql_addfield("label1", "products.name_product");
					$sql_struct_obj->prepare_sql_addorderby("code_product");
					$sql_struct_obj->prepare_sql_addwhere("id = 'CURRENTID' OR date_end = '0000-00-00'");
					
					$structure = form_helper_prepare_dropdownfromobj("productid", $sql_struct_obj);
					$structure["options"]["width"]		= "600";
					$structure["options"]["search_filter"]	= "enabled";
					$this->obj_form->add_input($structure);


					// description
					$structure = NULL;
					$structure["fieldname"] 	= "description";
					$structure["type"]		= "textarea";
					$structure["options"]["height"]	= "50";
					$structure["options"]["width"]	= 500;
					$this->obj_form->add_input($structure);

					// discount
					$structure = NULL;
					$structure["fieldname"] 		= "discount";
					$structure["type"]			= "input";
					$structure["options"]["width"]		= 50;
					$structure["options"]["label"]		= " %";
					$structure["options"]["max_length"]	= "2";
					$this->obj_form->add_input($structure);


					// define form layout
					$this->obj_form->subforms[$this->type ."_invoice_item"]		= array("timegroupid", "productid", "price", "description", "discount");

					// SQL query
					$this->obj_form->sql_query = "SELECT price, description, customid as productid, quantity, units FROM account_items WHERE id='". $this->itemid ."'";
				


					// fetch discount (if any) from customer/vendor
					if ($this->type == "ap")
					{
						$discount_org = sql_get_singlevalue("SELECT discount as value FROM vendors WHERE id='". $orgid ."' LIMIT 1");
					}
					else
					{
						$discount_org = sql_get_singlevalue("SELECT discount as value FROM customers WHERE id='". $orgid ."' LIMIT 1");
					}


					// TODO: need to look at improving time <-> product relationships
					// fetch discount (if any) from product
					// $discount_product = sql_get_singlevalue("SELECT discount FROM products WHERE id='". $this->productid ."' LIMIT 1");


					// choose the largest discount
					if ($discount_org || $discount_product)
					{
						if ($discount_org > $discount_product)
						{
							$this->obj_form->structure["discount"]["defaultvalue"] = $discount_org;
						}
						else
						{
							$this->obj_form->structure["discount"]["defaultvalue"] = $discount_product;
						}
					}
				}
				else
				{
					log_write("error", "inc_invoice_items", "Time items are only avaliable for AR invoices, please report the steps to access this page as an application bug.");
				}

			break;
			

			/*
				SERVICE (AR only)

				Service items can only be added via the automated invoicing capabilities, however we do
				allow users to adjust the description, price and discount once an item has been created.
			*/
			case "service":
			case "service_usage":

				if ($this->type == "ar")
				{
					// service group name
					$structure = NULL;
					$structure["fieldname"] 		= "id_service";
					$structure["type"]			= "text";
					$this->obj_form->add_input($structure);
			
					// price field
					$structure = NULL;
					$structure["fieldname"] 		= "price";
					$structure["type"]			= "money";
					$this->obj_form->add_input($structure);

					// quantity
					$structure = NULL;
					$structure["fieldname"] 		= "quantity";
					$structure["type"]			= "input";
					$structure["options"]["width"]		= 50;
					$this->obj_form->add_input($structure);

					// description
					$structure = NULL;
					$structure["fieldname"] 		= "description";
					$structure["type"]			= "textarea";
					$structure["options"]["height"]		= "50";
					$structure["options"]["width"]		= 500;
					$this->obj_form->add_input($structure);

					// discount
					$structure = NULL;
					$structure["fieldname"] 		= "discount";
					$structure["type"]			= "input";
					$structure["options"]["width"]		= 50;
					$structure["options"]["label"]		= " %";
					$structure["options"]["max_length"]	= "2";
					$this->obj_form->add_input($structure);


					// define form layout
					$this->obj_form->subforms[$this->type ."_invoice_item"]		= array("id_service", "price", "quantity", "description", "discount");

					// SQL query
					$this->obj_form->sql_query = "SELECT price, description, customid as id_service, quantity, units FROM account_items WHERE id='". $this->itemid ."'";
			

/*
					// fetch discount (if any) from customer/vendor
					$discount_org = sql_get_singlevalue("SELECT discount as value FROM customers WHERE id='". $orgid ."' LIMIT 1");

					// fetch discount (if any) from 
					// $discount_product = sql_get_singlevalue("SELECT discount FROM products WHERE id='". $this->productid ."' LIMIT 1");


					// choose the largest discount
					if ($discount_org || $discount_product)
					{
						if ($discount_org > $discount_product)
						{
							$this->obj_form->structure["discount"]["defaultvalue"] = $discount_org;
						}
						else
						{
							$this->obj_form->structure["discount"]["defaultvalue"] = $discount_product;
						}
					}
*/

				
				}
				else
				{
					log_write("error", "inc_invoice_items", "Service items are only avaliable for AR invoices.");
				}

			break;
	
			case "payment":
				/*
					PAYMENT

					Payments against invoices are also items which credit/subtract funds from a selected account. Note that payments
					typically come out of an asset account, but if the customer/vendor has credit and the payment is made from
					that credit, the account will be the AR/AP account
				*/
		



				$structure = NULL;
				$structure["fieldname"] 	= "date_trans";
				$structure["type"]		= "date";
				$structure["defaultvalue"]	= date("Y-m-d");
				$this->obj_form->add_input($structure);
				
				$structure = NULL;
				$structure["fieldname"] 	= "amount";
				$structure["type"]		= "money";
				$this->obj_form->add_input($structure);

				$structure = NULL;
				if ($this->type == "ap")
				{
					$structure = charts_form_prepare_acccountdropdown("chartid", "ap_payment");
				}
				else
				{
					$structure = charts_form_prepare_acccountdropdown("chartid", "ar_payment");
				}

				$structure["options"]["search_filter"]	= "enabled";

				$this->obj_form->add_input($structure);

				$structure = NULL;
				$structure["fieldname"] 	= "source";
				$structure["type"]		= "input";
				$this->obj_form->add_input($structure);
					
				$structure = NULL;
				$structure["fieldname"] 	= "description";
				$structure["type"]		= "textarea";
				$structure["options"]["height"]	= "50";
				$structure["options"]["width"]	= 500;
				$this->obj_form->add_input($structure);
				
		
				// define form layout
				$this->obj_form->subforms[$this->type ."_invoice_item"]		= array("date_trans", "amount", "chartid", "source", "description");

				// load data
				if ($this->itemid)
				{

					// credit details (if applicable)
					$credit = sql_get_singlevalue("SELECT option_value AS value FROM account_items_options WHERE itemid='". $this->itemid ."' AND option_name='CREDIT' LIMIT 1");

					// standard payment item data
					if ($credit)
					{
						$this->obj_form->sql_query = "SELECT amount as amount, description, 'credit' as chartid FROM account_items WHERE id='". $this->itemid ."'";
					}
					else
					{
						$this->obj_form->sql_query = "SELECT amount as amount, description, chartid FROM account_items WHERE id='". $this->itemid ."'";
					}
				}
				else
				{
					// set defaults
					$this->obj_form->structure["amount"]["defaultvalue"]	= sql_get_singlevalue("SELECT SUM(amount_total - amount_paid) as value FROM account_". $this->type ." WHERE id='". $this->invoiceid ."' LIMIT 1");

					/*
						Fetch credit information (if any)

						We handle credit information, by determining the maximum available credit and then
						overwriting values on the item's information including:
						- amount (equal to invoice or to max credit amout if less than invoice max)
						- account (set to AR/AP)
						- date (today's date)
					*/
					if ($this->type == "ap")
					{
						$credit		= sql_get_singlevalue("SELECT SUM(amount_total) as value FROM vendors_credits WHERE id_vendor='". $orgid."'");
					}
					else
					{
						$credit		= sql_get_singlevalue("SELECT SUM(amount_total) as value FROM customers_credits WHERE id_customer='". $orgid."'");
					}

					if ($credit > 0)
					{
						// customer/vendor has credit
						if ($credit > $this->obj_form->structure["amount"]["defaultvalue"])
						{
							// credit is more than the invoice amount, set to mac
							$credit = $this->obj_form->structure["amount"]["defaultvalue"];
						}

						// set default value
						$this->obj_form->structure["amount"]["defaultvalue"] = $credit;


						// set source
						$this->obj_form->structure["source"]["defaultvalue"]	= "CREDITED FUNDS";
					}
				}

				// overwrite account settings for credits

				if ($credit > 0 || $credit == "CREDIT")
				{
					if ($this->type == "ap")
					{
						$this->obj_form->structure["chartid"]["values"][] = "credit";
						$this->obj_form->structure["chartid"]["translations"]["credit"]	= "Vendor Credit";
					}
					else
					{
						$this->obj_form->structure["chartid"]["values"][] = "credit";
						$this->obj_form->structure["chartid"]["translations"]["credit"]	= "Customer Credit";
					}

					$this->obj_form->structure["chartid"]["defaultvalue"]	= "credit";
				}

			break;


			case "credit":
				/*
					Credit

					ar_credit or ap_credit only

					This item type only applies to credit notes and acts simular to a standard item but inherits pricing
					account and tax information from the original item. (inheritance done on item edit page).
				*/
				
				// basic details
				$structure = NULL;
				$structure["fieldname"] 		= "amount";
				$structure["type"]			= "money";
				$structure["options"]["prelabel"]	= "CREDIT ";
				$this->obj_form->add_input($structure);

				$structure = NULL;

				if ($this->type == "ap")
				{
					$structure = charts_form_prepare_acccountdropdown("chartid", "ap_expense");
				}
				else
				{
					$structure = charts_form_prepare_acccountdropdown("chartid", "ar_income");
				}
			
				$structure["options"]["search_filter"]	= "enabled";
				$structure["options"]["width"]		= "500";

				$this->obj_form->add_input($structure);
					
				$structure = NULL;
				$structure["fieldname"] 	= "description";
				$structure["type"]		= "textarea";
				$structure["options"]["height"]	= "50";
				$structure["options"]["width"]	= 500;
				$this->obj_form->add_input($structure);

		
				// define form layout
				$this->obj_form->subforms[$this->type ."_invoice_item"] = array("amount", "chartid", "description");

				// SQL query
				if ($this->itemid)
				{
					$this->obj_form->sql_query = "SELECT amount, description, chartid FROM account_items WHERE id='". $this->itemid ."'";
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
					$structure["defaultvalue"]		= "<p>Taxes have automatically been determed based on the options of the selected invoice item.</p>";
					$this->obj_form->add_input($structure);
				
					$this->obj_form->subforms[$this->type ."_invoice_item_tax"][] = "tax_message";


					// fetch customer/vendor tax defaults
					if ($this->type == "ap")
					{
						$defaulttax	= sql_get_singlevalue("SELECT tax_default as value FROM vendors WHERE id='". $orgid."'");
					}
					else
					{
						$defaulttax	= sql_get_singlevalue("SELECT tax_default as value FROM customers WHERE id='". $orgid ."'");
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

						// add to form
						$this->obj_form->add_input($structure);
						$this->obj_form->subforms[$this->type ."_invoice_item_tax"][] = "tax_". $data_tax["id"];
					}
				}


			break;

			default:
				log_write("error", "inc_invoice_items", "Unknown type passed to render form.");
			break;
		}



		// IDs
		$structure = NULL;
		$structure["fieldname"]		= "id_invoice";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->invoiceid;
		$this->obj_form->add_input($structure);	
		
		$structure = NULL;
		$structure["fieldname"]		= "id_item";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->itemid;
		$this->obj_form->add_input($structure);	
		
		$structure = NULL;
		$structure["fieldname"]		= "item_type";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->item_type;
		$this->obj_form->add_input($structure);	



		// submit
		$structure = NULL;
		$structure["fieldname"]		= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Save Changes";
		$this->obj_form->add_input($structure);


		// load data
		$this->obj_form->load_data();

		// custom loads for different item type
		if ($this->itemid)
		{
			switch ($this->item_type)
			{
				case "time":

					// fetch the time group ID
					$this->obj_form->structure["timegroupid"]["defaultvalue"]	= sql_get_singlevalue("SELECT option_value AS value FROM account_items_options WHERE itemid='". $this->itemid ."' AND option_name='TIMEGROUPID' LIMIT 1");

					// fetch discount (if any) from item
					$this->obj_form->structure["discount"]["defaultvalue"]	= sql_get_singlevalue("SELECT option_value as value FROM account_items_options WHERE itemid='". $this->itemid ."' AND option_name='DISCOUNT'");

				break;


				case "payment":

					// fetch payment date_trans and source fields.
					$this->obj_form->structure["date_trans"]["defaultvalue"]	= sql_get_singlevalue("SELECT option_value AS value FROM account_items_options WHERE itemid='". $this->itemid ."' AND option_name='DATE_TRANS' LIMIT 1");
					$this->obj_form->structure["source"]["defaultvalue"]	= sql_get_singlevalue("SELECT option_value AS value FROM account_items_options WHERE itemid='". $this->itemid ."' AND option_name='SOURCE' LIMIT 1");
				
				break;

				case "product":

					// fetch discount (if any) from item
					$this->obj_form->structure["discount"]["defaultvalue"]	= sql_get_singlevalue("SELECT option_value as value FROM account_items_options WHERE itemid='". $this->itemid ."' AND option_name='DISCOUNT'");

				break;

				case "service":
				case "service_usage":

					// fetch discount (if any) from item
					$this->obj_form->structure["discount"]["defaultvalue"]	= sql_get_singlevalue("SELECT option_value as value FROM account_items_options WHERE itemid='". $this->itemid ."' AND option_name='DISCOUNT'");

				break;

			}
		}


		/*
			Display Form
		*/
		
		$this->obj_form->subforms["hidden"]			= array("id_invoice", "id_item", "item_type");


		if ($this->item_type == "time" && count($this->obj_form->structure["timegroupid"]["values"]) == 0)
		{
			$this->obj_form->subforms["submit"]			= array();
		}
		else
		{
			$this->obj_form->subforms["submit"]			= array("submit");
		}

	}


	function render_html()
	{
		log_debug("invoice_form_item", "Executing render_html()");


		/*
			Display Form
		*/
		
		if ($this->item_type == "time" && count($this->obj_form->structure["timegroupid"]["values"]) == 0)
		{
			$this->obj_form->render_form();

			format_msgbox("important", "<p>There are currently no unprocessed time groups belonging to this customer - you must add time to a timegroup before you can create a time item.</p>");
			
		}
		else
		{
			$this->obj_form->render_form();
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
	
	$item		= @security_form_input_predefined("any", "item", 1, "You must select the type of item to add to the invoice");
	$invoiceid	= @security_form_input_predefined("any", "id", 1, "You must select an invoice before accessing this page");


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
	
	$item->id_invoice	= @security_form_input_predefined("int", "id_invoice", 1, "");
	$item->id_item		= @security_form_input_predefined("int", "id_item", 0, "");
	
	$item->type_invoice	= $type;
	$item->type_item	= @security_form_input_predefined("any", "item_type", 1, "");
	

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
			$data["amount"]		= @security_form_input_predefined("money", "amount", 0, "");
			$data["chartid"]	= @security_form_input_predefined("int", "chartid", 1, "");
			$data["description"]	= @security_form_input_predefined("any", "description", 0, "");

			// fetch information from all tax checkboxes from form
			$sql_tax_obj		= New sql_query;
			$sql_tax_obj->string	= "SELECT id FROM account_taxes";
			$sql_tax_obj->execute();

			if ($sql_tax_obj->num_rows())
			{
				$sql_tax_obj->fetch_array();

				foreach ($sql_tax_obj->data as $data_tax)
				{
					$data["tax_". $data_tax["id"] ]	= @security_form_input_predefined("any", "tax_". $data_tax["id"], 0, "");
				}

			} // end of loop through taxes
			
		break;


		case "product":
			/*
				PRODUCT ITEMS
			*/
			
			// fetch information from form
			$data["price"]		= @security_form_input_predefined("money", "price", 0, "");
			$data["quantity"]	= @security_form_input_predefined("float", "quantity", 1, "");
			$data["units"]		= @security_form_input_predefined("any", "units", 0, "");
			$data["customid"]	= @security_form_input_predefined("int", "productid", 1, "");
			$data["description"]	= @security_form_input_predefined("any", "description", 0, "");
			$data["discount"]	= @security_form_input_predefined("float", "discount", 0, "");

		break;


		case "time":
			/*
				TIME ITEMS
			*/
		
			// fetch information from form
			$data["price"]		= @security_form_input_predefined("money", "price", 0, "");
			$data["customid"]	= @security_form_input_predefined("int", "productid", 1, "");
			$data["timegroupid"]	= @security_form_input_predefined("int", "timegroupid", 1, "");
			$data["description"]	= @security_form_input_predefined("any", "description", 0, "");
			$data["discount"]	= @security_form_input_predefined("float", "discount", 0, "");
			$data["units"]		= "hours";
			
		break;


		case "service":
		case "service_usage":
			/*
				SERVICE ITEMS
			*/
		
			// fetch information from form
			$data["price"]		= @security_form_input_predefined("money", "price", 0, "");
			$data["quantity"]	= @security_form_input_predefined("float", "quantity", 1, "");
			$data["description"]	= @security_form_input_predefined("any", "description", 1, "");
			$data["discount"]	= @security_form_input_predefined("float", "discount", 0, "");
			$data["units"]		= "";

			// keep existing custom id
			$data["customid"]	= sql_get_singlevalue("SELECT customid as value FROM account_items WHERE id='". $item->id_item ."' LIMIT 1");
		break;


		case "payment":
			/*
				PAYMENT ITEM
			*/

			// fetch information from form
			$data["date_trans"]	= @security_form_input_predefined("date", "date_trans", 1, "");
			$data["amount"]		= @security_form_input_predefined("money", "amount", 1, "");
			$data["source"]		= @security_form_input_predefined("any", "source", 0, "");
			$data["description"]	= @security_form_input_predefined("any", "description", 0, "");
			
			if ($_POST["chartid"] == "credit")
			{
				$data["chartid"] = "credit";
			}
			else
			{
				$data["chartid"] = @security_form_input_predefined("int", "chartid", 1, "");
			}
			
		break;

		case "credit":
			/*
				CREDIT ITEMS
			*/

			// fetch information from form
			$data["amount"]		= @security_form_input_predefined("money", "amount", 0, "");
			$data["chartid"]	= @security_form_input_predefined("int", "chartid", 1, "");
			$data["description"]	= @security_form_input_predefined("any", "description", 0, "");

			// fetch information from all tax checkboxes from form
			$sql_tax_obj		= New sql_query;
			$sql_tax_obj->string	= "SELECT id FROM account_taxes";
			$sql_tax_obj->execute();

			if ($sql_tax_obj->num_rows())
			{
				$sql_tax_obj->fetch_array();

				foreach ($sql_tax_obj->data as $data_tax)
				{
					$data["tax_". $data_tax["id"] ]	= @security_form_input_predefined("any", "tax_". $data_tax["id"], 0, "");
				}

			} // end of loop through taxes
			
		break;



		default:
			log_write("error", "inc_invoice","Unknown item type passed to processing form.");
		break;
	}
	


	/*
		Process data
	*/
	if (!$item->prepare_data($data))
	{
		log_write("error", "process", "An error was encountered whilst processing supplied data.");
	}



	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"][$item->type_invoice ."_invoice_". $mode] = "failed";
		header("Location: ../../index.php?page=$returnpage_error&id=". $item->id_invoice ."&itemid=". $item->id_item ."&type=". $item->type_item ."");
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
			APPLY ITEM CHANGES
		*/
	
		if ($mode == "add")
		{
			$item->action_create();
			$item->action_update();
		}
		else
		{
			$item->action_update();
		}



		/*
			Re-calculate Taxes

			Note: Wo not re-calculate taxes for payment items, as this will change
			any overridden values made on AP invocies and is unnessacary.
		*/

		if ($item->type_item != "payment")
		{
			$item->action_update_tax();
		}



		/*
			Update invoice summary totals
		*/
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
			Commit
		*/

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "inc_invoice_items", "An error occured whilst updating the invoice item. No changes have been made.");
		}
		else
		{
			$sql_obj->trans_commit();
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
	
	$item->id_invoice	= @security_script_input("/^[0-9]*$/", $_GET["id"]);
	$item->id_item		= @security_script_input("/^[0-9]*$/", $_GET["itemid"]);

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
			Start Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();


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



		/*
			Commit
		*/

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "inc_invoice_items", "An error occured whilst deleting the invoice item. No changes have been made.");
		}
		else
		{
			$sql_obj->trans_commit();
		}

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
	
	$item->id_invoice	= @security_form_input_predefined("int", "invoiceid", 1, "");
	$item->id_item		= @security_form_input_predefined("int", "itemid", 1, "");

	$item->type_invoice	= "ap"; // only AP invoices can have taxes overridden
	
	
	/*
		Fetch all form data
	*/
	
	$data["amount"]		= @security_form_input_predefined("money", "amount", 0, "");


	
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
			Start SQL Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();


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
			$_SESSION["notification"]["message"] = array("Updated tax value with custom input.");
		}


		// update invoice summary
		$item->action_update_total();

		// update ledger
		$item->action_update_ledger();


		/*
			Commit
		*/

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "inc_invoice_items", "An error occured whilst overriding tax. No changes have been made");
		}
		else
		{
			$sql_obj->trans_commit();
		}

		// done
		header("Location: ../../index.php?page=$returnpage&id=". $item->id_invoice);
		exit(0);
	
	}


} // end of invoice_form_tax_override_process





?>
