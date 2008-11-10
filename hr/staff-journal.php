<?php
/*
	staff/journal.php
	
	access: staff_view (read-only)
		staff_write (write access)

	Standard journal for staff records and audit trail.
*/


if (user_permissions_get('staff_view'))
{
	$id = $_GET["id"];
	
	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "Employee's Details";
	$_SESSION["nav"]["query"][]	= "page=hr/staff-view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Employee's Journal";
	$_SESSION["nav"]["query"][]	= "page=hr/staff-journal.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=hr/staff-journal.php&id=$id";

	if (user_permissions_get('staff_write'))
	{
		$_SESSION["nav"]["title"][]	= "Delete Employee";
		$_SESSION["nav"]["query"][]	= "page=hr/staff-delete.php&id=$id";
	}



	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		print "<h3>EMPLOYEE'S JOURNAL</h3><br>";
		print "<p>The journal is a place where you can put your own notes, files and view the history of this employee.</p>";

		print "<p><b><a href=\"index.php?page=hr/staff-journal-edit.php&type=text&id=$id\">Add new journal entry</a> || <a href=\"index.php?page=hr/staff-journal-edit.php&type=file&id=$id\">Upload File</a></b></p>";


		// make sure the staff exists
		$sql = New sql_query;
		$sql->string = "SELECT id FROM `staff` WHERE id='$id'";
		$sql->execute();
		
		if (!$sql->num_rows())
		{
			print "<p><b>Error: The requested employee does not exist. <a href=\"index.php?page=staff/staff.php\">Try looking for your employee on the staff list page.</a></b></p>";
		}
		else
		{
			/*
				Define the journal structure
			*/

			// basic
			$journal		= New journal_display;
			$journal->journalname	= "staff";
			
			// set the pages to use for forms or file downloads
			$journal->prepare_set_form_process_page("hr/staff-journal-edit.php");
			$journal->prepare_set_download_page("hr/staff-journal-download-process.php");


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
