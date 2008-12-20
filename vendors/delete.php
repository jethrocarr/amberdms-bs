<?php
/*
	vendors/delete.php
	
	access:	vendors_write

	Allows an unwanted vendor to be deleted.
*/

class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_form;

	var $locked;


	function page_output()
	{
		// fetch variables
		$this->id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Vendor's Details", "page=vendors/view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Vendor's Journal", "page=vendors/journal.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Vendor's Invoices", "page=vendors/invoices.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Delete Vendor", "page=vendors/delete.php&id=". $this->id ."", TRUE);
	}



	function check_permissions()
	{
		return user_permissions_get("vendors_write");
	}



	function check_requirements()
	{
		// verify that vendor exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM vendors WHERE id='". $this->id ."'";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested vendor (". $this->id .") does not exist - possibly the vendor has been deleted.");
			return 0;
		}

		unset($sql_obj);


		return 1;
	}



	function execute()
	{

		// make sure vendor does not belong to any invoices
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_ap WHERE vendorid='". $this->id ."'";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$this->locked = 1;
		}
		
	
		/*
			Define form structure
		*/
		$this->obj_form = New form_input;
		$this->obj_form->formname = "vendor_delete";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "vendors/delete-process.php";
		$this->obj_form->method = "post";
		

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "name_vendor";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);


		// hidden
		$structure = NULL;
		$structure["fieldname"] 	= "id_vendor";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->id;
		$this->obj_form->add_input($structure);
		
		
		// confirm delete
		$structure = NULL;
		$structure["fieldname"] 	= "delete_confirm";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "Yes, I wish to delete this vendor and realise that once deleted the data can not be recovered.";
		$this->obj_form->add_input($structure);


		// define submit field
		$structure = NULL;
		$structure["fieldname"] = "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "delete";
		$this->obj_form->add_input($structure);


		
		// define subforms
		$this->obj_form->subforms["vendor_delete"]	= array("name_vendor");
		$this->obj_form->subforms["hidden"]		= array("id_vendor");

		if ($this->locked)
		{
			$this->obj_form->subforms["submit"]	= array();
		}
		else
		{
			$this->obj_form->subforms["submit"]		= array("delete_confirm", "submit");
		}

		
		// fetch the form data
		$this->obj_form->sql_query = "SELECT name_vendor FROM `vendors` WHERE id='". $this->id ."' LIMIT 1";
		$this->obj_form->load_data();
	}


	function render_html()
	{
		// title + summary	
		print "<h3>DELETE VENDOR</h3><br>";
		print "<p>This page allows you to delete an unwanted vendors.</p>";

		// display the form
		$this->obj_form->render_form();

		if ($this->locked)
		{
			format_msgbox("locked", "<p>This vendor can not be removed because there are invoices belong to this vendor.</p>");
		}

	}


} // end of page_output class
?>
