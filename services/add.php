<?php
/*
	services/add.php
	
	access: services_write

	Form to add a new service to the database.
*/

// include form functions
require("include/services/inc_services_forms.php");


class page_output
{
	var $obj_serviceform;

	function page_output()
	{
		$this->obj_serviceform			= New services_form_details;
		$this->obj_serviceform->serviceid	= 0;
		$this->obj_serviceform->mode		= "add";
	}


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
		return $this->obj_serviceform->execute();
	}

	function render_html()
	{
		// Title + Summary
		print "<h3>ADD SERVICE</h3><br>";
		print "<p>This page allows you to add a new service.</p>";


		// render form
		return $this->obj_serviceform->render_html();
	}

}


?>
