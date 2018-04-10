<?php
/*
	user/user-journal.php

	access: admin only

	Standard journal for users records and audit trail.
*/


class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_journal;


	function __construct()
	{
		// fetch variables
		$this->id = @security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("User's Details", "page=user/user-view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("User's Journal", "page=user/user-journal.php&id=". $this->id ."", TRUE);
		$this->obj_menu_nav->add_item("User's Permissions", "page=user/user-permissions.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("User's Staff Access Rights", "page=user/user-staffaccess.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Delete User", "page=user/user-delete.php&id=". $this->id ."");
	}


	function check_permissions()
	{
		return user_permissions_get("admin");
	}


	function check_requirements()
	{
		// verify that user exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM users WHERE id='". $this->id ."'";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested user (". $this->id .") does not exist - possibly the user has been deleted.");
			return 0;
		}

		unset($sql_obj);


		return 1;
	}


	function execute()
	{
		/*
			Define the journal structure
		*/

		// basic
		$this->obj_journal		= New journal_display;
		$this->obj_journal->journalname	= "users";
		
		// set the pages to use for forms or file downloads
		$this->obj_journal->prepare_set_form_process_page("user/user-journal-edit.php");
		$this->obj_journal->prepare_set_download_page("user/user-journal-download-process.php");
		
		// configure options form
		$this->obj_journal->prepare_predefined_optionform();
		$this->obj_journal->add_fixed_option("id", $this->id);

		// load options
		$this->obj_journal->load_options_form();


		// define SQL structure
		$this->obj_journal->sql_obj->prepare_sql_addwhere("customid='". $this->id ."'");		// we only want journal entries for this ticket!

		// process SQL			
		$this->obj_journal->generate_sql();
		$this->obj_journal->load_data();

	}
	

	function render_html()
	{
		// Title + Summary
		print "<h3>USER'S JOURNAL</h3><br>";
		print "<p>The journal is a place where you can put your own notes, files and view the history of this user.</p>";

		print "<p><a class=\"button\" href=\"index.php?page=user/user-journal-edit.php&type=text&id=". $this->id ."\">Add new journal entry</a> <a class=\"button\" href=\"index.php?page=user/user-journal-edit.php&type=file&id=". $this->id ."\">Upload File</a></p>";

		// display options form
		$this->obj_journal->render_options_form();

		// display			
		$this->obj_journal->render_journal();
		
	}

}

?>
