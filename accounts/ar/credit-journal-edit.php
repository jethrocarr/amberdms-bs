<?php
/*
	accounts/ar/credit-journal_edit.php
	
	access: accounts_ar_write

	Allows the addition or adjustment of journal entries for credit journal entries.
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
		$this->id		= @@security_script_input('/^[0-9]*$/', $_GET["id"]);
		$this->journalid	= @@security_script_input('/^[0-9]*$/', $_GET["journalid"]);
		$this->action		= @@security_script_input('/^[a-z]*$/', $_GET["action"]);
		$this->type		= @@security_script_input('/^[a-z]*$/', $_GET["type"]);
		

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Credit Details", "page=accounts/ar/credit-view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Credit Items", "page=accounts/ar/credit-items.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Credit Payment/Refund", "page=accounts/ar/credit-payments.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Credit Journal", "page=accounts/ar/credit-journal.php&id=". $this->id ."", TRUE);
		$this->obj_menu_nav->add_item("Export Credit Note", "page=accounts/ar/credit-export.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Delete Credit", "page=accounts/ar/credit-delete.php&id=". $this->id ."");
	}



	function check_permissions()
	{
		return user_permissions_get("accounts_ar_write");
	}



	function check_requirements()
	{
		// verify that the invoice
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_ar_credit WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested credit note (". $this->id .") does not exist - possibly the credit has been deleted.");
			return 0;
		}

		unset($sql_obj);


		return 1;
	}


	function execute()
	{

		/*
			Journal Forms
		*/

		$this->obj_form_journal = New journal_input;
			
		// basic details of this entry
		$this->obj_form_journal->prepare_set_journalname("account_ar_credit");
		$this->obj_form_journal->prepare_set_journalid($this->journalid);
		$this->obj_form_journal->prepare_set_customid($this->id);

		// set the processing form
		$this->obj_form_journal->prepare_set_form_process_page("accounts/ar/credit-journal-edit-process.php");
	}


	function render_html()
	{
		if ($this->action == "delete")
		{
			print "<h3>CREDIT NOTE JOURNAL - DELETE ENTRY</h3><br>";
			print "<p>This page allows you to delete an entry from the credit note journal.</p>";

			// render delete form
			$this->obj_form_journal->render_delete_form();		

		}
		else
		{
			if ($this->type == "file")
			{
				// file uploader
				if ($this->journalid)
				{
					print "<h3>CREDIT NOTE JOURNAL - UPLOAD FILE</h3><br>";
					print "<p>This page allows you to attach a file to the credit note journal.</p>";
				}
				else
				{
					print "<h3>CREDIT NOTE JOURNAL - UPLOAD FILE</h3><br>";
					print "<p>This page allows you to attach a file to the credit note journal.</p>";
				}

				// edit or add file
				$this->obj_form_journal->render_file_form();
			}
			else
			{
				// default to text
				if ($this->journalid)
				{
					print "<h3>CREDIT NOTE JOURNAL - EDIT ENTRY</h3><br>";
					print "<p>This page allows you to edit an existing entry in the credit note journal.</p>";
				}
				else
				{
					print "<h3>CREDIT NOTE JOURNAL - ADD ENTRY</h3><br>";
					print "<p>This page allows you to add an entry to the credit note journal.</p>";
				}

				// edit or add
				$this->obj_form_journal->render_text_form();		
			}
			
		}
	}
}

?>
