<?php
/*
	products/journal.php
	
	access: products_view (read-only)
		products_write (write access)

	Standard journal for product records and audit trail.
*/


class page_output
{
	var $id;

	var $obj_journal;
	
	
	function page_output()
	{
		$this->id = @security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Product Details", "page=products/view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Product Journal", "page=products/journal.php&id=". $this->id ."", TRUE);

		if (user_permissions_get("products_write"))
		{
			$this->obj_menu_nav->add_item("Delete Product", "page=products/delete.php&id=". $this->id ."");
		}
	}


	function check_permissions()
	{
		return user_permissions_get("products_view");
	}
	

	function check_requirements()
	{
		// verify that the product exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM products WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested product (". $this->id .") does not exist - possibly the product has been deleted.");
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
		$this->obj_journal->journalname	= "products";
		
		// set the pages to use for forms or file downloads
		$this->obj_journal->prepare_set_form_process_page("products/journal-edit.php");
		$this->obj_journal->prepare_set_download_page("products/journal-download-process.php");
		
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
		// title + summary
		print "<h3>PRODUCT JOURNAL</h3><br>";
		print "<p>The journal is a place where you can put your own notes, files and view the history of this product.</p>";

		print "<p><a class=\"button\" href=\"index.php?page=products/journal-edit.php&type=text&id=". $this->id ."\">Add new journal entry</a> <a class=\"button\" href=\"index.php?page=products/journal-edit.php&type=file&id=". $this->id ."\">Upload File</a></p>";

		// render options form
		$this->obj_journal->render_options_form();
		
		// display journal
		$this->obj_journal->render_journal();

	}

}


?>
