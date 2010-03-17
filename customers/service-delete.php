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

	var $obj_menu_nav;
	var $obj_form;
	

	function page_output()
	{
		$this->obj_customer		= New customer;
		$this->obj_service		= New service;


		// fetch variables
		$this->obj_customer->id			= @security_script_input('/^[0-9]*$/', $_GET["customerid"]);
		$this->obj_service->option_type		= "customer";
		$this->obj_service->option_type_id	= @security_script_input('/^[0-9]*$/', $_GET["serviceid"]);


		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Customer's Details", "page=customers/view.php&id=". $this->customerid ."");
		$this->obj_menu_nav->add_item("Customer's Journal", "page=customers/journal.php&id=". $this->customerid ."");
		$this->obj_menu_nav->add_item("Customer's Invoices", "page=customers/invoices.php&id=". $this->customerid ."");
		$this->obj_menu_nav->add_item("Customer's Services", "page=customers/services.php&id=". $this->customerid ."", TRUE);

		if (user_permissions_get("customers_write"))
		{
			$this->obj_menu_nav->add_item("Delete Customer", "page=customers/delete.php&id=". $this->customerid ."");
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
		if ($this->obj_service->option_type_id)
		{
			if (!$this->obj_service->verify_id_options())
			{
				log_write("error", "page_output", "The requested service (". $this->obj_service->id .") was not found and/or does not match the selected customer");
				return 0;
			}
		}


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
		$structure["fieldname"]		= "customerid";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->obj_customer->id;
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"]		= "id_service_customer";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->obj_service->option_type_id;
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
		$this->obj_form->subforms["hidden"]		= array("customerid", "id_service_customer");
		$this->obj_form->subforms["submit"]		= array("delete_confirm", "submit");


		// fetch DB data
		$this->obj_form->sql_query = "SELECT active, date_period_first, date_period_next, quantity FROM `services_customers` WHERE id='". $this->obj_service->option_type_id."' LIMIT 1";
		$this->obj_form->load_data();

		// fetch service item data
		$this->obj_service->load_data();
		$this->obj_service->load_data_options();

		$this->obj_form->structure["description"]["defaultvalue"]	= $this->obj_service->data["description"];
		$this->obj_form->structure["name_service"]["defaultvalue"]	= $this->obj_service->data["name_service"];


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
	}
	
} // end of page_output


?>
