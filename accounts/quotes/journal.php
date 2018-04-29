<?php
/*
	accounts/quotes/journal.php
	
	access: accounts_quotes_view
		accounts_quotes_write

	Standard journal for quote records and audit trail.
*/



class page_output
{
	var $id;
	
	var $obj_menu_nav;
	var $obj_journal;


	function __construct()
	{
		// fetch quote ID
		$this->id = @security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Quote Details", "page=accounts/quotes/quotes-view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Quote Items", "page=accounts/quotes/quotes-items.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Quote Journal", "page=accounts/quotes/journal.php&id=". $this->id ."", TRUE);
		$this->obj_menu_nav->add_item("Export Quote", "page=accounts/quotes/quotes-export.php&id=". $this->id ."");

		if (user_permissions_get("accounts_quotes_write"))
		{
                        $this->obj_menu_nav->add_item("Create Project", "page=accounts/quotes/quotes-convert-project.php&id=". $this->id ."");
			$this->obj_menu_nav->add_item("Convert to Invoice", "page=accounts/quotes/quotes-convert.php&id=". $this->id ."");
			$this->obj_menu_nav->add_item("Delete Quote", "page=accounts/quotes/quotes-delete.php&id=". $this->id ."");
		}
	}



	function check_permissions()
	{
		return user_permissions_get("accounts_quotes_view");
	}



	function check_requirements()
	{
		// verify that the quote
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_quotes WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested quote (". $this->id .") does not exist - possibly the quote has been deleted.");
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
		$this->obj_journal->journalname	= "account_quotes";
		
		// set the pages to use for forms or file downloads
		$this->obj_journal->prepare_set_form_process_page("accounts/quotes/journal-edit.php");
		$this->obj_journal->prepare_set_download_page("accounts/quotes/journal-download-process.php");


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
		// title and summary
		print "<h3>QUOTE JOURNAL</h3><br>";
		print "<p>The journal is a place where you can put your own notes, files and view the history of this quote.</p>";

		
		if (user_permissions_get("accounts_quotes_write"))
		{
			print "<p><a class=\"button\" href=\"index.php?page=accounts/quotes/journal-edit.php&type=text&id=". $this->id ."\">Add new journal entry</a> <a class=\"button\" href=\"index.php?page=accounts/quotes/journal-edit.php&type=file&id=". $this->id ."\">Upload File</a></p>";
		}
		else
		{
			format_msgbox("locked", "<p>Note: your permissions limit you to read-only access to the journal</p>");
		}

		// display options form
		$this->obj_journal->render_options_form();

		// display journal
		$this->obj_journal->render_journal();
	}
}

?>
