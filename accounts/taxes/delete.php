<?php
/*
	taxes/delete.php
	
	access:	accounts_taxes_write

	Allows an unwanted tax to be deleted.
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

		$this->obj_menu_nav->add_item("Tax Details", "page=accounts/taxes/view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Tax Ledger", "page=accounts/taxes/ledger.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Delete Tax", "page=accounts/taxes/delete.php&id=". $this->id ."", TRUE);
	}



	function check_permissions()
	{
		return user_permissions_get("accounts_taxes_write");
	}



	function check_requirements()
	{
		// verify that the tax exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_taxes WHERE id='". $this->id ."' LIMIT 1";
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
		$this->obj_form->formname = "tax_delete";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "accounts/taxes/delete-process.php";
		$this->obj_form->method = "post";
		

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "name_tax";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);


		// hidden
		$structure = NULL;
		$structure["fieldname"] 	= "id_tax";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->id;
		$this->obj_form->add_input($structure);
		
		
		// confirm delete
		$structure = NULL;
		$structure["fieldname"] 	= "delete_confirm";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "Yes, I wish to delete this tax and realise that once deleted the data can not be recovered.";
		$this->obj_form->add_input($structure);



		/*
			Check that the tax can be deleted
		*/

		$locked = 0;
		

		// make sure tax does not belong to any invoices
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_items WHERE type='tax' AND customid='". $this->id ."'";
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
			$structure["defaultvalue"]	= "<i>This tax can not be deleted because it has been used in an invoice.</i>";
		}
		else
		{
			$structure["type"]		= "submit";
			$structure["defaultvalue"]	= "delete";
		}
				
		$this->obj_form->add_input($structure);


		
		// define subforms
		$this->obj_form->subforms["tax_delete"]		= array("name_tax");
		$this->obj_form->subforms["hidden"]		= array("id_tax");
		$this->obj_form->subforms["submit"]		= array("delete_confirm", "submit");

		
		// fetch the form data
		$this->obj_form->sql_query = "SELECT name_tax FROM `account_taxes` WHERE id='". $this->id ."' LIMIT 1";
		$this->obj_form->load_data();
	}

	function render_html()
	{
		// title + summary
		print "<h3>DELETE TAX</h3><br>";
		print "<p>This page allows you to delete an unwanted tax, provided that the tax has not been used for any invoices.</p>";
		
		// display the form
		$this->obj_form->render_form();
	}
}

?>
