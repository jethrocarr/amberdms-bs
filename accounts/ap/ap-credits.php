<?php
/*
	accounts/ap/ap-credits.php
	
	access: accounts_ap_view

	List all the credit notes in the system and allow users to view and search them.
*/


class page_output
{
	var $obj_table;


	function check_permissions()
	{
		return user_permissions_get('accounts_ap_view');
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
		$this->obj_table->tablename	= "account_ap_credit_credit";

		// define all the columns and structure
		$this->obj_table->add_column("standard", "code_credit", "account_ap_credit.code_credit");
		$this->obj_table->add_column("standard", "code_ordernumber", "account_ap_credit.code_ordernumber");
		$this->obj_table->add_column("standard", "code_ponumber", "account_ap_credit.code_ponumber");
		$this->obj_table->add_column("standard", "name_vendor", "vendors.name_vendor");
		$this->obj_table->add_column("standard", "name_staff", "CONCAT_WS(' -- ', staff.staff_code, staff.name_staff)");
		$this->obj_table->add_column("date", "date_trans", "account_ap_credit.date_trans");
		$this->obj_table->add_column("price", "amount_tax", "account_ap_credit.amount_tax");
		$this->obj_table->add_column("price", "amount", "account_ap_credit.amount");
		$this->obj_table->add_column("price", "amount_total", "account_ap_credit.amount_total");

		// totals
		$this->obj_table->total_columns	= array("amount_tax", "amount", "amount_total");

		
		// defaults
		$this->obj_table->columns		= array("code_credit", "name_vendor", "date_trans", "amount_total");
		$this->obj_table->columns_order		= array("code_credit");
		$this->obj_table->columns_order_options	= array("code_credit", "code_ordernumber", "code_ponumber", "name_vendor", "name_staff", "date_trans", "date_due", "sent");

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("account_ap_credit");
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "account_ap_credit.id");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN vendors ON vendors.id = account_ap_credit.vendorid");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN staff ON staff.id = account_ap_credit.employeeid");


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
		
		$structure				= form_helper_prepare_dropdownfromdb("employeeid", "SELECT id, staff_code as label, name_staff as label1 FROM staff ORDER BY name_staff");
		$structure["sql"]			= "account_ap_credit.employeeid='value'";
		$structure["options"]["search_filter"]	= "enabled";
		$this->obj_table->add_filter($structure);

		$structure				= form_helper_prepare_dropdownfromdb("vendorid", "SELECT id, code_vendor as label, name_vendor as label1 FROM vendors ORDER BY name_vendor");
		$structure["sql"]			= "account_ap_credit.vendorid='value'";
		$structure["options"]["search_filter"]	= "enabled";
		$this->obj_table->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] 		= "credit_notes_search";
		$structure["type"]			= "input";
		$structure["sql"]			= "notes LIKE '%value%'";
		$this->obj_table->add_filter($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "hide_closed";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "Hide Locked Credits";
		$structure["defaultvalue"]	= "enabled";
		$structure["sql"]		= "account_ap_credit.locked='0'";
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
		print "<h3>LIST OF ACCOUNTS PAYABLES CREDIT NOTES</h3>";
		print "<p>This page only displays credit notes which have yet to be completed, unless the \"Hide Paid Credits\" check box is unchecked.</p>";

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
			$sql_obj->string	= "SELECT id FROM account_ap_credit LIMIT 1";
			$sql_obj->execute();
			
			if ($sql_obj->num_rows())
			{
				format_msgbox("important", "<p>Your current filter options do not match to any credit notes.</p>");
			}
			else
			{
				format_msgbox("info", "<p>You currently have no AP credit notes in your database.</p>");
			}
		}			
		else
		{
			// details link 
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("details", "accounts/ap/credit-view.php", $structure);


			// items link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("items", "accounts/ap/credit-items.php", $structure);

			// payments link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("payments", "accounts/ap/credit-payments.php", $structure);

			

			// display the table
			$this->obj_table->render_table_html();

			// display CSV/PDF download link
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=csv&page=accounts/ap/ap.php\">Export as CSV</a></p>";
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=pdf&page=accounts/ap/ap.php\">Export as PDF</a></p>";
		}
	}


	function render_csv()
	{
		$this->obj_table->render_table_csv();
	}

	function render_pdf()
	{
		$this->obj_table->render_table_pdf();
	}

}

?>
