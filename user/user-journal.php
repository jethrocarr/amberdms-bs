<?php
/*
	user/user-journal.php

	access: admin only

	Standard journal for users records and audit trail.
*/


if (user_permissions_get('admin'))
{
	$id = $_GET["id"];

	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "User's Details";
	$_SESSION["nav"]["query"][]	= "page=user/user-view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "User's Permissions";
	$_SESSION["nav"]["query"][]	= "page=user/user-permissions.php&id=$id";

	$_SESSION["nav"]["title"][]	= "User's Staff Access Rights";
	$_SESSION["nav"]["query"][]	= "page=user/user-staffaccess.php&id=$id";
	
	$_SESSION["nav"]["title"][]	= "User's Journal";
	$_SESSION["nav"]["query"][]	= "page=user/user-journal.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=user/user-journal.php&id=$id";
	
	$_SESSION["nav"]["title"][]	= "Delete User";
	$_SESSION["nav"]["query"][]	= "page=user/user-delete.php&id=$id";




	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		print "<h3>USER'S JOURNAL</h3><br>";
		print "<p>The journal is a place where you can put your own notes, files and view the history of this user.</p>";

		print "<p><b><a href=\"index.php?page=user/user-journal-edit.php&type=text&id=$id\">Add new journal entry</a> || <a href=\"index.php?page=user/user-journal-edit.php&type=file&id=$id\">Upload File</a></b></p>";


		// make sure the users exists
		$sql = New sql_query;
		$sql->string = "SELECT id FROM `users` WHERE id='$id'";
		$sql->execute();
		
		if (!$sql->num_rows())
		{
			print "<p><b>Error: The requested user does not exist. <a href=\"index.php?page=users/users.php\">Try looking for your user on the users list page.</a></b></p>";
		}
		else
		{
			/*
				Define the journal structure
			*/

			// basic
			$journal		= New journal_display;
			$journal->journalname	= "users";
			
			// set the pages to use for forms or file downloads
			$journal->prepare_set_form_process_page("user/user-journal-edit.php");
			$journal->prepare_set_download_page("user/user-journal-download-process.php");
			
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
