<?php
/*
	accounts/ap/credit-lock-process.php

	access: accounts_credits_write

	Locks the selected credit
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

	$returnpage_error	= "accounts/ap/credit-payments.php";
	$returnpage_success	= "accounts/ap/credit-payments.php";

	credit_form_lock_process("ap_credit",$returnpage_error, $returnpage_success);
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
