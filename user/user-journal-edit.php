<?php
/*
	users/journal_edit.php
	
	access: admin only

	Allows the addition or adjustment of journal entries.
*/

class page_output
{
	var $id;
	var $journalid;
	var $action;
	var $type;
	
	var $obj_menu_nav;
	var $obj_form_journal;


	function page_output()
	{
		// fetch variables
		$this->id		= @security_script_input('/^[0-9]*$/', $_GET["id"]);
		$this->journalid	= @security_script_input('/^[0-9]*$/', $_GET["journalid"]);
		$this->action		= @security_script_input('/^[a-z]*$/', $_GET["action"]);
		$this->type		= @security_script_input('/^[a-z]*$/', $_GET["type"]);

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
			Journal Form
		*/

		$this->obj_journal_form = New journal_input;
			
		// basic details of this entry
		$this->obj_journal_form->prepare_set_journalname("users");
		$this->obj_journal_form->prepare_set_journalid($this->journalid);
		$this->obj_journal_form->prepare_set_customid($this->id);

		// set the processing form
		$this->obj_journal_form->prepare_set_form_process_page("user/user-journal-edit-process.php");

	}


	function render_html()
	{
		if ($this->action == "delete")
		{
			print "<h3>USER JOURNAL - DELETE ENTRY</h3><br>";
			print "<p>This page allows you to delete an entry from the user's journal.</p>";

			// render delete form
			$this->obj_journal_form->render_delete_form();		

		}
		else
		{
			if ($this->type == "file")
			{
				// file uploader
				if ($this->journalid)
				{
					print "<h3>USER JOURNAL - UPLOAD FILE</h3><br>";
					print "<p>This page allows you to attach a file to the user's journal.</p>";
				}
				else
				{
					print "<h3>USER JOURNAL - UPLOAD FILE</h3><br>";
					print "<p>This page allows you to attach a file to the user's journal.</p>";
				}

				// edit or add file
				$this->obj_journal_form->render_file_form();
			}
			else
			{
				// default to text
				if ($this->journalid)
				{
					print "<h3>USER JOURNAL - EDIT ENTRY</h3><br>";
					print "<p>This page allows you to edit an existing entry in the user's journal.</p>";
				}
				else
				{
					print "<h3>USER JOURNAL - ADD ENTRY</h3><br>";
					print "<p>This page allows you to add an entry to the user's journal.</p>";
				}

				// edit or add
				$this->obj_journal_form->render_text_form();		
			}
			
		}
	}
		
}

?>
