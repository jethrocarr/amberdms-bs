<?php
/*
	customers/service-delete.php
	
	access: customers_write

	Form to delete a customer service.
*/

class page_output
{
	var $customerid;
	var $services_customers_id;
	
	var $obj_menu_nav;
	var $obj_form;
	

	function page_output()
	{
		// fetch variables
		$this->customerid		= security_script_input('/^[0-9]*$/', $_GET["customerid"]);
		$this->services_customers_id	= security_script_input('/^[0-9]*$/', $_GET["serviceid"]);

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
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM customers WHERE id='". $this->customerid ."'";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested customer (". $this->customerid .") does not exist - possibly the customer has been deleted.");
			return 0;
		}

		unset($sql_obj);


		// verify that the customer_service mapping exists and belongs to the correct customer
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT customerid FROM `services_customers` WHERE id='". $this->services_customers_id ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested service does not exist.");
			return 0;
		}
		else
		{
			$sql_obj->fetch_array();

			if ($sql_obj->data[0]["customerid"] != $this->customerid)
			{
				log_write("error", "page_output", "The requested service does not match the provided customer ID. Potential application bug?");
				return 0;
			}
		}

		unset($sql_obj);

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
		$structure["defaultvalue"]	= $customerid;
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"]		= "services_customers_id";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $services_customers_id;
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
		$this->obj_form->subforms["hidden"]		= array("customerid", "services_customers_id");
		$this->obj_form->subforms["submit"]		= array("delete_confirm", "submit");


		// fetch the form data
		$this->obj_form->sql_query = "SELECT services_customers.description, services.name_service FROM services_customers LEFT JOIN services ON services.id = services_customers.serviceid WHERE services_customers.id='". $this->services_customers_id ."' LIMIT 1";
		$this->obj_form->load_data();
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
