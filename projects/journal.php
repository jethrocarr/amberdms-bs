<?php
/*
	projects/journal.php
	
	access: projects_view (read-only)
		projects_write (write access)

	Standard journal for project records and audit trail.
*/


if (user_permissions_get('projects_view'))
{
	$id = $_GET["id"];

	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "Project Details";
	$_SESSION["nav"]["query"][]	= "page=projects/view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Project Phases";
	$_SESSION["nav"]["query"][]	= "page=projects/phases.php&id=$id";
	
	$_SESSION["nav"]["title"][]	= "Timebooked";
	$_SESSION["nav"]["query"][]	= "page=projects/timebooked.php&id=$id";
	
	$_SESSION["nav"]["title"][]	= "Project Journal";
	$_SESSION["nav"]["query"][]	= "page=projects/journal.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=projects/journal.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Delete Project";
	$_SESSION["nav"]["query"][]	= "page=projects/delete.php&id=$id";



	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		print "<h3>PROJECT JOURNAL</h3><br>";
		print "<p>The journal is a place where you can put your own notes, files and view the history of this project account.</p>";

		print "<p><b><a href=\"index.php?page=projects/journal-edit.php&type=text&id=$id\">Add new journal entry</a> || <a href=\"index.php?page=projects/journal-edit.php&type=file&id=$id\">Upload File</a></b></p>";


		// make sure the project exists
		$sql = New sql_query;
		$sql->string = "SELECT id FROM `projects` WHERE id='$id'";
		$sql->execute();
		
		if (!$sql->num_rows())
		{
			print "<p><b>Error: The requested project  does not exist. <a href=\"index.php?page=projects/projects.php\">Try looking for your project on the project list page.</a></b></p>";
		}
		else
		{
			/*
				Define the journal structure
			*/

			// basic
			$journal		= New journal_display;
			$journal->journalname	= "projects";
			
			// set the pages to use for forms or file downloads
			$journal->prepare_set_form_process_page("projects/journal-edit.php");
			$journal->prepare_set_download_page("projects/journal-download-process.php");


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
