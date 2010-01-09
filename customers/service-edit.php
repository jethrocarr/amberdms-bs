<?php
/*
	customers/service-edit.php
	
	access: customers_view
		customers_write

	Form to add or edit a customer service.
*/


class page_output
{
	var $customerid;
	var $serviceid;
	var $services_customers_id;
	
	var $obj_menu_nav;
	var $obj_form;
	

	function page_output()
	{
		// fetch variables
		$this->customerid		= @security_script_input('/^[0-9]*$/', $_GET["customerid"]);
		$this->services_customers_id	= @security_script_input('/^[0-9]*$/', $_GET["serviceid"]);

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
		return user_permissions_get("customers_view");
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


		// verify that the customer_service mapping exists and belongs to the correct customer (if we are doing an edit)
		if ($this->services_customers_id)
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT customerid, serviceid FROM `services_customers` WHERE id='". $this->services_customers_id ."' LIMIT 1";
			$sql_obj->execute();

			if (!$sql_obj->num_rows())
			{
				log_write("error", "page_output", "The requested service does not exist.");
				return 0;
			}
			else
			{
				$sql_obj->fetch_array();

				$this->serviceid = $sql_obj->data[0]["serviceid"];

				if ($sql_obj->data[0]["customerid"] != $this->customerid)
				{
					log_write("error", "page_output", "The requested service does not match the provided customer ID. Potential application bug?");
					return 0;
				}
			}

			unset($sql_obj);
		}

		return 1;
	}



	function execute()
	{
		/*
			Define form structure
		*/
		$this->obj_form = New form_input;
		$this->obj_form->formname = "service_view";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "customers/service-edit-process.php";
		$this->obj_form->method = "post";

				
	
		// general
		if ($this->services_customers_id)
		{
			// fetch service details
			$sql_service_obj		= New sql_query;
			$sql_service_obj->string	= "SELECT name_service, typeid, billing_cycle FROM services WHERE id='". $this->serviceid ."' LIMIT 1";
			$sql_service_obj->execute();
			$sql_service_obj->fetch_array();

			// fetch service type
			$service_type = sql_get_singlevalue("SELECT name as value FROM service_types WHERE id='". $sql_service_obj->data[0]["typeid"] ."' LIMIT 1");


			// general
			$structure = NULL;
			$structure["fieldname"]		= "serviceid";
			$structure["type"]		= "text";
			$structure["defaultvalue"]	= $sql_service_obj->data[0]["name_service"];
			$this->obj_form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"]		= "services_customers_id";
			$structure["type"]		= "text";
			$structure["defaultvalue"]	= $this->services_customers_id;
			$this->obj_form->add_input($structure);


			$structure = NULL;
			$structure["fieldname"] 	= "active";
			$structure["type"]		= "checkbox";
			$structure["options"]["label"]	= "Service is enabled";
			$this->obj_form->add_input($structure);
	

			// quantity field - licenses only
			if ($service_type == "licenses")
			{
				$structure = NULL;
				$structure["fieldname"] 	= "quantity_msg";
				$structure["type"]		= "message";
				$structure["defaultvalue"]	= "<i>Because this is a license service, you need to specifiy how many license in the box below. Note that this will only affect billing from the next invoice. If you wish to charge for usage between now and the next invoice, you will need to generate a manual invoice.</i>";
				$this->obj_form->add_input($structure);
				
				$structure = NULL;
				$structure["fieldname"] 	= "quantity";
				$structure["type"]		= "input";
				$structure["options"]["req"]	= "yes";
				$this->obj_form->add_input($structure);
			}

			
			// billing
			$structure = NULL;
			$structure["fieldname"]		= "billing_cycle";
			$structure["type"]		= "text";
			$structure["defaultvalue"]	= sql_get_singlevalue("SELECT name as value FROM billing_cycles WHERE id='". $sql_service_obj->data[0]["billing_cycle"] ."' LIMIT 1");
			$this->obj_form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"] 	= "date_period_first";
			$structure["type"]		= "text";
			$this->obj_form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"] 	= "date_period_next";
			$structure["type"]		= "text";
			$this->obj_form->add_input($structure);
		}
		else
		{
			$structure = form_helper_prepare_dropdownfromdb("serviceid", "SELECT id, name_service as label FROM services ORDER BY name_service");
			$structure["options"]["req"] = "yes";
			$this->obj_form->add_input($structure);
		
			$structure = NULL;
			$structure["fieldname"] 	= "date_period_first";
			$structure["type"]		= "date";
			$structure["options"]["req"]	= "yes";
			$structure["defaultvalue"]	= date("Y-m-d");
			$this->obj_form->add_input($structure);
		}


		$structure = NULL;
		$structure["fieldname"] 	= "description";
		$structure["type"]		= "textarea";
		$this->obj_form->add_input($structure);
		

		// hidden values
		$structure = NULL;
		$structure["fieldname"]		= "customerid";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->customerid;
		$this->obj_form->add_input($structure);
		

		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";

		if ($this->services_customers_id)
		{
			$structure["defaultvalue"]	= "Save Changes";
		}
		else
		{
			$structure["defaultvalue"]	= "Add Service";
		}
		$this->obj_form->add_input($structure);



		// define subforms
		if ($this->services_customers_id)
		{
			$this->obj_form->subforms["service_edit"]	= array("serviceid", "services_customers_id", "active", "description");
			$this->obj_form->subforms["service_billing"]	= array("billing_cycle", "date_period_first", "date_period_next");


			if ($service_type == "licenses")
			{
				$this->obj_form->subforms["service_options_licenses"]	= array("quantity_msg", "quantity");
			}
		}
		else
		{
			$this->obj_form->subforms["service_add"]	= array("serviceid", "date_period_first", "description");
		}
		
		
		$this->obj_form->subforms["hidden"] = array("customerid");

		if (user_permissions_get("customers_write"))
		{
			$this->obj_form->subforms["submit"] = array("submit");
		}
		else
		{
			$this->obj_form->subforms["submit"] = array();
		}


		// fetch the form data if editing
		if ($this->services_customers_id)
		{
			$this->obj_form->sql_query = "SELECT active, date_period_first, date_period_next, quantity, description FROM `services_customers` WHERE id='". $this->services_customers_id ."' LIMIT 1";
			$this->obj_form->load_data();
		}
		else
		{
			// load any data returned due to errors
			$this->obj_form->load_data_error();
		}

	}


	function render_html()
	{
		// title/summary
		if ($this->services_customers_id)
		{
			print "<h3>EDIT SERVICE</h3><br>";
			print "<p>This page allows you to modifiy a customer service.</p>";
		}
		else
		{
			print "<h3>ADD CUSTOMER TO SERVICE</h3><br>";
			print "<p>This page allows you to subscribe a customer to a new service.</p>";
		}

		// display the form
		$this->obj_form->render_form();

		
		if (!user_permissions_get("customers_write"))
		{
			format_msgbox("locked", "<p>Sorry, you do not have permissions to make changes to customer services</p>");
		}
	}

} // end page_output



?>
