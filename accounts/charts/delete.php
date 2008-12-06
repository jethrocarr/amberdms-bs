<?php
/*
	accounts/charts/delete.php
	
	access:	accounts_charts_write

	Allows an unwanted chart to be deleted.
*/

class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_form;


	function page_output()
	{
		// fetch variables
		$this->id = security_script_input('/^[0-9]*$/', $_GET["id"]);

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
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_charts WHERE id='". $this->id ."'";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested account (". $this->id .") does not exist - possibly the account has been deleted.");
			return 0;
		}

		unset($sql_obj);


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



		/*
			Check that the chart can be deleted
		*/

		$locked = 0;
		

		// make sure chart has no transactions in it
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_trans WHERE chartid='". $this->id ."'";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$locked = 1;
		}
		

		// make sure chart has no items belonging to it
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_items WHERE chartid='". $this->id ."'";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$locked = 1;
		}
		
		
		// define submit field
		$structure = NULL;
		$structure["fieldname"] = "submit";

		if ($locked)
		{
			$structure["type"]		= "message";
			$structure["defaultvalue"]	= "<i>This accounts can not be deleted because it has transactions or items belonging to it.</i>";
		}
		else
		{
			$structure["type"]		= "submit";
			$structure["defaultvalue"]	= "delete";
		}
				
		$this->obj_form->add_input($structure);


		
		// define subforms
		$this->obj_form->subforms["chart_delete"]		= array("code_chart", "description");
		$this->obj_form->subforms["hidden"]		= array("id_chart");
		$this->obj_form->subforms["submit"]		= array("delete_confirm", "submit");

		
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

	}
}

?>
