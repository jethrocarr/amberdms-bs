<?php
/*
	accounts/ar/quotes-edit-process.php

	access: accounts_quotes_write

	Allows new quotes to be added to the database, or for existing quotes to be adjusted.
*/

// includes
require("../../include/config.php");
require("../../include/amberphplib/main.php");

// custom includes
require("../../include/accounts/inc_quotes_process.php");



if (user_permissions_get('accounts_quotes_write'))
{
	/*
		Let the quotes functions do all the work for us
	*/

	if ($_POST["id_quote"])
	{
		// edit quote
		$mode			= "edit";
		$returnpage_error	= "accounts/quotes/quotes-view.php";
		$returnpage_success	= "accounts/quotes/quotes-view.php";
	}
	else
	{
		// new quote
		$mode			= "add";
		$returnpage_error	= "accounts/quotes/quotes-add.php";
		$returnpage_success	= "accounts/quotes/quotes-view.php";
	}

	quotes_form_details_process($mode, $returnpage_error, $returnpage_success);
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
