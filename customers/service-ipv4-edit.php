<?php
/*
	customers/service-ipv4-edit.php

	access: customers_view
		customers_write

	Allows the selected IPv4 address to be updated or a new IPv4 address to be added.
*/


require("include/customers/inc_customers.php");
require("include/services/inc_services.php");
require("include/services/inc_services_traffic.php");


class page_output
{
	var $obj_customer;
	var $obj_ipv4;

	var $obj_menu_nav;
	var $obj_form;

	

	function page_output()
	{
		$this->obj_customer				= New customer_services;
		$this->obj_ipv4					= New traffic_customer_service_ipv4;



		// fetch variables
		$this->obj_customer->id				= @security_script_input('/^[0-9]*$/', $_GET["id_customer"]);
		$this->obj_customer->id_service_customer	= @security_script_input('/^[0-9]*$/', $_GET["id_service_customer"]);
		$this->obj_ipv4->id				= @security_script_input('/^[0-9]*$/', $_GET["id_ipv4"]);

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
			$this->obj_menu_nav->add_item("DDI Configuration", "page=customers/service-ipv4.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $this->obj_customer->id_service_customer ."", TRUE);
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


		// verify that the selected IPv4 address is valid (if provided)
		if ($this->obj_ipv4->id)
		{
			if (!$this->obj_ipv4->verify_id())
			{
				log_write("error", "page_output", "The supplied IPv4 address is not valid");
				return 0;
			}
		}


		return 1;
	}





	function execute()
	{
		// load data
		$this->obj_customer->load_data();


		// define basic form details
		$this->obj_form = New form_input;
		$this->obj_form->formname = "service_ipv4_edit";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "customers/service-ipv4-edit-process.php";
		$this->obj_form->method = "post";



		// service details
		$structure = NULL;
		$structure["fieldname"] 	= "name_customer";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "service_name";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);
	

		// DDI Configuration
		$structure = NULL;
		$structure["fieldname"] 		= "ipv4_address";
		$structure["type"]			= "input";
		$structure["options"]["req"]		= "yes";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"]			= "ipv4_cidr";
		$structure["type"]			= "input";
		$structure["options"]["req"]		= "yes";
		$structure["options"]["max_length"]	= "2";
		$structure["options"]["width"]		= "40";
		$structure["defaultvalue"]		= "32";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]		= "description";
		$structure["type"]		= "textarea";
		$this->obj_form->add_input($structure);



		// hidden
		$structure = NULL;
		$structure["fieldname"] 	= "id_customer";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->obj_customer->id;
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "id_service_customer";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->obj_customer->id_service_customer;
		$this->obj_form->add_input($structure);


		$structure = NULL;
		$structure["fieldname"] 	= "id_ipv4";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->obj_ipv4->id;
		$this->obj_form->add_input($structure);

		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "submit";
		$this->obj_form->add_input($structure);
		

		// define subforms
		$this->obj_form->subforms["service_details"]	= array("name_customer", "service_name");
		$this->obj_form->subforms["ipv4_details"]	= array("ipv4_address", "ipv4_cidr", "description");
		$this->obj_form->subforms["hidden"]		= array("id_customer", "id_service_customer", "id_ipv4");
		$this->obj_form->subforms["submit"]		= array("submit");
		

		// load any data returned due to errors
		if (error_check())
		{
			$this->obj_form->load_data_error();
		}
		else
		{
			// set values
			$this->obj_form->structure["name_customer"]["defaultvalue"]		= $this->obj_customer->data["name_customer"];
			$this->obj_form->structure["service_name"]["defaultvalue"]		= $this->obj_customer->obj_service->data["name_service"];


			// load known values
			if ($this->obj_ipv4->id)
			{
				$this->obj_ipv4->load_data();
				
				$this->obj_form->structure["ipv4_address"]["defaultvalue"]		= $this->obj_ipv4->data["ipv4_address"];
				$this->obj_form->structure["ipv4_cidr"]["defaultvalue"]			= $this->obj_ipv4->data["ipv4_cidr"];
				$this->obj_form->structure["description"]["defaultvalue"]		= $this->obj_ipv4->data["description"];
			}
		}
	}



	function render_html()
	{
		// title and summary
		if ($this->obj_ipv4->id)
		{
			print "<h3>ADJUST IPv4 ADDRESS</h3><br>";
			print "<p>Use the form below to adjust the IPv4 address.</p>";
		}
		else
		{
			print "<h3>ADD NEW IPv4 ADDRESS</h3>";
			print "<p>Use the form below to assign a new IPv4 address to the customer.</p>";
		}

		// display the form
		$this->obj_form->render_form();
	}



}


?>
