<?php
/*
	customers/services-ddi-edit.php

	access: customers_view
		customers_write
	
	Allows the selected DDI to be updated or a new DDI to be added.
*/


require("include/customers/inc_customers.php");
require("include/services/inc_services.php");
require("include/services/inc_services_cdr.php");


class page_output
{
	var $obj_customer;
	var $obj_ddi;

	var $obj_menu_nav;
	var $obj_form;

	

	function page_output()
	{
		$this->obj_customer				= New customer_services;
		$this->obj_ddi					= New cdr_customer_service_ddi;



		// fetch variables
		$this->obj_customer->id				= @security_script_input('/^[0-9]*$/', $_GET["id_customer"]);
		$this->obj_customer->id_service_customer	= @security_script_input('/^[0-9]*$/', $_GET["id_service_customer"]);
		$this->obj_ddi->id				= @security_script_input('/^[0-9]*$/', $_GET["id_ddi"]);

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


		// verify that this is a phone trunk service
		if ($this->obj_customer->obj_service->data["typeid_string"] != "phone_trunk")
		{
			log_write("error", "page_output", "The requested service is not a phone_trunk service.");
			return 0;
		}


		// verify that the DDI value is correct (if one has been supplied)
		if ($this->obj_ddi->id)
		{
			if (!$this->obj_ddi->verify_id())
			{
				log_write("error", "page_output", "The supplied DDI ID is not valid");
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
		$this->obj_form->formname = "service_ddi_edit";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "customers/service-ddi-edit-process.php";
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
		$structure["fieldname"] 	= "ddi_start";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$structure["options"]["help"]	= "eg: 6412310010";
		$structure["options"]["label"]	= " First number in allocated range (inclusive)";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"]		= "ddi_finish";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$structure["options"]["help"]	= "eg: 6412310020";
		$structure["options"]["label"]	= " Last number in allocated range (inclusive)";
		$this->obj_form->add_input($structure);

		if ($GLOBALS["config"]["SERVICE_CDR_LOCAL"] == "prefix")
		{
			/*
				Prefix-based local rates are easy, we define the prefix number and
				from that we match when doing the rate billing.
			*/
			$structure = NULL;
			$structure["fieldname"]		= "phone_local_prefix";
			$structure["type"]		= "input";
			$structure["options"]["req"]	= "yes";
			$structure["options"]["help"]	= "eg: 64123";
			$structure["options"]["label"]	= " Any calls to numbers matching this prefix will be charged at LOCAL rate.";
			$this->obj_form->add_input($structure);
		}
		else
		{
			/*
				Handling destination based local calling rates is complex, since we need to:
				- fetch a list of all destinations
					- include overrides for the service
					- include overrides for the customer
					- include base zones
				- display the label instructing on use
				- handle regions that have no destination/description
			*/


			// fetch all rates, including override rates
			$obj_local_rates				= New cdr_rate_table_rates_override();

			$obj_local_rates->id				= $this->obj_customer->obj_service->data["id_rate_table"];
			$obj_local_rates->option_type			= "customer";
			$obj_local_rates->option_type_id		= $this->obj_customer->id_service_customer;
			$obj_local_rates->option_type_serviceid		= $this->obj_customer->obj_service->id;

			$obj_local_rates->load_data_rate_all();
			$obj_local_rates->load_data_rate_all_override();

			// aggregate the destination
			$cdr_destinations		= array();
			$cdr_destinations["NONE"]	= 1;		// placeholder for no local region

			foreach ($obj_local_rates->data["rates"] as $rate)
			{
				if (!empty($rate["rate_description"]))
				{
					$cdr_destinations[ $rate["rate_description"] ] = 1;
				}
			}

			$cdr_destinations = array_keys($cdr_destinations);
			sort($cdr_destinations);


			// generate dropdown object
			$structure = NULL;
			$structure["fieldname"]		= "phone_local_prefix";
			$structure["type"]		= "dropdown";
			$structure["values"]		= $cdr_destinations;
			$structure["options"]["req"]	= "yes";
			$structure["options"]["label"]	= " Charge calls to any prefix in this region as \"LOCAL\" call rates.";
			$this->obj_form->add_input($structure);
		}


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
		$structure["fieldname"] 	= "id_ddi";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->obj_ddi->id;
		$this->obj_form->add_input($structure);

		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "submit";
		$this->obj_form->add_input($structure);
		

		// define subforms
		$this->obj_form->subforms["service_details"]	= array("name_customer", "service_name");
		$this->obj_form->subforms["ddi_details"]	= array("ddi_start", "ddi_finish", "phone_local_prefix", "description");
		$this->obj_form->subforms["hidden"]		= array("id_customer", "id_service_customer", "id_ddi");
		$this->obj_form->subforms["submit"]		= array("submit");
		

		// load any data returned due to errors
		if (error_check())
		{
			$this->obj_form->load_data_error();
		}
		else
		{
			// load DDI
			if ($this->obj_ddi->id)
			{
				$this->obj_ddi->load_data();
			}
			

			// set values
			$this->obj_form->structure["name_customer"]["defaultvalue"]		= $this->obj_customer->data["name_customer"];
			$this->obj_form->structure["service_name"]["defaultvalue"]		= $this->obj_customer->obj_service->data["name_service"];

			$this->obj_form->structure["ddi_start"]["defaultvalue"]			= $this->obj_ddi->data["ddi_start"];
			$this->obj_form->structure["ddi_finish"]["defaultvalue"]		= $this->obj_ddi->data["ddi_finish"];
			$this->obj_form->structure["phone_local_prefix"]["defaultvalue"]	= $this->obj_ddi->data["local_prefix"];
			$this->obj_form->structure["description"]["defaultvalue"]		= $this->obj_ddi->data["description"];
		}
	}



	function render_html()
	{
		// title and summary
		if ($this->obj_ddi->id)
		{
			print "<h3>ADJUST DDI</h3><br>";
			print "<p>Use the form below to adjust the DDI values.</p>";
		}
		else
		{
			print "<h3>ADD NEW DDI</h3>";
			print "<p>Use the form below to add a new DDI value to the customer's service.</p>";
		}

		// display the form
		$this->obj_form->render_form();
	}



}


?>
