<?php
/*
	accounts/ar/ar.php

	Summary/Link page for other AR sections
*/

if (user_permissions_get("accounts_ar_view"))
{
	function page_render()
	{
		print "<h3>Accounts Receivables</h3>";

		print "<p>Have overview/summary of AR here - perhaps show outstanding invoices on this page?</p>";
	}
}
else
{
	error_render_noperms();
}

?>	
