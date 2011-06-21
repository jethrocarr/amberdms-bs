<?php
/*
	accounts/ar/credit-delete-process.php

	access: accounts_credits_write

	Deletes credits from the database
*/

// includes
require("../../include/config.php");
require("../../include/amberphplib/main.php");

// custom includes
require("../../include/accounts/inc_credits_process.php");



if (user_permissions_get('accounts_ar_write'))
{
	/*
		Let the credits functions do all the work for us
	*/

	$returnpage_error	= "accounts/ar/credit-delete.php";
	$returnpage_success	= "accounts/ar/ar.php";

	credit_form_delete_process("ar_credit",$returnpage_error, $returnpage_success);
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
