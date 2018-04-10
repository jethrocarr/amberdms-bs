<?php
/*
	accounts/ap/invoices-items-edit.php
	
	access: account_ap_write

	Allows adjusting or addition of new items to an invoice.
*/





// custom includes
require("include/accounts/inc_invoices.php");
require("include/accounts/inc_invoices_items.php");
require("include/accounts/inc_charts.php");


class page_output
{
	var $id;
	var $itemid;
	var $item_type;
	
	var $obj_menu_nav;
	
	var $obj_form_item;


	function __construct()
	{
		//require javascript file
		$this->requires["javascript"][]		= "include/accounts/javascript/invoice-items-edit_ap.js";
		
		// fetch vapiables
		$this->id		= @security_script_input('/^[0-9]*$/', $_GET["id"]);
		$this->itemid		= @security_script_input('/^[0-9]*$/', $_GET["itemid"]);
		$this->item_type	= @security_script_input('/^[a-z]*$/', $_GET["type"]);
		$this->productid	= @security_script_input('/^[0-9]*$/', $_GET["productid"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Invoice Details", "page=accounts/ap/invoice-view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Invoice Items", "page=accounts/ap/invoice-items.php&id=". $this->id ."", TRUE);
		$this->obj_menu_nav->add_item("Invoice Payments", "page=accounts/ap/invoice-payments.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Invoice Journal", "page=accounts/ap/journal.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Delete Invoice", "page=accounts/ap/invoice-delete.php&id=". $this->id ."");
	}



	function check_permissions()
	{
		return user_permissions_get("accounts_ap_write");
	}



	function check_requirements()
	{
		// verify that the invoice exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_ap WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested invoice (". $this->id .") does not exist - possibly the invoice has been deleted.");
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
				log_write("error", "page_output", "The requested item/invoice combination does not exist. Are you trying to use a link to a deleted item?");
				return 0;
			}
			else
			{
				$sql_obj->fetch_array();

				$this->item_type = $sql_obj->data[0]["type"];
			}
		}
		else
		{
			if (!$this->item_type)
			{
				log_write("error", "page_output", "You must supply the item of item to create");
				return 0;
			}
		}

		return 1;
	}


	function execute()
	{
		$this->obj_form_item			= New invoice_form_item;
		$this->obj_form_item->type		= "ap";
		$this->obj_form_item->invoiceid		= $this->id;
		$this->obj_form_item->itemid		= $this->itemid;
		$this->obj_form_item->item_type		= $this->item_type;
		$this->obj_form_item->productid		= $this->productid;
		$this->obj_form_item->processpage	= "accounts/ap/invoice-items-edit-process.php";
		
		$this->obj_form_item->execute();
	}


	function render_html()
	{
		// title + summapy
		print "<h3>ADD/EDIT INVOICE ITEM</h3><br>";
		print "<p>This page allows you to make changes to an invoice item.</p>";

		invoice_render_summarybox("ap", $this->id);

		$this->obj_form_item->render_html();
	}

}

?>
