<?php
/*
	products/journal_edit.php
	
	access: products_write

	Allows the addition or adjustment of journal entries.
*/



class page_output
{
	var $id;
	var $journalid;
	var $action;
	var $type;

	var $obj_form_journal;
	
	
	function __construct()
	{
		$this->id		= @security_script_input('/^[0-9]*$/', $_GET["id"]);
		$this->journalid	= @security_script_input('/^[0-9]*$/', $_GET["journalid"]);
		$this->action		= @security_script_input('/^[a-z]*$/', $_GET["action"]);
		$this->type		= @security_script_input('/^[a-z]*$/', $_GET["type"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Product Details", "page=products/view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Product Journal", "page=products/journal.php&id=". $this->id ."", TRUE);
		$this->obj_menu_nav->add_item("Delete Product", "page=products/delete.php&id=". $this->id ."");
	}


	function check_permissions()
	{
		return user_permissions_get("products_write");
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
			Journal Forms
		*/

		$this->obj_form_journal = New journal_input;
			
		// basic details of this entry
		$this->obj_form_journal->prepare_set_journalname("products");
		$this->obj_form_journal->prepare_set_journalid($this->journalid);
		$this->obj_form_journal->prepare_set_customid($this->id);

		// set the processing form
		$this->obj_form_journal->prepare_set_form_process_page("products/journal-edit-process.php");
	}


	function render_html()
	{
	
		if ($this->action == "delete")
		{
			print "<h3>PRODUCT JOURNAL - DELETE ENTRY</h3><br>";
			print "<p>This page allows you to delete an entry from the product's journal.</p>";

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
					print "<h3>PRODUCT JOURNAL - UPLOAD FILE</h3><br>";
					print "<p>This page allows you to attach a file to the product's journal.</p>";
				}
				else
				{
					print "<h3>PRODUCT JOURNAL - UPLOAD FILE</h3><br>";
					print "<p>This page allows you to attach a file to the product's journal.</p>";
				}

				// edit or add file
				$this->obj_form_journal->render_file_form();
			}
			else
			{
				// default to text
				if ($this->journalid)
				{
					print "<h3>PRODUCT JOURNAL - EDIT ENTRY</h3><br>";
					print "<p>This page allows you to edit an existing entry in the product's journal.</p>";
				}
				else
				{
					print "<h3>PRODUCT JOURNAL - ADD ENTRY</h3><br>";
					print "<p>This page allows you to add an entry to the product's journal.</p>";
				}

				// edit or add
				$this->obj_form_journal->render_text_form();		
			}
			
		}
	}
}

?>
