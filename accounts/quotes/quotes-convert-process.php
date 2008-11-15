<?php
/*
	accounts/quotes/quotes-convert-process.php

	access: accounts_quotes_write

	Converts quotes into AR invoices
*/

// includes
require("../../include/config.php");
require("../../include/amberphplib/main.php");

// custom includes
require("../../include/accounts/inc_invoices_items.php");
require("../../include/accounts/inc_quotes_convert.php");



if (user_permissions_get('accounts_quotes_write'))
{
	/*
		Let the quotes functions do all the work for us
	*/

	$returnpage_error	= "accounts/quotes/quotes-convert.php";
	$returnpage_success	= "accounts/ar/invoice-view.php";

	quotes_form_convert_process($returnpage_error, $returnpage_success);
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
