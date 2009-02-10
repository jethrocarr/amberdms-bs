<?php
/*
	reports/reports.php

	This page allows the user to fetch various financial reports from the system.
*/

class page_output
{
	function check_permissions()
	{
		return user_online();
	}

	function check_requirements()
	{
		// nothing todo
		return 1;
	}

	function execute()
	{
		// nothing todo
		return 1;
	}

	function render_html()
	{
		print "<h3>ACCOUNTING REPORTS</h3>";
		print "<p>Select one of the report types below to configure and view financial reports.</p>";


		// trial balance
		format_linkbox("default", "index.php?page=accounts/reports/trialbalance.php", "<p><b>TRIAL BALANCE</b></p>
			<p>Lists all the accounts which transactions are filed against and provides a basic
			overview of the current state of the accounts.</p>");

		// income statement
		print "<br>";
		format_linkbox("default", "index.php?page=accounts/reports/incomestatement.php", "<p><b>INCOME STATEMENT</b></p>
			<p>Reports income and expenses for a selected time period.</p>");

		
		// balance sheet
		print "<br>";
		format_linkbox("default", "index.php?page=accounts/reports/balancesheet.php", "<p><b>BALANCE SHEET</b></p>
			<p>Shows assets, liabilities and equity for the selected time period.</p>");



			
	}
}

?>	
