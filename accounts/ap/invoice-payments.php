<?php
/*
	accounts/ap/invoices-items.php
	
	access: account_ap_view

	Lists all the payments against this invoice and allows them to be added/edited/deleted.
*/

// custom includes
require("include/accounts/inc_invoices.php");
require("include/accounts/inc_invoices_items.php");
require("include/accounts/inc_charts.php");


class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_table_payments;


	function __construct()
	{
		// fetch vapiables
		$this->id = @security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Invoice Details", "page=accounts/ap/invoice-view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Invoice Items", "page=accounts/ap/invoice-items.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Invoice Payments", "page=accounts/ap/invoice-payments.php&id=". $this->id ."", TRUE);
		$this->obj_menu_nav->add_item("Invoice Journal", "page=accounts/ap/journal.php&id=". $this->id ."");

		if (user_permissions_get("accounts_ap_write"))
		{
			$this->obj_menu_nav->add_item("Delete Invoice", "page=accounts/ap/invoice-delete.php&id=". $this->id ."");
		}
	}



	function check_permissions()
	{
		return user_permissions_get("accounts_ap_view");
	}



	function check_requirements()
	{
		// verify that the invoice
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_ap WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested invoice (". $this->id .") does not exist - possibly the invoice has been deleted.");
			return 0;
		}

		unset($sql_obj);


		return 1;
	}


	function execute()
	{
		$this->obj_table_payments		= New invoice_list_payments;
		$this->obj_table_payments->type		= "ap";
		$this->obj_table_payments->invoiceid	= $this->id;
		$this->obj_table_payments->page_view	= "accounts/ap/invoice-payments-edit.php";
		$this->obj_table_payments->page_delete	= "accounts/ap/invoice-payments-delete-process.php";
		
		$this->obj_table_payments->execute();
	}


	function render_html()
	{
		// heading
		print "<h3>INVOICE PAYMENTS</h3><br>";
		print "<p>This page shows all payments made against this invoice and allows you to edit them.</p>";
		
		// display summapy box
		invoice_render_summarybox("ap", $this->id);

		// display form
		$this->obj_table_payments->render_html();
	}
	
}


?>
