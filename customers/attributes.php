<?php
/*
	customers/attributes.php
	
	access: customers_view (read-only)
		customers_write (write access)

	Customer Attributes list
*/



class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_journal;


	function page_output()
	{
		$this->requires["css"][]		= "include/attributes/css/attributes.css";
		
		// fetch variables
		$this->id = @security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Customer's Details", "page=customers/view.php&id=". $this->id ."");

		if (sql_get_singlevalue("SELECT value FROM config WHERE name='MODULE_CUSTOMER_PORTAL' LIMIT 1") == "enabled")
		{
			$this->obj_menu_nav->add_item("Portal Options", "page=customers/portal.php&id=". $this->id ."");
		}

		$this->obj_menu_nav->add_item("Customer's Journal", "page=customers/journal.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Customer's Attributes", "page=customers/attributes.php&id=". $this->id ."", TRUE);
		$this->obj_menu_nav->add_item("Customer's Invoices", "page=customers/invoices.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Customer's Services", "page=customers/services.php&id=". $this->id ."");

		if (user_permissions_get("customers_write"))
		{
			$this->obj_menu_nav->add_item("Delete Customer", "page=customers/delete.php&id=". $this->id ."");
		}
		
		// init the form object
		$this->form_obj = New form_input;
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
		//$this->obj_journal		= New journal_display;
		//$this->obj_journal->journalname	= "customers";
		
		// set the pages to use for forms or file downloads
		//$this->obj_journal->prepare_set_form_process_page("customers/journal-edit.php");
		//$this->obj_journal->prepare_set_download_page("customers/journal-download-process.php");
		
		// configure options form
		//$this->obj_journal->prepare_predefined_optionform();
		//$this->obj_journal->add_fixed_option("id", $this->id);

		// load options form
		//$this->obj_journal->load_options_form();

		// define SQL structure
		//$this->obj_journal->sql_obj->prepare_sql_addwhere("customid='". $this->id ."'");		// we only want journal entries for this ticket!

		// process SQL			
		//$this->obj_journal->generate_sql();
		//$this->obj_journal->load_data();
	}



	function render_html()
	{
		// display header
		print "<h3>CUSTOMER ATTRIBUTES</h3><br>";
		print "<p>You can add customer attributes here</p>";

		if (user_permissions_get("customers_write"))
		{
			print "<div class='add_attribute_box'>";
			print "<h4>Add Record</h4>"; 
			$this->render_new_key_value_form();
			print "</div>";
			//print "<p><b><a class=\"button\" href=\"index.php?page=customers/journal-edit.php&type=text&id=". $this->id ."\">Add new journal entry</a> <a class=\"button\" href=\"index.php?page=customers/journal-edit.php&type=file&id=". $this->id ."\">Upload File</a></b></p>";
		}

//		// display options form
//		$this->obj_journal->render_options_form();
//		
//		// display journal
//		$this->obj_journal->render_journal();
	}

	
	
	/*
		render_new_key_value_form()

		Displays a form for creating a key/value pair
		
		If $this->structure["id"] has been defined, this form will be an edit form.

		Return codes:
		0	failure 
		1	success
	*/
	
	function render_new_key_value_form() {
		/*
			Define form structure
		*/
		$this->form_obj->formname = "new_key_value_pair";
		$this->form_obj->action = "attributes-process.php";
		$this->form_obj->method = "post";
		
		$structure = NULL;
		$structure["fieldname"] 	= "key";
		$structure["type"]		= "input";
		$structure["options"]["prelabel"] = "<label>"."Key:";
		$structure["options"]["label"] = "</label>";
		$this->form_obj->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "value";
		$structure["type"]		= "input";
		$structure["options"]["prelabel"] = "<label>"."Value:";
		$structure["options"]["label"] = "</label>";
		$this->form_obj->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "key_id";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= "";
		$this->form_obj->add_input($structure);	
		
		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Save";
		$this->form_obj->add_input($structure);
	
		print "<form enctype=\"multipart/form-data\" method=\"". $this->form_obj->method ."\" action=\"". $this->form_obj->action ."\" class=\"form_standard\">";
		$this->form_obj->render_field('key');
		$this->form_obj->render_field('value');
		$this->form_obj->render_field('key_id');
		$this->form_obj->render_field('submit');
		print "</form>";
		
		
	}
	
	
} // end of page_output class
?>
