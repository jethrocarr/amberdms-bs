<?php
/*
	customers/journal_edit.php
	
	access: customers_write

	Allows the addition or adjustment of journal entries.
*/


class page_output
{
	var $id;
	var $journalid;
	var $action;
	var $type;
	
	var $obj_menu_nav;
	var $obj_form;


	function page_output()
	{
		// fetch variables
		$this->id		= security_script_input('/^[0-9]*$/', $_GET["id"]);
		$this->journalid	= security_script_input('/^[0-9]*$/', $_GET["journalid"]);
		$this->action		= security_script_input('/^[a-z]*$/', $_GET["action"]);
		$this->type		= security_script_input('/^[a-z]*$/', $_GET["type"]);
	
	
		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Customer's Details", "page=customers/view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Customer's Journal", "page=customers/journal.php&id=". $this->id ."", TRUE);
		$this->obj_menu_nav->add_item("Customer's Invoices", "page=customers/invoices.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Customer's Services", "page=customers/services.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Delete Customer", "page=customers/delete.php&id=". $this->id ."");
	}


	function check_permissions()
	{
		return user_permissions_get("customers_write");
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
			Configure journal form basics
		*/

		$this->obj_form = New journal_input;
			
		// basic details of this entry
		$this->obj_form->prepare_set_journalname("customers");
		$this->obj_form->prepare_set_journalid($this->journalid);
		$this->obj_form->prepare_set_customid($this->id);

		// set the processing form
		$this->obj_form->prepare_set_form_process_page("customers/journal-edit-process.php");
	}



	function render_html()
	{
		if ($this->action == "delete")
		{
			print "<h3>CUSTOMER JOURNAL - DELETE ENTRY</h3><br>";
			print "<p>This page allows you to delete an entry from the customer's journal.</p>";

			// render delete form
			$this->obj_form->render_delete_form();		

		}
		else
		{
			if ($this->type == "file")
			{
				// file uploader
				if ($this->journalid)
				{
					print "<h3>CUSTOMER JOURNAL - UPLOAD FILE</h3><br>";
					print "<p>This page allows you to attach a file to the customer's journal.</p>";
				}
				else
				{
					print "<h3>CUSTOMER JOURNAL - UPLOAD FILE</h3><br>";
					print "<p>This page allows you to attach a file to the customer's journal.</p>";
				}

				// edit or add file
				$this->obj_form->render_file_form();
			}
			else
			{
				// default to text
				if ($this->journalid)
				{
					print "<h3>CUSTOMER JOURNAL - EDIT ENTRY</h3><br>";
					print "<p>This page allows you to edit an existing entry in the customer's journal.</p>";
				}
				else
				{
					print "<h3>CUSTOMER JOURNAL - ADD ENTRY</h3><br>";
					print "<p>This page allows you to add an entry to the customer's journal.</p>";
				}

				// edit or add
				$this->obj_form->render_text_form();		
			}
			
		}
		
	}



} // end of page_output

?>
