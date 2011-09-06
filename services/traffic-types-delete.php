<?php
/*
	services/traffic-types-delete.php

	access: services_write

	Deletes the selected traffic type.
*/


require("include/services/inc_services.php");
require("include/services/inc_services_traffic.php");


class page_output
{
	var $obj_form;
	var $obj_traffic_type;
	var $locked;

	function page_output()
	{
		$this->obj_traffic_type	= New traffic_types;


		// fetch variables
		$this->obj_traffic_type->id = @security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Traffic Type Details", "page=services/traffic-types-view.php&id=". $this->obj_traffic_type->id ."");
		$this->obj_menu_nav->add_item("Delete Traffic Type", "page=services/traffic-types-delete.php&id=". $this->obj_traffic_type->id ."", TRUE);
	}



	function check_permissions()
	{
		return user_permissions_get("services_view");
	}


	function check_requirements()
	{
		if (!$this->obj_traffic_type->verify_id())
		{
			log_write("error", "page_output", "The supplied traffic type ID ". $this->obj_traffic_type->id ." does not exist");
			return 0;
		}


		// check if the page is locked or not
		$this->locked = $this->obj_traffic_type->check_delete_lock();

		return 1;
	}


	function execute()
	{
		// define basic form details
		$this->obj_form = New form_input;
		$this->obj_form->formname = "traffic_types_delete";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "services/traffic-types-delete-process.php";
		$this->obj_form->method = "post";
		

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "type_name";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"]		= "type_label";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]		= "type_description";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);


		// hidden fields
		$structure = NULL;
		$structure["fieldname"] 	= "id";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->obj_traffic_type->id;
		$this->obj_form->add_input($structure);


		// confirm delete
		$structure = NULL;
		$structure["fieldname"] 	= "delete_confirm";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "Yes, I wish to delete this traffic type and realise that once deleted the data can not be recovered.";
		$this->obj_form->add_input($structure);

		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "delete";
		$this->obj_form->add_input($structure);
		

		// define subforms
		$this->obj_form->subforms["traffic_type_delete"]	= array("type_name", "type_label", "type_description");
		$this->obj_form->subforms["hidden"]			= array("id");
	
		if (!$this->locked)
		{
			$this->obj_form->subforms["submit"]	= array("delete_confirm", "submit");
		}
		else
		{
			$this->obj_form->subforms["submit"]	= array();
		}
		


		// load data
		if (error_check())
		{
			$this->obj_form->load_data_error();
		}
		else
		{
			$this->obj_traffic_type->load_data();

			$this->obj_form->structure["type_name"]["defaultvalue"]		= $this->obj_traffic_type->data["type_name"];
			$this->obj_form->structure["type_label"]["defaultvalue"]	= $this->obj_traffic_type->data["type_label"];
			$this->obj_form->structure["type_description"]["defaultvalue"]	= $this->obj_traffic_type->data["type_description"];
		}

	}



	function render_html()
	{
		// title and summary
		print "<h3>DELETE TRAFFIC TYPE</h3><br>";
		print "<p>Use this page to delete a defined traffic type - note that you can only delete traffic types that are not in use, and deleting this type will not impact any data records.</p>";

		// display the form
		$this->obj_form->render_form();

		if ($this->locked)
		{
			format_msgbox("locked", "<p>Sorry, the traffic type can not be deleted as it is used by some services.</p>");
		}
	}



}


?>
