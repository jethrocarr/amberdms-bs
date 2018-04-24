<?php
/*
	accounts/charts/delete.php
	
	access:	accounts_charts_write

	Allows an unwanted chart to be deleted.
*/


// custom includes
require("include/accounts/inc_charts.php");


class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_form;
	var $obj_chart;

	var $locked;
	

	function __construct()
	{
		// fetch variables
		$this->id = @security_script_input('/^[0-9]*$/', $_GET["id"]);

		// create chart object
		$this->obj_chart	= New chart;
		$this->obj_chart->id	= $this->id;

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Account Details", "page=accounts/charts/view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Account Ledger", "page=accounts/charts/ledger.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Delete Account", "page=accounts/charts/delete.php&id=". $this->id ."", TRUE);
	}



	function check_permissions()
	{
		return user_permissions_get("accounts_charts_write");
	}



	function check_requirements()
	{
		// verify that the account exists
		if (!$this->obj_chart->verify_id())
		{
			log_write("error", "page_output", "The requested account (". $this->id .") does not exist - possibly the account has been deleted.");
			return 0;
		}

		unset($sql_obj);


		// check the lock status of the account
		$this->locked = $this->obj_chart->check_delete_lock();

		return 1;
	}


	function execute()
	{
		/*
			Define form structure
		*/
		$this->obj_form = New form_input;
		$this->obj_form->formname = "chart_delete";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "accounts/charts/delete-process.php";
		$this->obj_form->method = "post";
		

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "code_chart";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "description";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);


		// hidden
		$structure = NULL;
		$structure["fieldname"] 	= "id_chart";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->id;
		$this->obj_form->add_input($structure);
		
		
		// confirm delete
		$structure = NULL;
		$structure["fieldname"] 	= "delete_confirm";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "Yes, I wish to delete this account and realise that once deleted the data can not be recovered.";
		$this->obj_form->add_input($structure);



		// define submit field
		$structure = NULL;
		$structure["fieldname"] = "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "delete";
		$this->obj_form->add_input($structure);


		
		// define subforms
		$this->obj_form->subforms["chart_delete"]	= array("code_chart", "description");
		$this->obj_form->subforms["hidden"]		= array("id_chart");

		if ($this->locked)
		{
			$this->obj_form->subforms["submit"]	= array();
		}
		else
		{
			$this->obj_form->subforms["submit"]		= array("delete_confirm", "submit");
		}

		
		// fetch the form data
		$this->obj_form->sql_query = "SELECT code_chart, description FROM `account_charts` WHERE id='". $this->id ."' LIMIT 1";
		$this->obj_form->load_data();
	}
	

	function render_html()
	{
		// Title + Summary
		print "<h3>DELETE ACCOUNT</h3><br>";
		print "<p>This page allows you to delete an unwanted account, provided that account has no transactions in it.</p>";

		// display the form
		$this->obj_form->render_form();

		if ($this->locked)
		{
			format_msgbox("locked", "<p>This account is locked and can not be removed.</p>");
		}

	}
}

?>
