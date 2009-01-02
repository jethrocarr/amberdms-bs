<?php
/*
	accounts/ar/invoice-export.php
	
	access: accounts_ar_view

	Provides the ability to export the invoice in different formats (eg: PDF, PS) and to be able to send it (via email or to a printer)

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
		// fetch variables
		$this->id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Invoice Details", "page=accounts/ar/invoice-view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Invoice Items", "page=accounts/ar/invoice-items.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Invoice Payments", "page=accounts/ar/invoice-payments.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Invoice Journal", "page=accounts/ar/journal.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Export Invoice", "page=accounts/ar/invoice-export.php&id=". $this->id ."", TRUE);

		if (user_permissions_get("accounts_ar_write"))
		{
			$this->obj_menu_nav->add_item("Delete Invoice", "page=accounts/ar/invoice-delete.php&id=". $this->id ."");
		}
	}



	function check_permissions()
	{
		return user_permissions_get("accounts_ar_view");
	}



	function check_requirements()
	{
		// verify that the invoice
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_ar WHERE id='". $this->id ."' LIMIT 1";
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
		$this->obj_form_invoice			= New invoice_form_export;
		$this->obj_form_invoice->type		= "ar";
		$this->obj_form_invoice->invoiceid	= $this->id;
		$this->obj_form_invoice->processpage	= "accounts/ar/invoice-export-process.php";
		
		$this->obj_form_invoice->execute();
	}

	function render_html()
	{
		// heading
		print "<h3>EXPORT INVOICE</h3><br>";
		print "<p>This page allows you to export the invoice in different formats and provides functions to allow you to email the invoice directly to the customer.</p>";

		// display summary box
		invoice_render_summarybox("ar", $this->id);

		// display form
		$this->obj_form_invoice->render_html();
	}

}

?>
