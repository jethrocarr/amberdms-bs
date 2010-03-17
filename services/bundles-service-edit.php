<?php
/*
	services/bundles-service-edit.php

	access: services_view
		services_view

	Shows details and allows adjustment of service component in bundle.
*/

// includes
require("include/services/inc_services.php");


class page_output
{
	var $id;		// bundle ID
	var $id_component;	// ID of component (bundle-service mapping)

	var $obj_bundle;	
	var $obj_component;

	var $obj_form;
	var $obj_menu_nav;


	function page_output()
	{
		// service objects
		$this->obj_bundle	= New service_bundle;
		$this->obj_component	= New service;

		// fetch key service details
		$this->id		= @security_script_input('/^[0-9]*$/', $_GET["id_bundle"]);
		$this->id_component	= @security_script_input('/^[0-9]*$/', $_GET["id_component"]);

		$this->obj_bundle->id			= $this->id;
		$this->obj_component->option_type	= "bundle";
		$this->obj_component->option_type_id	= $this->id_component;

		$this->obj_component->verify_id_options();
		$this->obj_component->load_data();
		$this->obj_component->load_data_options();


		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Service Details", "page=services/view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Service Plan", "page=services/plan.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Bundle Components", "page=services/bundles.php&id=". $this->id ."", TRUE);
		$this->obj_menu_nav->add_item("Service Journal", "page=services/journal.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Delete Service", "page=services/delete.php&id=". $this->id ."");
	}


	function check_permissions()
	{
		return user_permissions_get("services_view");
	}


	function check_requirements()
	{
		// verify that the service exists and is a bundle

		if (!$this->obj_bundle->verify_is_bundle())
		{
			log_write("error", "page_output", "The requested service (". $this->id .") does not exist or is not a bundle service");
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
		$this->obj_form->formname = "services_bundles_service";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "services/bundles-service-edit-process.php";
		$this->obj_form->method = "post";


		// bundle details
		$structure = NULL;
		$structure["fieldname"]		= "name_bundle";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]		= "description_bundle";
		$structure["type"]		= "text";



		// service details
		$structure = NULL;
		$structure["fieldname"]		= "name_service";
		$structure["type"]		= "input";
		$structure["defaultvalue"]	= $this->obj_component->data["name_service"];
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]		= "service_type";
		$structure["type"]		= "text";
		$structure["defaultvalue"]	= $this->obj_component->data["typeid_string"];
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]		= "description_service";
		$structure["type"]		= "input";
		$structure["defaultvalue"]	= $this->obj_component->data["description"];
		$this->obj_form->add_input($structure);
		


		// hidden fields
		$structure = NULL;
		$structure["fieldname"]		= "id_bundle";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->id;
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]		= "id_component";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->id_component;
		$this->obj_form->add_input($structure);


		// submit section
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Save Changes";
		$this->obj_form->add_input($structure);
		
		
		// define subforms
		$this->obj_form->subforms["bundle_details"]		= array("name_bundle", "description_bundle");
		$this->obj_form->subforms["service_details"]		= array("service_type", "name_service", "description_service");
		$this->obj_form->subforms["hidden"]			= array("id_bundle", "id_component");
		$this->obj_form->subforms["submit"]			= array("submit");



		/*
			Load Data
		*/
		if (error_check())
		{
			// fetch error data
			$this->obj_form->load_data_error();
		}
		
		
		// fetch service bundle data
		$this->obj_form->sql_query = "SELECT
						services.name_service as name_bundle,
						services.description as description_bundle
						FROM services
						WHERE id = '". $this->id ."'";

		$this->obj_form->load_data_sql();
	}


	function render_html()
	{
		// Title + Summary
		print "<h3>BUNDLE COMPONENT DETAILS</h3><br>";
		print "<p>The following information details the service belonging to the bundle and provides various options that can be adjusted for some services, such as the service name that appears on the invoice or other fields.</p>";


		// display the form
		$this->obj_form->render_form();
	}
	
}


?>
