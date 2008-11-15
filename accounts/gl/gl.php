<?php
/*
	accounts/gl/gl.php
	
	access: accounts_gl_view

	Displays a list of all the gl on the system.
*/

// include ledger functions
require("include/accounts/inc_ledger.php");


if (user_permissions_get('accounts_gl_view'))
{
	function page_render()
	{
		// heading
		print "<h3>GENERAL LEDGER</h3>";
		print "<p>This page lists all the transactions in all the accounts.</p>";


	
		// establish a new table object
		$transaction_list = New table;

		$transaction_list->language	= $_SESSION["user"]["lang"];
		$transaction_list->tablename	= "account_gl";

		// define all the columns and structure
		$transaction_list->add_column("standard", "date_trans", "account_trans.date_trans");
		$transaction_list->add_column("standard", "code_reference", "NONE");
		$transaction_list->add_column("standard", "code_chart", "CONCAT_WS('--', account_charts.code_chart, account_charts.description)");
		$transaction_list->add_column("standard", "description", "account_trans.memo");
		$transaction_list->add_column("standard", "source", "account_trans.source");
		$transaction_list->add_column("money", "debit", "account_trans.amount_debit");
		$transaction_list->add_column("money", "credit", "account_trans.amount_credit");

		// defaults
		$transaction_list->columns		= array("date_trans", "code_reference", "description", "source", "debit", "credit", "code_chart");
		$transaction_list->columns_order	= array("date_trans");

		// totals
		$transaction_list->total_columns	= array("debit", "credit");

		// define SQL structure
		$transaction_list->sql_obj->prepare_sql_settable("account_trans");
		$transaction_list->sql_obj->prepare_sql_addfield("id", "account_trans.id");
		$transaction_list->sql_obj->prepare_sql_addfield("type", "account_trans.type");
		$transaction_list->sql_obj->prepare_sql_addfield("customid", "account_trans.customid");
		$transaction_list->sql_obj->prepare_sql_addjoin("LEFT JOIN account_charts ON account_charts.id = account_trans.chartid");



		// acceptable filter options
		$structure = NULL;
		$structure["fieldname"] = "date_start";
		$structure["type"]	= "date";
		$structure["sql"]	= "date_trans >= 'value'";
		$transaction_list->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] = "date_end";
		$structure["type"]	= "date";
		$structure["sql"]	= "date_trans <= 'value'";
		$transaction_list->add_filter($structure);
		
		$structure = NULL;
		$structure["fieldname"] = "searchbox";
		$structure["type"]	= "input";
		$structure["sql"]	= "memo LIKE '%value%' OR source LIKE '%value%'";
		$transaction_list->add_filter($structure);


		// options form
		$transaction_list->load_options_form();
		$transaction_list->render_options_form();



		// fetch all the transaction information
		$transaction_list->generate_sql();

		// append extra ordering rules to the SQL query
		$transaction_list->sql_obj->string .= ", customid DESC";
		$transaction_list->sql_obj->string .= ", account_trans.type='ar' DESC";
		$transaction_list->sql_obj->string .= ", account_trans.type='ar_tax' DESC";
		$transaction_list->sql_obj->string .= ", account_trans.type='ar_pay' DESC";
		$transaction_list->sql_obj->string .= ", account_trans.type='ap' DESC";
		$transaction_list->sql_obj->string .= ", account_trans.type='ap_tax' DESC";
		$transaction_list->sql_obj->string .= ", account_trans.type='ap_pay' DESC";

		
		$transaction_list->load_data_sql();


		/*
			Fetch all the reference information
			
			Type of transaction, ID and the link for it
		*/
		for ($i=0; $i < count(array_keys($transaction_list->data)); $i++)
		{
			$transaction_list->data[$i]["code_reference"] = ledger_trans_typelabel($transaction_list->data[$i]["type"], $transaction_list->data[$i]["customid"]);
		}



		if (!count($transaction_list->columns))
		{
			print "<p><b>Please select some valid options to display.</b></p>";
		}
		elseif (!$transaction_list->data_num_rows)
		{
			print "<p><b>You currently have no transactions matching the filter options in your database.</b></p>";
		}
		else
		{
			// display the table
			$transaction_list->render_table();

			// TODO: display CSV download link
		}

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
