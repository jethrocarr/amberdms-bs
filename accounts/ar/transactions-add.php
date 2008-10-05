<?php
/*
	accounts/ar/add-transaction.php
	
	access: account_ar_add

	Form to add a new transaction to the database.

	This page is a lot more complicated than most of the other forms in this program, since
	it needs to allow the user to "update" the form, so that the form adds additional input
	fields for more transaction listings.

	The update option will also generate and return totals back to the program.
	
*/

// custom includes
require("include/accounts/inc_transactions.php");
require("include/accounts/inc_transactions_forms.php");
require("include/accounts/inc_charts.php");


if (user_permissions_get('accounts_ar_write'))
{
	function page_render()
	{
		/*
			Title + Summary
		*/
		print "<h3>ADD TRANSACTION</h3><br>";
		print "<p>This page allows you to add new transactions to the database. The transactions feature is intended for processing
		simple sales. If you wish to bill a customer for work performed on a project or a particular product sale, please use the invoice
		features.</p>";

		transaction_form_details_render("ar", 0, "accounts/ar/transactions-edit-process.php");


	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
