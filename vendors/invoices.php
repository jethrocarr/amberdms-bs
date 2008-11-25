<?php
/*
	vendors/invoices.php
	
	access: vendors_view

	Lists all the invoices belonging to the selected vendor
*/

if (user_permissions_get('vendors_view'))
{
	$id = $_GET["id"];
	
	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "Vendor's Details";
	$_SESSION["nav"]["query"][]	= "page=vendors/view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Vendors's Journal";
	$_SESSION["nav"]["query"][]	= "page=vendors/journal.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Vendor's Invoices";
	$_SESSION["nav"]["query"][]	= "page=vendors/invoices.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=vendors/invoices.php&id=$id";

	
	if (user_permissions_get('vendors_write'))
	{
		$_SESSION["nav"]["title"][]	= "Delete Vendor";
		$_SESSION["nav"]["query"][]	= "page=vendors/delete.php&id=$id";
	}



	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);



		// heading
		print "<h3>VENDOR'S INVOICES</h3>";
		print "<p>This page lists all the AP invoices from this vendor. <a href=\"index.php?page=accounts/ap/invoice-add.php\">Click here to add a new AP invoice</a>.</p>";


		$mysql_string	= "SELECT id FROM `vendors` WHERE id='$id'";
		$mysql_result	= mysql_query($mysql_string);
		$mysql_num_rows	= mysql_num_rows($mysql_result);

		if (!$mysql_num_rows)
		{
			print "<p><b>Error: The requested vendor does not exist. <a href=\"index.php?page=vendors/vendors.php\">Try looking for your vendor on the vendor list page.</a></b></p>";
		}
		else
		{


			// establish a new table object
			$invoice_list = New table;

			$invoice_list->language	= $_SESSION["user"]["lang"];
			$invoice_list->tablename	= "vendor_invoices";

			// define all the columns and structure
			$invoice_list->add_column("standard", "code_invoice", "account_ap.code_invoice");
			$invoice_list->add_column("standard", "code_ordernumber", "account_ap.code_ordernumber");
			$invoice_list->add_column("standard", "code_ponumber", "account_ap.code_ponumber");
			$invoice_list->add_column("standard", "name_staff", "staff.name_staff");
			$invoice_list->add_column("date", "date_trans", "account_ap.date_trans");
			$invoice_list->add_column("date", "date_due", "account_ap.date_due");
			$invoice_list->add_column("price", "amount_tax", "account_ap.amount_tax");
			$invoice_list->add_column("price", "amount", "account_ap.amount");
			$invoice_list->add_column("price", "amount_total", "account_ap.amount_total");
			$invoice_list->add_column("price", "amount_paid", "account_ap.amount_paid");

			// totals
			$invoice_list->total_columns	= array("amount_tax", "amount", "amount_total", "amount_paid");

			
			// defaults
			$invoice_list->columns		= array("code_invoice", "name_staff", "date_trans", "date_due", "amount_total", "amount_paid");
			$invoice_list->columns_order	= array("code_invoice");

			// define SQL structure
			$invoice_list->sql_obj->prepare_sql_settable("account_ap");
			$invoice_list->sql_obj->prepare_sql_addfield("id", "account_ap.id");
			$invoice_list->sql_obj->prepare_sql_addjoin("LEFT JOIN staff ON staff.id = account_ap.employeeid");
			$invoice_list->sql_obj->prepare_sql_addwhere("account_ap.vendorid='$id'");


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
			$structure["sql"]	= "account_ap.employeeid='value'";
			$invoice_list->add_filter($structure);

			$structure = NULL;
			$structure["fieldname"] 	= "hide_closed";
			$structure["type"]		= "checkbox";
			$structure["options"]["label"]	= "Hide Closed Invoices";
			$structure["defaultvalue"]	= "";
			$structure["sql"]		= "account_ap.amount_paid!=account_ap.amount_total";
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
				print "<p><b>You currently have no invoices belonging to this vendor and matching your search requirements.</b></p>";
			}
			else
			{
				if (user_permissions_get("accounts_ap_view"))
				{
					// view link
					$structure = NULL;
					$structure["id"]["column"]	= "id";
					$invoice_list->add_link("view", "accounts/ap/invoice-view.php", $structure);
				}

				// display the table
				$invoice_list->render_table();

				// TODO: display CSV download link
			}
			
		} // end if vendor exists

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
