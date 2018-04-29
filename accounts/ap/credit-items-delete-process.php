<?php
/*
	accounts/ap/credit-items-delete-process.php

	access: accounts_ap_write

	Allows a user to delete an item from an credit note
*/

// includes
require("../../include/config.php");
require("../../include/amberphplib/main.php");

// custom includes
require("../../include/accounts/inc_credits.php");
require("../../include/accounts/inc_invoices.php");
require("../../include/accounts/inc_invoices_items.php");



if (user_permissions_get('accounts_ap_write'))
{
	/*
		Let the credits functions do all the work for us
	*/

	$returnpage_error	= "accounts/ap/credit-items.php";
	$returnpage_success	= "accounts/ap/credit-items.php";

	invoice_form_items_delete_process("ap_credit", $returnpage_error, $returnpage_success);
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
