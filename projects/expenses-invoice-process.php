<?php
/*
	projects/expenses-invoice-process.php

	access: projects_write

	Allows a user to add expenses to an invoice
*/

// includes
require("../include/config.php");
require("../include/amberphplib/main.php");

// custom includes
require("../include/accounts/inc_invoices.php");
require("../include/accounts/inc_invoices_items.php");



if (user_permissions_get('accounts_ar_write'))
{
	/*
		Let the invoices functions do all the work for us
	*/

	$returnpage_error	= "projects/expenses.php";
	$returnpage_success	= "projects/expenses.php";

	expenses_to_invoice_form_items_process($returnpage_error, $returnpage_success);
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
