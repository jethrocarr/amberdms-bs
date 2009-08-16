<?php
/*
	timebooked.php
	
	access: staff_view

	Displays all the time booked to the selected employee
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

		$this->obj_menu_nav->add_item("Employee's Details", "page=hr/staff-view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Timesheet", "page=hr/staff-timebooked.php&id=". $this->id ."", TRUE);
		$this->obj_menu_nav->add_item("Employee's Journal", "page=hr/staff-journal.php&id=". $this->id ."");

		if (user_permissions_get("staff_write"))
		{
			$this->obj_menu_nav->add_item("Delete Employee", "page=hr/staff-delete.php&id=". $this->id ."");
		}

	}



	function check_permissions()
	{
		return user_permissions_get("staff_view");
	}



	function check_requirements()
	{
		// verify that employee exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM staff WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested employee (". $this->id .") does not exist - possibly the employee has been deleted.");
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
		$this->obj_table->tablename	= "staff_timesheet_table";

		// define all the columns and structure
		$this->obj_table->add_column("date", "date", "timereg.date");
		$this->obj_table->add_column("standard", "name_project", "projects.name_project");
		$this->obj_table->add_column("standard", "name_phase", "project_phases.name_phase");
		$this->obj_table->add_column("standard", "time_group", "time_groups.name_group");
		$this->obj_table->add_column("standard", "description", "timereg.description");
		$this->obj_table->add_column("hourmins", "time_booked", "timereg.time_booked");

		// defaults
		$this->obj_table->columns		= array("date", "name_project", "name_phase", "time_group", "description", "time_booked");
		$this->obj_table->columns_order		= array("date", "name_project");
		$this->obj_table->columns_order_options	= array("date", "name_project", "name_phase", "time_group", "description", "time_booked");

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("timereg");
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "timereg.id");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN time_groups ON timereg.groupid = time_groups.id");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN project_phases ON timereg.phaseid = project_phases.id");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN projects ON project_phases.projectid = projects.id");
		$this->obj_table->sql_obj->prepare_sql_addwhere("timereg.employeeid = '". $this->id ."'");
		
		
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
		
		$structure = NULL;
		$structure["fieldname"]	= "no_group";
		$structure["type"]	= "checkbox";
		$structure["sql"]	= "groupid='0'";
		$structure["options"]["label"] = "Only show unprocessed time";
		$this->obj_table->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] = "searchbox";
		$structure["type"]	= "input";
		$structure["sql"]	= "timereg.description LIKE '%value%' OR projects.name_project LIKE '%value%' OR project_phases.name_phase LIKE '%value%'";
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
		print "<h3>EMPLOYEE TIMESHEET</h3>";
		print "<p>This page shows all the time that has been booked by this employee.</p>";


		// display options form
		$this->obj_table->render_options_form();

		// Display table data
		if (!$this->obj_table->data_num_rows)
		{
			format_msgbox("info", "<p>There is currently no time booked by this employee that matches your filter options.</p>");
		}
		else
		{
			if (user_permissions_get("timekeeping"))
			{
				$structure = NULL;
				$structure["editid"]["column"]		= "id";
				$structure["date"]["column"]		= "date";
				$structure["employeeid"]["value"]	= $this->id;
				$this->obj_table->add_link("view/edit", "timekeeping/timereg-day.php", $structure);
			}

			$this->obj_table->render_table_html();


			// display CSV download link
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=csv&page=hr/staff-timebooked.php&id=". $this->id ."\">Export as CSV</a></p>";
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=pdf&page=hr/staff-timebooked.php&id=". $this->id ."\">Export as PDF</a></p>";
		}
	}


	function render_csv()
	{
		$this->obj_table->render_table_csv();
	}
	
}

?>
