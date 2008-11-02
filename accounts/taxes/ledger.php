<?php
/*
	accounts/taxes/ledger.php
	
	access: accounts_taxes_view (read-only)

	Links to other pages with information for the best way to get tax ledgers.
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
	
	$_SESSION["nav"]["title"][]	= "Tax Details";
	$_SESSION["nav"]["query"][]	= "page=accounts/taxes/view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Tax Ledger";
	$_SESSION["nav"]["query"][]	= "page=accounts/taxes/ledger.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=accounts/taxes/ledger.php&id=$id";
	
	$_SESSION["nav"]["title"][]	= "Delete Tax";
	$_SESSION["nav"]["query"][]	= "page=accounts/taxes/delete.php&id=$id";



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
		
		/*
			Verify that the tax exists and fetch useful information
		*/
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT chartid, CONCAT_WS(' -- ',account_charts.code_chart,account_charts.description) as name_chart FROM account_taxes LEFT JOIN account_charts ON account_charts.id = account_taxes.chartid WHERE account_taxes.id='$id' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			print "<p><b>Error: The requested tax does not exist. <a href=\"index.php?page=accounts/taxes/taxes.php\">Try looking for your tax on the taxes list page.</a></b></p>";
		}
		else
		{
			$sql_obj->fetch_array();

			/*
				Page Heading
			*/
			print "<h3>TAX LEDGER</h3>";
			print "<p>There are 3 different types of ledger-style reports you can generate for taxes.</p>";


			print "<br><p>";
			print "<b>1. Account Ledger</b><br><br>";
			print "Transactions for this tax are entered against account \"". $sql_obj->data[0]["name_chart"] ."\". You can <a href=\"index.php?page=accounts/charts/ledger.php&id=". $sql_obj->data[0]["chartid"] ."\">view the ledger for this account here</a>.<br>";
			print "</p>";

			print "<br><p>";
			print "<b>2. Tax Collected Report</b><br><br>";
			print "Generate reports on the amount of tax collected either on an invoice or cash basis from accounts recievables using the <a href=\"index.php?page=accounts/taxes/tax_collected.php&id=$id\">AR tax collected report</a>.<br>";
			print "</p>";

			print "<br><p>";
			print "<b>3. Tax Paid Report</b><br><br>";
			print "Generate reports on the amount of tax paid either on an invoice or cash basis from accounts payable using the <a href=\"index.php?page=accounts/taxes/tax_paid.php&id=$id\">AP tax paid report</a>.<br>";
			print "</p>";
		
		} // end if tax exists

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
