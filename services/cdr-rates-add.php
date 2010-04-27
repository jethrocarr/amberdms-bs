<?php
/*
	services/cdr-rates-add.php

	access: services_write

	Form to add new CDR rate tables to the database.
*/


require("include/services/inc_services.php");
require("include/services/inc_services_cdr.php");


class page_output
{
	var $obj_form;


	function check_permissions()
	{
		return user_permissions_get("services_write");
	}

	function check_requirements()
	{
		// nothing todo
		return 1;
	}


	function execute()
	{
		// define basic form details
		$this->obj_form = New form_input;
		$this->obj_form->formname = "cdr_rate_table_add";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "services/cdr-rates-edit-process.php";
		$this->obj_form->method = "post";
		

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "rate_table_name";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"]		= "rate_table_description";
		$structure["type"]		= "input";
		$this->obj_form->add_input($structure);

		$structure = form_helper_prepare_dropdownfromdb("id_vendor", "SELECT id, code_vendor as label, name_vendor as label1 FROM vendors ORDER BY name_vendor");
		$structure["options"]["req"]	= "yes";
		$structure["options"]["width"]	= "600";
		$this->obj_form->add_input($structure);

		$structure = form_helper_prepare_dropdownfromdb("id_usage_mode", "SELECT id, description as label FROM cdr_rate_usage_modes ORDER BY name");
		$structure["options"]["req"]	= "yes";
		$structure["options"]["width"]	= "600";
		$this->obj_form->add_input($structure);


		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "create_rate_table";
		$this->obj_form->add_input($structure);
		

		// define subforms
		$this->obj_form->subforms["rate_table_add"]	= array("rate_table_name", "rate_table_description", "id_vendor", "id_usage_mode");
		$this->obj_form->subforms["submit"]		= array("submit");
		
		// load any data returned due to errors
		$this->obj_form->load_data_error();
	}



	function render_html()
	{
		// title and summary
		print "<h3>CREATE RATE TABLE</h3><br>";
		print "<p>Use this page to add a new CDR rate table to the billing system.</p>";

		// display the form
		$this->obj_form->render_form();
	}



}


?>
