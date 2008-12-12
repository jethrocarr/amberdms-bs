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
		print "<h3>Reports</h3>";
		print "<p>Have links and general details about the different reports here</p>";
	}
}

?>	
