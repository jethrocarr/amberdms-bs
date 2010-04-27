<?php
/*
	services/cdr-rates-view.php

	access: services_view
		services_write

	Form to add or update rate table details.
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

		$this->obj_menu_nav->add_item("Rate Table Details", "page=services/cdr-rates-view.php&id=". $this->obj_rate_table->id ."", TRUE);
		$this->obj_menu_nav->add_item("Rate Table Items", "page=services/cdr-rates-items.php&id=". $this->obj_rate_table->id ."");

		if (user_permissions_get("services_write"))
		{
			$this->obj_menu_nav->add_item("Delete Rate Table", "page=services/cdr-rates-delete.php&id=". $this->obj_rate_table->id ."");
		}
	}



	function check_permissions()
	{
		return user_permissions_get("services_view");
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
		// define basic form details
		$this->obj_form = New form_input;
		$this->obj_form->formname = "cdr_rate_table_view";
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



		// hidden fields
		$structure = NULL;
		$structure["fieldname"] 	= "id";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->obj_rate_table->id;
		$this->obj_form->add_input($structure);


		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "submit";
		$this->obj_form->add_input($structure);
		

		// define subforms
		$this->obj_form->subforms["rate_table_view"]	= array("rate_table_name", "rate_table_description", "id_vendor", "id_usage_mode");
		$this->obj_form->subforms["hidden"]		= array("id");

		if (user_permissions_get("services_write"))
		{
			$this->obj_form->subforms["submit"]	= array("submit");
		}
		else
		{
			$this->obj_form->subforms["submit"]	= array("");
		}
		

		// load any data returned due to errors
		if (error_check())
		{
			$this->obj_form->load_data_error();
		}
		else
		{
			$this->obj_rate_table->load_data();

			$this->obj_form->structure["rate_table_name"]["defaultvalue"]		= $this->obj_rate_table->data["rate_table_name"];
			$this->obj_form->structure["rate_table_description"]["defaultvalue"]	= $this->obj_rate_table->data["rate_table_description"];
			$this->obj_form->structure["id_vendor"]["defaultvalue"]			= $this->obj_rate_table->data["id_vendor"];
			$this->obj_form->structure["id_usage_mode"]["defaultvalue"]		= $this->obj_rate_table->data["id_usage_mode"];
		}
	}



	function render_html()
	{
		// title and summary
		print "<h3>RATE TABLE DETAILS</h3><br>";
		print "<p>View/Adjust the basic details of the selected rate table below, or use the nav menu above to select the items page to modify the prefixes and matching.</p>";

		// display the form
		$this->obj_form->render_form();

		if (!user_permissions_get("services_write"))
		{
			format_msgbox("locked", "<p>Sorry, you do not have permission to edit this CDR rate table</p>");
		}
	}



}


?>
