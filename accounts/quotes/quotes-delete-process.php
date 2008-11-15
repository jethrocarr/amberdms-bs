<?php
/*
	accounts/quotes/quotes-delete-process.php

	access: accounts_quotes_write

	Deletes quotes from the database
*/

// includes
require("../../include/config.php");
require("../../include/amberphplib/main.php");

// custom includes
require("../../include/accounts/inc_quotes_delete.php");



if (user_permissions_get('accounts_quotes_write'))
{
	/*
		Let the quotes functions do all the work for us
	*/

	$returnpage_error	= "accounts/quotes/quotes-delete.php";
	$returnpage_success	= "accounts/quotes/quotes.php";

	quotes_form_delete_process($returnpage_error, $returnpage_success);
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
