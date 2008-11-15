<?php
/*
	accounts/ar/invoice-delete.php
	
	access: account_ar_write

	Form to delete an invoice from the database - this page will only permit the invoice
	to be deleted if the invoice was created less than ACCOUNT_INVOICE_LOCK days ago.
	
*/

// custom includes
require("include/accounts/inc_invoices.php");
require("include/accounts/inc_invoices_delete.php");
require("include/accounts/inc_charts.php");


if (user_permissions_get('accounts_ar_write'))
{
	$id = $_GET["id"];

	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "Invoice Details";
	$_SESSION["nav"]["query"][]	= "page=accounts/ar/invoice-view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Invoice Items";
	$_SESSION["nav"]["query"][]	= "page=accounts/ar/invoice-items.php&id=$id";
	
	$_SESSION["nav"]["title"][]	= "Invoice Payments";
	$_SESSION["nav"]["query"][]	= "page=accounts/ar/invoice-payments.php&id=$id";
	
	$_SESSION["nav"]["title"][]	= "Invoice Journal";
	$_SESSION["nav"]["query"][]	= "page=accounts/ar/journal.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Delete Invoice";
	$_SESSION["nav"]["query"][]	= "page=accounts/ar/invoice-delete.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=accounts/ar/invoice-delete.php&id=$id";



	function page_render()
	{
		$id		= security_script_input('/^[0-9]*$/', $_GET["id"]);


		/*
			Title + Summary
		*/

		$expirydate = sql_get_singlevalue("SELECT value FROM config WHERE name='ACCOUNTS_INVOICE_LOCK'");
		
		print "<h3>DELETE INVOICE</h3><br>";
		print "<p>This page allows you to delete incorrect invoices, provided that they are less than $expirydate days old.</p>";

		invoice_render_summarybox("ar", $id);

		invoice_form_delete_render("ar", $id, "accounts/ar/invoice-delete-process.php");


	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
