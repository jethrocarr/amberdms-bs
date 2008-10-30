<?php
/*
	accounts/ar/journal.php
	
	access: accounts_ar_view (read-only)
		accounts_ar_write (write access)

	Standard journal for invoice/invoice records and journal trail.
*/


if (user_permissions_get('accounts_ar_view'))
{
	$id = $_GET["id"];
	
	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "Invoice Details";
	$_SESSION["nav"]["query"][]	= "page=accounts/ar/invoice-view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Invoice Items";
	$_SESSION["nav"]["query"][]	= "page=accounts/ar/invoice-items.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Invoice Payments";
	$_SESSION["nav"]["query"][]	= "page=accounts/ar/invoice-payments.php&id=$id";
	
	$_SESSION["nav"]["title"][]	= "Invoice Journal";
	$_SESSION["nav"]["query"][]	= "page=accounts/ar/journal.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=accounts/ar/journal.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Delete Invoice";
	$_SESSION["nav"]["query"][]	= "page=accounts/ar/invoice-delete.php&id=$id";



	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		print "<h3>INVOICE JOURNAL</h3><br>";
		print "<p>The journal is a place where you can put your own notes, files and view the history of this invoice.</p>";

		print "<p><b><a href=\"index.php?page=accounts/ar/journal-edit.php&type=text&id=$id\">Add new journal entry</a> || <a href=\"index.php?page=accounts/ar/journal-edit.php&type=file&id=$id\">Upload File</a></b></p>";


		// make sure the invoice exists
		$sql = New sql_query;
		$sql->string = "SELECT id FROM `account_ar` WHERE id='$id'";
		$sql->execute();
		
		if (!$sql->num_rows())
		{
			print "<p><b>Error: The requested invoice does not exist. <a href=\"index.php?page=accounts/ar.php\">Try looking for your invoice on the invoice list page.</a></b></p>";
		}
		else
		{
			/*
				Define the journal structure
			*/

			// basic
			$journal		= New journal_display;
			$journal->journalname	= "account_ar";
			
			// set the pages to use for forms or file downloads
			$journal->prepare_set_form_process_page("accounts/ar/journal-edit.php");
			$journal->prepare_set_download_page("accounts/ar/journal-download-process.php");


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
