<?php
/*
	accounts/ap/journal_edit.php
	
	access: accounts_ap_write

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
		// fetch vapiables
		$this->id		= security_script_input('/^[0-9]*$/', $_GET["id"]);
		$this->journalid	= security_script_input('/^[0-9]*$/', $_GET["journalid"]);
		$this->action		= security_script_input('/^[a-z]*$/', $_GET["action"]);
		$this->type		= security_script_input('/^[a-z]*$/', $_GET["type"]);
		

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Invoice Details", "page=accounts/ap/invoice-view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Invoice Items", "page=accounts/ap/invoice-items.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Invoice Payments", "page=accounts/ap/invoice-payments.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Invoice Journal", "page=accounts/ap/journal.php&id=". $this->id ."", TRUE);
		$this->obj_menu_nav->add_item("Delete Invoice", "page=accounts/ap/invoice-delete.php&id=". $this->id ."");
	}



	function check_permissions()
	{
		return user_permissions_get("accounts_ap_write");
	}



	function check_requirements()
	{
		// verify that the invoice
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_ap WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested invoice (". $this->id .") does not exist - possibly the invoice has been deleted.");
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
		$this->obj_form_journal->prepare_set_journalname("account_ap");
		$this->obj_form_journal->prepare_set_journalid($this->journalid);
		$this->obj_form_journal->prepare_set_customid($this->id);

		// set the processing form
		$this->obj_form_journal->prepare_set_form_process_page("accounts/ap/journal-edit-process.php");
	}


	function render_html()
	{
		if ($this->action == "delete")
		{
			print "<h3>INVOICE JOURNAL - DELETE ENTRY</h3><br>";
			print "<p>This page allows you to delete an entry from the invoice/invoice journal.</p>";

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
					print "<h3>INVOICE JOURNAL - UPLOAD FILE</h3><br>";
					print "<p>This page allows you to attach a file to the invoice journal.</p>";
				}
				else
				{
					print "<h3>INVOICE JOURNAL - UPLOAD FILE</h3><br>";
					print "<p>This page allows you to attach a file to the invoice journal.</p>";
				}

				// edit or add file
				$this->obj_form_journal->render_file_form();
			}
			else
			{
				// default to text
				if ($this->journalid)
				{
					print "<h3>INVOICE JOURNAL - EDIT ENTRY</h3><br>";
					print "<p>This page allows you to edit an existing entry in the invoice journal.</p>";
				}
				else
				{
					print "<h3>INVOICE JOURNAL - ADD ENTRY</h3><br>";
					print "<p>This page allows you to add an entry to the invoice journal.</p>";
				}

				// edit or add
				$this->obj_form_journal->render_text_form();		
			}
			
		}
	}
}

?>
