<?php
/*
	accounts/ar/ar.php
	
	access: accounts_ar_view

	Lists all the transactions/invoices in the system and allows the user to search + filter them
*/

if (user_permissions_get('accounts_ar_view'))
{
	function page_render()
	{
		// establish a new table object
		$transaction_list = New table;

		$transaction_list->language	= $_SESSION["user"]["lang"];
		$transaction_list->tablename	= "account_ar";

		// define all the columns and structure
		$transaction_list->add_column("standard", "name_customer", "customers.name_customer");
		$transaction_list->add_column("standard", "name_staff", "staff.name_staff");
		$transaction_list->add_column("standard", "code_invoice", "account_ar.code_invoice");
		$transaction_list->add_column("standard", "code_ordernumber", "account_ar.code_ordernumber");
		$transaction_list->add_column("standard", "code_ponumber", "account_ar.code_ponumber");
		$transaction_list->add_column("date", "date_transaction", "account_ar.date_transaction");
		$transaction_list->add_column("date", "date_due", "account_ar.date_due");
		$transaction_list->add_column("date", "date_paid", "account_ar.date_paid");
		$transaction_list->add_column("price", "amount_tax", "account_ar.amount_tax");
		$transaction_list->add_column("price", "amount", "account_ar.amount");
		$transaction_list->add_column("price", "amount_total", "account_ar.amount_total");
		$transaction_list->add_column("price", "amount_paid", "account_ar.amount_paid");

		// totals
		$transaction_list->total_columns	= array("amount_tax", "amount", "amount_total", "amount_paid");

		
		// defaults
		$transaction_list->columns		= array("name_customer", "code_invoice", "date_transaction", "amount_total", "amount_paid");
		$transaction_list->columns_order	= array("code_invoice");

		// define SQL structure
		$transaction_list->sql_obj->prepare_sql_settable("account_ar");
		$transaction_list->sql_obj->prepare_sql_addfield("id", "account_ar.id");
		$transaction_list->sql_obj->prepare_sql_addjoin("LEFT JOIN customers ON customers.id = account_ar.customerid");
		$transaction_list->sql_obj->prepare_sql_addjoin("LEFT JOIN staff ON staff.id = account_ar.employeeid");


		// acceptable filter options
		$structure = NULL;
		$structure["fieldname"] = "date_start";
		$structure["type"]	= "date";
		$structure["sql"]	= "date_transaction >= 'value'";
		$transaction_list->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] = "date_end";
		$structure["type"]	= "date";
		$structure["sql"]	= "date_transaction <= 'value'";
		$transaction_list->add_filter($structure);
		
		$structure		= form_helper_prepare_dropdownfromdb("employeeid", "SELECT id, name_staff as label FROM staff ORDER BY name_staff ASC");
		$structure["sql"]	= "account_ar.employeeid='value'";
		$transaction_list->add_filter($structure);

		$structure		= form_helper_prepare_dropdownfromdb("customerid", "SELECT id, name_customer as label FROM customers ORDER BY name_customer ASC");
		$structure["sql"]	= "account_ar.customerid='value'";
		$transaction_list->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "hide_closed";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "Hide Completed Transactions";
		$structure["defaultvalue"]	= "enabled";
		$structure["sql"]		= "account_ar.amount_paid!=account_ar.amount_total";
		$transaction_list->add_filter($structure);




		// heading
		print "<h3>LIST OF TRANSACTIONS</h3><br><br>";


		// options form
		$transaction_list->load_options_form();
		$transaction_list->render_options_form();


		// fetch all the chart information
		$transaction_list->generate_sql();
		$transaction_list->load_data_sql();

		if (!count($transaction_list->columns))
		{
			print "<p><b>Please select some valid options to display.</b></p>";
		}
		elseif (!$transaction_list->data_num_rows)
		{
			print "<p><b>You currently have no transactions or invoices in your database.</b></p>";
		}
		else
		{
			// view link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$transaction_list->add_link("view", "accounts/ar/transactions-view.php", $structure);

			// display the table
			$transaction_list->render_table();

			// TODO: display CSV download link
		}

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
