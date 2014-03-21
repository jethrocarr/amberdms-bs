<?php
/*
	accounts/ap/invoice-delete.php
	
	access: account_ap_write

	Form to delete an invoice from the database - this page will only permit the invoice
	to be deleted if the invoice was created less than ACCOUNT_INVOICE_LOCK days ago.
	
*/


// custom includes
require("include/accounts/inc_invoices_forms.php");


class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_form_invoice;


	function page_output()
	{
		// fetch vapiables
		$this->id = @security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Invoice Details", "page=accounts/ap/invoice-view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Invoice Items", "page=accounts/ap/invoice-items.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Invoice Payments", "page=accounts/ap/invoice-payments.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Invoice Journal", "page=accounts/ap/journal.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Delete Invoice", "page=accounts/ap/invoice-delete.php&id=". $this->id ."", TRUE);
	}


	function check_permissions()
	{
		return user_permissions_get("accounts_ap_write");
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
		$this->obj_form_invoice			= New invoice_form_delete;
		$this->obj_form_invoice->type		= "ap";
		$this->obj_form_invoice->invoiceid	= $this->id;
		$this->obj_form_invoice->processpage	= "accounts/ap/invoice-delete-process.php";
		
		$this->obj_form_invoice->execute();
	}

	function render_html()
	{
		// heading
		print "<h3>DELETE INVOICE</h3><br>";
		print "<p>This page allows you to delete incorrect invoices, provided that they have not been locked.</p>";
		
		// display summapy box
		invoice_render_summarybox("ap", $this->id);

		// display form
		$this->obj_form_invoice->render_html();
	}
	
}


?>
