<?php
/*
	accounts/quotes/quote-add.php
	
	access: accounts_quotes_write

	Provides the form to add a new quotation to the system	
*/

// custom includes
require("include/accounts/inc_quotes_forms.php");



class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_form_quote;


	function check_permissions()
	{
		return user_permissions_get("accounts_quotes_write");
	}


	function check_requirements()
	{
		// nothing to check
		return 1;
	}


	function execute()
	{
		$this->obj_form_quote			= New quote_form_details;
		$this->obj_form_quote->type		= "ar";
		$this->obj_form_quote->quoteid		= 0;
		$this->obj_form_quote->processpage	= "accounts/quotes/quotes-edit-process.php";
		
		$this->obj_form_quote->execute();
	}

	function render_html()
	{
		// heading
		print "<h3>CREATE QUOTATION</h3><br>";
		print "<p>This page provides features to allow you to add new quotes to the system.</p>";

		// display form
		$this->obj_form_quote->render_html();
	}
	
}

?>
