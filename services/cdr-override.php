<?php
/*
	services/cdr-override.php

	access: services_view
		services_write

	Shows the rates for the selected CDR call and allows them to be overridden.
*/


require("include/services/inc_services.php");
require("include/services/inc_services_cdr.php");


class page_output
{
	var $id;		// service ID
	var $service_type;	// service type (string)

	var $obj_cdr_rate_table;
	var $obj_table;
	var $obj_menu_nav;


	function page_output()
	{
		// init
		$this->obj_cdr_rate_table	= New cdr_rate_table_rates_override;


		// fetch key service details
		$this->id		= @security_script_input('/^[0-9]*$/', $_GET["id"]);
		$this->service_type	= sql_get_singlevalue("SELECT service_types.name as value FROM services LEFT JOIN service_types ON service_types.id = services.typeid WHERE services.id='". $this->id ."' LIMIT 1");

		$this->obj_cdr_rate_table->option_type		= "service";
		$this->obj_cdr_rate_table->option_type_id	= $this->id;


		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Service Details", "page=services/view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Service Plan", "page=services/plan.php&id=". $this->id ."");

		if ($this->service_type == "bundle")
		{
			$this->obj_menu_nav->add_item("Bundle Components", "page=services/bundles.php&id=". $this->id ."");
		}

		if ($this->service_type == ("phone_single" || "phone_trunk" || "phone_tollfree"))
		{
			$this->obj_menu_nav->add_item("Call Rate Override", "page=services/cdr-override.php&id=". $this->id ."", TRUE);
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
		$sql_obj->string	= "SELECT id, id_rate_table FROM services WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$sql_obj->fetch_array();
			
			// verify the rate is valid
			if ($sql_obj->data[0]["id_rate_table"])
			{
				$this->obj_cdr_rate_table->id	= $sql_obj->data[0]["id_rate_table"];

				if (!$this->obj_cdr_rate_table->verify_id())
				{
					log_write("error", "page_output", "The requested CDR rate table is invalid, there may be some problems with the information in the database.");
					return 0;
				}
			}
			else
			{
				log_write("error", "page_output", "You have yet to set a CDR Rate Table for this service to use - please do so using the plan page before attempting to override the rates");
				return 0;
			}
		}
		else
		{
			log_write("error", "page_output", "The requested service (". $this->id .") does not exist - possibly the service has been deleted.");
			return 0;
		}

		unset($sql_obj);


		// verify that this is a phone service
		if ($this->service_type != ("phone_single" || "phone_trunk" || "phone_tollfree"))
		{
			log_write("error", "page_output", "The requested service is not a phone service.");
			return 0;
		}
		


		return 1;
	}


	function execute()
	{
		/*
			Load CDR Data
		*/

		// get the rate table info for display purposes
		$this->obj_cdr_rate_table->load_data();

		// get the values
		$this->obj_cdr_rate_table->load_data_rate_all();
		$this->obj_cdr_rate_table->load_data_rate_all_override();

		// get all the overrides in use



		/*
			Draw Display Table
		*/


		// establish a new table object
		$this->obj_table = New table;

		$this->obj_table->language	= $_SESSION["user"]["lang"];
		$this->obj_table->tablename	= "service_cdr_override";

		// define all the columns and structure
		$this->obj_table->add_column("standard", "rate_prefix", "");
		$this->obj_table->add_column("standard", "rate_description", "");
		$this->obj_table->add_column("money", "rate_price_sale", "");
		$this->obj_table->add_column("money", "rate_price_cost", "");
		$this->obj_table->add_column("standard", "rate_override", "");


		// defaults
		$this->obj_table->columns		= array("rate_prefix", "rate_description", "rate_price_sale", "rate_price_cost", "rate_override");

		// custom-load the service rate data
		$this->obj_table->data_num_rows		= count(array_keys($this->obj_cdr_rate_table->data["rates"]));


		$i = 0;
		foreach (array_keys($this->obj_cdr_rate_table->data["rates"]) as $rate_prefix)
		{
			$this->obj_table->data[$i]["id_rate"]		= $this->obj_cdr_rate_table->data["rates"][ $rate_prefix ]["id_rate"];
			$this->obj_table->data[$i]["id_rate_override"]	= $this->obj_cdr_rate_table->data["rates"][ $rate_prefix ]["id_rate_override"];

			$this->obj_table->data[$i]["rate_prefix"]	= $this->obj_cdr_rate_table->data["rates"][ $rate_prefix ]["rate_prefix"];
			$this->obj_table->data[$i]["rate_description"]	= $this->obj_cdr_rate_table->data["rates"][ $rate_prefix ]["rate_description"];
			$this->obj_table->data[$i]["rate_price_sale"]	= $this->obj_cdr_rate_table->data["rates"][ $rate_prefix ]["rate_price_sale"];
			$this->obj_table->data[$i]["rate_price_cost"]	= $this->obj_cdr_rate_table->data["rates"][ $rate_prefix ]["rate_price_cost"];

			if ($this->obj_cdr_rate_table->data["rates"][ $rate_prefix ]["id_rate_override"])
			{
				$this->obj_table->data[$i]["rate_override"] = "<span class=\"table_highlight_important\">SERVICE OVERRIDE</span>";
			}

			$i++;
		}
	}


	function render_html()
	{
		// Title + Summary
		print "<h3>CDR RATE OVERRIDE</h3><br>";
		print "<p>This page allows you to view the rates in the selected rate table for this service, as well as allowing them to be overridden to offer service-specific pricing variations.</p>";


		if (user_permissions_get("services_write"))
		{
			// override link
			$structure = NULL;
			$structure["logic"]["if_not"]["column"]		= "id_rate_override";

			$structure["id_service"]["value"]		= $this->id;
			$structure["id_rate"]["column"]			= "id_rate";
			$structure["id_rate_override"]["column"]	= "id_rate_override";

			$this->obj_table->add_link("tbl_lnk_override", "services/cdr-override-edit.php", $structure);


			// adjust link
			$structure = NULL;
			$structure["logic"]["if"]["column"]		= "id_rate_override";

			$structure["id_service"]["value"]		= $this->id;
			$structure["id_rate"]["column"]			= "id_rate";
			$structure["id_rate_override"]["column"]	= "id_rate_override";

			$this->obj_table->add_link("tbl_lnk_adjust_override", "services/cdr-override-edit.php", $structure);


			// delete link
			$structure = NULL;
			$structure["logic"]["if"]["column"]		= "id_rate_override";
			$structure["full_link"]				= "yes";

			$structure["id_service"]["value"]		= $this->id;
			$structure["id_rate"]["column"]			= "id_rate";
			$structure["id_rate_override"]["column"]	= "id_rate_override";

			$this->obj_table->add_link("tbl_lnk_delete_override", "services/cdr-override-delete-process.php", $structure);
		}

		// display the table
		$this->obj_table->render_table_html();


		// add link
		if (user_permissions_get("services_write"))
		{
			print "<p><a class=\"button\" href=\"index.php?page=services/cdr-override-edit.php&id_service=". $this->id ."\">Add Custom Prefix Rate</a></p>";
		}

	}	

}

?>
