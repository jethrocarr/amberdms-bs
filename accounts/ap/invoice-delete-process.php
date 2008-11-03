<?php
/*
	accounts/ap/invoice-delete-process.php

	access: accounts_ap_write

	Deletes invoices from the database
*/

// includes
require("../../include/config.php");
require("../../include/amberphplib/main.php");

// custom includes
require("../../include/accounts/inc_invoices.php");
require("../../include/accounts/inc_invoices_delete.php");



if (user_permissions_get('accounts_ap_write'))
{
	/*
		Let the invoices functions do all the work for us
	*/

	$returnpage_error	= "accounts/ap/invoice-delete.php";
	$returnpage_success	= "accounts/ap/ap.php";

	invoice_form_delete_process("ap",$returnpage_error, $returnpage_success);
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
