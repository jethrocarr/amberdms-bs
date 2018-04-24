<?php
/*
	accounts/ar/ar.php
	
	access: accounts_ar_view

	Lists all the invoices/invoices in the system and allows the user to search + filter them
*/


class page_output
{
	var $obj_table;


	function check_permissions()
	{
		return user_permissions_get('accounts_ar_view');
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
		$this->obj_table->tablename	= "account_ar";

		// define all the columns and structure
		$this->obj_table->add_column("standard", "code_invoice", "account_ar.code_invoice");
		$this->obj_table->add_column("standard", "code_ordernumber", "account_ar.code_ordernumber");
		$this->obj_table->add_column("standard", "code_ponumber", "account_ar.code_ponumber");
		$this->obj_table->add_column("standard", "name_customer", "CONCAT_WS(' -- ', customers.code_customer, customers.name_customer)");
		$this->obj_table->add_column("standard", "name_staff", "CONCAT_WS(' -- ', staff.staff_code, staff.name_staff)");
		$this->obj_table->add_column("date", "date_trans", "account_ar.date_trans");
		$this->obj_table->add_column("date", "date_due", "account_ar.date_due");
		$this->obj_table->add_column("price", "amount_tax", "account_ar.amount_tax");
		$this->obj_table->add_column("price", "amount", "account_ar.amount");
		$this->obj_table->add_column("price", "amount_total", "account_ar.amount_total");
		$this->obj_table->add_column("price", "amount_paid", "account_ar.amount_paid");
		$this->obj_table->add_column("bool_tick", "sent", "account_ar.sentmethod");
                $this->obj_table->add_column("bool_tick", "cancelled", "account_ar.cancelled");
                $this->obj_table->add_column("bool_tick", "closed", "(account_ar.amount_paid=account_ar.amount_total AND account_ar.amount_total>0)");

		// totals
		$this->obj_table->total_columns	= array("amount_tax", "amount", "amount_total", "amount_paid");

		
		// defaults
		$this->obj_table->columns		= array("code_invoice", "name_customer", "date_trans", "amount_total", "amount_paid","closed");
		$this->obj_table->columns_order		= array("code_invoice");
		$this->obj_table->columns_order_options	= array("code_invoice", "code_ordernumber", "code_ponumber", "name_customer", "name_staff", "date_trans", "date_due", "sent");

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("account_ar");
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "account_ar.id");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN customers ON customers.id = account_ar.customerid");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN staff ON staff.id = account_ar.employeeid");


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
		$structure["sql"]			= "account_ar.employeeid='value'";
		$structure["options"]["search_filter"]	= "enabled";
		$this->obj_table->add_filter($structure);

		$structure				= form_helper_prepare_dropdownfromdb("customerid", "SELECT id, code_customer as label, name_customer as label1 FROM customers ORDER BY name_customer");
		$structure["sql"]			= "account_ar.customerid='value'";
		$structure["options"]["search_filter"]	= "enabled";
		$this->obj_table->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] 		= "invoice_notes_search";
		$structure["type"]			= "input";
		$structure["sql"]			= "notes LIKE '%value%'";
		$this->obj_table->add_filter($structure);
		
		$structure = NULL;
		$structure["fieldname"]                 = "hide_closed";
		$structure["type"]                      = "checkbox";
		$structure["options"]["label"]          = "Hide Completed Invoices";
		$structure["defaultvalue"]              = "enabled";
		$structure["sql"]                       = "((account_ar.amount_paid<>account_ar.amount_total AND account_ar.amount_total>0) OR (account_ar.amount_total=0))";
		$this->obj_table->add_filter($structure);
                
                $structure = NULL;
		$structure["fieldname"]                 = "hide_cancelled";
		$structure["type"]                      = "checkbox";
		$structure["options"]["label"]          = "Hide Cancelled Invoices";
                $structure["options"]["no_fieldname"]   = true;
                $structure["options"]["no_shift"]       = true;
		$structure["defaultvalue"]              = "enabled";
		$structure["sql"]                       = "account_ar.cancelled=0";
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
		print "<h3>LIST OF ACCOUNTS RECEIVABLES INVOICES</h3><br><br>";

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
			$sql_obj->string	= "SELECT id FROM account_ar LIMIT 1";
			$sql_obj->execute();
			
			if ($sql_obj->num_rows())
			{
				format_msgbox("important", "<p>Your current filter options do not match to any invoices.</p>");
			}
			else
			{
				format_msgbox("info", "<p>You currently have no AR invoices in your database.</p>");
			}
		}			
		else
		{
			// details link 
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("details", "accounts/ar/invoice-view.php", $structure);


			// items link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("items", "accounts/ar/invoice-items.php", $structure);

			// payments link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("payments", "accounts/ar/invoice-payments.php", $structure);

                        // journal link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("journal", "accounts/ar/journal.php", $structure);

			// export link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("export", "accounts/ar/invoice-export.php", $structure);

			

			// display the table
			$this->obj_table->render_table_html();

			// display CSV/PDF download link
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=csv&page=accounts/ar/ar.php\">Export as CSV</a></p>";
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=pdf&page=accounts/ar/ar.php\">Export as PDF</a></p>";
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
