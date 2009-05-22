<?php
/*
	accounts/ap/ap.php
	
	access: accounts_ap_view

	Lists all the invoices/invoices in the system and allows the user to search + filter them
*/

class page_output
{
	var $obj_table;


	function check_permissions()
	{
		return user_permissions_get("accounts_ap_view");
	}

	function check_requirements()
	{
		// nothing todo
		return 1;
	}


	function execute()
	{
		// establish a new table object
		$this->obj_table = New table;

		$this->obj_table->language	= $_SESSION["user"]["lang"];
		$this->obj_table->tablename	= "account_ap";

		// define all the columns and structure
		$this->obj_table->add_column("standard", "name_vendor", "CONCAT_WS(' -- ', vendors.code_vendor, vendors.name_vendor)");
		$this->obj_table->add_column("standard", "code_invoice", "account_ap.code_invoice");
		$this->obj_table->add_column("standard", "code_ordernumber", "account_ap.code_ordernumber");
		$this->obj_table->add_column("standard", "code_ponumber", "account_ap.code_ponumber");
		$this->obj_table->add_column("standard", "name_staff", "CONCAT_WS(' -- ', staff.staff_code, staff.name_staff)");
		$this->obj_table->add_column("date", "date_trans", "account_ap.date_trans");
		$this->obj_table->add_column("date", "date_due", "account_ap.date_due");
		$this->obj_table->add_column("price", "amount_tax", "account_ap.amount_tax");
		$this->obj_table->add_column("price", "amount", "account_ap.amount");
		$this->obj_table->add_column("price", "amount_total", "account_ap.amount_total");
		$this->obj_table->add_column("price", "amount_paid", "account_ap.amount_paid");

		// totals
		$this->obj_table->total_columns	= array("amount_tax", "amount", "amount_total", "amount_paid");

		
		// defaults
		$this->obj_table->columns		= array("name_vendor", "code_invoice", "date_trans", "amount_total", "amount_paid");
		$this->obj_table->columns_order		= array("code_invoice");
		$this->obj_table->columns_order_options	= array("name_vendor", "code_invoice", "code_ordernumber", "code_ponumber", "name_staff", "date_trans", "date_due", "sent");
		
		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("account_ap");
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "account_ap.id");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN vendors ON vendors.id = account_ap.vendorid");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN staff ON staff.id = account_ap.employeeid");


		// acceptable filter options
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

		$structure		= form_helper_prepare_dropdownfromdb("vendorid", "SELECT id, code_vendor as label, name_vendor as label1 FROM vendors ORDER BY name_vendor");
		$structure["sql"]	= "account_ap.vendorid='value'";
		$this->obj_table->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "hide_closed";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "Hide Closed Invoices";
		$structure["defaultvalue"]	= "enabled";
		$structure["sql"]		= "account_ap.amount_paid!=account_ap.amount_total";
		$this->obj_table->add_filter($structure);



		// load options
		$this->obj_table->load_options_form();


		// fetch all the chapt information
		$this->obj_table->generate_sql();
		$this->obj_table->load_data_sql();
	}


	function render_html()
	{
		// heading
		print "<h3>LIST OF ACCOUNTS PAYABLE INVOICES</h3><br><br>";


		// display options form
		$this->obj_table->render_options_form();


		// display table
		if (!count($this->obj_table->columns))
		{
			format_msgbox("important", "<p>Please select some valid options to display.</p>");
		}
		elseif (!$this->obj_table->data_num_rows)
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM account_ap LIMIT 1";
			$sql_obj->execute();
			
			if ($sql_obj->num_rows())
			{
				format_msgbox("important", "<p>Your current filter options do not match to any invoices.</p>");
			}
			else
			{
				format_msgbox("info", "<p>You currently have no AP invoices in your database.</p>");
			}
			
		}
		else
		{
			// details link 
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("details", "accounts/ap/invoice-view.php", $structure);

			// items link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("items", "accounts/ap/invoice-items.php", $structure);

			// payments link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("payments", "accounts/ap/invoice-payments.php", $structure);


			// display the table
			$this->obj_table->render_table_html();

			// display CSV download link
			print "<p align=\"right\"><a href=\"index-export.php?mode=csv&page=accounts/ap/ap.php\">Export as CSV</a></p>";
		}
	}


	function render_csv()
	{
		$this->obj_table->render_table_csv();
	}
	
}

?>
