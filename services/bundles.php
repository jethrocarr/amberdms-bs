<?php
/*
	services/bundles.php

	access: services_view
		services_write

	Shows the components that make up the bundle and allows them to be added,
	edited or removed if the user has write permissions.
*/


require("include/services/inc_services.php");


class page_output
{
	var $id;		// service ID
	var $service_type;	// service type (string)

	var $obj_table;
	var $obj_menu_nav;


	function __construct()
	{
		// fetch key service details
		$this->id		= @security_script_input('/^[0-9]*$/', $_GET["id"]);
		$this->service_type	= sql_get_singlevalue("SELECT service_types.name as value FROM services LEFT JOIN service_types ON service_types.id = services.typeid WHERE services.id='". $this->id ."' LIMIT 1");


		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Service Details", "page=services/view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Service Plan", "page=services/plan.php&id=". $this->id ."");

		if ($this->service_type == "bundle")
		{
			$this->obj_menu_nav->add_item("Bundle Components", "page=services/bundles.php&id=". $this->id ."", TRUE);
		}

		$this->obj_menu_nav->add_item("Service Journal", "page=services/journal.php&id=". $this->id ."");

		if (user_permissions_get("services_write"))
		{
			$this->obj_menu_nav->add_item("Delete Service", "page=services/delete.php&id=". $this->id ."");
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
		$sql_obj->string	= "SELECT id FROM services WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested service (". $this->id .") does not exist - possibly the service has been deleted.");
			return 0;
		}

		unset($sql_obj);


		// verify that this is a bundle service
		if ($this->service_type != "bundle")
		{
			log_write("error", "page_output", "The requested service is not a bundle service.");
			return 0;
		}

		return 1;
	}


	function execute()
	{
		// establish a new table object
		$this->obj_table = New table;

		$this->obj_table->language	= $_SESSION["user"]["lang"];
		$this->obj_table->tablename	= "service_bundle_components";

		// define all the columns and structure
		$this->obj_table->add_column("standard", "name_service", "NONE");
		$this->obj_table->add_column("standard", "service_type", "NONE");
		$this->obj_table->add_column("standard", "description", "NONE");


		// defaults
		$this->obj_table->columns	= array("name_service", "service_type", "description");


		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("services_bundles");
		$this->obj_table->sql_obj->prepare_sql_addfield("id_service", "services_bundles.id_service");
		$this->obj_table->sql_obj->prepare_sql_addfield("id_component", "services_bundles.id");
		$this->obj_table->sql_obj->prepare_sql_addwhere("id_bundle = '". $this->id ."'");

		// run SQL query
		$this->obj_table->generate_sql();
		$this->obj_table->load_data_sql();


		// run through services and fetch options
		for ($i=0; $i < $this->obj_table->data_num_rows; $i++)
		{
			$obj_service			= New service;
			$obj_service->option_type	= "bundle";
			$obj_service->option_type_id	= $this->obj_table->data[$i]["id_component"];
			$obj_service->verify_id_options();

			$obj_service->load_data();
			$obj_service->load_data_options();

			$this->obj_table->data[$i]["name_service"]	= $obj_service->data["name_service"];
			$this->obj_table->data[$i]["service_type"]	= $obj_service->data["typeid_string"];
			$this->obj_table->data[$i]["description"]	= $obj_service->data["description"];
		}
	}


	function render_html()
	{
		// Title + Summary
		print "<h3>BUNDLE COMPONENTS</h3><br>";
		print "<p>This page allows you to view and adjust what component services make up the bundle and the number of units on each service. Note that any changes will only affect the next invoice for customers, it will not adjust any invoices that have already been created.</p>";



		// display table
		if (!$this->obj_table->data_num_rows)
		{
			format_msgbox("important", "<p>You need to add service components to this bundle using the button below to make it useful.</p>");
		}
		else
		{
			if (user_permissions_get("services_write"))
			{
				// details link
				$structure = NULL;
				$structure["id_bundle"]["value"]	= $this->id;
				$structure["id_component"]["column"]	= "id_component";
				$this->obj_table->add_link("tbl_lnk_details", "services/bundles-service-edit.php", $structure);

				// delete link
				$structure = NULL;
				$structure["id_bundle"]["value"]	= $this->id;
				$structure["id_service"]["column"]	= "id_service";
				$structure["full_link"]			= "yes";
				$this->obj_table->add_link("tbl_lnk_delete", "services/bundles-service-delete-process.php", $structure);


			}

			// display the table
			$this->obj_table->render_table_html();
		}

		// add link
		if (user_permissions_get("services_write"))
		{
			print "<p><a class=\"button\" href=\"index.php?page=services/bundles-service-add.php&id_bundle=". $this->id ."\">Add Service Component</a></p>";
		}

	}	

}

?>
