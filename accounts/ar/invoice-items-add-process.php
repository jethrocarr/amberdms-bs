<?php
/*
	accounts/ar/invoices-items-add-process.php
	
	access: account_ar_write

	Checks user input on the invoices-items.php and if valid directs them to the add invoice item page.
*/




// includes
require("../../include/config.php");
require("../../include/amberphplib/main.php");

// custom includes
require("../../include/accounts/inc_invoices.php");
require("../../include/accounts/inc_invoices_items.php");



if (user_permissions_get('accounts_ar_write'))
{
	/*
		Let the invoices functions do all the work for us
	*/

	$returnpage_error	= "accounts/ar/invoice-items.php";
	$returnpage_success	= "accounts/ar/invoice-items-edit.php";

	invoice_form_items_add_process("ar", $returnpage_error, $returnpage_success);
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
