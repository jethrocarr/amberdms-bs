<?php
/*
	customers/orders.php
	
	access: "customers_view"	(read-only)
		"customers_orders"	(read-write)

	Displays all the services currently assigned to the user's account, and allows the customer
	to have new services added/removed.
*/


require("include/customers/inc_customers.php");



class page_output
{
	var $id;

	var $obj_customer;
	var $obj_menu_nav;
	var $obj_table;


	function page_output()
	{
		// customer object
		$this->obj_customer		= New customer_orders;
		$this->obj_customer->id		= @security_script_input('/^[0-9]*$/', $_GET["id_customer"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Customer's Details", "page=customers/view.php&id=". $this->obj_customer->id ."");

		if ($GLOBALS["config"]["MODULE_CUSTOMER_PORTAL"] == "enabled")
		{
			$this->obj_menu_nav->add_item("Portal Options", "page=customers/portal.php&id=". $this->obj_customer->id ."");
		}

		$this->obj_menu_nav->add_item("Customer's Journal", "page=customers/journal.php&id=". $this->obj_customer->id ."");
		$this->obj_menu_nav->add_item("Customer's Attributes", "page=customers/attributes.php&id_customer=". $this->obj_customer->id ."");
		$this->obj_menu_nav->add_item("Customer's Orders", "page=customers/orders.php&id_customer=". $this->obj_customer->id ."", "enabled");
		$this->obj_menu_nav->add_item("Customer's Invoices", "page=customers/invoices.php&id=". $this->obj_customer->id ."");
		$this->obj_menu_nav->add_item("Customer's Credit", "page=customers/credit.php&id_customer=". $this->obj_customer->id ."");
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


		return 1;
	}



	function execute()
	{

		// establish a new table object
		$this->obj_table = New table;

		$this->obj_table->language		= $_SESSION["user"]["lang"];
		$this->obj_table->tablename		= "orders_list";

		// define all the columns and structure
		$this->obj_table->add_column("date", "date_ordered", "");
		$this->obj_table->add_column("standard", "type", "");
		$this->obj_table->add_column("standard", "item", "NONE");
		$this->obj_table->add_column("standard", "quantity", "");
		$this->obj_table->add_column("standard", "units", "");
		$this->obj_table->add_column("money", "amount", "");
		$this->obj_table->add_column("money", "price", "");
		$this->obj_table->add_column("percentage", "discount", "");
		$this->obj_table->add_column("standard", "description", "");

		// defaults
		$this->obj_table->columns = array("date_ordered", "type", "item", "quantity", "units", "price", "discount", "amount", "description");

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("customers_orders");
		$this->obj_table->sql_obj->prepare_sql_addfield("id_order", "id");
		$this->obj_table->sql_obj->prepare_sql_addfield("customid", "customid");
		$this->obj_table->sql_obj->prepare_sql_addwhere("id_customer = '". $this->obj_customer->id ."'");
		$this->obj_table->sql_obj->prepare_sql_addorderby_desc("date_ordered");

		// run SQL query
		$this->obj_table->generate_sql();
		$this->obj_table->load_data_sql();

		// load service item data and optiosn
		for ($i=0; $i < $this->obj_table->data_num_rows; $i++)
		{
			switch ($this->obj_table->data[$i]["type"])
			{
				case "product":	
					// lookup product code + name
					$this->obj_table->data[$i]["item"] = sql_get_singlevalue("SELECT CONCAT_WS(' -- ',code_product,name_product) AS value FROM products WHERE id='". $this->obj_table->data[$i]["customid"] ."'");
				break;

				case "service":
					// lookup service name
					$this->obj_table->data[$i]["item"] = sql_get_singlevalue("SELECT name_service AS value FROM services WHERE id='". $this->obj_table->data[$i]["customid"] ."'");
				break;
			}
		}

	}



	function render_html()
	{
		// heading
		print "<h3>CUSTOMER ORDERS</h3>";
		print "<p>The customer orders page is a function developed to allow products and charges to be assigned to a customer and then involved onto a single bill - this may involve
			use patterns like adding service setup fees and hardware (eg: connection fee + ADSL modem) and having them automatically billed at the next service run.</p>";


		// summary box
		$this->obj_customer->order_render_summarybox();


		// table
		if (!$this->obj_table->data_num_rows)
		{
			format_msgbox("info", "<p>This customer does not have any items on order at this stage.</p>");

			if (user_permissions_get("customers_orders"))
			{
				print "<p><b><a class=\"button\" href=\"index.php?page=customers/orders-view.php&id_customer=". $this->obj_customer->id ."\">Order Products</a></b></p>";
			}
		}
		else
		{
			// details link
			$structure = NULL;
			$structure["id_customer"]["value"]			= $this->obj_customer->id;
			$structure["id_order"]["column"]			= "id_order";
			$this->obj_table->add_link("details", "customers/orders-view.php", $structure);

			if (user_permissions_get("customers_orders"))
			{
				// delete link
				$structure = NULL;
				$structure["id_customer"]["value"]		= $this->obj_customer->id;
				$structure["id_order"]["column"]		= "id_order";
				$structure["full_link"]				= "yes";
				$this->obj_table->add_link("delete", "customers/orders-delete-process.php", $structure);
			}


			// display the table
			$this->obj_table->render_table_html();


			if (user_permissions_get("customers_orders"))
			{
				print "<p><a class=\"button\" href=\"index.php?page=customers/orders-view.php&id_customer=". $this->obj_customer->id ."\">Order Products</a> <a class=\"button\" href=\"customers/orders-invoicegen-process.php?id_customer=". $this->obj_customer->id ."\">Generate any new invoices</a></p>";
			}
		}

	}

} // end of page_output class


?>
