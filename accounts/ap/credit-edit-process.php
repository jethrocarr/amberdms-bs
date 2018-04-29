<?php
/*
	accounts/ap/credit-edit-process.php

	access: accounts_ap_write

	Allows new credits to be added to the database, or for existing credits to be adjusted.
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

	if (isset($_POST["id_credit"]))
	{
		// edit credit
		$mode			= "edit";
		$returnpage_error	= "accounts/ap/credit-view.php";
		$returnpage_success	= "accounts/ap/credit-view.php";
	}
	else
	{
		// new credit
		$mode			= "add";
		$returnpage_error	= "accounts/ap/credit-add.php";
		$returnpage_success	= "accounts/ap/credit-view.php";
	}

	credit_form_details_process("ap_credit", $mode, $returnpage_error, $returnpage_success);
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
