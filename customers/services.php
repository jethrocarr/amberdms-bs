<?php
/*
	customers/services.php
	
	access: "customers_view"	(read-only)
		"customers_write"

	Displays all the services currently assigned to the user's account, and allows the customer
	to have new services added/removed.
*/


require("include/services/inc_services.php");



class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_table;


	function page_output()
	{
		// fetch variables
		$this->id = @security_script_input('/^[0-9]*$/', $_GET["id"]);

		// TODO temp compability hack
		if (!$this->id)
		{
			$this->id = @security_script_input('/^[0-9]*$/', $_GET["id_customer"]);
		}

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Customer's Details", "page=customers/view.php&id=". $this->id ."");

		if (sql_get_singlevalue("SELECT value FROM config WHERE name='MODULE_CUSTOMER_PORTAL' LIMIT 1") == "enabled")
		{
			$this->obj_menu_nav->add_item("Portal Options", "page=customers/portal.php&id=". $this->id ."");
		}

		$this->obj_menu_nav->add_item("Customer's Journal", "page=customers/journal.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Customer's Attributes", "page=customers/attributes.php&id_customer=". $this->id ."");
		$this->obj_menu_nav->add_item("Customer's Orders", "page=customers/orders.php&id_customer=". $this->id ."");
		$this->obj_menu_nav->add_item("Customer's Invoices", "page=customers/invoices.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Customer's Credit", "page=customers/credit.php&id_customer=". $this->id ."");
		$this->obj_menu_nav->add_item("Customer's Services", "page=customers/services.php&id=". $this->id ."", TRUE);
		$this->obj_menu_nav->add_item("Resellers Customers", "page=customers/reseller.php&id_customer=". $this->id ."");

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
		$this->obj_table->tablename		= "service_list";

		// define all the columns and structure
		$this->obj_table->add_column("standard", "name_service", "NONE");
		$this->obj_table->add_column("bool_tick", "active", "active");
		$this->obj_table->add_column("standard", "typeid", "NONE");
		$this->obj_table->add_column("standard", "billing_cycles", "NONE");
		$this->obj_table->add_column("date", "date_period_first", "date_period_first");
		$this->obj_table->add_column("date", "date_period_next", "date_period_next");
		$this->obj_table->add_column("money", "price_monthly", "NONE");
		$this->obj_table->add_column("money", "price_yearly", "NONE");
		$this->obj_table->add_column("percentage", "discount", "NONE");
		$this->obj_table->add_column("standard", "description", "NONE");

		// defaults
		$this->obj_table->columns = array("name_service", "active", "typeid", "date_period_next", "description");

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("services_customers");
		$this->obj_table->sql_obj->prepare_sql_addfield("id_service_customer", "id");
		$this->obj_table->sql_obj->prepare_sql_addfield("id_service", "serviceid");
		$this->obj_table->sql_obj->prepare_sql_addwhere("customerid = '". $this->id ."'");

		// acceptable filter options
		$this->obj_table->add_fixed_option("id", $this->id);

		$structure = NULL;
		$structure["fieldname"] 	= "show_prices_with_discount";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "Display service prices with discounts applied";
		$structure["defaultvalue"]	= "1";
		$structure["sql"]		= "";
		$this->obj_table->add_filter($structure);


		// load options
		$this->obj_table->load_options_form();

		// run SQL query
		$this->obj_table->generate_sql();
		$this->obj_table->load_data_sql();

		// fetch service billing cycle information
		$obj_cycles_sql		= New sql_query;
		$obj_cycles_sql->string	= "SELECT id, name, priority FROM billing_cycles";
		$obj_cycles_sql->execute();
		$obj_cycles_sql->fetch_array();

		// load service item data and optiosn
		for ($i=0; $i < $this->obj_table->data_num_rows; $i++)
		{
			$obj_service			= New service;

			$obj_service->option_type	= "customer";
			$obj_service->option_type_id	= $this->obj_table->data[$i]["id_service_customer"];
			$obj_service->id		= $this->obj_table->data[$i]["id_service"];

			$obj_service->load_data();
			$obj_service->load_data_options();

			$this->obj_table->data[$i]["name_service"]		= $obj_service->data["name_service"];
			$this->obj_table->data[$i]["typeid"]			= $obj_service->data["typeid_string"];
			$this->obj_table->data[$i]["billing_cycles"]		= $obj_service->data["billing_cycle_string"];
			$this->obj_table->data[$i]["discount"]			= $obj_service->data["discount"];
			$this->obj_table->data[$i]["price_with_discount"]	= $obj_service->data["price_with_discount"];
			$this->obj_table->data[$i]["description"]		= $obj_service->data["description"];

			// calculate pricing
			foreach ($obj_cycles_sql->data as $data_cycles)
			{
				if ($obj_service->data["billing_cycle"] == $data_cycles["id"])
				{
					if ($data_cycles["priority"] < 32)
					{
						// monthly or less
						if ($data_cycles["name"] == "monthly")
						{
							// monthly billed service
							$this->obj_table->data[$i]["price_monthly"] = $obj_service->data["price"];
						}
						else
						{
							// less than a month, calculate a month's amount
							$ratio = 28 / $data_cycles["priority"];

							$this->obj_table->data[$i]["price_monthly"] = $obj_service->data["price"] * $ratio;
						}
					}
					else
					{
						if ($data_cycles["name"] == "yearly")
						{
							// yearly billed service
							$this->obj_table->data[$i]["price_yearly"] = $obj_service->data["price"];
						}
						else
						{
							// more than a month, less than a year, calcuate a year's amount
							$ratio = 365 / $data_cycles["priority"];

							$this->obj_table->data[$i]["price_yearly"] = $obj_service->data["price"] * $ratio;
						}
					}
				}

			} // end of calculate pricing


			// apply discount if enabled
			if ($_SESSION["form"]["service_list"]["filters"]["filter_show_prices_with_discount"])
			{
				if (!empty($this->obj_table->data[$i]["price_monthly"]))
				{
					$this->obj_table->data[$i]["price_monthly"] = $this->obj_table->data[$i]["price_monthly"] - ($this->obj_table->data[$i]["price_monthly"] * ($this->obj_table->data[$i]["discount"] /100));
				}

				if (!empty($this->obj_table->data[$i]["price_yearly"]))
				{
					$this->obj_table->data[$i]["price_yearly"] = $this->obj_table->data[$i]["price_yearly"] - ($this->obj_table->data[$i]["price_yearly"] * ($this->obj_table->data[$i]["discount"] /100));
				}
			}


			// special handling for bundle items
			if ($obj_service->data["id_bundle_component"])
			{
				// bundle items have no cost, since it's charged as part of the bundle
				$this->obj_table->data[$i]["price_monthly"]	= NULL;
				$this->obj_table->data[$i]["price_yearly"]	= NULL;

/*				// prefix the name with bundle, if haven't already for UI
				if (!strpos($this->obj_table->data[$i]["name_service"], '[bundled]'))
				{
					$this->obj_table->data[$i]["name_service"] = '[bundled] '. $this->obj_table->data[$i]["name_service"];
				}
*/
			}

		}

	}



	function render_html()
	{
		// heading
		print "<h3>CUSTOMER SERVICES</h3>";
		print "<p>This page allows you to manage all the services that the customer is assigned to.</p>";
	

		if (!$this->obj_table->data_num_rows)
		{
			format_msgbox("info", "<p>This customer is not currently subscribed to any services.</p>");

			if (user_permissions_get("customers_write"))
			{
				print "<p><b><a class=\"button\" href=\"index.php?page=customers/service-edit.php&id_customer=". $this->id ."\">Add a new service to this customer</a></b></p>";
			}
		}
		else
		{
			// details link
			$structure = NULL;
			$structure["id_customer"]["value"]		= $this->id;
			$structure["id_service_customer"]["column"]	= "id_service_customer";
			$this->obj_table->add_link("details", "customers/service-edit.php", $structure);

			// periods link
			$structure = NULL;
			$structure["id_customer"]["value"]		= $this->id;
			$structure["id_service_customer"]["column"]	= "id_service_customer";
			$this->obj_table->add_link("periods", "customers/service-history.php", $structure);
			
			
			if (user_permissions_get("customers_write"))
			{
				// delete link
				$structure = NULL;
				$structure["id_customer"]["value"]		= $this->id;
				$structure["id_service_customer"]["column"]	= "id_service_customer";
				$this->obj_table->add_link("delete", "customers/service-delete.php", $structure);
			}


			// display the table
			$this->obj_table->render_options_form();
			$this->obj_table->render_table_html();


			if (user_permissions_get("customers_write"))
			{
				print "<p><a class=\"button\" href=\"index.php?page=customers/service-edit.php&id_customer=". $this->id ."\">Add Service to Customer</a> <a class=\"button\" href=\"customers/services-invoicegen-process.php?customerid=". $this->id ."\">Generate any new invoices</a></p>";
			}
		}

	}

} // end of page_output class


?>
