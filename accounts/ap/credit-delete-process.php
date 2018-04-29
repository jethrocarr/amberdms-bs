<?php
/*
	accounts/ap/credit-delete-process.php

	access: accounts_ap_write

	Deletes credits from the database
*/

// includes
require("../../include/config.php");
require("../../include/amberphplib/main.php");

// custom includes
require("../../include/accounts/inc_credits_process.php");



if (user_permissions_get('accounts_ap_write'))
{
	/*
		Let the credits functions do all the work for us
	*/

	$returnpage_error	= "accounts/ap/credit-delete.php";
	$returnpage_success	= "accounts/ap/ap-credits.php";

	credit_form_delete_process("ap_credit",$returnpage_error, $returnpage_success);
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
