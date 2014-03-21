<?php
/*
	services/traffic-types-add.php

	access: services_write

	Form to add new traffic types to the database.
*/


require("include/services/inc_services.php");
require("include/services/inc_services_traffic.php");


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
		$this->obj_form->formname = "traffic_types_add";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "services/traffic-types-edit-process.php";
		$this->obj_form->method = "post";
		

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "type_name";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"]		= "type_label";
		$structure["type"]		= "input";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]		= "type_description";
		$structure["type"]		= "input";
		$this->obj_form->add_input($structure);



		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "define_traffic_type";
		$this->obj_form->add_input($structure);
		

		// define subforms
		$this->obj_form->subforms["traffic_type_add"]	= array("type_name", "type_label", "type_description");
		$this->obj_form->subforms["submit"]		= array("submit");
		
		// load any data returned due to errors
		$this->obj_form->load_data_error();
	}



	function render_html()
	{
		// title and summary
		print "<h3>DEFINE TRAFFIC TYPE</h3><br>";
		print "<p>Use this page to define a new type of data traffic.</p>";

		// display the form
		$this->obj_form->render_form();
	}



}


?>
