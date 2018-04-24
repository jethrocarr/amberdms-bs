<?php
/*
	services/bundles-service-add.php

	access: services_write

	Adds a new service component to a bundle.
*/

// includes
require("include/services/inc_services.php");


class page_output
{
	var $id;		// bundle ID

	var $obj_bundle;
	var $obj_form;
	var $obj_menu_nav;


	function __construct()
	{
		// service objects
		$this->obj_bundle	= New service_bundle;

		// fetch key service details
		$this->id		= @security_script_input('/^[0-9]*$/', $_GET["id_bundle"]);
		$this->obj_bundle->id	= $this->id;


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
		return user_permissions_get("services_write");
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

		$this->obj_form->action = "services/bundles-service-add-process.php";
		$this->obj_form->method = "post";


		// service dropdown
		$structure = form_helper_prepare_dropdownfromdb("id_service", "SELECT services.id as id, name_service as label FROM `services` LEFT JOIN service_types ON service_types.id = services.typeid WHERE service_types.name != 'bundle' ORDER BY name_service");
		$this->obj_form->add_input($structure);
		


		// hidden fields
		$structure = NULL;
		$structure["fieldname"]		= "id_bundle";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->id;
		$this->obj_form->add_input($structure);
	
		// submit section
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Save Changes";
		$this->obj_form->add_input($structure);
		
		
		// define subforms
		$this->obj_form->subforms["hidden"]			= array("id_bundle");
		$this->obj_form->subforms["bundle_services"]		= array("id_service");
		$this->obj_form->subforms["submit"]			= array("submit");

		$this->obj_form->load_data_error();
	}


	function render_html()
	{
		// Title + Summary
		print "<h3>ADD BUNDLE COMPONENT</h3><br>";

		print "<p>Select the service that you want to add to the bundle below:</p>";

		// display the form
		$this->obj_form->render_form();
	}
	
}


?>
