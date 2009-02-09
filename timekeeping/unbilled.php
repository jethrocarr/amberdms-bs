<?php
/*
	unbilled.php

	access: projects_timegroup

	Displays all time which is current unprocessed.
*/


class page_output
{
	var $id;
	var $name_project;
	
	var $obj_menu_nav;
	var $obj_table;


	function check_permissions()
	{
		return user_permissions_get("projects_timegroup");
	}



	function check_requirements()
	{
		// do nothing
		return 1;
	}



	function execute()
	{
		// establish a new table object
		$this->obj_table = New table;

		$this->obj_table->language	= $_SESSION["user"]["lang"];
		$this->obj_table->tablename	= "timereg_unbilled";

		// define all the columns and structure
		$this->obj_table->add_column("date", "date", "timereg.date");
		$this->obj_table->add_column("standard", "name_phase", "CONCAT_WS(' -- ', projects.name_project, project_phases.name_phase)");
		$this->obj_table->add_column("standard", "name_staff", "staff.name_staff");
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
		$this->obj_table->sql_obj->prepare_sql_addfield("projectid", "projects.id");
		$this->obj_table->sql_obj->prepare_sql_addfield("employeeid", "timereg.employeeid");
		$this->obj_table->sql_obj->prepare_sql_addfield("timegroupid", "time_groups.id");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN staff ON timereg.employeeid = staff.id");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN time_groups ON timereg.groupid = time_groups.id");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN project_phases ON timereg.phaseid = project_phases.id");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN projects ON project_phases.projectid = projects.id");
		$this->obj_table->sql_obj->prepare_sql_addwhere("(time_groups.invoiceid='' || time_groups.invoiceid='0')");
		
		
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
		
		$structure = form_helper_prepare_dropdownfromdb("phaseid", "SELECT projects.name_project as label,
													project_phases.id as id, 
													project_phases.name_phase as label1
													FROM `projects` 
													LEFT JOIN project_phases ON project_phases.projectid = projects.id
													ORDER BY projects.name_project, project_phases.name_phase");
													
		$structure["sql"]	= "project_phases.id='value'";
		$this->obj_table->add_filter($structure);

		$structure		= form_helper_prepare_dropdownfromdb("employeeid", "SELECT id, name_staff as label FROM staff ORDER BY name_staff ASC");
		$structure["sql"]	= "timereg.employeeid='value'";
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
		print "<h3>UNBILLED TIME</h3>";
		print "<p>This page shows all time which has not yet been added to an invoice.</p>";


		// display options form
		$this->obj_table->render_options_form();

		// Display table data
		if (!$this->obj_table->data_num_rows)
		{
			format_msgbox("info", "<p>There is currently no unbilled time matching your search filter options.</p>");
		}
		else
		{
			// time entry link
			$structure = NULL;
			$structure["id"]["column"]		= "id";
			$structure["date"]["column"]		= "date";
			$structure["employeeid"]["column"]	= "employeeid";
			$this->obj_table->add_link("tbl_lnk_view_timeentry", "timekeeping/timereg-day-edit.php", $structure);

			// project/phase ID
			$structure = NULL;
			$structure["id"]["column"]		= "projectid";
			$structure["column"]			= "name_phase";
			$this->obj_table->add_link("tbl_lnk_project", "projects/timebooked.php", $structure);

			// project/phase ID
			$structure = NULL;
			$structure["id"]["column"]		= "projectid";
			$structure["groupid"]["column"]		= "timegroupid";
			$structure["column"]			= "time_group";
			$this->obj_table->add_link("tbl_lnk_groupid", "projects/timebooked.php", $structure);



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
