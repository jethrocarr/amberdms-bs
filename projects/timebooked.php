<?php
/*
	timebooked.php
	
	access: "projects_view" group members

	Displays all the time recorded against the selected project and all it's phases.
*/


class page_output
{
	var $id;
	var $name_project;
	
	var $obj_menu_nav;
	var $obj_table;


	function page_output()
	{
		// fetch variables
		$this->id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Project Details", "page=projects/view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Project Phases", "page=projects/phases.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Timebooked", "page=projects/timebooked.php&id=". $this->id ."", TRUE);
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
		$sql_obj->string	= "SELECT id, name_project FROM projects WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested project (". $this->id .") does not exist - possibly the project has been deleted.");
			return 0;
		}
		else
		{
			$sql_obj->fetch_array();

			$this->name_project = $sql_obj->data[0]["name_project"];
		}

		unset($sql_obj);


		return 1;
	}



	function execute()
	{
		// establish a new table object
		$this->obj_table = New table;

		$this->obj_table->language	= $_SESSION["user"]["lang"];
		$this->obj_table->tablename	= "timereg_table";

		// define all the columns and structure
		$this->obj_table->add_column("date", "date", "timereg.date");
		$this->obj_table->add_column("standard", "name_phase", "project_phases.name_phase");
		$this->obj_table->add_column("standard", "name_staff", "staff.name_staff");
		$this->obj_table->add_column("standard", "time_group", "time_groups.name_group");
		$this->obj_table->add_column("standard", "description", "timereg.description");
		$this->obj_table->add_column("hourmins", "time_booked", "timereg.time_booked");

		// defaults
		$this->obj_table->columns		= array("date", "name_phase", "name_staff", "time_group", "description", "time_booked");
		$this->obj_table->columns_order	= array("date", "name_phase");

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("timereg");
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "timereg.id");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN staff ON timereg.employeeid = staff.id");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN time_groups ON timereg.groupid = time_groups.id");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN project_phases ON timereg.phaseid = project_phases.id");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN projects ON project_phases.projectid = projects.id");
		$this->obj_table->sql_obj->prepare_sql_addwhere("projects.id = '". $this->id ."'");
		
		
		/// Filtering/Display Options

		// fixed options
		$this->obj_table->add_fixed_option("id", $this->id);


		// acceptable filter options
		$structure = NULL;
		$structure["fieldname"] = "date_start";
		$structure["type"]	= "date";
		$structure["sql"]	= "date >= 'value'";
		$this->obj_table->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] = "date_end";
		$structure["type"]	= "date";
		$structure["sql"]	= "date <= 'value'";
		$this->obj_table->add_filter($structure);
		
		$structure		= form_helper_prepare_dropdownfromdb("phaseid", "SELECT id, name_phase as label FROM project_phases WHERE projectid='". $this->id ."' ORDER BY name_phase ASC");
		$structure["sql"]	= "project_phases.id='value'";
		$this->obj_table->add_filter($structure);

		$structure		= form_helper_prepare_dropdownfromdb("employeeid", "SELECT id, name_staff as label FROM staff ORDER BY name_staff ASC");
		$structure["sql"]	= "timereg.employeeid='value'";
		$this->obj_table->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"]	= "no_group";
		$structure["type"]	= "checkbox";
		$structure["sql"]	= "groupid='0'";
		$structure["options"]["label"] = "Only show unprocessed time";
		$this->obj_table->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] = "searchbox";
		$structure["type"]	= "input";
		$structure["sql"]	= "timereg.description LIKE '%value%' OR project_phases.name_phase LIKE '%value%' OR staff.name_staff LIKE '%value%'";
		$this->obj_table->add_filter($structure);



		// create totals
		$this->obj_table->total_columns	= array("time_booked");


		// load options form
		$this->obj_table->load_options_form();

		
		// generate & execute SQL query			
		$this->obj_table->generate_sql();
		$this->obj_table->load_data_sql();

	}

	function render_html()
	{
		// heading
		print "<h3>TIME BOOKED TO PROJECT</h3>";
		print "<p>This page shows all the time that has been booked to the ". $this->name_project ." project.</p>";


		// display options form
		$this->obj_table->render_options_form();

		// Display table data
		if (!$this->obj_table->data_num_rows)
		{
			print "<p><b>There is currently no time registered to this project that matches your filter options.</b></p>";
		}
		else
		{
			$structure = NULL;
			$structure["editid"]["column"]	= "id";
			$structure["date"]["column"]	= "date";
			$this->obj_table->add_link("view/edit", "timekeeping/timereg-day.php", $structure);

			$this->obj_table->render_table_html();


			// display CSV download link
			print "<p align=\"right\"><a href=\"index-export.php?mode=csv&page=projects/timebooked.php&id=". $this->id ."\">Export as CSV</a></p>";
		}
	}


	function render_csv()
	{
		$this->obj_table->render_table_csv();
	}
	
}

?>
