<?php
/*
	customers/journal.php
	
	access: customers_view (read-only)
		customers_write (write access)

	Standard journal for customer records and audit trail.
*/



class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_journal;


	function page_output()
	{
		// fetch variables
		$this->id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Customer's Details", "page=customers/view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Customer's Journal", "page=customers/journal.php&id=". $this->id ."", TRUE);
		$this->obj_menu_nav->add_item("Customer's Invoices", "page=customers/invoices.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Customer's Services", "page=customers/services.php&id=". $this->id ."");

		if (user_permissions_get("customers_write"))
		{
			$this->obj_menu_nav->add_item("Delete Customer", "page=customers/delete.php&id=". $this->id ."");
		}
	}



	function check_permissions()
	{
		return user_permissions_get("customers_view");
	}



	function check_requirements()
	{
		// verify that customer exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM customers WHERE id='". $this->id ."'";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested customer (". $this->id .") does not exist - possibly the customer has been deleted.");
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
		$this->obj_journal->journalname	= "customers";
		
		// set the pages to use for forms or file downloads
		$this->obj_journal->prepare_set_form_process_page("customers/journal-edit.php");
		$this->obj_journal->prepare_set_download_page("customers/journal-download-process.php");
		
		// configure options form
		$this->obj_journal->prepare_predefined_optionform();
		$this->obj_journal->add_fixed_option("id", $this->id);

		// load options form
		$this->obj_journal->load_options_form();

		// define SQL structure
		$this->obj_journal->sql_obj->prepare_sql_addwhere("customid='". $this->id ."'");		// we only want journal entries for this ticket!

		// process SQL			
		$this->obj_journal->generate_sql();
		$this->obj_journal->load_data();
	}


	function render_html()
	{
		// display header
		print "<h3>CUSTOMER JOURNAL</h3><br>";
		print "<p>The journal is a place where you can put your own notes, files and view the history of this customer account.</p>";

		if (user_permissions_get("customers_write"))
		{
			print "<p><b><a href=\"index.php?page=customers/journal-edit.php&type=text&id=". $this->id ."\">Add new journal entry</a> || <a href=\"index.php?page=customers/journal-edit.php&type=file&id=". $this->id ."\">Upload File</a></b></p>";
		}

		// display options form
		$this->obj_journal->render_options_form();
		
		// display journal
		$this->obj_journal->render_journal();
	}

} // end of page_output class
?>
