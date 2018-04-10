<?php
/*
	accounts/ar/credit-add.php
	
	access: accounts_ar_wirte

	Provides the UI for entering new credit notes into ABS.
*/

// custom includes
require("include/accounts/inc_credits_forms.php");



class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_form_credit;

	function __construct()
	{
		$this->requires["javascript"][]		= "include/customers/javascript/populate_invoices_dropdown.js";
	}

	function check_permissions()
	{
		return user_permissions_get("accounts_ar_write");
	}


	function check_requirements()
	{
		// nothing to check
		return 1;
	}


	function execute()
	{
		
		$customer_id = @security_script_input('/^[0-9]*$/', $_GET["customerid"]);
		
		$this->obj_form_credit			= New credit_form_details;
		$this->obj_form_credit->type		= "ar_credit";
		$this->obj_form_credit->credit_id	= 0;
		$this->obj_form_credit->customer_id	= $customer_id;
		$this->obj_form_credit->processpage	= "accounts/ar/credit-edit-process.php";
		
		$this->obj_form_credit->execute();
	}

	function render_html()
	{
		// heading
		print "<h3>ADD CREDIT NODE</h3><br>";
		print "<p>This page provides features to allow you to create a new credit note.</p>";

		// display form
		$this->obj_form_credit->render_html();
	}
	
}

?>
