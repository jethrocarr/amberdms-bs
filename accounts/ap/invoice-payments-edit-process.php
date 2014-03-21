<?php
/*
	accounts/ap/invoice-payments-edit-process.php

	access: accounts_invoices_write

	Allows a user to adjust or create new invoice payments.
*/

// includes
require("../../include/config.php");
require("../../include/amberphplib/main.php");

// custom includes
require("../../include/accounts/inc_invoices.php");
require("../../include/accounts/inc_invoices_items.php");



if (user_permissions_get('accounts_ap_write'))
{
	/*
		Let the invoices functions do all the work for us
	*/

	$returnpage_error	= "accounts/ap/invoice-payments-edit.php";
	$returnpage_success	= "accounts/ap/invoice-payments.php";

	invoice_form_items_process("ap", $returnpage_error, $returnpage_success);
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
