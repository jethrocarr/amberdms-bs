<?php
/*
	customers/invoices.php
	
	access: customers_view

	Lists all the invoices belonging to the selected customer
*/

if (user_permissions_get('customers_view'))
{
	$id = $_GET["id"];
	
	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "Customer's Details";
	$_SESSION["nav"]["query"][]	= "page=customers/view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Customer's Journal";
	$_SESSION["nav"]["query"][]	= "page=customers/journal.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Customer's Invoices";
	$_SESSION["nav"]["query"][]	= "page=customers/invoices.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=customers/invoices.php&id=$id";
	
	$_SESSION["nav"]["title"][]	= "Customer's Services";
	$_SESSION["nav"]["query"][]	= "page=customers/services.php&id=$id";

	if (user_permissions_get('customers_write'))
	{
		$_SESSION["nav"]["title"][]	= "Delete Customer";
		$_SESSION["nav"]["query"][]	= "page=customers/delete.php&id=$id";
	}


	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);



		// heading
		print "<h3>CUSTOMER'S INVOICES</h3>";
		print "<p>This page lists all the invoices belonging to this customer. <a href=\"index.php?page=accounts/ar/invoice-add.php\">Click here to add a new invoice</a></p>";


		$mysql_string	= "SELECT id FROM `customers` WHERE id='$id'";
		$mysql_result	= mysql_query($mysql_string);
		$mysql_num_rows	= mysql_num_rows($mysql_result);

		if (!$mysql_num_rows)
		{
			print "<p><b>Error: The requested customer does not exist. <a href=\"index.php?page=customers/customers.php\">Try looking for your customer on the customer list page.</a></b></p>";
		}
		else
		{


			// establish a new table object
			$invoice_list = New table;

			$invoice_list->language	= $_SESSION["user"]["lang"];
			$invoice_list->tablename	= "customer_invoices";

			// define all the columns and structure
			$invoice_list->add_column("standard", "code_invoice", "account_ar.code_invoice");
			$invoice_list->add_column("standard", "code_ordernumber", "account_ar.code_ordernumber");
			$invoice_list->add_column("standard", "code_ponumber", "account_ar.code_ponumber");
			$invoice_list->add_column("standard", "name_staff", "staff.name_staff");
			$invoice_list->add_column("date", "date_trans", "account_ar.date_trans");
			$invoice_list->add_column("date", "date_due", "account_ar.date_due");
			$invoice_list->add_column("price", "amount_tax", "account_ar.amount_tax");
			$invoice_list->add_column("price", "amount", "account_ar.amount");
			$invoice_list->add_column("price", "amount_total", "account_ar.amount_total");
			$invoice_list->add_column("price", "amount_paid", "account_ar.amount_paid");

			// totals
			$invoice_list->total_columns	= array("amount_tax", "amount", "amount_total", "amount_paid");

			
			// defaults
			$invoice_list->columns		= array("code_invoice", "name_staff", "date_trans", "date_due", "amount_total", "amount_paid");
			$invoice_list->columns_order	= array("code_invoice");

			// define SQL structure
			$invoice_list->sql_obj->prepare_sql_settable("account_ar");
			$invoice_list->sql_obj->prepare_sql_addfield("id", "account_ar.id");
			$invoice_list->sql_obj->prepare_sql_addjoin("LEFT JOIN staff ON staff.id = account_ar.employeeid");
			$invoice_list->sql_obj->prepare_sql_addwhere("account_ar.customerid='$id'");


			// acceptable filter options
			$invoice_list->add_fixed_option("id", $id);
			
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

			$structure = NULL;
			$structure["fieldname"] 	= "hide_closed";
			$structure["type"]		= "checkbox";
			$structure["options"]["label"]	= "Hide Closed Invoices";
			$structure["defaultvalue"]	= "";
			$structure["sql"]		= "account_ar.amount_paid!=account_ar.amount_total";
			$invoice_list->add_filter($structure);




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
				print "<p><b>You currently have no invoices belonging to this customer and matching your search requirements.</b></p>";
			}
			else
			{
				// view link
				if (user_permissions_get("accounts_ar_view"))
				{
					$structure = NULL;
					$structure["id"]["column"]	= "id";
					$invoice_list->add_link("view invoice", "accounts/ar/invoice-view.php", $structure);
				}

				// display the table
				$invoice_list->render_table();

				// TODO: display CSV download link
			}
			
		} // end if customer exists

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
