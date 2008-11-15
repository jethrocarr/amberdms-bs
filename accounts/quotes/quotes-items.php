<?php
/*
	accounts/quotes/quotes-items.php
	
	access: account_quotes_view

	Page to list all the items on the quote. We call some of the invoice functions here since the code
	needed for invoice items is the mostly the same as the code needed for quote items.
	
*/

// custom includes
require("include/accounts/inc_quotes.php");
require("include/accounts/inc_invoices_items.php");


if (user_permissions_get('accounts_quotes_view'))
{
	$id = $_GET["id"];

	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;

	$_SESSION["nav"]["title"][]	= "Quote Details";
	$_SESSION["nav"]["query"][]	= "page=accounts/quotes/quotes-view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Quote Items";
	$_SESSION["nav"]["query"][]	= "page=accounts/quotes/quotes-items.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=accounts/quotes/quotes-items.php&id=$id";
	
	$_SESSION["nav"]["title"][]	= "Quote Journal";
	$_SESSION["nav"]["query"][]	= "page=accounts/quotes/journal.php&id=$id";

	if (user_permissions_get('accounts_quotes_write'))
	{
		$_SESSION["nav"]["title"][]	= "Delete Quote";
		$_SESSION["nav"]["query"][]	= "page=accounts/quotes/quotes-delete.php&id=$id";
	}



	function page_render()
	{
		$id		= security_script_input('/^[0-9]*$/', $_GET["id"]);


		/*
			Title + Summary
		*/
		print "<h3>QUOTE ITEMS</h3><br>";
		print "<p>This page shows all the items belonging to the quote and allows you to edit them.</p>";

		quotes_render_summarybox($id);

		invoice_list_items("quotes", $id, "accounts/quotes/quotes-items-edit.php", "accounts/quotes/quotes-items-delete-process.php");


	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
