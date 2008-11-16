<?php
/*
	services/journal.php
	
	access: services_view (read-only)
		services_write (write access)

	Standard journal for service records and audit trail.
*/


if (user_permissions_get('services_view'))
{
	$id = $_GET["id"];


	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;

	$_SESSION["nav"]["title"][]	= "Service Details";
	$_SESSION["nav"]["query"][]	= "page=services/view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Service Plan";
	$_SESSION["nav"]["query"][]	= "page=services/plan.php&id=$id";
	
	$_SESSION["nav"]["title"][]	= "Service Journal";
	$_SESSION["nav"]["query"][]	= "page=services/journal.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=services/journal.php&id=$id";

	if (user_permissions_get('services_write'))
	{
		$_SESSION["nav"]["title"][]	= "Delete Service";
		$_SESSION["nav"]["query"][]	= "page=services/delete.php&id=$id";
	}


	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		print "<h3>SERVICE JOURNAL</h3><br>";
		print "<p>The journal is a place where you can put your own notes, files and view the history of this service.</p>";

		print "<p><b><a href=\"index.php?page=services/journal-edit.php&type=text&id=$id\">Add new journal entry</a> || <a href=\"index.php?page=services/journal-edit.php&type=file&id=$id\">Upload File</a></b></p>";


		// make sure the service exists
		$sql = New sql_query;
		$sql->string = "SELECT id FROM `services` WHERE id='$id'";
		$sql->execute();
		
		if (!$sql->num_rows())
		{
			print "<p><b>Error: The requested service  does not exist. <a href=\"index.php?page=services/services.php\">Try looking for your service on the service list page.</a></b></p>";
		}
		else
		{
			/*
				Define the journal structure
			*/

			// basic
			$journal		= New journal_display;
			$journal->journalname	= "services";
			
			// set the pages to use for forms or file downloads
			$journal->prepare_set_form_process_page("services/journal-edit.php");
			$journal->prepare_set_download_page("services/journal-download-process.php");
			
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
