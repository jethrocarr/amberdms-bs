<?php
/*
	accounts/quotes/quotes-convert.php
	
	access: account_quotes_write

	Form to convert a quote into an invoice
*/

// custom includes
require("include/accounts/inc_invoices.php");
require("include/accounts/inc_charts.php");
require("include/accounts/inc_quotes.php");
require("include/accounts/inc_quotes_convert.php");


if (user_permissions_get('accounts_quotes_write'))
{
	$id = $_GET["id"];

	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;

	$_SESSION["nav"]["title"][]	= "Quote Details";
	$_SESSION["nav"]["query"][]	= "page=accounts/quotes/quotes-view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Quote Items";
	$_SESSION["nav"]["query"][]	= "page=accounts/quotes/quotes-items.php&id=$id";
	
	$_SESSION["nav"]["title"][]	= "Quote Journal";
	$_SESSION["nav"]["query"][]	= "page=accounts/quotes/journal.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Convert to Invoice";
	$_SESSION["nav"]["query"][]	= "page=accounts/quotes/quotes-convert.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=accounts/quotes/quotes-convert.php&id=$id";
	
	$_SESSION["nav"]["title"][]	= "Delete Quote";
	$_SESSION["nav"]["query"][]	= "page=accounts/quotes/quotes-delete.php&id=$id";
	

	function page_render()
	{
		$id		= security_script_input('/^[0-9]*$/', $_GET["id"]);


		/*
			Title + Summary
		*/

		print "<h3>CONVERT QUOTE</h3><br>";
		print "<p>This page allows you to convert this quote into an invoice. Please note that this action can not be reversed.</p>";		

		quotes_render_summarybox($id);

		quotes_form_convert_render($id, "accounts/quotes/quotes-convert-process.php");


	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
