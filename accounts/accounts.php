<?php
/*
	accounts/accounts.php

	Summary/Link page for other accounts sections
*/

if (user_online())
{
	function page_render()
	{
		print "<h3>Accounts</h3>";

		print "<p>Have overview/summary of account status here.</p>";
	}
}
else
{
	error_render_noperms();
}

?>	
