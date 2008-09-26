<?php
/*
	accounts/charts/charts.php
	
	access: accounts_charts_view

	Displays a list of all the charts on the system.
*/

if (user_permissions_get('accounts_charts_view'))
{
	function page_render()
	{
		// establish a new table object
		$chart_list = New table;

		$chart_list->language	= $_SESSION["user"]["lang"];
		$chart_list->tablename	= "account_charts";

		// define all the columns and structure
		$chart_list->add_column("standard", "code_chart", "account_charts.code_chart");
		$chart_list->add_column("standard", "description", "account_charts.description");
		$chart_list->add_column("standard", "chart_type", "account_chart_type.value");
		$chart_list->add_column("standard", "chart_category", "account_charts.chart_category");

		// defaults
		$chart_list->columns		= array("code_chart", "description", "chart_type", "chart_category");
		$chart_list->columns_order	= array("code_chart");

		// define SQL structure
		$chart_list->sql_obj->prepare_sql_settable("account_charts");
		$chart_list->sql_obj->prepare_sql_addfield("id", "account_charts.id");
		$chart_list->sql_obj->prepare_sql_addjoin("LEFT JOIN account_chart_type ON account_chart_type.id = account_charts.chart_type");

/*
		// acceptable filter options
		$structure = NULL;
		$structure["fieldname"] = "date_start";
		$structure["type"]	= "date";
		$structure["sql"]	= "date >= 'value'";
		$chart_list->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] = "date_end";
		$structure["type"]	= "date";
		$structure["sql"]	= "date <= 'value'";
		$chart_list->add_filter($structure);
		
		$structure = NULL;
		$structure["fieldname"] = "searchbox";
		$structure["type"]	= "input";
		$structure["sql"]	= "name_chart LIKE '%value%' OR name_contact LIKE '%value%' OR contact_email LIKE '%value%' OR contact_phone LIKE '%value%' OR contact_fax LIKE '%fax%'";
		$chart_list->add_filter($structure);
*/


		// heading
		print "<h3>CHART OF ACCOUNTS</h3><br><br>";


		// options form
		$chart_list->load_options_form();
		$chart_list->render_options_form();


		// fetch all the chart information
		$chart_list->generate_sql();
		$chart_list->load_data_sql();

		if (!count($chart_list->columns))
		{
			print "<p><b>Please select some valid options to display.</b></p>";
		}
		elseif (!$chart_list->data_num_rows)
		{
			print "<p><b>You currently have no charts in your database.</b></p>";
		}
		else
		{
			// view link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$chart_list->add_link("view", "accounts/charts/view.php", $structure);

			// display the table
			$chart_list->render_table();

			// TODO: display CSV download link
		}

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
