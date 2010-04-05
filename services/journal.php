<?php
/*
	services/journal.php
	
	access: services_view (read-only)
		services_write (write access)

	Standard journal for service records and audit trail.
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

		$this->obj_menu_nav->add_item("Service Details", "page=services/view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Service Plan", "page=services/plan.php&id=". $this->id ."");

		if (sql_get_singlevalue("SELECT service_types.name as value FROM services LEFT JOIN service_types ON service_types.id = services.typeid WHERE services.id='". $this->id ."' LIMIT 1") == "bundle")
		{
			$this->obj_menu_nav->add_item("Bundle Components", "page=services/bundles.php&id=". $this->id ."");
		}

		if (sql_get_singlevalue("SELECT service_types.name as value FROM services LEFT JOIN service_types ON service_types.id = services.typeid WHERE services.id='". $this->id ."' LIMIT 1") == ("phone_single" || "phone_tollfree" || "phone_trunk"))
		{
			$this->obj_menu_nav->add_item("Call Rate Override", "page=services/cdr-override.php&id=". $this->id ."");
		}

		$this->obj_menu_nav->add_item("Service Journal", "page=services/journal.php&id=". $this->id ."", TRUE);

		if (user_permissions_get("services_write"))
		{
			$this->obj_menu_nav->add_item("Delete Service", "page=services/delete.php&id=". $this->id ."");
		}
	}


	function check_permissions()
	{
		return user_permissions_get("services_view");
	}


	function check_requirements()
	{
		// verify that the service exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM services WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested service (". $this->id .") does not exist - possibly the service has been deleted.");
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
		$this->obj_journal->journalname	= "services";
		
		// set the pages to use for forms or file downloads
		$this->obj_journal->prepare_set_form_process_page("services/journal-edit.php");
		$this->obj_journal->prepare_set_download_page("services/journal-download-process.php");
		
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
		// Title + Summary
		print "<h3>SERVICE JOURNAL</h3><br>";
		print "<p>The journal is a place where you can put your own notes, files and view the history of this service.</p>";

		if (user_permissions_get("services_write"))
		{
			print "<p><a class=\"button\" href=\"index.php?page=services/journal-edit.php&type=text&id=". $this->id ."\">Add new journal entry</a> <a class=\"button\" href=\"index.php?page=services/journal-edit.php&type=file&id=". $this->id ."\">Upload File</a></p>";
		}

	
		// display options form
		$this->obj_journal->render_options_form();

		// display journal
		$this->obj_journal->render_journal();
	}

}

?>
