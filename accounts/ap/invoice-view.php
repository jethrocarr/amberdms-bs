<?php
/*
	accounts/ap/invoices-view.php
	
	access: account_ap_view

	Form to add a new invoice to the database.

	This page is a lot more complicated than most of the other forms in this program, since
	it needs to allow the user to "update" the form, so that the form adds additional input
	fields for more invoice listings.

	The update option will also generate and return totals back to the program.
	
*/

// custom includes
require("include/accounts/inc_invoices.php");
require("include/accounts/inc_invoices_details.php");
require("include/accounts/inc_charts.php");


if (user_permissions_get('accounts_ap_view'))
{
	$id = $_GET["id"];

	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "Invoice Details";
	$_SESSION["nav"]["query"][]	= "page=accounts/ap/invoice-view.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=accounts/ap/invoice-view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Invoice Items";
	$_SESSION["nav"]["query"][]	= "page=accounts/ap/invoice-items.php&id=$id";
	
	$_SESSION["nav"]["title"][]	= "Invoice Payments";
	$_SESSION["nav"]["query"][]	= "page=accounts/ap/invoice-payments.php&id=$id";
	
	$_SESSION["nav"]["title"][]	= "Invoice Journal";
	$_SESSION["nav"]["query"][]	= "page=accounts/ap/journal.php&id=$id";

	if (user_permissions_get('accounts_ap_write'))
	{
		$_SESSION["nav"]["title"][]	= "Delete Invoice";
		$_SESSION["nav"]["query"][]	= "page=accounts/ap/invoice-delete.php&id=$id";
	}




	function page_render()
	{
		$id		= security_script_input('/^[0-9]*$/', $_GET["id"]);


		/*
			Title + Summary
		*/
		print "<h3>VIEW INVOICE</h3><br>";
		print "<p>This page allows you to view the basic details of the invoice. You can use the links in the green navigation menu above to change to different sections of the invoice, in order to add items, payments or journal entries to the invoice.</p>";

		invoice_render_summarybox("ap", $id);

		invoice_form_details_render("ap", $id, "accounts/ap/invoice-edit-process.php");


	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
