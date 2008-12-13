<?php
/*
	accounts/ap/journal.php
	
	access: accounts_ap_view
		accounts_ap_write

	Standapd journal for invoice/invoice records and journal trail.
*/



class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_journal;


	function page_output()
	{
		// fetch vapiables
		$this->id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Invoice Details", "page=accounts/ap/invoice-view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Invoice Items", "page=accounts/ap/invoice-items.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Invoice Payments", "page=accounts/ap/invoice-payments.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Invoice Journal", "page=accounts/ap/journal.php&id=". $this->id ."", TRUE);

		if (user_permissions_get("accounts_ap_write"))
		{
			$this->obj_menu_nav->add_item("Delete Invoice", "page=accounts/ap/invoice-delete.php&id=". $this->id ."");
		}
	}



	function check_permissions()
	{
		return user_permissions_get("accounts_ap_view");
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
			Define the journal structure
		*/

		// basic
		$this->obj_journal		= New journal_display;
		$this->obj_journal->journalname	= "account_ap";
		
		// set the pages to use for forms or file downloads
		$this->obj_journal->prepare_set_form_process_page("accounts/ap/journal-edit.php");
		$this->obj_journal->prepare_set_download_page("accounts/ap/journal-download-process.php");


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
		// Title + Summapy
		print "<h3>INVOICE JOURNAL</h3><br>";
		print "<p>The journal is a place where you can put your own notes, files and view the history of this invoice.</p>";

		if (user_permissions_get("accounts_ap_write"))
		{
			print "<p><b><a href=\"index.php?page=accounts/ap/journal-edit.php&type=text&id=". $this->id ."\">Add new journal entry</a> || <a href=\"index.php?page=accounts/ap/journal-edit.php&type=file&id=". $this->id ."\">Upload File</a></b></p>";
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
