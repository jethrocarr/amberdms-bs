<?php
/*
	accounts/gl/gl.php
	
	access: accounts_gl_view

	Displays a list of all the gl on the system.
*/

// include ledger functions
require("include/accounts/inc_ledger.php");


class page_output
{
	var $obj_table;


	function check_permissions()
	{
		return user_permissions_get('accounts_gl_view');
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
		$this->obj_table->tablename	= "account_gl";

		// define all the columns and structure
		$this->obj_table->add_column("standard", "date_trans", "account_trans.date_trans");
		$this->obj_table->add_column("standard", "code_reference", "NONE");
		$this->obj_table->add_column("standard", "description", "account_trans.memo");
		$this->obj_table->add_column("standard", "source", "account_trans.source");
		$this->obj_table->add_column("money", "debit", "account_trans.amount_debit");
		$this->obj_table->add_column("money", "credit", "account_trans.amount_credit");
		$this->obj_table->add_column("standard", "code_chart", "CONCAT_WS('--', account_charts.code_chart, account_charts.description)");

		// defaults
		$this->obj_table->columns	= array("date_trans", "code_reference", "description", "source", "debit", "credit", "code_chart");
		$this->obj_table->columns_order	= array("date_trans");

		// totals
		$this->obj_table->total_columns	= array("debit", "credit");

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("account_trans");
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "account_trans.id");
		$this->obj_table->sql_obj->prepare_sql_addfield("type", "account_trans.type");
		$this->obj_table->sql_obj->prepare_sql_addfield("customid", "account_trans.customid");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN account_charts ON account_charts.id = account_trans.chartid");



		// acceptable filter options
		$structure = NULL;
		$structure["fieldname"] = "date_start";
		$structure["type"]	= "date";
		$structure["sql"]	= "date_trans >= 'value'";
		$this->obj_table->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] = "date_end";
		$structure["type"]	= "date";
		$structure["sql"]	= "date_trans <= 'value'";
		$this->obj_table->add_filter($structure);
		
		$structure = NULL;
		$structure["fieldname"] = "searchbox";
		$structure["type"]	= "input";
		$structure["sql"]	= "memo LIKE '%value%' OR source LIKE '%value%'";
		$this->obj_table->add_filter($structure);


		// load options
		$this->obj_table->load_options_form();



		if ($this->obj_table->filter["filter_date_start"]["defaultvalue"] && $this->obj_table->filter["filter_date_end"]["defaultvalue"])
		{
			// fetch all the transaction information
			$this->obj_table->generate_sql();

			// add ordering rule to order by the ID - this causes all the transactions
			// to be sorted by the other that they were addded to the database once they have
			// been sorted by date. If this was not done, the accounts look odd with transactions being
			// out of order.
			$this->obj_table->sql_obj->string .= ", id ASC";
		
			$this->obj_table->load_data_sql();
		}
	}


	function render_html()
	{
		// heading
		print "<h3>GENERAL LEDGER</h3>";
		print "<p>This page lists all the transactions in all the accounts.</p>";


		// display options form
		$this->obj_table->render_options_form();


		// display table
		
		if (!$this->obj_table->filter["filter_date_start"]["defaultvalue"] || !$this->obj_table->filter["filter_date_end"]["defaultvalue"])
		{
			format_msgbox("important", "<p><b>Please select a time period to display using the filter options above.</b></p>");
			return 0;
		}
		else
		{
	
			if (!count($this->obj_table->columns))
			{
				format_msgbox("important", "<p>Please select some valid options to display.</p>");
			}
			elseif (!$this->obj_table->data_num_rows)
			{
				format_msgbox("info", "<p>You currently have no transactions matching the filter options in your database.</p>");;
			}
			else
			{
				// label all the reference links
				for ($i=0; $i < count(array_keys($this->obj_table->data)); $i++)
				{
					$this->obj_table->data[$i]["code_reference"] = ledger_trans_typelabel($this->obj_table->data[$i]["type"], $this->obj_table->data[$i]["customid"], TRUE);
				}
			
				// display the table
				$this->obj_table->render_table_html();

				// display CSV download link
				print "<p align=\"right\"><a href=\"index-export.php?mode=csv&page=accounts/gl/gl.php\">Export as CSV</a></p>";
			}
		}
	}

	function render_csv()
	{

		// label all the reference links
		for ($i=0; $i < count(array_keys($this->obj_table->data)); $i++)
		{
			$this->obj_table->data[$i]["code_reference"] = ledger_trans_typelabel($this->obj_table->data[$i]["type"], $this->obj_table->data[$i]["customid"], FALSE);
		}

		// display table
		$this->obj_table->render_table_csv();
	
	}
}

?>
