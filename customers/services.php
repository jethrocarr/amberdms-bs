<?php
/*
	services.php
	
	access: "customers_view"	(read-only)
		"customers_write"

	Displays all the services currently assigned to the user's account, and allows the customer
	to have new services added/removed.
*/


class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_table;


	function page_output()
	{
		// fetch variables
		$this->id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Customer's Details", "page=customers/view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Customer's Journal", "page=customers/journal.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Customer's Invoices", "page=customers/invoices.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Customer's Services", "page=customers/services.php&id=". $this->id ."", TRUE);

		if (user_permissions_get("customers_write"))
		{
			$this->obj_menu_nav->add_item("Delete Customer", "page=customers/delete.php&id=". $this->id ."");
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
		$sql_obj->string	= "SELECT id FROM customers WHERE id='". $this->id ."'";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested customer (". $this->id .") does not exist - possibly the customer has been deleted.");
			return 0;
		}

		unset($sql_obj);


		return 1;
	}



	function execute()
	{

		// establish a new table object
		$this->obj_table = New table;

		$this->obj_table->language		= $_SESSION["user"]["lang"];
		$this->obj_table->tablename	= "service_list";

		// define all the columns and structure
		$this->obj_table->add_column("standard", "name_service", "services.name_service");
		$this->obj_table->add_column("bool_tick", "active", "services_customers.active");
		$this->obj_table->add_column("standard", "typeid", "service_types.name");
		$this->obj_table->add_column("standard", "billing_cycles", "billing_cycles.name");
		$this->obj_table->add_column("date", "date_period_first", "");
		$this->obj_table->add_column("date", "date_period_next", "");
		$this->obj_table->add_column("standard", "description", "services_customers.description");

		// defaults
		$this->obj_table->columns		= array("name_service", "active", "typeid", "date_period_next", "description");
		$this->obj_table->columns_order		= array("name_service");

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("services_customers");
		
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN services ON services.id = services_customers.serviceid");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN billing_cycles ON billing_cycles.id = services.billing_cycle");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN service_types ON service_types.id = services.typeid");
		
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "services_customers.id");
		$this->obj_table->sql_obj->prepare_sql_addwhere("services_customers.customerid = '". $this->id ."'");

		// run SQL query
		$this->obj_table->generate_sql();
		$this->obj_table->load_data_sql();

	}



	function render_html()
	{
		// heading
		print "<h3>CUSTOMER SERVICES</h3>";
		print "<p>This page allows you to manage all the services that the customer is assigned to.</p>";
	

		if (!$this->obj_table->data_num_rows)
		{
			format_msgbox("important", "<p><b>This customer is not currently subscribed to any services.</b></p>");

			print "<p><b><a href=\"index.php?page=customers/service-edit.php&id=". $this->id ."\">Click here to add this customer to a service</a>.</b></p>";
		}
		else
		{
			// details link
			$structure = NULL;
			$structure["customerid"]["value"]	= $this->id;
			$structure["serviceid"]["column"]	= "id";
			$this->obj_table->add_link("details", "customers/service-edit.php", $structure);

			// periods link
			$structure = NULL;
			$structure["customerid"]["value"]	= $this->id;
			$structure["serviceid"]["column"]	= "id";
			$this->obj_table->add_link("periods", "customers/service-history.php", $structure);
						
			// delete link
			$structure = NULL;
			$structure["customerid"]["value"]	= $this->id;
			$structure["serviceid"]["column"]	= "id";
			$this->obj_table->add_link("delete", "customers/service-delete.php", $structure);


			// display the table
			$this->obj_table->render_table_html();

			
			print "<p><b><a href=\"index.php?page=customers/service-edit.php&customerid=". $this->id ."\">Click here to add a new service to your customer</a>.</b></p>";
		}

		
		print "<p><b><a href=\"customers/services-invoicegen-process.php?customerid=". $this->id ."\">Automatically generate any new invoices</a>.</b></p>";

	}

} // end of page_output class


?>
