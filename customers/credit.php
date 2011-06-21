<?php
/*
	customers/credit.php
	
	access: customers_view
		customers_credit

	Displays any credit on the customer's account and allows new credit to be added.
*/


require("include/customers/inc_customers.php");


class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_table;

	
	function page_output()
	{
		// fetch variables
		$this->obj_customer		= New customer_credits;
		$this->obj_customer->id		= @security_script_input('/^[0-9]*$/', $_GET["id_customer"]);



		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Customer's Details", "page=customers/view.php&id=". $this->obj_customer->id ."");

		if (sql_get_singlevalue("SELECT value FROM config WHERE name='MODULE_CUSTOMER_PORTAL' LIMIT 1") == "enabled")
		{
			$this->obj_menu_nav->add_item("Portal Options", "page=customers/portal.php&id=". $this->obj_customer->id ."");
		}

		$this->obj_menu_nav->add_item("Customer's Journal", "page=customers/journal.php&id=". $this->obj_customer->id ."");
		$this->obj_menu_nav->add_item("Customer's Orders", "page=customers/orders.php&id_customer=". $this->obj_customer->id ."");
		$this->obj_menu_nav->add_item("Customer's Invoices", "page=customers/invoices.php&id=". $this->obj_customer->id ."");
		$this->obj_menu_nav->add_item("Customer's Credit", "page=customers/credit.php&id_customer=". $this->obj_customer->id ."", TRUE);
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

		$this->obj_table->language	= $_SESSION["user"]["lang"];
		$this->obj_table->tablename	= "customers_credits";

		// define all the columns and structure
		$this->obj_table->add_column("date", "date_trans", "");
		$this->obj_table->add_column("standard", "type", "");
		$this->obj_table->add_column("standard", "accounts", "NONE");
		$this->obj_table->add_column("standard", "employee", "CONCAT_WS(' -- ', staff_code, name_staff)");
		$this->obj_table->add_column("standard", "description", "");
		$this->obj_table->add_column("money", "amount_total", "");


		// totals
		$this->obj_table->total_columns	= array("amount_total");

		
		// defaults
		$this->obj_table->columns	= array("date_trans", "type", "accounts", "description", "amount_total");
		$this->obj_table->columns_order	= array("date_trans", "type");

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("customers_credits");
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "customers_credits.id");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN staff ON staff.id = customers_credits.id_employee");
		$this->obj_table->sql_obj->prepare_sql_addwhere("customers_credits.id_customer='". $this->obj_customer->id ."'");


		// acceptable filter options
		$this->obj_table->add_fixed_option("id_customer", $this->obj_customer->id);
		
		$structure = NULL;
		$structure["fieldname"] = "date_start";
		$structure["type"]	= "date";
		$structure["sql"]	= "date_trans >= 'value'";
		$this->obj_table->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] = "date_end";
		$structure["type"]	= "date";
		$structure["sql"]	= "date_trans <= 'value'";
		$this->obj_table->add_filter($structure);
		
		$structure		= form_helper_prepare_dropdownfromdb("id_employee", "SELECT id, staff_code as label, name_staff as label1 FROM staff ORDER BY name_staff");
		$structure["sql"]	= "customers_credits.id_employee='value'";
		$this->obj_table->add_filter($structure);


		// load options
		$this->obj_table->load_options_form();

		// fetch all the chart information
		$this->obj_table->generate_sql();
		$this->obj_table->load_data_sql();

	}


	function render_html()
	{
		// heading
		print "<h3>CUSTOMER'S CREDIT</h3>";
		print "<p>This page provides a full list of all credit belonging to this customer as well as providing the option to add additional credits to the customer.</p>";

		$this->obj_customer->credit_render_summarybox();


		// display options form	
		$this->obj_table->render_options_form();


		// display data
		if (!count($this->obj_table->columns))
		{
			format_msgbox("important", "<p>Please select some valid options to display.</p>");
		}
		elseif (!$this->obj_table->data_num_rows)
		{
			format_msgbox("info", "<p>This customer has no available credit as per the filter options.</p>");
		}
		else
		{
			// define links
			if (user_permissions_get("customers_credit"))
			{
				$structure = NULL;
				$structure["id"]["column"]	= "id";
				$this->obj_table->add_link("tbl_lnk_details", "customers/credit-edit.php", $structure);
			}

			// display the table
			$this->obj_table->render_table_html();

			// display CSV/PDF download link
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=csv&page=customers/credit.php&id_customer=". $this->obj_customer->id ."\">Export as CSV</a></p>";
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=pdf&page=customers/credit.php&id_customer=". $this->obj_customer->id ."\">Export as PDF</a></p>";
		}


		// define add credit link
		print "<p><a class=\"button\" href=\"index.php?page=accounts/ar/credit-add.php&customerid=". $this->obj_customer->id ."\">Create Credit Note</a></p>";
	}


	function render_csv()
	{
		// display table
		$this->obj_table->render_table_csv();
	}


	function render_pdf()
	{
		// display table
		$this->obj_table->render_table_pdf();
	}


} // end of page_output

?>
