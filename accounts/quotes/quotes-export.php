<?php
/*
	accounts/quotes/quotes-export.php
	
	access: accounts_quots_view

	Provides the ability to export the quote in different formats (eg: PDF, PS) and to be able to send it (via email or to a printer)

*/

// custom includes
require("include/accounts/inc_invoices_forms.php");
require("include/accounts/inc_quotes.php");



class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_form_invoice;


	function page_output()
	{
		// fetch variables
		$this->id = @security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Quote Details", "page=accounts/quotes/quotes-view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Quote Items", "page=accounts/quotes/quotes-items.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Quote Journal", "page=accounts/quotes/journal.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Export Quote", "page=accounts/quotes/quotes-export.php&id=". $this->id ."", TRUE);

		if (user_permissions_get("accounts_quotes_write"))
		{
			$this->obj_menu_nav->add_item("Convert to Invoice", "page=accounts/quotes/quotes-convert.php&id=". $this->id ."");
			$this->obj_menu_nav->add_item("Delete Quote", "page=accounts/quotes/quotes-delete.php&id=". $this->id ."");
		}
	}



	function check_permissions()
	{
		return user_permissions_get("accounts_quotes_view");
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

		return 1;
	}


	function execute()
	{
		$this->obj_form_quote			= New invoice_form_export;
		$this->obj_form_quote->type		= "quotes";
		$this->obj_form_quote->invoiceid	= $this->id;
		$this->obj_form_quote->processpage	= "accounts/quotes/quotes-export-process.php";
		
		$this->obj_form_quote->execute();
	}

	function render_html()
	{
		// heading
		print "<h3>EXPORT QUOTE</h3><br>";
		print "<p>This page allows you to export the quote in different formats and provides functions to allow you to email the quote directly to the customer.</p>";

		// display summary box
		quotes_render_summarybox($this->id);

		// display form
		$this->obj_form_quote->render_html();
	}
	
}

?>
