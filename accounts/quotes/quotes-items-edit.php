<?php
/*
	accounts/ar/quotes-items-edit.php
	
	access: account_ar_write

	Allows adjusting or addition of new items to an quote.
*/

// custom includes
require("include/accounts/inc_quotes.php");
require("include/accounts/inc_charts.php");
require("include/accounts/inc_invoices_items.php");


if (user_permissions_get('accounts_quotes_write'))
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

	$_SESSION["nav"]["title"][]	= "Convert to Invoice";
	$_SESSION["nav"]["query"][]	= "page=accounts/quotes/quotes-convert.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Delete Quote";
	$_SESSION["nav"]["query"][]	= "page=accounts/quotes/quotes-delete.php&id=$id";
	

	function page_render()
	{
		$id		= security_script_input('/^[0-9]*$/', $_GET["id"]);


		/*
			Title + Summary
		*/
		print "<h3>ADD/EDIT QUOTE ITEM</h3><br>";
		print "<p>This page allows you to make changes to an quote item.</p>";

		quotes_render_summarybox("quotes", $id);

		invoice_form_items_render("quotes", $id, "accounts/quotes/quotes-items-edit-process.php");


	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
