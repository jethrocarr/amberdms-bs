<?php
/*
	services/cdr-rates-import.php

	access: services_write

	Import function to load in CDR rates from CSV files, which can typically be supplied by the
	call provider.

	This page provides the field to upload the file, reads it into a session variable, provides
	for column assignement and then imports.
*/


require("include/services/inc_services.php");
require("include/services/inc_services_cdr.php");


class page_output
{
	var $obj_rate_table;
	var $obj_menu_nav;
	var $obj_form;



	function page_output()
	{
		$this->obj_rate_table	= New cdr_rate_table;


		// fetch variables
		$this->obj_rate_table->id = @security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Rate Table Details", "page=services/cdr-rates-view.php&id=". $this->obj_rate_table->id ."");
		$this->obj_menu_nav->add_item("Rate Table Items", "page=services/cdr-rates-items.php&id=". $this->obj_rate_table->id ."");
		$this->obj_menu_nav->add_item("Rate Table Import", "page=services/cdr-rates-import.php&id=". $this->obj_rate_table->id ."", TRUE);
		$this->obj_menu_nav->add_item("Delete Rate Table", "page=services/cdr-rates-delete.php&id=". $this->obj_rate_table->id ."");
	}



	function check_permissions()
	{
		return user_permissions_get("services_write");
	}


	function check_requirements()
	{
		if (!$this->obj_rate_table->verify_id())
		{
			log_write("error", "page_output", "The supplied rate table ID ". $this->obj_rate_table->id ." does not exist");
			return 0;
		}

		return 1;
	}


	function execute()
	{	
		$this->obj_form 			= New form_input;
		$this->obj_form->formname 		= "cdr_rate_import";
		$this->obj_form->language 		= $_SESSION["user"]["lang"];
		$this->obj_form->action 		= "services/cdr-rates-import-process.php";
		$this->obj_form->method 		= "post";
		

		// general upload options
		$structure 				= NULL;
		$structure["fieldname"]			= "cdr_rate_import_file";
		$structure["type"]			= "file";
		$this->obj_form->add_input($structure);

		// hidden fields
		$structure 				= NULL;
		$structure["fieldname"]			= "id_rate_table";
		$structure["type"]			= "hidden";
		$structure["defaultvalue"]		= $this->obj_rate_table->id;
		$this->obj_form->add_input($structure);


		// submit
		$structure 				= NULL;
		$structure["fieldname"]			= "submit";
		$structure["type"]			= "submit";
		$structure["defaultvalue"]		= "import";
		$this->obj_form->add_input($structure);
		

		// define subforms
		$this->obj_form->subforms["cdr_upload"]	= array("cdr_rate_import_file");
		$this->obj_form->subforms["hidden"]	= array("id_rate_table");
		$this->obj_form->subforms["import"]	= array("submit");

	}



	function render_html()
	{
		// title and summary
		print "<h3>CDR IMPORT</h3><br>";
		print "<p>This page allows you to import call pricing from a CSV file. You are given the option to update all records or just the cost pricing once you have supplied the file to import.</p>";

		// display the form
		$this->obj_form->render_form();
	}

}


?>
