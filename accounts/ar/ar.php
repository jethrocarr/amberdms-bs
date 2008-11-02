<?php
/*
	accounts/ar/ar.php
	
	access: accounts_ar_view

	Lists all the invoices/invoices in the system and allows the user to search + filter them
*/

if (user_permissions_get('accounts_ar_view'))
{
	function page_render()
	{
		// establish a new table object
		$invoice_list = New table;

		$invoice_list->language	= $_SESSION["user"]["lang"];
		$invoice_list->tablename	= "account_ar";

		// define all the columns and structure
		$invoice_list->add_column("standard", "name_customer", "customers.name_customer");
		$invoice_list->add_column("standard", "name_staff", "staff.name_staff");
		$invoice_list->add_column("standard", "code_invoice", "account_ar.code_invoice");
		$invoice_list->add_column("standard", "code_ordernumber", "account_ar.code_ordernumber");
		$invoice_list->add_column("standard", "code_ponumber", "account_ar.code_ponumber");
		$invoice_list->add_column("date", "date_trans", "account_ar.date_trans");
		$invoice_list->add_column("date", "date_due", "account_ar.date_due");
		$invoice_list->add_column("price", "amount_tax", "account_ar.amount_tax");
		$invoice_list->add_column("price", "amount", "account_ar.amount");
		$invoice_list->add_column("price", "amount_total", "account_ar.amount_total");
		$invoice_list->add_column("price", "amount_paid", "account_ar.amount_paid");

		// totals
		$invoice_list->total_columns	= array("amount_tax", "amount", "amount_total", "amount_paid");

		
		// defaults
		$invoice_list->columns		= array("name_customer", "code_invoice", "date_trans", "amount_total", "amount_paid");
		$invoice_list->columns_order	= array("code_invoice");

		// define SQL structure
		$invoice_list->sql_obj->prepare_sql_settable("account_ar");
		$invoice_list->sql_obj->prepare_sql_addfield("id", "account_ar.id");
		$invoice_list->sql_obj->prepare_sql_addjoin("LEFT JOIN customers ON customers.id = account_ar.customerid");
		$invoice_list->sql_obj->prepare_sql_addjoin("LEFT JOIN staff ON staff.id = account_ar.employeeid");


		// acceptable filter options
		$structure = NULL;
		$structure["fieldname"] = "date_start";
		$structure["type"]	= "date";
		$structure["sql"]	= "date_trans >= 'value'";
		$invoice_list->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] = "date_end";
		$structure["type"]	= "date";
		$structure["sql"]	= "date_trans <= 'value'";
		$invoice_list->add_filter($structure);
		
		$structure		= form_helper_prepare_dropdownfromdb("employeeid", "SELECT id, name_staff as label FROM staff ORDER BY name_staff ASC");
		$structure["sql"]	= "account_ar.employeeid='value'";
		$invoice_list->add_filter($structure);

		$structure		= form_helper_prepare_dropdownfromdb("customerid", "SELECT id, name_customer as label FROM customers ORDER BY name_customer ASC");
		$structure["sql"]	= "account_ar.customerid='value'";
		$invoice_list->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "hide_closed";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "Hide Closed Invoices";
		$structure["defaultvalue"]	= "enabled";
		$structure["sql"]		= "account_ar.amount_paid!=account_ar.amount_total";
		$invoice_list->add_filter($structure);




		// heading
		print "<h3>LIST OF TRANSACTIONS</h3><br><br>";


		// options form
		$invoice_list->load_options_form();
		$invoice_list->render_options_form();


		// fetch all the chart information
		$invoice_list->generate_sql();
		$invoice_list->load_data_sql();

		if (!count($invoice_list->columns))
		{
			print "<p><b>Please select some valid options to display.</b></p>";
		}
		elseif (!$invoice_list->data_num_rows)
		{
			print "<p><b>You currently have no invoices in your database.</b></p>";
		}
		else
		{
			// view link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$invoice_list->add_link("view", "accounts/ar/invoice-view.php", $structure);

			// display the table
			$invoice_list->render_table();

			// TODO: display CSV download link
		}

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
