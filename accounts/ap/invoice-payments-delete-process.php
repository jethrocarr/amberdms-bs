<?php
/*
	accounts/ap/invoice-payments-delete-process.php

	access: accounts_ap_write

	Allows a user to delete a payment from an invoice
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

	$returnpage_error	= "accounts/ap/invoice-payments.php";
	$returnpage_success	= "accounts/ap/invoice-payments.php";

	invoice_form_items_delete_process("ap", $returnpage_error, $returnpage_success);
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
