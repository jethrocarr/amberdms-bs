<?php
/*
	customers/services-cdr-override-edit.php

	access: customers_view
		customers_write

	allow a selected override to be adjusted or a new call rate override to be defined.
*/


require("include/customers/inc_customers.php");
require("include/services/inc_services.php");
require("include/services/inc_services_cdr.php");


class page_output
{
	var $obj_customer;
	var $obj_cdr_rate_table;
	
	var $obj_menu_nav;
	var $obj_form;

	

	function page_output()
	{
		$this->obj_customer				= New customer_services;
		$this->obj_cdr_rate_table			= New cdr_rate_table_rates_override;


		// fetch variables
		$this->obj_customer->id				= @security_script_input('/^[0-9]*$/', $_GET["id_customer"]);
		$this->obj_customer->id_service_customer	= @security_script_input('/^[0-9]*$/', $_GET["id_service_customer"]);

		$this->obj_cdr_rate_table->id_rate		= @security_script_input('/^[0-9]*$/', $_GET["id_rate"]);
		$this->obj_cdr_rate_table->id_rate_override	= @security_script_input('/^[0-9]*$/', $_GET["id_rate_override"]);


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
			$this->obj_menu_nav->add_item("CDR Override", "page=customers/service-cdr-override.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $this->obj_customer->id_service_customer ."", TRUE);
		}

		if ($this->obj_customer->obj_service->data["typeid_string"] == "phone_trunk")
		{
			$this->obj_menu_nav->add_item("DDI Configuration", "page=customers/service-ddi.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $this->obj_customer->id_service_customer ."");
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

		// check if the option override rate id is valid (if supplied)
		if (!$this->obj_cdr_rate_table->verify_id_rate_override())
		{
			$this->obj_cdr_rate_table->id_rate_override = 0;
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
		// load data
		$this->obj_customer->load_data();


		// define basic form details
		$this->obj_form = New form_input;
		$this->obj_form->formname = "cdr_override_edit";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "customers/service-cdr-override-edit-process.php";
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
		
		$structure = NULL;
		$structure["fieldname"]		= "service_description";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);


		// rate table details
		$structure = NULL;
		$structure["fieldname"] 	= "rate_table_name";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"]		= "rate_table_description";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);


		// item options
		$structure = NULL;
		$structure["fieldname"]		= "rate_prefix";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]		= "rate_description";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]		= "rate_price_sale";
		$structure["type"]		= "money";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]		= "rate_price_cost";
		$structure["type"]		= "money";
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
		$structure["fieldname"] 	= "id_rate_override";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->obj_cdr_rate_table->id_rate_override;
		$this->obj_form->add_input($structure);

		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "submit";
		$this->obj_form->add_input($structure);
		

		// define subforms
		$this->obj_form->subforms["service_details"]	= array("name_customer", "service_name", "service_description");
		$this->obj_form->subforms["rate_table_details"]	= array("rate_table_name", "rate_table_description");
		$this->obj_form->subforms["rate_table_items"]	= array("rate_prefix", "rate_description", "rate_price_sale", "rate_price_cost");
		$this->obj_form->subforms["hidden"]		= array("id_customer", "id_service_customer", "id_rate_override");
		$this->obj_form->subforms["submit"]		= array("submit");
		

		// load any data returned due to errors
		if (error_check())
		{
			$this->obj_form->load_data_error();
		}
		else
		{
			$this->obj_cdr_rate_table->load_data();


			// load rate values from standard item
			if ($this->obj_cdr_rate_table->id_rate)
			{
				$this->obj_cdr_rate_table->load_data_rate();
			}

			// load override values
			if ($this->obj_cdr_rate_table->id_rate_override)
			{
				$this->obj_cdr_rate_table->load_data_rate_override();
			}

			

			$this->obj_form->structure["name_customer"]["defaultvalue"]		= $this->obj_customer->data["name_customer"];
			$this->obj_form->structure["service_name"]["defaultvalue"]		= $this->obj_customer->obj_service->data["name_service"];
			$this->obj_form->structure["service_description"]["defaultvalue"]	= $this->obj_customer->obj_service->data["description"];

			$this->obj_form->structure["rate_table_name"]["defaultvalue"]		= $this->obj_cdr_rate_table->data["rate_table_name"];
			$this->obj_form->structure["rate_table_description"]["defaultvalue"]	= $this->obj_cdr_rate_table->data["rate_table_description"];

			$this->obj_form->structure["rate_prefix"]["defaultvalue"]		= $this->obj_cdr_rate_table->data_rate["rate_prefix"];
			$this->obj_form->structure["rate_description"]["defaultvalue"]		= $this->obj_cdr_rate_table->data_rate["rate_description"];
			$this->obj_form->structure["rate_price_sale"]["defaultvalue"]		= $this->obj_cdr_rate_table->data_rate["rate_price_sale"];
			$this->obj_form->structure["rate_price_cost"]["defaultvalue"]		= $this->obj_cdr_rate_table->data_rate["rate_price_cost"];
		}
	}



	function render_html()
	{
		// title and summary
		if ($this->obj_cdr_rate_table->id_rate)
		{
			print "<h3>OVERRIDE CDR RATE</h3><br>";
			print "<p>Use the form below to set a CDR override that will take preference compared to the standard rate in the table.</p>";
		}
		else
		{
			print "<h3>ADD OVERRIDE PREFIX</h3><br>";
			print "<p>Use the form below to add an override prefix.</p>";
		}

		// display the form
		$this->obj_form->render_form();
	}



}


?>
