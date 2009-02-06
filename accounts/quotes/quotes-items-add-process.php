<?php
/*
	accounts/quotes/quotes-items-add-process.php
	
	access: account_quotes_write

	Checks user input on the quotes-items.php page and if valid, directs
	them to the add quote item page.
*/




// includes
require("../../include/config.php");
require("../../include/amberphplib/main.php");

// custom includes
require("../../include/accounts/inc_invoices.php");
require("../../include/accounts/inc_invoices_items.php");



if (user_permissions_get('accounts_quotes_write'))
{
	/*
		Let the invoice functions do all the work for us
	*/

	$returnpage_error	= "accounts/quotes/quotes-items.php";
	$returnpage_success	= "accounts/quotes/quotes-items-edit.php";

	invoice_form_items_add_process("quotes", $returnpage_error, $returnpage_success);
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
