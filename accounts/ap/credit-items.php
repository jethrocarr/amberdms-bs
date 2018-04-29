<?php
/*
	accounts/ap/credit-items.php
	
	access: account_ap_view

	Displays all the items assigned to the credit - note that a number of functions were originally
	designed for the invoice pages, but apply equally well to the credit pages.
	
*/

// custom includes
require("include/accounts/inc_credits.php");
require("include/accounts/inc_invoices_items.php");
require("include/accounts/inc_charts.php");


class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_table_items;


	function __construct()
	{
		$this->requires["css"][]	= "include/accounts/css/invoice-items-edit.css";

		// fetch variables
		$this->id = @security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Credit Details", "page=accounts/ap/credit-view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Credit Items", "page=accounts/ap/credit-items.php&id=". $this->id ."", TRUE);
		$this->obj_menu_nav->add_item("Credit Payment/Refund", "page=accounts/ap/credit-payments.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Credit Journal", "page=accounts/ap/credit-journal.php&id=". $this->id ."");
		
		if (user_permissions_get("accounts_ap_write"))
		{
			$this->obj_menu_nav->add_item("Delete Credit", "page=accounts/ap/credit-delete.php&id=". $this->id ."");
		}
	}



	function check_permissions()
	{
		return user_permissions_get("accounts_ap_view");
	}



	function check_requirements()
	{
		// verify that the credit
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_ap_credit WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested credit note (". $this->id .") does not exist - possibly the credit has been deleted.");
			return 0;
		}

		unset($sql_obj);


		return 1;
	}


	function execute()
	{
		$this->obj_table_items			= New invoice_list_items;
		$this->obj_table_items->type		= "ap_credit";
		$this->obj_table_items->invoiceid	= $this->id;
		$this->obj_table_items->page_view	= "accounts/ap/credit-items-edit.php";
		$this->obj_table_items->page_delete	= "accounts/ap/credit-items-delete-process.php";
		
		$this->obj_table_items->execute();
	}

	function render_html()
	{
		// heading
		print "<h3>CREDIT NOTE ITEMS</h3><br>";
		print "<p>This page shows all the items belonging to the credit and allows you to edit them.</p>";
		
		// display summary box
		credit_render_summarybox("ap_credit", $this->id);

		// display credit item box
		credit_render_invoiceselect("ap_credit", $this->id, "accounts/ap/credit-items-edit.php");

		// display form
		$this->obj_table_items->render_html();
	}
	
}

?>
