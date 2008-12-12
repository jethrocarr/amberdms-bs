<?php
/*
	accounts/quotes/quotes-export-process.php

	access: accounts_quotes_view (PDF download only)
		accounts_quotes_write (PDF download + email features)

	Allows invoices to be exported or email to customers
*/

// includes
require("../../include/config.php");
require("../../include/amberphplib/main.php");

// custom includes
require("../../include/accounts/inc_invoices_process.php");


if (user_permissions_get('accounts_quotes_write'))
{
	/*
		Let the invoices functions do all the work for us
	*/

	// edit invoice
	$returnpage_error	= "accounts/quotes/invoice-export.php";
	$returnpage_success	= "accounts/quotes/invoice-export.php";

	invoice_form_export_process("quotes", $returnpage_error, $returnpage_success);
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
