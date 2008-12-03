<?php
/*
	home.php

	This page contains links to all the tools that the user is allowed to access.
*/

if (!user_online())
{
	// Because this is the default page to be directed to, if the user is not
	// logged in, they should go straight to the login page.
	//
	// All other pages will display an error and prompt the user to login.
	//
	include_once("user/login.php");
}
else
{
	class page_output
	{
		function check_permissions()
		{
			return 1;
		}

		function execute()
		{
			// nothing todo
		}

		function render_html()
		{
			print "<h3>SYSTEM OVERVIEW</h3>";
			print "<p>Overview of all features needs to go here</p>";
		}
	}
}


?>
