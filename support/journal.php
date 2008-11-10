<?php
/*
	support/journal.php
	
	access: support_view (read-only)
		support_write (write access)

	Standard journal for support records and audit trail.
*/


if (user_permissions_get('support_view'))
{
	$id = $_GET["id"];
	
	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "Support Ticket Details";
	$_SESSION["nav"]["query"][]	= "page=support/view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Support Ticket Journal";
	$_SESSION["nav"]["query"][]	= "page=support/journal.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=support/journal.php&id=$id";
	
	if (user_permissions_get('support_write'))
	{
		$_SESSION["nav"]["title"][]	= "Delete Support Ticket";
		$_SESSION["nav"]["query"][]	= "page=support/delete.php&id=$id";
	}



	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		print "<h3>SUPPORT JOURNAL</h3><br>";
		
		print "<p>Use this journal to file all notes, attachments or other information relating to this support ticket.</p>";
		print "<p><b><a href=\"index.php?page=support/journal-edit.php&type=text&id=$id\">Add new journal entry</a> || <a href=\"index.php?page=support/journal-edit.php&type=file&id=$id\">Upload File</a></b></p>";


		// make sure the support exists
		$sql = New sql_query;
		$sql->string = "SELECT id FROM `support_tickets` WHERE id='$id'";
		$sql->execute();
		
		if (!$sql->num_rows())
		{
			print "<p><b>Error: The requested support  does not exist. <a href=\"index.php?page=support/support.php\">Try looking for your support on the support list page.</a></b></p>";
		}
		else
		{
			/*
				Define the journal structure
			*/

			// basic
			$journal		= New journal_display;
			$journal->journalname	= "support_tickets";
			
			// set the pages to use for forms or file downloads
			$journal->prepare_set_form_process_page("support/journal-edit.php");
			$journal->prepare_set_download_page("support/journal-download-process.php");

			// configure options form
			$journal->prepare_predefined_optionform();
			$journal->add_fixed_option("id", $id);

			// load + display options form
			$journal->load_options_form();
			$journal->render_options_form();


			// define SQL structure
			$journal->sql_obj->prepare_sql_addwhere("customid='$id'");		// we only want journal entries for this ticket!

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
