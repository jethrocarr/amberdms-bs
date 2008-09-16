<?php
/*
	admin/admin.php

	Summary/link page for administrator tools.
*/

if (user_online())
{
	function page_render()
	{
		print "<h3>ADMINISTRATOR TOOLS</h3>";

		print "<p>This page contains tools for the system administrator to use for configuring user accounts, IP blacklisting and general settings for the entire billing system.</p>";

	}
}
else
{
	error_render_noperms();
}

?>	
