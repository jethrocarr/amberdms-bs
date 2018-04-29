<?php
/*
	accounts/ar/credits-view.php
	
	access: accounts_ar_view

	Displays details of the selected credit and allows them to be adjusted.

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

		$this->obj_menu_nav->add_item("Credit Details", "page=accounts/ar/credit-view.php&id=". $this->id ."", TRUE);
		$this->obj_menu_nav->add_item("Credit Items", "page=accounts/ar/credit-items.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Credit Payment/Refund", "page=accounts/ar/credit-payments.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Credit Journal", "page=accounts/ar/credit-journal.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Export Credit Note", "page=accounts/ar/credit-export.php&id=". $this->id ."");

		if (user_permissions_get("accounts_ar_write"))
		{
			$this->obj_menu_nav->add_item("Delete Credit", "page=accounts/ar/credit-delete.php&id=". $this->id ."");
		}
	}



	function check_permissions()
	{
		return user_permissions_get("accounts_ar_view");
	}



	function check_requirements()
	{
		// verify that the credit
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_ar_credit WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested credit (". $this->id .") does not exist - possibly the credit has been deleted.");
			return 0;
		}

		unset($sql_obj);


		return 1;
	}


	function execute()
	{
		$this->obj_form_credit			= New credit_form_details;
		$this->obj_form_credit->type		= "ar_credit";
		$this->obj_form_credit->credit_id	= $this->id;
		$this->obj_form_credit->processpage	= "accounts/ar/credit-edit-process.php";
		
		$this->obj_form_credit->execute();
	}

	function render_html()
	{
		// heading
		print "<h3>VIEW CREDIT NOTE</h3><br>";
		print "<p>This page allows you to view the basic details of the credit. You can use the links in the green navigation menu above to change to different sections of the credit, in order to add items, payments or journal entries to the credit.</p>";

		// display summary box
		credit_render_summarybox("ar_credit", $this->id);

		// display form
		$this->obj_form_credit->render_html();
	}
	
}

?>
