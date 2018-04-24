<?php
/*
	services/cdr-override-edit.php

	access: services_write

	Form to add or edit CDR rate overrides.
*/


require("include/services/inc_services.php");
require("include/services/inc_services_cdr.php");


class page_output
{
	var $id;			// service ID
	var $service_type;		// service type (string)

	var $obj_cdr_rate_table;	// CDR rates information
	var $obj_service;		// service information

	var $obj_table;
	var $obj_menu_nav;


	function __construct()
	{
		// init
		$this->obj_cdr_rate_table	= New cdr_rate_table_rates_override;
		$this->obj_service		= New service;


		// fetch key service details
		$this->obj_service->id				= @security_script_input('/^[0-9]*$/', $_GET["id_service"]);
		$this->obj_cdr_rate_table->id_rate		= @security_script_input('/^[0-9]*$/', $_GET["id_rate"]);
		$this->obj_cdr_rate_table->id_rate_override	= @security_script_input('/^[0-9]*$/', $_GET["id_rate_override"]);

		$this->service_type				= sql_get_singlevalue("SELECT service_types.name as value FROM services LEFT JOIN service_types ON service_types.id = services.typeid WHERE services.id='". $this->obj_service->id ."' LIMIT 1");


		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Service Details", "page=services/view.php&id=". $this->obj_service->id ."");
		$this->obj_menu_nav->add_item("Service Plan", "page=services/plan.php&id=". $this->obj_service->id ."");

		if ($this->service_type == "bundle")
		{
			$this->obj_menu_nav->add_item("Bundle Components", "page=services/bundles.php&id=". $this->obj_service->id ."");
		}

		$this->obj_menu_nav->add_item("Call Rate Override", "page=services/cdr-override.php&id=". $this->obj_service->id ."", TRUE);
		$this->obj_menu_nav->add_item("Service Journal", "page=services/journal.php&id=". $this->obj_service->id ."");

		if (user_permissions_get("services_write"))
		{
			$this->obj_menu_nav->add_item("Delete Service", "page=services/delete.php&id=". $this->obj_service->id ."");
		}
	}


	function check_permissions()
	{
		return user_permissions_get("services_write");
	}


	function check_requirements()
	{
		// verify that the service exists
		if ($this->obj_service->verify_id())
		{
			$this->obj_service->load_data();

			// verify the rate is valid
			if ($this->obj_service->data["id_rate_table"])
			{
				$this->obj_cdr_rate_table->id	= $this->obj_service->data["id_rate_table"];

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
			log_write("error", "page_output", "The requested service (". $this->obj_service->id .") does not exist - possibly the service has been deleted.");
			return 0;
		}

		unset($sql_obj);


		// verify that this is a phone service
		if ($this->service_type != ("phone_single" || "phone_tollfree" || "phone_trunk"))
		{
			log_write("error", "page_output", "The requested service is not a phone service.");
			return 0;
		}
		


		return 1;
	}


	function execute()
	{
		// define basic form details
		$this->obj_form = New form_input;
		$this->obj_form->formname = "cdr_override_edit";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "services/cdr-override-edit-process.php";
		$this->obj_form->method = "post";



		// service details
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

		$structure = form_helper_prepare_dropdownfromdb("rate_billgroup", "SELECT id, billgroup_name as label FROM cdr_rate_billgroups");
		$structure["options"]["req"]		= "yes";
		$structure["options"]["width"]		= "100";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]		= "rate_price_sale";
		$structure["type"]		= "money";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]		= "rate_price_cost";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);



		// hidden
		$structure = NULL;
		$structure["fieldname"] 	= "id_service";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->obj_service->id;
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
		$this->obj_form->subforms["service_details"]	= array("service_name", "service_description");
		$this->obj_form->subforms["rate_table_details"]	= array("rate_table_name", "rate_table_description");
		$this->obj_form->subforms["rate_table_items"]	= array("rate_prefix", "rate_description", "rate_billgroup", "rate_price_sale", "rate_price_cost");
		$this->obj_form->subforms["hidden"]		= array("id_service", "id_rate_override");
		$this->obj_form->subforms["submit"]		= array("submit");
		

		// load any data returned due to errors
		if (error_check())
		{
			$this->obj_form->load_data_error();
		}
		else
		{
			$this->obj_cdr_rate_table->load_data();

			$this->obj_cdr_rate_table->option_type		= "service";
			$this->obj_cdr_rate_table->option_type_id	= $this->obj_service->id;


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

			

			$this->obj_form->structure["service_name"]["defaultvalue"]		= $this->obj_service->data["name_service"];
			$this->obj_form->structure["service_description"]["defaultvalue"]	= $this->obj_service->data["description"];

			$this->obj_form->structure["rate_table_name"]["defaultvalue"]		= $this->obj_cdr_rate_table->data["rate_table_name"];
			$this->obj_form->structure["rate_table_description"]["defaultvalue"]	= $this->obj_cdr_rate_table->data["rate_table_description"];

			$this->obj_form->structure["rate_prefix"]["defaultvalue"]		= $this->obj_cdr_rate_table->data_rate["rate_prefix"];
			$this->obj_form->structure["rate_description"]["defaultvalue"]		= $this->obj_cdr_rate_table->data_rate["rate_description"];
			$this->obj_form->structure["rate_billgroup"]["defaultvalue"]		= $this->obj_cdr_rate_table->data_rate["rate_billgroup"];
			$this->obj_form->structure["rate_price_sale"]["defaultvalue"]		= $this->obj_cdr_rate_table->data_rate["rate_price_sale"];
			$this->obj_form->structure["rate_price_cost"]["defaultvalue"]		= format_money($this->obj_cdr_rate_table->data_rate["rate_price_cost"]);
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
