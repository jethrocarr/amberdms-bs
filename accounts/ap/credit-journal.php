<?php
/*
	accounts/ap/credit-journal.php
	
	access: accounts_ap_view
		accounts_ap_write

	Standard journal for credit/credit records and journal trail.
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

		$this->obj_menu_nav->add_item("Credit Details", "page=accounts/ap/credit-view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Credit Items", "page=accounts/ap/credit-items.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Credit Payment/Refund", "page=accounts/ap/credit-payments.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Credit Journal", "page=accounts/ap/credit-journal.php&id=". $this->id ."", TRUE);

		if (user_permissions_get("accounts_ap_write"))
		{
			$this->obj_menu_nav->add_item("Delete Credit", "page=accounts/ap/credit-delete.php&id=". $this->id ."");
		}
	}



	function check_permissions()
	{
		return user_permissions_get("accounts_ap_view");
	}



	function check_requirements()
	{
		// verify that the credit
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_ap_credit WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested credit (". $this->id .") does not exist - possibly the credit has been deleted.");
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
		$this->obj_journal->journalname	= "account_ap_credit";
		
		// set the pages to use for forms or file downloads
		$this->obj_journal->prepare_set_form_process_page("accounts/ap/credit-journal-edit.php");
		$this->obj_journal->prepare_set_download_page("accounts/ap/credit-journal-download-process.php");


		// configure options form
		$this->obj_journal->prepare_predefined_optionform();
		$this->obj_journal->add_fixed_option("id", $this->id);

		// load options
		$this->obj_journal->load_options_form();

		// define SQL structure
		$this->obj_journal->sql_obj->prepare_sql_addwhere("customid='". $this->id ."'");

		// process SQL			
		$this->obj_journal->generate_sql();
		$this->obj_journal->load_data();
	}




	function render_html()
	{
		// Title + Summary
		print "<h3>CREDIT NOTE JOURNAL</h3><br>";
		print "<p>The journal is a place where you can put your own notes, files and view the history of this credit note.</p>";

		if (user_permissions_get("accounts_ap_write"))
		{
			print "<p><a class=\"button\" href=\"index.php?page=accounts/ap/credit-journal-edit.php&type=text&id=". $this->id ."\">Add new journal entry</a> <a class=\"button\" href=\"index.php?page=accounts/ap/credit-journal-edit.php&type=file&id=". $this->id ."\">Upload File</a></p>";
		}
		else
		{
			format_msgbox("locked", "<p>Note: your permissions limit you to read-only access to the journal</p>");
		}
		
		// display options form
		$this->obj_journal->render_options_form();
		
		// display the journal
		$this->obj_journal->render_journal();
	}

}

?>
