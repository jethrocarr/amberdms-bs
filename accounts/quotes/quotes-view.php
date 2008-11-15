<?php
/*
	accounts/quotes/quotes-view.php
	
	access: account_quotes_view

	Displays the details of the selected quote.
*/

// custom includes
require("include/accounts/inc_quotes.php");
require("include/accounts/inc_quotes_details.php");


if (user_permissions_get('accounts_quotes_view'))
{
	$id = $_GET["id"];

	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "Quote Details";
	$_SESSION["nav"]["query"][]	= "page=accounts/quotes/quotes-view.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=accounts/quotes/quotes-view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Quote Items";
	$_SESSION["nav"]["query"][]	= "page=accounts/quotes/quotes-items.php&id=$id";
	
	$_SESSION["nav"]["title"][]	= "Quote Journal";
	$_SESSION["nav"]["query"][]	= "page=accounts/quotes/journal.php&id=$id";

	if (user_permissions_get('accounts_quotes_write'))
	{
		$_SESSION["nav"]["title"][]	= "Convert to Invoice";
		$_SESSION["nav"]["query"][]	= "page=accounts/quotes/quotes-convert.php&id=$id";

		$_SESSION["nav"]["title"][]	= "Delete Quote";
		$_SESSION["nav"]["query"][]	= "page=accounts/quotes/quotes-delete.php&id=$id";
	}



	function page_render()
	{
		$id		= security_script_input('/^[0-9]*$/', $_GET["id"]);


		/*
			Title + Summary
		*/
		print "<h3>VIEW QUOTE</h3><br>";
		print "<p>This page allows you to view the basic details of the quote. You can use the links in the green navigation menu above to change to different sections of the quote, in order to add items or journal entries to the quote.</p>";

		quotes_render_summarybox($id);

		quotes_form_details_render($id, "accounts/quotes/quotes-edit-process.php");


	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
