<?php
/*
	services/plan.php

	access: services_view
		services_write
	
	Displays the selected service plan details and allows these details
	to be updated.
*/


// include form functions
require("include/services/inc_services_forms.php");


class page_output
{
	var $obj_serviceform;

	function page_output()
	{
		$this->obj_serviceform			= New services_form_plan;
		$this->obj_serviceform->serviceid	= @security_script_input('/^[0-9]*$/', $_GET["id"]);


		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Service Details", "page=services/view.php&id=". $this->obj_serviceform->serviceid ."");
		$this->obj_menu_nav->add_item("Service Plan", "page=services/plan.php&id=". $this->obj_serviceform->serviceid ."", TRUE);

		if (sql_get_singlevalue("SELECT service_types.name as value FROM services LEFT JOIN service_types ON service_types.id = services.typeid WHERE services.id='". $this->obj_serviceform->serviceid ."' LIMIT 1") == "bundle")
		{
			$this->obj_menu_nav->add_item("Bundle Components", "page=services/bundles.php&id=". $this->obj_serviceform->serviceid ."");
		}

		if (sql_get_singlevalue("SELECT service_types.name as value FROM services LEFT JOIN service_types ON service_types.id = services.typeid WHERE services.id='". $this->obj_serviceform->serviceid ."' LIMIT 1") == ("phone_single" || "phone_tollfree" || "phone_trunk"))
		{
			$this->obj_menu_nav->add_item("Call Rate Override", "page=services/cdr-override.php&id=". $this->obj_serviceform->serviceid ."");
		}

		$this->obj_menu_nav->add_item("Service Journal", "page=services/journal.php&id=". $this->obj_serviceform->serviceid ."");

		if (user_permissions_get("services_write"))
		{
			$this->obj_menu_nav->add_item("Delete Service", "page=services/delete.php&id=". $this->obj_serviceform->serviceid ."");
		}
	}


	function check_permissions()
	{
		return user_permissions_get("services_view");
	}


	function check_requirements()
	{
		// verify that the service exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM services WHERE id='". $this->obj_serviceform->serviceid ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested service (". $this->obj_serviceform->serviceid .") does not exist - possibly the service has been deleted.");
			return 0;
		}

		unset($sql_obj);

		return 1;
	}


	function execute()
	{
		return $this->obj_serviceform->execute();
	}

	function render_html()
	{
		// Title + Summary
		print "<h3>SERVICE PLAN CONFIGURATION</h3><br>";
		print "<p>This page allows you to view and adjust the service. Note that any changes will only affect the next invoice for customers, it will not adjust any invoices that have already been created.</p>";


		// render form
		return $this->obj_serviceform->render_html();
	}

}

?>
