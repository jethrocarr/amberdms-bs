<?php
/*
	accounts/quotes/quotes-add.php
	
	access: account_quotes_add

	Form to add a new quotes to the database.
*/

// custom includes
require("include/accounts/inc_quotes.php");
require("include/accounts/inc_quotes_details.php");


if (user_permissions_get('accounts_quotes_write'))
{
	function page_render()
	{
		/*
			Title + Summary
		*/
		print "<h3>ADD QUOTE</h3><br>";
		print "<p>This page provides features to allow you to add new quotes to the system.</p>";

		quotes_form_details_render(0, "accounts/quotes/quotes-edit-process.php");


	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
