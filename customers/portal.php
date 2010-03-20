<?php
/*
	customers/portal.php

	access: customers_view (read-only)
		customers_write (write access)

	Provides options for configuring customer's credentials and other options for the
	customer portal, which connects via the SOAP API.
*/


require("include/customers/inc_customers.php");

class page_output
{
	var $obj_customer;

	var $obj_menu_nav;
	var $obj_form;


	function page_output()
	{
		$this->obj_customer		= New customer;


		// fetch variables
		$this->obj_customer->id = @security_script_input('/^[0-9]*$/', $_GET["id"]);


		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Customer's Details", "page=customers/view.php&id=". $this->obj_customer->id ."");

		if (sql_get_singlevalue("SELECT value FROM config WHERE name='MODULE_CUSTOMER_PORTAL' LIMIT 1") == "enabled")
		{
			$this->obj_menu_nav->add_item("Portal Options", "page=customers/portal.php&id=". $this->obj_customer->id ."", TRUE);
		}

		$this->obj_menu_nav->add_item("Customer's Journal", "page=customers/journal.php&id=". $this->obj_customer->id ."");
		$this->obj_menu_nav->add_item("Customer's Invoices", "page=customers/invoices.php&id=". $this->obj_customer->id ."");
		$this->obj_menu_nav->add_item("Customer's Services", "page=customers/services.php&id=". $this->obj_customer->id ."");

		if (user_permissions_get("customers_write"))
		{
			$this->obj_menu_nav->add_item("Delete Customer", "page=customers/delete.php&id=". $this->obj_customer->id ."");
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

		// ensure that the portal module is enabled
		if (sql_get_singlevalue("SELECT value FROM config WHERE name='MODULE_CUSTOMER_PORTAL' LIMIT 1") != "enabled")
		{
			log_write("error", "page_output", "MODULE_CUSTOMER_PORTAL is disabled, enable it if you wish to adjust customer portal configuration options.");
			return 0;
		}


		return 1;
	}


	function execute()
	{
		/*
			Define form structure
		*/
		$this->obj_form = New form_input;
		$this->obj_form->formname = "customer_portal";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "customers/portal-process.php";
		$this->obj_form->method = "post";
		

		// general customer details
		$structure = NULL;
		$structure["fieldname"] 	= "code_customer";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "name_customer";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);


		// passwords
		$structure = NULL;
		$structure["fieldname"]		= "password_message";
		$structure["type"]		= "message";
		$structure["defaultvalue"]	= "<i>Only input a password if you wish to change the existing one.</i>";
		$this->obj_form->add_input($structure);
		
		
		$structure = NULL;
		$structure["fieldname"]		= "password";
		$structure["type"]		= "password";
		$this->obj_form->add_input($structure);
	
		$structure = NULL;
		$structure["fieldname"]		= "password_confirm";
		$structure["type"]		= "password";
		$this->obj_form->add_input($structure);
	
		
		
		// last login information
		$structure = NULL;
		$structure["fieldname"]		= "login_time";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]		= "login_ipaddress";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);




		// submit section
		if (user_permissions_get("customers_write"))
		{
			$structure = NULL;
			$structure["fieldname"] 	= "submit";
			$structure["type"]		= "submit";
			$structure["defaultvalue"]	= "Save Changes";
			$this->obj_form->add_input($structure);
		
		}
		else
		{
			$structure = NULL;
			$structure["fieldname"] 	= "submit";
			$structure["type"]		= "message";
			$structure["defaultvalue"]	= "<p><i>Sorry, you don't have permissions to make changes to customer records.</i></p>";
			$this->obj_form->add_input($structure);
		}


		// hidden
		$structure = NULL;
		$structure["fieldname"] 	= "id_customer";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->obj_customer->id;
		$this->obj_form->add_input($structure);
					
		
		// define subforms
		$this->obj_form->subforms["customer_view"]		= array("code_customer", "name_customer");
		$this->obj_form->subforms["customer_portal_history"]	= array("login_time", "login_ipaddress");
		$this->obj_form->subforms["customer_portal_password"]	= array("password_message", "password", "password_confirm");
		$this->obj_form->subforms["hidden"]			= array("id_customer");

		if (user_permissions_get("customers_write"))
		{
			$this->obj_form->subforms["submit"]		= array("submit");
		}
		else
		{
			$this->obj_form->subforms["submit"]		= array();
		}

		
		// fetch the form data
		$this->obj_customer->load_data();

		$this->obj_form->structure["code_customer"]["defaultvalue"]		= $this->obj_customer->data["code_customer"];
		$this->obj_form->structure["name_customer"]["defaultvalue"]		= $this->obj_customer->data["name_customer"];
		$this->obj_form->structure["login_time"]["defaultvalue"]		= $this->obj_customer->data["portal_login_time"];
		$this->obj_form->structure["login_ipaddress"]["defaultvalue"]		= $this->obj_customer->data["portal_login_ipaddress"];


		if (error_check())
		{
			$this->obj_form->load_data_error();
		}

	}


	function render_html()
	{
		// title	
		print "<h3>CUSTOMER PORTAL OPTIONS</h3><br>";
		print "<p>There are various options for the customer portal which can be configured and defined here, such as the customer's login password.</p>";

		// display the form
		$this->obj_form->render_form();

		if (!user_permissions_get("customers_write"))
		{
			format_msgbox("locked", "<p>Sorry, you do not have permission to edit this customer</p>");
		}

	}


} // end of page_output class

?>
