<?php
/*
	accounts/ar/quotes-items-edit.php
	
	access: account_ar_write

	Allows adjusting or addition of new items to an quote.
*/




// custom includes
require("include/accounts/inc_quotes.php");
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

		// fetch vapiables
		$this->id		= @security_script_input('/^[0-9]*$/', $_GET["id"]);
		$this->itemid		= @security_script_input('/^[0-9]*$/', $_GET["itemid"]);
		$this->item_type	= @security_script_input('/^[a-z]*$/', $_GET["type"]);
		$this->productid	= @security_script_input('/^[0-9]*$/', $_GET["productid"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Quote Details", "page=accounts/quotes/quotes-view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Quote Items", "page=accounts/quotes/quotes-items.php&id=". $this->id ."", TRUE);
		$this->obj_menu_nav->add_item("Quote Journal", "page=accounts/quotes/journal.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Export Quote", "page=accounts/quotes/quotes-export.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Convert to Invoice", "page=accounts/quotes/quotes-convert.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Delete Quote", "page=accounts/quotes/quotes-delete.php&id=". $this->id ."");

	}



	function check_permissions()
	{
		return user_permissions_get("accounts_quotes_write");
	}



	function check_requirements()
	{
		// verify that the quote exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_quotes WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested quote (". $this->id .") does not exist - possibly the quote has been deleted or converted into an invoice.");
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
				log_write("error", "page_output", "The requested item/quote combination does not exist. Are you trying to use a link to a deleted item?");
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
		$this->obj_form_item->type		= "quotes";
		$this->obj_form_item->invoiceid		= $this->id;
		$this->obj_form_item->itemid		= $this->itemid;
		$this->obj_form_item->item_type		= $this->item_type;
		$this->obj_form_item->productid		= $this->productid;
		$this->obj_form_item->processpage	= "accounts/quotes/quotes-items-edit-process.php";
		
		$this->obj_form_item->execute();
	}


	function render_html()
	{
		// title + summapy
		print "<h3>ADD/EDIT QUOTE ITEM</h3><br>";
		print "<p>This page allows you to make changes to an quote item.</p>";

		quotes_render_summarybox($this->id);

		$this->obj_form_item->render_html();
	}

}



?>
