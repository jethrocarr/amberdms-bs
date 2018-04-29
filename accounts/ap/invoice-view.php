<?php
/*
	accounts/ap/invoices-view.php
	
	access: account_ap_view

	Form to add a new invoice to the database.

	This page is a lot more complicated than most of the other forms in this program, since
	it needs to allow the user to "update" the form, so that the form adds additional input
	fields for more invoice listings.

	The update option will also generate and return totals back to the program.
	
*/

// custom includes
require("include/accounts/inc_invoices_forms.php");



class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_form_invoice;


	function __construct()
	{
		// fetch vapiables
		$this->id = @security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Invoice Details", "page=accounts/ap/invoice-view.php&id=". $this->id ."", TRUE);
		$this->obj_menu_nav->add_item("Invoice Items", "page=accounts/ap/invoice-items.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Invoice Payments", "page=accounts/ap/invoice-payments.php&id=". $this->id ."");
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
		$this->obj_form_invoice			= New invoice_form_details;
		$this->obj_form_invoice->type		= "ap";
		$this->obj_form_invoice->invoiceid	= $this->id;
		$this->obj_form_invoice->processpage	= "accounts/ap/invoice-edit-process.php";
		
		$this->obj_form_invoice->execute();
	}

	function render_html()
	{
		// heading
		print "<h3>VIEW INVOICE</h3><br>";
		print "<p>This page allows you to view the basic details of the invoice. You can use the links in the green navigation menu above to change to different sections of the invoice, in order to add items, payments or journal entries to the invoice.</p>";

		// display summapy box
		invoice_render_summarybox("ap", $this->id);

		// display form
		$this->obj_form_invoice->render_html();
	}
	
}

?>
