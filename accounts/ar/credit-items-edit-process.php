<?php
/*
	accounts/ar/credit-items-edit-process.php

	access: accounts_credits_write

	Allows a user to adjust or create new credit items.
*/

// includes
require("../../include/config.php");
require("../../include/amberphplib/main.php");

// custom includes
require("../../include/accounts/inc_credits.php");
require("../../include/accounts/inc_invoices.php");
require("../../include/accounts/inc_invoices_items.php");



if (user_permissions_get('accounts_ar_write'))
{
	/*
		Let the credits functions do all the work for us
	*/

	$returnpage_error	= "accounts/ar/credit-items-edit.php";
	$returnpage_success	= "accounts/ar/credit-items.php";

	invoice_form_items_process("ar_credit", $returnpage_error, $returnpage_success);
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
