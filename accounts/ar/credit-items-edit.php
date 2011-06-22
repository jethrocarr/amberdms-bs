<?php
/*
	accounts/ar/credits-items-edit.php
	
	access: account_ar_write

	Allows the addition or adjustment of items belonging to an invoice.
*/





// custom includes
require("include/accounts/inc_credits.php");
require("include/accounts/inc_invoices_items.php");
require("include/accounts/inc_charts.php");


class page_output
{
	var $id;
	var $itemid;
	var $item_type;
	var $requires;
	
	var $obj_menu_nav;
	
	var $obj_form_item;


	function page_output()
	{
		//require javascript file
		$this->requires["javascript"][]		= "include/accounts/javascript/invoice-items-edit.js";
		
		// fetch variables
		$this->id		= @@security_script_input('/^[0-9]*$/', $_GET["id"]);
		$this->itemid		= @@security_script_input('/^[0-9]*$/', $_GET["itemid"]);
		$this->item_type	= @@security_script_input('/^[a-z]*$/', $_GET["type"]);
		$this->invoice_item	= @@security_script_input('/^[0-9]*$/', $_GET["invoice_item"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Credit Details", "page=accounts/ar/credit-view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Credit Items", "page=accounts/ar/credit-items.php&id=". $this->id ."", TRUE);
		$this->obj_menu_nav->add_item("Credit Payment/Refund", "page=accounts/ar/credit-payments.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Credit Journal", "page=accounts/ar/credit-journal.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Export Credit Note", "page=accounts/ar/credit-export.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Delete Credit", "page=accounts/ar/credit-delete.php&id=". $this->id ."");
	}



	function check_permissions()
	{
		return user_permissions_get("accounts_ar_write");
	}



	function check_requirements()
	{
		// verify that the credit exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_ar_credit WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested credit note (". $this->id .") does not exist - possibly the credit note has been deleted.");
			return 0;
		}

		unset($sql_obj);


		// verify that the item id supplied exists and fetch required information
		if ($this->itemid)
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id, type FROM account_items WHERE id='". $this->itemid ."' AND invoiceid='". $this->id ."' LIMIT 1";
			$sql_obj->execute();

			if (!$sql_obj->num_rows())
			{
				log_write("error", "page_output", "The requested item/credit combination does not exist. Are you trying to use a link to a deleted item?");
				return 0;
			}
			else
			{
				$sql_obj->fetch_array();

				$this->item_type = $sql_obj->data[0]["type"];
			}
		}

		return 1;
	}


	function execute()
	{
		/*
			Generate Credit Item Form
		*/
		$this->obj_form_item			= New invoice_form_item;
		$this->obj_form_item->type		= "ar_credit";
		$this->obj_form_item->invoiceid		= $this->id;
		$this->obj_form_item->itemid		= $this->itemid;
		$this->obj_form_item->item_type		= "credit";
		$this->obj_form_item->processpage	= "accounts/ar/credit-items-edit-process.php";
		
		$this->obj_form_item->execute();



		/*
			Credit Information
		*/
		$this->credit	 	= New credit;
		$this->credit->id	= $this->id;
		$this->credit->type	= "ar_credit";

		$this->credit->load_data();


		/*
			Fetch details for selected item and overwrite form (if an item is selected)
		*/



		if ($this->invoice_item)
		{
			$sql_item_obj		= New sql_query;
			$sql_item_obj->string	= "SELECT type, description, chartid, customid, amount FROM account_items WHERE id='". $this->invoice_item ."' LIMIT 1";
			$sql_item_obj->execute();

			if ($sql_item_obj->num_rows())
			{
				$sql_item_obj->fetch_array();

				$description  = "Credit for ";

				switch ($sql_item_obj->data[0]["type"])
				{
					case "standard":

						$description .= sql_get_singlevalue("SELECT CONCAT_WS('--', code_chart, description) as value FROM account_charts WHERE id='". $sql_item_obj->data[0]["chartid"] ."' LIMIT 1");

						// fetch taxes from account_items_options
						$sql_tax_obj		= New sql_query;
						$sql_tax_obj->string	= "SELECT option_value FROM account_items_options WHERE itemid='". $this->invoice_item ."'";
						$sql_tax_obj->execute();

						if ($sql_tax_obj->num_rows())
						{
							$sql_tax_obj->fetch_array();

							foreach ($sql_tax_obj->data as $data_tax)
							{
								$this->obj_form_item->obj_form->structure["tax_". $data_tax["option_value"] ]["defaultvalue"]	= "on";
							}
						}

						unset($sql_tax_obj);

					break;

					case "time":
					case "product":
						$description .= sql_get_singlevalue("SELECT CONCAT_WS('--', code_product, name_product) as value FROM products WHERE id='". $sql_item_obj->data[0]["customid"] ."' LIMIT 1");

						// fetch taxes from products
						$sql_tax_obj		= New sql_query;
						$sql_tax_obj->string	= "SELECT taxid FROM products_taxes WHERE productid='". $sql_item_obj->data[0]["customid"]."'";
						$sql_tax_obj->execute();

						if ($sql_tax_obj->num_rows())
						{
							$sql_tax_obj->fetch_array();

							foreach ($sql_tax_obj->data as $data_tax)
							{
								$sql_cust_tax_obj		= New sql_query;
								$sql_cust_tax_obj->string	= "SELECT id FROM customers_taxes WHERE customerid='". $this->credit->data["customerid"] ."' AND taxid='". $data_tax["taxid"] ."'";
								$sql_cust_tax_obj->execute();

								if ($sql_cust_tax_obj->num_rows())
								{
									$this->obj_form_item->obj_form->structure["tax_". $data_tax["taxid"] ]["defaultvalue"]	= "on";
								}

								unset($sql_cust_tax_obj);
							}
						}

						unset($sql_tax_obj);

					break;

					case "service":
					case "service_usage":
						$description .= sql_get_singlevalue("SELECT name_service as value FROM services WHERE id='". $sql_item_obj->data[0]["customid"] ."' LIMIT 1");

						// fetch taxes from services
						$sql_tax_obj		= New sql_query;
						$sql_tax_obj->string	= "SELECT taxid FROM services_taxes WHERE serviceid='". $sql_item_obj->data[0]["customid"]."'";
						$sql_tax_obj->execute();

						if ($sql_tax_obj->num_rows())
						{
							$sql_tax_obj->fetch_array();

							foreach ($sql_tax_obj->data as $data_tax)
							{
								$sql_cust_tax_obj		= New sql_query;
								$sql_cust_tax_obj->string	= "SELECT id FROM customers_taxes WHERE customerid='". $this->credit->data["customerid"] ."' AND taxid='". $data_tax["taxid"] ."'";
								$sql_cust_tax_obj->execute();

								if ($sql_cust_tax_obj->num_rows())
								{
									$this->obj_form_item->obj_form->structure["tax_". $data_tax["taxid"] ]["defaultvalue"]	= "on";
								}
							}
						}

						unset($sql_tax_obj);


					break;

					default:
						$description .= "unknown item";
					break;
				}

				$description .= " [". $sql_item_obj->data[0]["description"] ."]";
			}
			else
			{
				// no such invoice item
				log_write("error", "page", "The selected item does not appear to exist.");
			}


			/*
				Add Data
			*/

			$this->obj_form_item->obj_form->structure["amount"]["defaultvalue"]		= $sql_item_obj->data[0]["amount"];
			$this->obj_form_item->obj_form->structure["chartid"]["defaultvalue"]		= $sql_item_obj->data[0]["chartid"];
			$this->obj_form_item->obj_form->structure["description"]["defaultvalue"]	= $description;

		}
		else
		{
			log_write("debug", "page", "No invoice_item supplied, if an item is being edited, this will be OK.");
		}


	}


	function render_html()
	{
		// title + summary
		print "<h3>ADD/EDIT CREDIT NOTE ITEM</h3><br>";
		print "<p>This page allows you to make changes to an credit item.</p>";

		credit_render_summarybox("ar_credit", $this->id);

		$this->obj_form_item->render_html();
	}

}

?>
