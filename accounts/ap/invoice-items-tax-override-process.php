<?php
/*
	accounts/ap/invoice-items-tax-override-process.php

	access: accounts_invoices_write

	Replaces the tax amount with a custom value - commonly used
	for correcting rounding mistakes on vendor invoices.
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

	invoice_form_tax_override_process("accounts/ap/invoice-items.php");
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
