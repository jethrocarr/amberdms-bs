<?php
/*
	accounts/quotes/quotes.php
	
	access: accounts_quotes_view

	Lists all the quotes and allows the user to search through them.
*/

if (user_permissions_get('accounts_quotes_view'))
{
	function page_render()
	{
		// establish a new table object
		$quote_list = New table;

		$quote_list->language		= $_SESSION["user"]["lang"];
		$quote_list->tablename		= "account_quotes";

		// define all the columns and structure
		$quote_list->add_column("standard", "name_customer", "customers.name_customer");
		$quote_list->add_column("standard", "name_staff", "staff.name_staff");
		$quote_list->add_column("standard", "code_quote", "account_quotes.code_quote");
		$quote_list->add_column("date", "date_trans", "account_quotes.date_trans");
		$quote_list->add_column("date", "date_validtill", "account_quotes.date_validtill");
		$quote_list->add_column("price", "amount_tax", "account_quotes.amount_tax");
		$quote_list->add_column("price", "amount", "account_quotes.amount");
		$quote_list->add_column("price", "amount_total", "account_quotes.amount_total");

		// totals
		$quote_list->total_columns	= array("amount_tax", "amount", "amount_total");

		
		// defaults
		$quote_list->columns		= array("name_customer", "code_quote", "date_trans", "amount_total");
		$quote_list->columns_order	= array("code_quote");

		// define SQL structure
		$quote_list->sql_obj->prepare_sql_settable("account_quotes");
		$quote_list->sql_obj->prepare_sql_addfield("id", "account_quotes.id");
		$quote_list->sql_obj->prepare_sql_addjoin("LEFT JOIN customers ON customers.id = account_quotes.customerid");
		$quote_list->sql_obj->prepare_sql_addjoin("LEFT JOIN staff ON staff.id = account_quotes.employeeid");


		// acceptable filter options
		$structure = NULL;
		$structure["fieldname"] = "date_start";
		$structure["type"]	= "date";
		$structure["sql"]	= "date_trans >= 'value'";
		$quote_list->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] = "date_end";
		$structure["type"]	= "date";
		$structure["sql"]	= "date_trans <= 'value'";
		$quote_list->add_filter($structure);
		
		$structure		= form_helper_prepare_dropdownfromdb("employeeid", "SELECT id, name_staff as label FROM staff ORDER BY name_staff ASC");
		$structure["sql"]	= "account_quotes.employeeid='value'";
		$quote_list->add_filter($structure);

		$structure		= form_helper_prepare_dropdownfromdb("customerid", "SELECT id, name_customer as label FROM customers ORDER BY name_customer ASC");
		$structure["sql"]	= "account_quotes.customerid='value'";
		$quote_list->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "hide_closed";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "Hide Closed Quotes";
		$structure["defaultvalue"]	= "enabled";
		$structure["sql"]		= "account_quotes.date_validtill >= '". date("Y-m-d") ."'";
		$quote_list->add_filter($structure);




		// heading
		print "<h3>LIST OF QUOTES</h3><br><br>";


		// options form
		$quote_list->load_options_form();
		$quote_list->render_options_form();


		// fetch all the chart information
		$quote_list->generate_sql();
		$quote_list->load_data_sql();

		if (!count($quote_list->columns))
		{
			print "<p><b>Please select some valid options to display.</b></p>";
		}
		elseif (!$quote_list->data_num_rows)
		{
			print "<p><b>You currently have no quotes in your database.</b></p>";
		}
		else
		{
			// view link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$quote_list->add_link("view", "accounts/quotes/quotes-view.php", $structure);

			// display the table
			$quote_list->render_table();

			// TODO: display CSV download link
		}

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
