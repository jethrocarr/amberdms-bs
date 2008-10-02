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

		// the debit and credit columns need to be calculated by a seporate query
		$chart_list->add_column("price", "debit", "NONE");
		$chart_list->add_column("price", "credit", "NONE");

		// defaults
		$chart_list->columns		= array("code_chart", "description", "chart_type", "debit", "credit");
		$chart_list->columns_order	= array("code_chart");

		// totals
		$chart_list->total_columns	= array("debit", "credit");

		// define SQL structure
		$chart_list->sql_obj->prepare_sql_settable("account_charts");
		$chart_list->sql_obj->prepare_sql_addfield("id", "account_charts.id");
		$chart_list->sql_obj->prepare_sql_addjoin("LEFT JOIN account_chart_type ON account_chart_type.id = account_charts.chart_type");



		// heading
		print "<h3>CHART OF ACCOUNTS</h3>";
		print "<p>This page lists all the accounts which transactions are filed against and provides a basic overview of the current state of the financials.</p>";


		// fetch all the chart information
		$chart_list->generate_sql();
		$chart_list->load_data_sql();


		// fetch debit and credit summaries for all charts in advance - this
		// is better than running a query per chart just to get all the totals
		$sql_amount_obj		= New sql_query;
		$sql_amount_obj->string	= "SELECT chartid, SUM(amount_credit) as credit, SUM(amount_debit) as debit FROM account_trans GROUP BY chartid";
		$sql_amount_obj->execute();

		if ($sql_amount_obj->num_rows())
		{
			$sql_amount_obj->fetch_array();


			// run through all the chart rows and fill in the credit/debit fields
			for ($i = 0; $i < count(array_keys($chart_list->data)); $i++)
			{
				foreach ($sql_amount_obj->data as $data_amount)
				{
					if ($data_amount["chartid"] == $chart_list->data[$i]["id"])
					{
						$chart_list->data[$i]["credit"]	= $data_amount["credit"];
						$chart_list->data[$i]["debit"]	= $data_amount["debit"];
					}
				}
			}
		}
	
	

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
