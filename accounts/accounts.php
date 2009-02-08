<?php
/*
	accounts/accounts.php

	Summary/Link page for other accounts sections
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
		print "<h3>ACCOUNTS</h3>";
		print "<p>Have overview/summary of account status here.</p>";
	}
}

?>	
