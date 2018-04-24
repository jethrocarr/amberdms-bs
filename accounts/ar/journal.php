<?php
/*
	accounts/ar/journal.php
	
	access: accounts_ar_view
		accounts_ar_write

	Standard journal for invoice/invoice records and journal trail.
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

		$this->obj_menu_nav->add_item("Invoice Details", "page=accounts/ar/invoice-view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Invoice Items", "page=accounts/ar/invoice-items.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Invoice Payments", "page=accounts/ar/invoice-payments.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Invoice Journal", "page=accounts/ar/journal.php&id=". $this->id ."", TRUE);
		$this->obj_menu_nav->add_item("Export Invoice", "page=accounts/ar/invoice-export.php&id=". $this->id ."");

		if (user_permissions_get("accounts_ar_write") 
                        && ((sql_get_singlevalue("SELECT cancelled as value FROM account_ar WHERE id='".$this->id."'")=='0' && $GLOBALS["config"]["ACCOUNTS_CANCEL_DELETE"]=="1")
                                || $GLOBALS["config"]["ACCOUNTS_CANCEL_DELETE"]=="0")
                    )
		{
                    if($GLOBALS["config"]["ACCOUNTS_CANCEL_DELETE"]=="1")
                    {
                        $title="Cancel Invoice";
                    }
                    else
                    {
                        $title="Delete Invoice";
                    }
                    $this->obj_menu_nav->add_item($title, "page=accounts/ar/invoice-delete.php&id=". $this->id ."");
		}
	}



	function check_permissions()
	{
		return user_permissions_get("accounts_ar_view");
	}



	function check_requirements()
	{
		// verify that the invoice
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_ar WHERE id='". $this->id ."' LIMIT 1";
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
			Define the journal structure
		*/

		// basic
		$this->obj_journal		= New journal_display;
		$this->obj_journal->journalname	= "account_ar";
		
		// set the pages to use for forms or file downloads
		$this->obj_journal->prepare_set_form_process_page("accounts/ar/journal-edit.php");
		$this->obj_journal->prepare_set_download_page("accounts/ar/journal-download-process.php");


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
		print "<h3>INVOICE JOURNAL</h3><br>";
		print "<p>The journal is a place where you can put your own notes, files and view the history of this invoice.</p>";

		if (user_permissions_get("accounts_ar_write"))
		{
			print "<p><a class=\"button\" href=\"index.php?page=accounts/ar/journal-edit.php&type=text&id=". $this->id ."\">Add new journal entry</a> <a class=\"button\" href=\"index.php?page=accounts/ar/journal-edit.php&type=file&id=". $this->id ."\">Upload File</a></p>";
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
