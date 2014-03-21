<?php
/*
	customers/service-delete.php
	
	access: customers_write

	Form to delete a customer service.
*/


require("include/customers/inc_customers.php");
require("include/services/inc_services.php");


class page_output
{
	var $obj_customer;
	var $obj_service;

	var $locked;

	var $obj_menu_nav;
	var $obj_form;
	

	function page_output()
	{
		$this->obj_customer		= New customer_services;


		// fetch variables
		$this->obj_customer->id				= @security_script_input('/^[0-9]*$/', $_GET["id_customer"]);
		$this->obj_customer->id_service_customer	= @security_script_input('/^[0-9]*$/', $_GET["id_service_customer"]);

		
		// load data
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
			$this->obj_menu_nav->add_item("IPv4 Addresses", "page=customers/service-ipv4.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $this->obj_customer->id_service_customer ."");
		}

		if (user_permissions_get("customers_write"))
		{
			$this->obj_menu_nav->add_item("Service Delete", "page=customers/service-delete.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $this->obj_customer->id_service_customer ."", TRUE);
		}


	}



	function check_permissions()
	{
		return user_permissions_get("customers_write");
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
		if (!$this->obj_customer->verify_id_service_customer())
		{
			log_write("error", "page_output", "The requested service (". $this->obj_customer->id_service_customer .") was not found and/or does not match the selected customer");
			return 0;
		}
		else
		{
			$this->obj_customer->load_data_service();
		}


		// check if the service-customer entry can be deleted
		$this->locked = $this->obj_customer->service_check_delete_lock();

		return 1;
	}


	function execute()
	{
		/*
			Define form structure
		*/
		$this->obj_form = New form_input;
		$this->obj_form->formname = "service_delete";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "customers/service-delete-process.php";
		$this->obj_form->method = "post";
	
	
		// general
		$structure = NULL;
		$structure["fieldname"] 	= "name_service";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "description";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);


		// hidden values
		$structure = NULL;
		$structure["fieldname"]		= "id_customer";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->obj_customer->id;
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"]		= "id_service_customer";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->obj_customer->id_service_customer;
		$this->obj_form->add_input($structure);
		

		// confirm delete
		$structure = NULL;
		$structure["fieldname"] 	= "delete_confirm";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "Yes, I wish to delete this service and realise that once deleted the data can not be recovered.";
		$this->obj_form->add_input($structure);


		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Delete Service";
		$this->obj_form->add_input($structure);


		// define subforms
		$this->obj_form->subforms["service_delete"]	= array("name_service", "description");
		$this->obj_form->subforms["hidden"]		= array("id_customer", "id_service_customer");


		if ($this->locked)
		{
			$this->obj_form->subforms["submit"]	= array();
		}
		else
		{
			$this->obj_form->subforms["submit"]	= array("delete_confirm", "submit");
		}



		// fetch DB data
		$this->obj_form->sql_query = "SELECT active, date_period_first, date_period_next FROM `services_customers` WHERE id='". $this->obj_service->option_type_id."' LIMIT 1";
		$this->obj_form->load_data();

		// fetch service item data
		$this->obj_form->structure["description"]["defaultvalue"]	= $this->obj_customer->obj_service->data["description"];
		$this->obj_form->structure["name_service"]["defaultvalue"]	= $this->obj_customer->obj_service->data["name_service"];


		// fetch the form data
		if (error_check())
		{
			$this->obj_form->load_data_error();
		}
	

	}


	function render_html()
	{
		// title + summary
		print "<h3>DELETE SERVICE</h3><br>";
		print "<p>This page allows you to delete a service from a customer's account.</p>";
	
		// display the form
		$this->obj_form->render_form();

	
		if ($this->locked)
		{
			format_msgbox("locked", "<p>This service is locked and can not be deleted at this time.</p>");
		}

	}
	
} // end of page_output


?>
