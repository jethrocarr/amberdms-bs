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

		if (in_array($this->obj_customer->obj_service->data["typeid_string"], array("phone_single", "phone_tollfree", "phone_trunk")))
		{
			$this->obj_menu_nav->add_item("CDR Override", "page=customers/service-cdr-override.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $this->obj_customer->id_service_customer ."");
		}
		
		if ($this->obj_customer->obj_service->data["typeid_string"] == "phone_trunk")
		{
			$this->obj_menu_nav->add_item("DDI Configuration", "page=customers/service-ddi.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $this->obj_customer->id_service_customer ."", TRUE);
		}

		if ($this->obj_customer->obj_service->data["typeid_string"] == "data_traffic")
		{
			$this->obj_menu_nav->add_item("IPv4 Addresses", "page=customers/service-ipv4.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $this->obj_customer->id_service_customer ."");
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
		if ($this->obj_customer->id_service_customer)
		{
			if (!$this->obj_customer->verify_id_service_customer())
			{
				log_write("error", "page_output", "The requested service (". $this->obj_customer->id_service_customer .") was not found and/or does not match the selected customer");
				return 0;
			}
		}

		// verify that this is a phone service
		if ($this->obj_customer->obj_service->data["typeid_string"] != "phone_trunk")
		{
			log_write("error", "page_output", "The requested service is not a phone_trunk service.");
			return 0;
		}


		return 1;
	}



	function execute()
	{
		/*
			Define DDI table
		*/


		// establish a new table object
		$this->obj_table = New table;

		$this->obj_table->language	= $_SESSION["user"]["lang"];
		$this->obj_table->tablename	= "service_ddi";

		// define all the columns and structure
		$this->obj_table->add_column("standard", "ddi_start", "");
		$this->obj_table->add_column("standard", "ddi_finish", "");
		$this->obj_table->add_column("standard", "local_prefix", "");
		$this->obj_table->add_column("standard", "description", "");

		// defaults
		$this->obj_table->columns		= array("ddi_start", "ddi_finish", "local_prefix", "description");

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("services_customers_ddi");
		$this->obj_table->sql_obj->prepare_sql_addfield("id_ddi", "id");
		$this->obj_table->sql_obj->prepare_sql_addwhere("id_service_customer='". $this->obj_customer->id_service_customer ."'");
		

		// load settings from options form
		//$this->obj_table->load_options_form();

		// fetch all the customer information
		$this->obj_table->generate_sql();
		$this->obj_table->load_data_sql();

	}


	function render_html()
	{
		// Title + Summary
		print "<h3>CUSTOMER SERVICE DDI ASSIGNMENT</h3><br>";
		print "<p>This page allows you to assign DDIs to a trunk phone service.</p>";

		// service status summary
		$this->obj_customer->service_render_summarybox();


		if (user_permissions_get("customers_write"))
		{
			// details/edit link
			$structure = NULL;
			$structure["id_customer"]["value"]		= $this->obj_customer->id;
			$structure["id_service_customer"]["value"]	= $this->obj_customer->id_service_customer;
			$structure["id_ddi"]["column"]			= "id_ddi";

			$this->obj_table->add_link("tbl_lnk_details", "customers/service-ddi-edit.php", $structure);


			// delete link
			$structure = NULL;
			$structure["full_link"]				= "yes";

			$structure["id_customer"]["value"]		= $this->obj_customer->id;
			$structure["id_service_customer"]["value"]	= $this->obj_customer->id_service_customer;
			$structure["id_ddi"]["column"]			= "id_ddi";

			$this->obj_table->add_link("tbl_lnk_delete", "customers/service-ddi-delete-process.php", $structure);
		}

		// display the table
		if ($this->obj_table->data_num_rows)
		{
			$this->obj_table->render_table_html();
		}
		else
		{
			format_msgbox("important", "<p>There are no DDIs defined for this customer/service, use the button below to begin adding DDIs.");
		}


		// add link
		if (user_permissions_get("customers_write"))
		{
			print "<p><a class=\"button\" href=\"index.php?page=customers/service-ddi-edit.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $this->obj_customer->id_service_customer ."\">Assign new DDI to customer</a></p>";
		}

	}	

}

?>
