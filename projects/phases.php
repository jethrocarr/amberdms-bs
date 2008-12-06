<?php
/*
	phases.php
	
	access: "projects_view" group members

	Displays a list of all the phases belonging to the selected project.
*/


class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_table;


	function page_output()
	{
		// fetch variables
		$this->id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Project Details", "page=projects/view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Project Phases", "page=projects/phases.php&id=". $this->id ."", TRUE);
		$this->obj_menu_nav->add_item("Timebooked", "page=projects/timebooked.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Timebilled/Grouped", "page=projects/timebilled.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Project Journal", "page=projects/journal.php&id=". $this->id ."");

		if (user_permissions_get("projects_write"))
		{
			$this->obj_menu_nav->add_item("Delete Project", "page=projects/delete.php&id=". $this->id ."");
		}
	}



	function check_permissions()
	{
		return user_permissions_get("projects_view");
	}



	function check_requirements()
	{
		// verify that project exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM projects WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested project (". $this->id .") does not exist - possibly the project has been deleted.");
			return 0;
		}

		unset($sql_obj);


		return 1;
	}


	function execute()
	{
		// establish a new table object
		$this->obj_table = New table;

		$this->obj_table->language	= $_SESSION["user"]["lang"];
		$this->obj_table->tablename	= "phase_list";

		// define all the columns and structure
		$this->obj_table->add_column("standard", "name_phase", "");
		$this->obj_table->add_column("standard", "description", "");

		// defaults
		$this->obj_table->columns		= array("name_phase", "description");
		$this->obj_table->columns_order	= array("name_phase");

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("project_phases");
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "");
		$this->obj_table->sql_obj->prepare_sql_addwhere("projectid = '". $this->id ."'");

		// run SQL query
		$this->obj_table->generate_sql();
		$this->obj_table->load_data_sql();
	}


	function render_html()
	{
		// heading
		print "<h3>PROJECT PHASES</h3>";
		print "<p>All projects need to have at least one project phase, which staff can then use to book time to. A typical usage example is to have different phases
		for different sections of work on the project - eg: \"design phase\", \"implementation phase\" and \"testing phase\".</p>";

		print "<p>You can check what time has been booked to the phases, by using the \"Timebooked\" button on the menu above.</p>";
		
			

		if (!$this->obj_table->data_num_rows)
		{
			print "<p><b>You currently have no phases belonging to this project. <a href=\"index.php?page=projects/phase-edit.php&projectid=". $this->id ."\">Click here to add a phase to your project</a>.</b></p>";
		}
		else
		{
			// edit link
			$structure = NULL;
			$structure["id"]["value"]		= $this->id;
			$structure["phaseid"]["column"]		= "id";
			$this->obj_table->add_link("edit", "projects/phase-edit.php", $structure);
			
			// delete link
			$structure = NULL;
			$structure["id"]["value"]		= $this->id;
			$structure["phaseid"]["column"]		= "id";
			$this->obj_table->add_link("delete", "projects/phase-delete.php", $structure);


			// display the table
			$this->obj_table->render_table_html();

			
			print "<p><b><a href=\"index.php?page=projects/phase-edit.php&id=". $this->id ."\">Click here to add a new phase to your project</a>.</b></p>";
		}
	}
}

?>
