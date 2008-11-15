<?php
/*
	accounts/quotes/journal.php
	
	access: accounts_quotes_view (read-only)
		accounts_quotes_write (write access)

	Standard journal for quote records and journal trail.
*/


if (user_permissions_get('accounts_quotes_view'))
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
	$_SESSION["nav"]["current"]	= "page=accounts/quotes/journal.php&id=$id";

	if (user_permissions_get('accounts_quotes_write'))
	{
		$_SESSION["nav"]["title"][]	= "Delete Quote";
		$_SESSION["nav"]["query"][]	= "page=accounts/quotes/quotes-delete.php&id=$id";
	}




	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		print "<h3>QUOTE JOURNAL</h3><br>";
		print "<p>The journal is a place where you can put your own notes, files and view the history of this quote.</p>";

		print "<p><b><a href=\"index.php?page=accounts/quotes/journal-edit.php&type=text&id=$id\">Add new journal entry</a> || <a href=\"index.php?page=accounts/quotes/journal-edit.php&type=file&id=$id\">Upload File</a></b></p>";


		// make sure the quote exists
		$sql = New sql_query;
		$sql->string = "SELECT id FROM `account_quotes` WHERE id='$id'";
		$sql->execute();
		
		if (!$sql->num_rows())
		{
			print "<p><b>Error: The requested quote does not exist. <a href=\"index.php?page=accounts/quotes/quotes.php\">Try looking for your quote on the quote list page.</a></b></p>";
		}
		else
		{
			/*
				Define the journal structure
			*/

			// basic
			$journal		= New journal_display;
			$journal->journalname	= "account_quotes";
			
			// set the pages to use for forms or file downloads
			$journal->prepare_set_form_process_page("accounts/quotes/journal-edit.php");
			$journal->prepare_set_download_page("accounts/quotes/journal-download-process.php");


			// configure options form
			$journal->prepare_predefined_optionform();
			$journal->add_fixed_option("id", $id);

			// load + display options form
			$journal->load_options_form();
			$journal->render_options_form();


			// define SQL structure
			$journal->sql_obj->prepare_sql_addwhere("customid='$id'");

			// process SQL			
			$journal->generate_sql();
			$journal->load_data();


			// display			
			$journal->render_journal();
			
		}

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
