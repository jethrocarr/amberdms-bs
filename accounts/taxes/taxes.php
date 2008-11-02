<?php
/*
	accounts/taxes/taxes.php
	
	access: accounts_taxes_view

	Displays a list of all the taxes on the system.
*/

if (user_permissions_get('accounts_taxes_view'))
{
	function page_render()
	{
		// establish a new table object
		$tax_list = New table;

		$tax_list->language	= $_SESSION["user"]["lang"];
		$tax_list->tablename	= "account_taxes";

		// define all the columns and structure
		$tax_list->add_column("standard", "name_tax", "account_taxes.name_tax");
		$tax_list->add_column("standard", "taxrate", "account_taxes.taxrate");
		$tax_list->add_column("standard", "chartid", "account_charts.code_chart");
		$tax_list->add_column("standard", "description", "account_taxes.description");

		// defaults
		$tax_list->columns		= array("name_tax", "taxrate", "chartid", "description");
		$tax_list->columns_order	= array("name_tax");

		// define SQL structure
		$tax_list->sql_obj->prepare_sql_settable("account_taxes");
		$tax_list->sql_obj->prepare_sql_addfield("id", "account_taxes.id");
		$tax_list->sql_obj->prepare_sql_addjoin("LEFT JOIN account_charts ON account_charts.id = account_taxes.chartid");

/*
		// acceptable filter options
		$structure = NULL;
		$structure["fieldname"] = "date_start";
		$structure["type"]	= "date";
		$structure["sql"]	= "date >= 'value'";
		$tax_list->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] = "date_end";
		$structure["type"]	= "date";
		$structure["sql"]	= "date <= 'value'";
		$tax_list->add_filter($structure);
		
		$structure = NULL;
		$structure["fieldname"] = "searchbox";
		$structure["type"]	= "input";
		$structure["sql"]	= "name_tax LIKE '%value%' OR name_contact LIKE '%value%' OR contact_email LIKE '%value%' OR contact_phone LIKE '%value%' OR contact_fax LIKE '%fax%'";
		$tax_list->add_filter($structure);
*/


		// heading
		print "<h3>TAXES</h3><br><br>";


		// options form
		$tax_list->load_options_form();
		$tax_list->render_options_form();


		// fetch all the tax information
		$tax_list->generate_sql();
		$tax_list->load_data_sql();

		if (!count($tax_list->columns))
		{
			print "<p><b>Please select some valid options to display.</b></p>";
		}
		elseif (!$tax_list->data_num_rows)
		{
			print "<p><b>You currently have no taxes in your database.</b></p>";
		}
		else
		{
			// view link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$tax_list->add_link("view", "accounts/taxes/view.php", $structure);

			// ledger link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$tax_list->add_link("ledger", "accounts/taxes/ledger.php", $structure);

			// display the table
			$tax_list->render_table();

			// TODO: display CSV download link
		}

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
