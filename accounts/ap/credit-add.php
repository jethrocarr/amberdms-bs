<?php
/*
	accounts/ap/credit-add.php
	
	access: accounts_ap_write

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
		$this->requires["javascript"][]		= "include/vendors/javascript/populate_invoices_dropdown.js";
	}

	function check_permissions()
	{
		return user_permissions_get("accounts_ap_write");
	}


	function check_requirements()
	{
		// nothing to check
		return 1;
	}


	function execute()
	{
		
		$vendor_id = @security_script_input('/^[0-9]*$/', $_GET["vendorid"]);
		
		$this->obj_form_credit			= New credit_form_details;
		$this->obj_form_credit->type		= "ap_credit";
		$this->obj_form_credit->credit_id	= 0;
		$this->obj_form_credit->vendor_id	= $vendor_id;
		$this->obj_form_credit->processpage	= "accounts/ap/credit-edit-process.php";
		
		$this->obj_form_credit->execute();
	}

	function render_html()
	{
		// heading
		print "<h3>ADD CREDIT NOTE</h3><br>";
		print "<p>This page provides features to allow you to create a new credit note for a vendor's invoice.</p>";

		// display form
		$this->obj_form_credit->render_html();
	}
	
}

?>
