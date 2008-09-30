<?php
/*
	accounts/ar/transactions-edit-process.php

	access: accounts_transactions_write

	Allows new transactions to be added to the database, or for existing transactions to be adjusted.
*/

// includes
require("../../include/config.php");
require("../../include/amberphplib/main.php");

// custom includes
require("../../include/accounts/inc_transactions.php");



if (user_permissions_get('accounts_ar_write'))
{
	/*
		Let the transactions functions do all the work for us
	*/

	if ($_POST["id_transaction"])
	{
		// edit transaction
		$mode			= "edit";
		$returnpage_error	= "accounts/ar/transactions-view.php";
		$returnpage_success	= "accounts/ar/transactions-view.php";
	}
	else
	{
		// new transaction
		$mode			= "add";
		$returnpage_error	= "accounts/ar/transactions-add.php";
		$returnpage_success	= "accounts/ar/transactions-view.php";
	}

	transaction_form_details_process("ar", $mode, $returnpage_error, $returnpage_success);
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
