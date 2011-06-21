<?php
/*
	accounts/ar/credit-export-process.php

	access: accounts_ar_view (PDF download only)
		accounts_ar_write (PDF download + email features)

	Allows credits to be exported or email to customers
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

	// edit credit
	$returnpage_error	= "accounts/ar/credit-export.php";
	$returnpage_success	= "accounts/ar/credit-export.php";

	credit_form_export_process("ar_credit", $returnpage_error, $returnpage_success);
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
