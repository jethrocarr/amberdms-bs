<?php
/*
	customers/services-ddi.php

	access: customers_view
		customers_write

	Shows the DDIs for the selected customer and allows them to be overridden.
*/


require("include/customers/inc_customers.php");
require("include/services/inc_services.php");
require("include/services/inc_services_cdr.php");


class page_output
{
	var $obj_customer;
	var $obj_;
	
	var $obj_menu_nav;
	var $obj_form;

	

	function page_output()
	{
		$this->obj_customer				= New customer_services;
		$this->obj_cdr_rate_table			= New cdr_rate_table_rates_override;


		// fetch variables
		$this->obj_customer->id				= @security_script_input('/^[0-9]*$/', $_GET["id_customer"]);
		$this->obj_customer->id_service_customer	= @security_script_input('/^[0-9]*$/', $_GET["id_service_customer"]);

		// load service data
		$this->obj_customer->load_data_service();

		// set CDR data
		$this->obj_cdr_rate_table->option_type		= "customer";
		$this->obj_cdr_rate_table->option_type_id	= $this->obj_customer->id_service_customer;


		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;
		
		$this->obj_menu_nav->add_item("Return to Customer Services Page", "page=customers/services.php&id=". $this->obj_customer->id ."");
		$this->obj_menu_nav->add_item("Service Details", "page=customers/service-edit.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $this->obj_customer->id_service_customer ."");
		$this->obj_menu_nav->add_item("Service History", "page=customers/service-history.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $this->obj_customer->id_service_customer ."");

		if ($this->obj_customer->obj_service->data["typeid_string"] == ("phone_single" || "phone_tollfree" || "phone_trunk"))
		{
			$this->obj_menu_nav->add_item("CDR Override", "page=customers/service-cdr-override.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $this->obj_customer->id_service_customer ."", TRUE);
		}
	
		if (user_permissions_get("customers_write"))
		{
			$this->obj_menu_nav->add_item("Service Delete", "page=customers/service-delete.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $this->obj_customer->id_service_customer ."");
		}
	}



	function check_permissions()
	{
		return user_permissions_get("customers_view");
	}



	function check_requirements()
	{
		// verify that customer exists
		if (!$this->obj_customer->verify_id())
		{
			log_write("error", "page_output", "The requested customer (". $this->obj_customer->id .") does not exist - possibly the customer has been deleted.");
			return 0;
		}


		// verify that the service-customer entry exists
		if ($this->ob_customer->id_service_customer)
		{
			if (!$this->obj_customer->verify_id_service_customer())
			{
				log_write("error", "page_output", "The requested service (". $this->obj_customer->id_service_customer .") was not found and/or does not match the selected customer");
				return 0;
			}
		}


		// verify the options IDs
		if (!$this->obj_cdr_rate_table->verify_id_override())
		{
			log_write("error", "page_output", "The requested service does not have a valid CDR rate table");
		}

		// verify that this is a phone service
		if ($this->obj_customer->obj_service->data["typeid_string"] != ("phone_single" || "phone_trunk" || "phone_tollfree"))
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

			if ($this->obj_cdr_rate_table->data["rates"][ $rate_prefix ]["option_type"] == "service")
			{
				$this->obj_table->data[$i]["rate_override"] = "<span class=\"table_highlight_important\">SERVICE OVERRIDE</span>";
			}

			if ($this->obj_cdr_rate_table->data["rates"][ $rate_prefix ]["option_type"] == "customer")
			{
				$this->obj_table->data[$i]["rate_override"]		= "<span class=\"table_highlight_info\">CUSTOMER OVERRIDE</span>";
				$this->obj_table->data[$i]["rate_override_customer"]	= 1;
			}

			$i++;
		}
	}


	function render_html()
	{
		// Title + Summary
		print "<h3>CUSTOMER CDR RATE OVERRIDE</h3><br>";
		print "<p>This page allows you to view the rates set for call costs for the selected customer as well as allowing them to be overridden with customised prices.</p>";

		// service status summary
		$this->obj_customer->service_render_summarybox();


		if (user_permissions_get("customers_write"))
		{
			// override link
			$structure = NULL;
			$structure["logic"]["if_not"]["column"]		= "rate_override_customer";

			$structure["id_customer"]["value"]		= $this->obj_customer->id;
			$structure["id_service_customer"]["value"]	= $this->obj_customer->id_service_customer;
			$structure["id_rate"]["column"]			= "id_rate";
			$structure["id_rate_override"]["column"]	= "id_rate_override";

			$this->obj_table->add_link("tbl_lnk_override", "customers/service-cdr-override-edit.php", $structure);


			// adjust link
			$structure = NULL;
			$structure["logic"]["if"]["column"]		= "rate_override_customer";

			$structure["id_customer"]["value"]		= $this->obj_customer->id;
			$structure["id_service_customer"]["value"]	= $this->obj_customer->id_service_customer;
			$structure["id_rate"]["column"]			= "id_rate";
			$structure["id_rate_override"]["column"]	= "id_rate_override";

			$this->obj_table->add_link("tbl_lnk_adjust_override", "customers/service-cdr-override-edit.php", $structure);


			// delete link
			$structure = NULL;
			$structure["logic"]["if"]["column"]		= "rate_override_customer";
			$structure["full_link"]				= "yes";

			$structure["id_customer"]["value"]		= $this->obj_customer->id;
			$structure["id_service_customer"]["value"]	= $this->obj_customer->id_service_customer;
			$structure["id_rate"]["column"]			= "id_rate";
			$structure["id_rate_override"]["column"]	= "id_rate_override";

			$this->obj_table->add_link("tbl_lnk_delete_override", "customers/service-cdr-override-delete-process.php", $structure);
		}

		// display the table
		$this->obj_table->render_table_html();


		// add link
		if (user_permissions_get("customers_write"))
		{
			print "<p><a class=\"button\" href=\"index.php?page=customers/service-cdr-override-edit.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $this->obj_customer->id_service_customer ."\">Add Custom Prefix Rate</a></p>";
		}

	}	

}

?>
