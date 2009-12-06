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

	var $access_staff_ids;


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
		if (user_permissions_get("projects_view"))
		{
			// accept user if they have access to all staff
			if (user_permissions_get("timekeeping_all_view"))
			{
				return 1;
			}

			// select the IDs that the user does have access to
			if ($this->access_staff_ids = user_permissions_staff_getarray("timereg_view"))
			{
				return 1;
			}
			else
			{
				log_render("error", "page", "Before you can view project hours, your administrator must configure the staff accounts you may access, or set the timekeeping_all_view permission.");
			}
		}
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
		$this->obj_table->add_column("standard", "name_staff", "CONCAT_WS(' -- ', staff.staff_code, staff.name_staff)");
		$this->obj_table->add_column("standard", "time_group", "time_groups.name_group");
		$this->obj_table->add_column("standard", "description", "timereg.description");
		$this->obj_table->add_column("hourmins", "time_booked", "timereg.time_booked");

		// defaults
		$this->obj_table->columns		= array("date", "name_phase", "name_staff", "time_group", "description", "time_booked");
		$this->obj_table->columns_order		= array("date", "name_phase");
		$this->obj_table->columns_order_options	= array("date", "name_phase", "name_staff", "time_group", "description", "time_booked");

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("timereg");
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "timereg.id");
		$this->obj_table->sql_obj->prepare_sql_addfield("employeeid", "timereg.employeeid");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN staff ON timereg.employeeid = staff.id");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN time_groups ON timereg.groupid = time_groups.id");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN project_phases ON timereg.phaseid = project_phases.id");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN projects ON project_phases.projectid = projects.id");
		$this->obj_table->sql_obj->prepare_sql_addwhere("projects.id = '". $this->id ."'");
		
		if ($this->access_staff_ids)
		{
			$this->obj_table->sql_obj->prepare_sql_addwhere("timereg.employeeid IN (". format_arraytocommastring($this->access_staff_ids) .")");
		}
		
		
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


		$sql_obj = New sql_query;
		$sql_obj->prepare_sql_settable("staff");
		$sql_obj->prepare_sql_addfield("id", "id");
		$sql_obj->prepare_sql_addfield("label", "staff_code");
		$sql_obj->prepare_sql_addfield("label1", "name_staff");
		
		if ($this->access_staff_ids)
		{
			$sql_obj->prepare_sql_addwhere("id IN (". format_arraytocommastring($this->access_staff_ids) .")");
		}

		$sql_obj->generate_sql();

		$structure		= form_helper_prepare_dropdownfromdb("employeeid", $sql_obj->string);
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
		$structure["sql"]	= "(timereg.description LIKE '%value%' OR project_phases.name_phase LIKE '%value%' OR staff.name_staff LIKE '%value%')";
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


		// display notice about limited access if suitable
		if ($this->access_staff_ids)
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM staff";
			$sql_obj->execute();
			$sql_obj->num_rows();
			
			if (count($this->access_staff_ids) != $sql_obj->num_rows())
			{
				format_msgbox("info", "<p>Please note that the following list of hours only includes the specific users whom you have been configured to view - to view all employees, ask your admin to enable the timekeeping_all_view permission.</p>");
				print "<br>";
			}
		}


		// Display table data
		if (!$this->obj_table->data_num_rows)
		{
			format_msgbox("info", "<p>There is currently no time registered to this project that matches your filter options.</p>");
		}
		else
		{
			$structure = NULL;
			$structure["editid"]["column"]		= "id";
			$structure["date"]["column"]		= "date";
			$structure["employeeid"]["column"]	= "employeeid";
			$this->obj_table->add_link("view/edit", "timekeeping/timereg-day.php", $structure);

			$this->obj_table->render_table_html();


			// display CSV/PDF download link
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=csv&page=projects/timebooked.php&id=". $this->id ."\">Export as CSV</a></p>";
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=pdf&page=projects/timebooked.php&id=". $this->id ."\">Export as PDF</a></p>";
		}
	}


	function render_csv()
	{
		$this->obj_table->render_table_csv();
	}
	
}

?>
