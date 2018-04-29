<?php
/*
	accounts/ap/credit-delete.php
	
	access: account_ap_write

	Form to delete a credit from the database - this page will only permit the credit
	to be deleted if the credit was created less than ACCOUNT_INVOICE_LOCK days ago.
	
*/


// custom includes
require("include/accounts/inc_credits.php");
require("include/accounts/inc_credits_forms.php");


class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_form_credit;


	function __construct()
	{
		// fetch variables
		$this->id = @security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Credit Details", "page=accounts/ap/credit-view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Credit Items", "page=accounts/ap/credit-items.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Credit Payment/Refund", "page=accounts/ap/credit-payments.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Credit Journal", "page=accounts/ap/credit-journal.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Delete Credit", "page=accounts/ap/credit-delete.php&id=". $this->id ."", TRUE);
	}


	function check_permissions()
	{
		return user_permissions_get("accounts_ap_write");
	}



	function check_requirements()
	{
		// verify that the credit
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_ap_credit WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested credit note (". $this->id .") does not exist - possibly the credit note has been deleted.");
			return 0;
		}

		unset($sql_obj);


		return 1;
	}


	function execute()
	{
		$this->obj_form_credit			= New credit_form_delete;
		$this->obj_form_credit->type		= "ap_credit";
		$this->obj_form_credit->credit_id	= $this->id;
		$this->obj_form_credit->processpage	= "accounts/ap/credit-delete-process.php";
		
		$this->obj_form_credit->execute();
	}

	function render_html()
	{
		// heading
		print "<h3>DELETE CREDIT NOTE</h3><br>";
		print "<p>This page allows you to delete incorrect credit notes, provided that they have not been locked.</p>";
		
		// display summary box
		credit_render_summarybox("ap_credit", $this->id);

		// display form
		$this->obj_form_credit->render_html();
	}
	
}


?>
