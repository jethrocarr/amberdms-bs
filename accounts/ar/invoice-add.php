<?php
/*
	accounts/ar/invoice-add.php
	
	access: account_ar_add

	Form to add a new invoice to the database.

	This page is a lot more complicated than most of the other forms in this program, since
	it needs to allow the user to "update" the form, so that the form adds additional input
	fields for more invoice listings.

	The update option will also generate and return totals back to the program.
	
*/

// custom includes
require("include/accounts/inc_invoices.php");
require("include/accounts/inc_invoices_forms.php");
require("include/accounts/inc_charts.php");


if (user_permissions_get('accounts_ar_write'))
{
	function page_render()
	{
		/*
			Title + Summary
		*/
		print "<h3>ADD INVOICE</h3><br>";
		print "<p>This page provides features to allow you to add new invoices to the system.</p>";

		invoice_form_render("ar", 0, "accounts/ar/invoice-edit-process.php");


	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
