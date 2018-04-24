<?php
/*
	projects/expenses-add-process.php
	
	access: projects_write

	Checks user input on the expenses.php and if valid directs them to the add expense item page.
*/




// includes
require("../include/config.php");
require("../include/amberphplib/main.php");

// custom includes
require("../include/accounts/inc_invoices.php");
require("../include/accounts/inc_invoices_items.php");



if (user_permissions_get('projects_write'))
{
	/*
		Let the invoices functions do all the work for us
	*/

	$returnpage_error	= "projects/expenses.php";
	$returnpage_success	= "projects/expenses-edit.php";

	invoice_form_items_add_process("project", $returnpage_error, $returnpage_success);
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
