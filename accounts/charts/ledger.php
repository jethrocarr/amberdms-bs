<?php
/*
	charts/ledger.php
	
	access: accounts_charts_view (read-only)
		accounts_charts_write (write access)

	Displays a ledger for the selected chart, with options to be able to search, select date periods
	and other filter options.
*/

// include ledger functions
require("include/accounts/inc_ledger.php");


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
			
			/*
				Page Heading
			*/
			print "<h3>ACCOUNT LEDGER</h3>";
			print "<p>This page displays a list of transactions for the selected account. You can use the filter options to define dates and other search/filtering criteria.</p>";

			/*
				Display Ledger
			*/

			// define ledger
			$ledger			= New ledger_account_list;
			$ledger->ledgername	= "account_ledger";
			$ledger->chartid	= $id;

			$ledger->prepare_ledger();

			// display options form
			$ledger->render_options_form();

			// define SQL structure
			$ledger->prepare_generate_sql();

			// load data
			$ledger->prepare_load_data();

			// render
			$ledger->render_table_html();

		} // end if chart/account exists

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
