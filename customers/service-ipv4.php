<?php
/*
	customers/services-ipv4.php

	access: customers_view
		customers_write


	Displays the IPv4 addresses configured against the selected service and allows them to be changed.

	> Note that this only applies to data_traffic service types <
*/


require("include/customers/inc_customers.php");
require("include/services/inc_services.php");
require("include/services/inc_services_traffic.php");


class page_output
{
	var $obj_customer;
	var $obj_;
	
	var $obj_menu_nav;
	var $obj_form;

	

	function page_output()
	{
		$this->obj_customer				= New customer_services;


		// fetch variables
		$this->obj_customer->id				= @security_script_input('/^[0-9]*$/', $_GET["id_customer"]);
		$this->obj_customer->id_service_customer	= @security_script_input('/^[0-9]*$/', $_GET["id_service_customer"]);

		// load service data
		$this->obj_customer->load_data_service();


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
			$this->obj_menu_nav->add_item("DDI Configuration", "page=customers/service-ddi.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $this->obj_customer->id_service_customer ."");
		}

		if ($this->obj_customer->obj_service->data["typeid_string"] == "data_traffic")
		{
			$this->obj_menu_nav->add_item("IPv4 Addresses", "page=customers/service-ipv4.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $this->obj_customer->id_service_customer ."", TRUE);
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

		// verify that this is a data_traffic service
		if ($this->obj_customer->obj_service->data["typeid_string"] != "data_traffic")
		{
			log_write("error", "page_output", "The requested service is not a data_traffic service.");
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
		$this->obj_table->tablename	= "service_ipv4";

		// define all the columns and structure
		$this->obj_table->add_column("standard", "ipv4_address", "");
		$this->obj_table->add_column("standard", "ipv4_cidr", "");
		$this->obj_table->add_column("standard", "description", "");

		// defaults
		$this->obj_table->columns		= array("ipv4_address", "ipv4_cidr", "description");

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("services_customers_ipv4");
		$this->obj_table->sql_obj->prepare_sql_addfield("id_ipv4", "id");
		$this->obj_table->sql_obj->prepare_sql_addwhere("id_service_customer='". $this->obj_customer->id_service_customer ."'");
		

		// load settings from options form
		$this->obj_table->load_options_form();

		// fetch all the customer information
		$this->obj_table->generate_sql();
		$this->obj_table->load_data_sql();

	}


	function render_html()
	{
		// Title + Summary
		print "<h3>CUSTOMER SERVICE IPv4 ASSIGNMENT</h3><br>";
		print "<p>This page allows you to assign IPv4 addresses to a data traffic service in order to match to billing records.</p>";

		// service status summary
		$this->obj_customer->service_render_summarybox();


		if (user_permissions_get("customers_write"))
		{
			// details/edit link
			$structure = NULL;
			$structure["id_customer"]["value"]		= $this->obj_customer->id;
			$structure["id_service_customer"]["value"]	= $this->obj_customer->id_service_customer;
			$structure["id_ipv4"]["column"]			= "id_ipv4";

			$this->obj_table->add_link("tbl_lnk_details", "customers/service-ipv4-edit.php", $structure);


			// delete link
			$structure = NULL;
			$structure["full_link"]				= "yes";

			$structure["id_customer"]["value"]		= $this->obj_customer->id;
			$structure["id_service_customer"]["value"]	= $this->obj_customer->id_service_customer;
			$structure["id_ipv4"]["column"]			= "id_ipv4";

			$this->obj_table->add_link("tbl_lnk_delete", "customers/service-ipv4-delete-process.php", $structure);
		}

		// display the table
		if ($this->obj_table->data_num_rows)
		{
			$this->obj_table->render_table_html();
		}
		else
		{
			format_msgbox("important", "<p>There are no IPv4 addresses defined for this customer/service, use the button below to add an address or subnet.");
		}


		// add link
		if (user_permissions_get("customers_write"))
		{
			print "<p><a class=\"button\" href=\"index.php?page=customers/service-ipv4-edit.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $this->obj_customer->id_service_customer ."\">Assign new IPv4 address to customer</a></p>";
		}

	}	

}

?>
