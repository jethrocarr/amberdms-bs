<?php
/*
	accounts/quotes/quotes-items-edit-process.php

	access: accounts_quotes_write

	Allows a user to adjust or create new quote items.
*/

// includes
require("../../include/config.php");
require("../../include/amberphplib/main.php");

// custom includes
require("../../include/accounts/inc_charts.php");
require("../../include/accounts/inc_invoices.php");
require("../../include/accounts/inc_invoices_items.php");



if (user_permissions_get('accounts_quotes_write'))
{
	/*
		Let the invoice functions do all the work for us
	*/

	$returnpage_error	= "accounts/quotes/quotes-items-edit.php";
	$returnpage_success	= "accounts/quotes/quotes-items.php";

	invoice_form_items_process("quotes", $returnpage_error, $returnpage_success);
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
