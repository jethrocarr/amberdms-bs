<?php
/*
	accounts/reports/trialbalance.php
	
	access: accounts_reports

	Displays the complete totals of all the accounts in the system. This differs from the chart of accounts
	page which shows the differences only.
*/

class page_output
{
	var $obj_table;


	function check_permissions()
	{
		return user_permissions_get('accounts_reports');
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
		$this->obj_table->tablename	= "accounts_reports_trialbalance";

		// define all the columns and structure
		$this->obj_table->add_column("standard", "code_chart", "account_charts.code_chart");
		$this->obj_table->add_column("standard", "description", "account_charts.description");
		$this->obj_table->add_column("standard", "chart_type", "account_chart_type.value");

		// the debit and credit columns need to be calculated by a seporate query
		$this->obj_table->add_column("price", "debit", "NONE");
		$this->obj_table->add_column("price", "credit", "NONE");

		// defaults
		$this->obj_table->columns		= array("code_chart", "description", "chart_type", "debit", "credit");
		$this->obj_table->columns_order		= array("code_chart");

		// totals
		$this->obj_table->total_columns	= array("debit", "credit");

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("account_charts");
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "account_charts.id");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN account_chart_type ON account_chart_type.id = account_charts.chart_type");


		// acceptable filter options
		// note the lack of SQL statements - this is because the values of these
		// filters are used by the seporate SQL statement for querying account credit/debit.
		$structure = NULL;
		$structure["fieldname"] = "date_start";
		$structure["type"]	= "date";
		$this->obj_table->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] = "date_end";
		$structure["type"]	= "date";
		$this->obj_table->add_filter($structure);
	

		// load options
		$this->obj_table->load_options_form();




		// fetch all the chart information
		$this->obj_table->generate_sql();
		$this->obj_table->load_data_sql();


		// fetch debit and credit summaries for all charts in advance - this
		// is better than running a query per chart just to get all the totals
		$sql_amount_obj = New sql_query;
		
		$sql_amount_obj->prepare_sql_settable("account_trans");
		$sql_amount_obj->prepare_sql_addfield("chartid");
		$sql_amount_obj->prepare_sql_addfield("credit", "SUM(amount_credit)");
		$sql_amount_obj->prepare_sql_addfield("debit", "SUM(amount_debit)");
		
		
		if ($this->obj_table->filter["filter_date_start"]["defaultvalue"])
		{
			$sql_amount_obj->prepare_sql_addwhere("date_trans >= '". $this->obj_table->filter["filter_date_start"]["defaultvalue"] ."'");
		}
		
		
		if ($this->obj_table->filter["filter_date_end"]["defaultvalue"])
		{
			$sql_amount_obj->prepare_sql_addwhere("date_trans <= '". $this->obj_table->filter["filter_date_end"]["defaultvalue"] ."'");
		}
		
		$sql_amount_obj->prepare_sql_addgroupby("chartid");
		$sql_amount_obj->generate_sql();
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
						$this->obj_table->data[$i]["debit"]	= $data_amount["debit"];
						$this->obj_table->data[$i]["credit"]	= $data_amount["credit"];
					}
				}
			}
		}
	}


	function render_html()
	{
		// heading
		print "<h3>TRIAL BALANCE</h3>";
		print "<p>This page lists all the accounts which transactions are filed against and provides a basic overview of the current state of the financials.</p>";

		// display options form
		$this->obj_table->render_options_form();

		// display table
		if (!$this->obj_table->data_num_rows)
		{
			format_msgbox("important", "<p>You currently have no accounts in your database.</p>");
		}
		else
		{
			// display the table
			$this->obj_table->render_table_html();

			// display CSV download link
			print "<p align=\"right\"><a href=\"index-export.php?mode=csv&page=accounts/reports/trialbalance.php\">Export as CSV</a></p>";
		}
	}


	function render_csv()
	{
		$this->obj_table->render_table_csv();
	}
}


?>
