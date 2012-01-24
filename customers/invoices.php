<?php
/*
	customers/invoices.php
	
	access: customers_view

	Lists all the invoices belonging to the selected customer
*/
	
class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_table;

	
	function page_output()
	{
		// fetch variables
		$this->id = @security_script_input('/^[0-9]*$/', $_GET["id"]);


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
		$this->obj_menu_nav->add_item("Customer's Invoices", "page=customers/invoices.php&id=". $this->id ."", TRUE);
		$this->obj_menu_nav->add_item("Customer's Credit", "page=customers/credit.php&id_customer=". $this->id ."");
		$this->obj_menu_nav->add_item("Customer's Services", "page=customers/services.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Resellers Customers", "page=customers/reseller.php&id=". $this->id ."");

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
		// verifiy that customer exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM customers WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "The requested customer (". $this->id .") does not exist - possibly the customer has been deleted.");
			return 0;
		}

		unset($sql_obj);

		return 1;
	}



	function execute()
	{

		// establish a new table object
		$this->obj_table = New table;

		$this->obj_table->language	= $_SESSION["user"]["lang"];
		$this->obj_table->tablename	= "customer_invoices";

		// define all the columns and structure
		$this->obj_table->add_column("standard", "code_invoice", "account_ar.code_invoice");
		$this->obj_table->add_column("standard", "code_ordernumber", "account_ar.code_ordernumber");
		$this->obj_table->add_column("standard", "code_ponumber", "account_ar.code_ponumber");
		$this->obj_table->add_column("standard", "name_staff", "staff.name_staff");
		$this->obj_table->add_column("date", "date_trans", "account_ar.date_trans");
		$this->obj_table->add_column("date", "date_due", "account_ar.date_due");
		$this->obj_table->add_column("price", "amount_tax", "account_ar.amount_tax");
		$this->obj_table->add_column("price", "amount", "account_ar.amount");
		$this->obj_table->add_column("price", "amount_total", "account_ar.amount_total");
		$this->obj_table->add_column("price", "amount_paid", "account_ar.amount_paid");
		$this->obj_table->add_column("bool_tick", "sent", "account_ar.sentmethod");

		// totals
		$this->obj_table->total_columns	= array("amount_tax", "amount", "amount_total", "amount_paid");

		
		// defaults
		$this->obj_table->columns	= array("code_invoice", "name_staff", "date_trans", "date_due", "amount_total", "amount_paid", "sent");
		$this->obj_table->columns_order	= array("code_invoice");

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("account_ar");
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "account_ar.id");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN staff ON staff.id = account_ar.employeeid");
		$this->obj_table->sql_obj->prepare_sql_addwhere("account_ar.customerid='". $this->id ."'");


		// acceptable filter options
		$this->obj_table->add_fixed_option("id", $this->id);
		
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
		
		$structure		= form_helper_prepare_dropdownfromdb("employeeid", "SELECT id, staff_code as label, name_staff as label1 FROM staff ORDER BY name_staff");
		$structure["sql"]	= "account_ar.employeeid='value'";
		$this->obj_table->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] 		= "invoice_notes_search";
		$structure["type"]			= "input";
		$structure["sql"]			= "notes LIKE '%value%'";
		$this->obj_table->add_filter($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "hide_closed";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "Hide Closed Invoices";
		$structure["defaultvalue"]	= "";
		$structure["sql"]		= "account_ar.amount_paid!=account_ar.amount_total";
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
		print "<h3>CUSTOMER'S INVOICES</h3>";
		print "<p>This page lists all the invoices belonging to this customer.</p>";


		// create invoice link
		if (user_permissions_get("accounts_ar_write"))
		{
			print "<p><a class=\"button\" href=\"index.php?page=accounts/ar/invoice-add.php&customerid=". $this->id ."\">Create New Invoice</a></p>";
		}

	
		// display options form	
		$this->obj_table->render_options_form();


		// display data
		if (!count($this->obj_table->columns))
		{
			format_msgbox("important", "<p>Please select some valid options to display.</p>");
		}
		elseif (!$this->obj_table->data_num_rows)
		{
			format_msgbox("info", "<p>You currently have no invoices belonging to this customer or matching your search requirements.</p>");
		}
		else
		{
			// view link
			if (user_permissions_get("accounts_ar_view"))
			{
				$structure = NULL;
				$structure["id"]["column"]	= "id";
				$this->obj_table->add_link("view invoice", "accounts/ar/invoice-view.php", $structure);
			}

			// display the table
			$this->obj_table->render_table_html();

			// display CSV/PDF download link
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=csv&page=customers/invoices.php&id=". $this->id ."\">Export as CSV</a></p>";
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=pdf&page=customers/invoices.php&id=". $this->id ."\">Export as PDF</a></p>";
		}
	
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
