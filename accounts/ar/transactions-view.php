<?php
/*
	accounts/ar/view-transaction.php
	
	access: account_ar_view

	Form to add a new transaction to the database.

	This page is a lot more complicated than most of the other forms in this program, since
	it needs to allow the user to "update" the form, so that the form adds additional input
	fields for more transaction listings.

	The update option will also generate and return totals back to the program.
	
*/

// custom includes
require("include/accounts/inc_transactions.php");
require("include/accounts/inc_charts.php");


if (user_permissions_get('accounts_ar_write'))
{
	function page_render()
	{
		$id		= security_script_input('/^[0-9]*$/', $_GET["id"]);


		/*
			Title + Summary
		*/
		print "<h3>VIEW TRANSACTION</h3><br>";
		print "<p>This page allows you to view or edit a transaction.</p>";

		transaction_render_form("ar", $id);


	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
