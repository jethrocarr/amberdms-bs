<?php
/*
	charts/ledger.php
	
	access: accounts_charts_view (read-only)
		accounts_charts_write (write access)

	Displays a ledger for the selected chart, with options to be able to search, select date periods
	and other filter options.
*/

if (user_permissions_get('accounts_charts_view'))
{
	if ($_GET["id"])
	{
		$id = $_GET["id"];
	}
	else
	{
		$id = $_GET["filter_id"];
	}
	
	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "Account Details";
	$_SESSION["nav"]["query"][]	= "page=accounts/charts/view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Account Ledger";
	$_SESSION["nav"]["query"][]	= "page=accounts/charts/ledger.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=accounts/charts/ledger.php&id=$id";
	
	$_SESSION["nav"]["title"][]	= "Delete Account";
	$_SESSION["nav"]["query"][]	= "page=accounts/charts/delete.php&id=$id";


	function page_render()
	{
		if ($_GET["id"])
		{
			$id = security_script_input('/^[0-9]*$/', $_GET["id"]);
		}
		else
		{
			$id = security_script_input('/^[0-9]*$/', $_GET["filter_id"]);
		}
		

		$mysql_string	= "SELECT id FROM `account_charts` WHERE id='$id'";
		$mysql_result	= mysql_query($mysql_string);
		$mysql_num_rows	= mysql_num_rows($mysql_result);

		if (!$mysql_num_rows)
		{
			print "<p><b>Error: The requested chart does not exist. <a href=\"index.php?page=charts/charts.php\">Try looking for your chart on the chart list page.</a></b></p>";
		}
		else
		{


			// establish a new table object
			$ledger_list = New table;

			$ledger_list->language	= $_SESSION["user"]["lang"];
			$ledger_list->tablename	= "account_ledger";

			// define all the columns and structure
			$ledger_list->add_column("date", "date_trans", "account_trans.date_trans");
			$ledger_list->add_column("standard", "item_id", "NONE");
	//		$ledger_list->add_column("standard", "dest_name_chart", "CONCAT_WS(' -- ',account_charts.code_chart,account_charts.description)");
			$ledger_list->add_column("price", "debit", "account_trans.amount_debit");
			$ledger_list->add_column("price", "credit", "account_trans.amount_credit");

			// total rows
			$ledger_list->total_columns		= array("credit", "debit");
			$ledger_list->total_rows		= array("credit", "debit");
			$ledger_list->total_rows_mode		= "incrementing";

			// defaults
			$ledger_list->columns		= array("date_trans", "item_id", "debit", "credit");
			$ledger_list->columns_order	= array("date_trans");

			// define SQL structure
			$ledger_list->sql_obj->prepare_sql_settable("account_trans");
			$ledger_list->sql_obj->prepare_sql_addfield("id", "account_trans.id");
			$ledger_list->sql_obj->prepare_sql_addfield("type", "account_trans.type");
			$ledger_list->sql_obj->prepare_sql_addfield("customid", "account_trans.customid");
			$ledger_list->sql_obj->prepare_sql_addwhere("chartid='$id'");
			$ledger_list->sql_obj->prepare_sql_addjoin("LEFT JOIN account_charts ON account_charts.id = account_trans.chartid");


			// acceptable filter options
			$ledger_list->add_fixed_option("id", $id);
			
			$structure = NULL;
			$structure["fieldname"] = "date_start";
			$structure["type"]	= "date";
			$structure["sql"]	= "account_trans.date_trans >= 'value'";
			$ledger_list->add_filter($structure);

			$structure = NULL;
			$structure["fieldname"] = "date_end";
			$structure["type"]	= "date";
			$structure["sql"]	= "account_trans.date_trans <= 'value'";
			$ledger_list->add_filter($structure);
			


			// heading
			print "<h3>CHART LEDGERS</h3><br><br>";


			// options form
			$ledger_list->load_options_form();
			$ledger_list->render_options_form();


			// fetch all the ledger information
			$ledger_list->generate_sql();
			$ledger_list->load_data_sql();

			/*
				Label the items the transaction belongs to

				Because there are range of different items types (ar, ap, general ledger, etc) we need
				to check the type of the ledger entry, then display the correct title and link
			*/
			if ($ledger_list->data_num_rows)
			{
				for ($i=0; $i < count(array_keys($ledger_list->data)); $i++)
				{
					switch ($ledger_list->data[$i]["type"])
					{
						case "ar":
						case "ar_tax":

							// for AR invoices/transaction fetch the invoice ID
							$result = sql_get_singlevalue("SELECT code_invoice as value FROM account_ar WHERE id='". $ledger_list->data[$i]["customid"] ."'");
							
							$ledger_list->data[$i]["item_id"] = "<a href=\"index.php?page=accounts/ar/invoice-view.php&id=". $ledger_list->data[$i]["customid"] ."\">AR invoice $result</a>";
						break;

						case "ar_pay":
							// for AR invoice payments fetch the invoice ID
							$result = sql_get_singlevalue("SELECT code_invoice as value FROM account_ar WHERE id='". $ledger_list->data[$i]["customid"] ."'");
							
							$ledger_list->data[$i]["item_id"] = "<a href=\"index.php?page=accounts/ar/invoice-payments.php&id=". $ledger_list->data[$i]["customid"] ."\">AR payment $result</a>";
						break;



						default:
							$ledger_list->data[$i]["item_id"] = "unknown";
						break;
					}
					
				}
			}


			if (!count($ledger_list->columns))
			{
				print "<p><b>Please select some valid options to display.</b></p>";
			}
			elseif (!$ledger_list->data_num_rows)
			{
				print "<p><b>No transactions belong to this chart which match your search criteria.</b></p>";
			}
			else
			{
	/*
				TODO: the links are going to depend on the type of transaction
	// view link
				$structure = NULL;
				$structure["id"]["column"]	= "id";
				$ledger_list->add_link("view", "ar/accounts/view.php", $structure);
	*/

				// display the table
				$ledger_list->render_table();

				// TODO: display CSV download link
			}

		} // end if chart/account exists

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
