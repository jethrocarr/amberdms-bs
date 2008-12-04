<?php
/*
	admin/admin.php

	Summary/link page for administrator tools.
*/

class page_output
{
	function check_permissions()
	{
		return user_permissions_get("admin");
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
		print "<h3>ADMINISTRATOR TOOLS</h3>";
		print "<p>This page contains tools for the system administrator to use for configuring user accounts, IP blacklisting and general settings for the entire billing system.</p>";
	}
}

?>	
