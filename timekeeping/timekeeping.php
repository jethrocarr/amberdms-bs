<?php
/*
	timekeeping/timekeeping.php

	Summary page for booked time for either the currently viewing employee, or all employees
	if the user has the correct permissions level.
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
		print "<h3>Time Keeping</h3>";

		print "<p>This section of the billing system allows you to keep track of the hours you work, leave earned/taken and your payslips.</p>";
	}
}

?>	
