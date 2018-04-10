<?php
/*
	vendors/invoices.php
	
	access: vendors_view

	Lists all the invoices belonging to the selected vendor
*/

class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_table;


	function __construct()
	{
		// fetch variables
		$this->id = @security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Vendor's Details", "page=vendors/view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Vendor's Journal", "page=vendors/journal.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Vendor's Invoices", "page=vendors/invoices.php&id=". $this->id ."", TRUE);

		if (user_permissions_get("vendors_write"))
		{
			$this->obj_menu_nav->add_item("Delete Vendor", "page=vendors/delete.php&id=". $this->id ."");
		}
	}



	function check_permissions()
	{
		return user_permissions_get("vendors_view");
	}



	function check_requirements()
	{
		// verify that vendor exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM vendors WHERE id='". $this->id ."'";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested vendor (". $this->id .") does not exist - possibly the vendor has been deleted.");
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
		$this->obj_table->tablename	= "vendor_invoices";

		// define all the columns and structure
		$this->obj_table->add_column("standard", "code_invoice", "account_ap.code_invoice");
		$this->obj_table->add_column("standard", "code_ordernumber", "account_ap.code_ordernumber");
		$this->obj_table->add_column("standard", "code_ponumber", "account_ap.code_ponumber");
		$this->obj_table->add_column("standard", "name_staff", "staff.name_staff");
		$this->obj_table->add_column("date", "date_trans", "account_ap.date_trans");
		$this->obj_table->add_column("date", "date_due", "account_ap.date_due");
		$this->obj_table->add_column("price", "amount_tax", "account_ap.amount_tax");
		$this->obj_table->add_column("price", "amount", "account_ap.amount");
		$this->obj_table->add_column("price", "amount_total", "account_ap.amount_total");
		$this->obj_table->add_column("price", "amount_paid", "account_ap.amount_paid");

		// totals
		$this->obj_table->total_columns		= array("amount_tax", "amount", "amount_total", "amount_paid");

		
		// defaults
		$this->obj_table->columns		= array("code_invoice", "name_staff", "date_trans", "date_due", "amount_total", "amount_paid");
		$this->obj_table->columns_order		= array("code_invoice");

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("account_ap");
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "account_ap.id");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN staff ON staff.id = account_ap.employeeid");
		$this->obj_table->sql_obj->prepare_sql_addwhere("account_ap.vendorid='". $this->id ."'");


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
		$structure["sql"]	= "account_ap.employeeid='value'";
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
		$structure["sql"]		= "account_ap.amount_paid!=account_ap.amount_total";
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
		print "<h3>VENDOR'S INVOICES</h3>";
		print "<p>This page lists all the AP invoices from this vendor.</p>";

		// create invoice link
		if (user_permissions_get("accounts_ap_write"))
		{
			print "<p><a class=\"button\" href=\"index.php?page=accounts/ap/invoice-add.php&vendorid=". $this->id ."\">Create New Invoice</a></p>";
		}

		// display options form
		$this->obj_table->render_options_form();
		
		// display table
		if (!count($this->obj_table->columns))
		{
			format_msgbox("important", "<p>Please select some valid options to display.</p>");
		}
		elseif (!$this->obj_table->data_num_rows)
		{
			format_msgbox("info", "<p>You currently have no invoices belonging to this vendor and matching your search requirements.</p>");
		}
		else
		{
			if (user_permissions_get("accounts_ap_view"))
			{
				// view link
				$structure = NULL;
				$structure["id"]["column"]	= "id";
				$this->obj_table->add_link("view", "accounts/ap/invoice-view.php", $structure);
			}

			// display the table
			$this->obj_table->render_table_html();


			// display CSV download link
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=csv&page=vendors/invoices.php&id=". $this->id ."\">Export as CSV</a></p>";
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=pdf&page=vendors/invoices.php&id=". $this->id ."\">Export as PDF</a></p>";
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



} // end class page_output
?>
