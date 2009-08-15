<?php
/*
	accounts/charts/charts.php
	
	access: accounts_charts_view

	Displays a list of all the charts on the system.
*/

class page_output
{
	var $obj_table;


	function check_permissions()
	{
		return user_permissions_get('accounts_charts_view');
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
		$this->obj_table->tablename	= "account_charts";

		// define all the columns and structure
		$this->obj_table->add_column("standard", "code_chart", "account_charts.code_chart");
		$this->obj_table->add_column("standard", "description", "account_charts.description");
		$this->obj_table->add_column("standard", "chart_type", "account_chart_type.value");

		// the debit and credit columns need to be calculated by a seporate query
		$this->obj_table->add_column("price", "debit", "NONE");
		$this->obj_table->add_column("price", "credit", "NONE");

		// defaults
		$this->obj_table->columns	= array("code_chart", "description", "chart_type", "debit", "credit");
		$this->obj_table->columns_order	= array("code_chart");

		// totals
		$this->obj_table->total_columns	= array("debit", "credit");

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("account_charts");
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "account_charts.id");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN account_chart_type ON account_chart_type.id = account_charts.chart_type");


		// fetch all the chart information
		$this->obj_table->generate_sql();
		$this->obj_table->load_data_sql();


		// set different row styling for specifc chart types
		// such as the heading account
		for ($i = 0; $i < count(array_keys($this->obj_table->data)); $i++)
		{
			if ($this->obj_table->data[$i]["chart_type"] == "Heading")
			{
				$this->obj_table->data[$i]["options"]["css_class"] = "chart_heading";
			}
		}


		// fetch debit and credit summaries for all charts in advance - this
		// is better than running a query per chart just to get all the totals
		$sql_amount_obj		= New sql_query;
		$sql_amount_obj->string	= "SELECT chartid, SUM(amount_credit) as credit, SUM(amount_debit) as debit FROM account_trans GROUP BY chartid";
		$sql_amount_obj->execute();

		if ($sql_amount_obj->num_rows())
		{
			$sql_amount_obj->fetch_array();

			// run through all the chart rows and fill in the credit/debit fields
			for ($i = 0; $i < count(array_keys($this->obj_table->data)); $i++)
			{
				foreach ($sql_amount_obj->data as $data_amount)
				{
					if ($data_amount["chartid"] == $this->obj_table->data[$i]["id"])
					{
						/*
							we only want to show financial difference in the columns - for example, if
							debit == $100 and credit == $200, we just show credit as $100 and leave debit
							blank
						*/

						if ($data_amount["credit"] == $data_amount["debit"])
						{
							$this->obj_table->data[$i]["debit"]	= "";
							$this->obj_table->data[$i]["credit"]	= "";
						}
						elseif ($data_amount["debit"] > $data_amount["credit"])
						{
							$this->obj_table->data[$i]["debit"]	= $data_amount["debit"] - $data_amount["credit"];
							$this->obj_table->data[$i]["credit"]	= "";
						}
						elseif ($data_amount["debit"] < $data_amount["credit"])
						{
							$this->obj_table->data[$i]["debit"]	= "";
							$this->obj_table->data[$i]["credit"]	= $data_amount["credit"] - $data_amount["debit"];
						}
					}
				}
			}
		}
	


	}


	function render_html()
	{
		// heading
		print "<h3>CHART OF ACCOUNTS</h3>";
		print "<p>This page lists all the accounts which transactions are filed against and provides a basic overview of the current state of the financials.</p>";


		// display table
		if (!$this->obj_table->data_num_rows)
		{
			format_msgbox("important", "<p>You currently have no accounts in your database.</p>");
		}
		else
		{
			// view link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("view", "accounts/charts/view.php", $structure);

			// ledger link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("ledger", "accounts/charts/ledger.php", $structure);


			// display the table
			$this->obj_table->render_table_html();

			// display CSV & PDF download link
			print "<p align=\"right\"><a class=\"button\" href=\"index-export.php?mode=csv&page=accounts/charts/charts.php\">Export as CSV</a></p>";
			print "<p align=\"right\"><a class=\"button\" href=\"index-export.php?mode=pdf&page=accounts/charts/charts.php\">Export as PDF</a></p>";
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
