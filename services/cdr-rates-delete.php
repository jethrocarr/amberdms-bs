<?php
/*
	services/cdr-rates-delete.php

	access: services_write

	Form to delete rate tables.
*/


require("include/services/inc_services_cdr.php");


class page_output
{
	var $obj_rate_table;
	var $obj_menu_nav;
	var $obj_form;
	var $locked;


	function page_output()
	{
		$this->obj_rate_table	= New cdr_rate_table;


		// fetch variables
		$this->obj_rate_table->id = @security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Rate Table Details", "page=services/cdr-rates-view.php&id=". $this->obj_rate_table->id ."");
		$this->obj_menu_nav->add_item("Rate Table Items", "page=services/cdr-rates-items.php&id=". $this->obj_rate_table->id ."");
		$this->obj_menu_nav->add_item("Delete Rate Table", "page=services/cdr-rates-delete.php&id=". $this->obj_rate_table->id ."", TRUE);
	}



	function check_permissions()
	{
		return user_permissions_get("services_write");
	}

	function check_requirements()
	{
		// verify ID
		if (!$this->obj_rate_table->verify_id())
		{
			log_write("error", "page_output", "The supplied rate table ID ". $this->obj_rate_table->id ." does not exist");
			return 0;
		}

		// check if the page is locked or not
		$this->locked = $this->obj_rate_table->check_delete_lock();


		return 1;
	}


	function execute()
	{
		// define basic form details
		$this->obj_form = New form_input;
		$this->obj_form->formname = "cdr_rate_table_delete";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "services/cdr-rates-delete-process.php";
		$this->obj_form->method = "post";
		

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "rate_table_name";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"]		= "rate_table_description";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);


		// hidden fields
		$structure = NULL;
		$structure["fieldname"] 	= "id";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->obj_rate_table->id;
		$this->obj_form->add_input($structure);


		// confirm delete
		$structure = NULL;
		$structure["fieldname"] 	= "delete_confirm";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "Yes, I wish to delete this rate table and realise that once deleted the data can not be recovered.";
		$this->obj_form->add_input($structure);


		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "delete";
		$this->obj_form->add_input($structure);
		


		// define subforms
		$this->obj_form->subforms["rate_table_delete"]	= array("rate_table_name", "rate_table_description");
		$this->obj_form->subforms["hidden"]		= array("id");

		if ($this->locked)
		{
			$this->obj_form->subforms["submit"]		= array();
		}
		else
		{
			$this->obj_form->subforms["submit"]		= array("delete_confirm", "submit");
		}
		

		// load any data

		$this->obj_rate_table->load_data();

		$this->obj_form->structure["rate_table_name"]["defaultvalue"]		= $this->obj_rate_table->data["rate_table_name"];
		$this->obj_form->structure["rate_table_description"]["defaultvalue"]	= $this->obj_rate_table->data["rate_table_description"];

		if (error_check())
		{
			$this->obj_form->load_data_error();
		}
		
	}



	function render_html()
	{
		// title and summary
		print "<h3>RATE TABLE DETAILS</h3><br>";
		print "<p>View/Adjust the basic details of the selected rate table below, or use the nav menu above to select the items page to modify the prefixes and matching.</p>";

		// display the form
		$this->obj_form->render_form();

		if ($this->locked)
		{
			format_msgbox("locked", "<p>Sorry, the rate table can not be deleted as it is in use by some services.</p>");
		}
	}



}


?>
