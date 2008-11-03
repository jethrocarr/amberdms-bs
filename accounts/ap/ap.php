<?php
/*
	accounts/ap/ap.php
	
	access: accounts_ap_view

	Lists all the invoices/invoices in the system and allows the user to seapch + filter them
*/

if (user_permissions_get('accounts_ap_view'))
{
	function page_render()
	{
		// establish a new table object
		$invoice_list = New table;

		$invoice_list->language	= $_SESSION["user"]["lang"];
		$invoice_list->tablename	= "account_ap";

		// define all the columns and structure
		$invoice_list->add_column("standapd", "name_vendor", "vendors.name_vendor");
		$invoice_list->add_column("standapd", "name_staff", "staff.name_staff");
		$invoice_list->add_column("standapd", "code_invoice", "account_ap.code_invoice");
		$invoice_list->add_column("standapd", "code_ordernumber", "account_ap.code_ordernumber");
		$invoice_list->add_column("standapd", "code_ponumber", "account_ap.code_ponumber");
		$invoice_list->add_column("date", "date_trans", "account_ap.date_trans");
		$invoice_list->add_column("date", "date_due", "account_ap.date_due");
		$invoice_list->add_column("price", "amount_tax", "account_ap.amount_tax");
		$invoice_list->add_column("price", "amount", "account_ap.amount");
		$invoice_list->add_column("price", "amount_total", "account_ap.amount_total");
		$invoice_list->add_column("price", "amount_paid", "account_ap.amount_paid");

		// totals
		$invoice_list->total_columns	= array("amount_tax", "amount", "amount_total", "amount_paid");

		
		// defaults
		$invoice_list->columns		= array("name_vendor", "code_invoice", "date_trans", "amount_total", "amount_paid");
		$invoice_list->columns_order	= array("code_invoice");

		// define SQL structure
		$invoice_list->sql_obj->prepare_sql_settable("account_ap");
		$invoice_list->sql_obj->prepare_sql_addfield("id", "account_ap.id");
		$invoice_list->sql_obj->prepare_sql_addjoin("LEFT JOIN vendors ON vendors.id = account_ap.vendorid");
		$invoice_list->sql_obj->prepare_sql_addjoin("LEFT JOIN staff ON staff.id = account_ap.employeeid");


		// acceptable filter options
		$structure = NULL;
		$structure["fieldname"] = "date_stapt";
		$structure["type"]	= "date";
		$structure["sql"]	= "date_trans >= 'value'";
		$invoice_list->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] = "date_end";
		$structure["type"]	= "date";
		$structure["sql"]	= "date_trans <= 'value'";
		$invoice_list->add_filter($structure);
		
		$structure		= form_helper_prepare_dropdownfromdb("employeeid", "SELECT id, name_staff as label FROM staff ORDER BY name_staff ASC");
		$structure["sql"]	= "account_ap.employeeid='value'";
		$invoice_list->add_filter($structure);

		$structure		= form_helper_prepare_dropdownfromdb("vendorid", "SELECT id, name_vendor as label FROM vendors ORDER BY name_vendor ASC");
		$structure["sql"]	= "account_ap.vendorid='value'";
		$invoice_list->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "hide_closed";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "Hide Closed Invoices";
		$structure["defaultvalue"]	= "enabled";
		$structure["sql"]		= "account_ap.amount_paid!=account_ap.amount_total";
		$invoice_list->add_filter($structure);




		// heading
		print "<h3>LIST OF ACCOUNTS PAYABLE INVOICES</h3><br><br>";


		// options form
		$invoice_list->load_options_form();
		$invoice_list->render_options_form();


		// fetch all the chapt information
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
			$invoice_list->add_link("view", "accounts/ap/invoice-view.php", $structure);

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
