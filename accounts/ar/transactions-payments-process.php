<?php
/*
	accounts/ar/transactions-payments-process.php

	access: accounts_transactions_write

	Allows payments to be added/deleted from an invoice
*/

// includes
require("../../include/config.php");
require("../../include/amberphplib/main.php");

// custom includes
require("../../include/accounts/inc_transactions.php");
require("../../include/accounts/inc_payments_forms.php");



if (user_permissions_get('accounts_ar_write'))
{
	/*
		Let the transactions functions do all the work for us
	*/

	$returnpage_error	= "accounts/ar/transactions-payments.php";
	$returnpage_success	= "accounts/ar/transactions-view.php";

	transaction_form_details_process("ar", $returnpage_error, $returnpage_success);
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
