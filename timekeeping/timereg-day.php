<?php
/*
	timekeeping/timereg-day.php
	
	access: time_keeping

	Displays all the time registered for the selected day.
*/


// custom includes
require("include/user/permissions_staff.php");



class page_output
{
	var $date;
	var $date_split;
	
	var $employeeid;

	var $obj_menu_nav;
	var $obj_form_employee;
	var $obj_table_day;

	var $config_timesheet_booktofuture;


	function page_output()
	{
		// get selected employee
		$this->employeeid	= security_script_input('/^[0-9]*$/', $_GET["employeeid"]);

		if ($this->employeeid)
		{
			// save to session vars
			$_SESSION["form"]["timereg"]["employeeid"] = $this->employeeid;
		}
		else
		{
			// load from session vars
			if ($_SESSION["form"]["timereg"]["employeeid"])
				$this->employeeid = $_SESSION["form"]["timereg"]["employeeid"];
		}


		// get selected date
		$this->date	= security_script_input('/^\S*$/', $_GET["date"]);

		if (!$this->date)
		{
			// try alternative input syntax
			$this->date = security_script_input_predefined("date", $_GET["date_yyyy"] ."-". $_GET["date_mm"] ."-". $_GET["date_dd"]);
		}

		if ($this->date)
		{
			// save to session vars
			$_SESSION["timereg"]["date"] = $this->date;
		}
		else
		{
			// load from session vars
			if ($_SESSION["timereg"]["date"])
				$this->date = $_SESSION["timereg"]["date"];
		}

		$this->date_split = split("-", $this->date);



		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Weekview", "page=timekeeping/timereg.php&year=". time_calculate_yearnum($this->date) ."&weekofyear=". time_calculate_weeknum($this->date) ."");
		$this->obj_menu_nav->add_item("Day View", "page=timekeeping/timereg-day.php&date=". $this->date ."", TRUE);
		
		
		// get future booking config option
		$this->config_timesheet_booktofuture	= sql_get_singlevalue("SELECT value FROM config WHERE name='TIMESHEET_BOOKTOFUTURE'");
	}



	function check_permissions()
	{
		return user_permissions_get("timekeeping");
	}



	function check_requirements()
	{
		// make sure the user actually has access to some employees - if not,
		// it means that they can not book or view time
		
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM `users_permissions_staff` WHERE userid='". $_SESSION["user"]["id"] ."'";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "Sorry, you are currently unable to book time - you need your administrator to configure you with staff access rights.");
			return 0;
		}

		unset($sql_obj);


		// check if user has permissions to view the selected employee
		if ($this->employeeid)
		{
			if (!user_permissions_staff_get("timereg_view", $this->employeeid))
			{
				log_write("error", "page_output", "Sorry, you do not have permissions to view the timesheet for the selected employee");
				return 0;
			}
		}

		// prevent access to a date in the future
		if ($this->config_timesheet_booktofuture == "disabled")
		{
			if (time_date_to_timestamp($this->date) > mktime())
			{
				log_write("error", "page_output", "You are unable to book time to days in future. If you wish to change this behaviour, adjust the TIMESHEET_BOOKTOFUTURE configuration option.");
				return 0;
			}
		}

		return 1;
	}



	function execute()
	{
		/*
			Employee Selection Form
		*/
		
		$this->obj_form_employee = New form_input;
		$this->obj_form_employee->formname = "timereg_employee";
		$this->obj_form_employee->language = $_SESSION["user"]["lang"];


		// employee selection box
		$sql_string = "SELECT "
				."staff.id as id, "
				."staff.staff_code as label, "
				."staff.name_staff as label1 "
				."FROM users_permissions_staff "
				."LEFT JOIN staff ON staff.id = users_permissions_staff.staffid "
				."WHERE users_permissions_staff.userid='". $_SESSION["user"]["id"] ."' "
				."GROUP BY users_permissions_staff.staffid "
				."ORDER BY staff.name_staff";

		$structure = form_helper_prepare_dropdownfromdb("employeeid", $sql_string);

		
		// if there is currently no employee set, and there is only one
		// employee in the selection box, automatically select it and update
		// the session variables.
		
		if (!$this->employeeid && count($structure["values"]) == 1)
		{
			$this->employeeid				= $structure["values"][0];
			$_SESSION["form"]["timereg"]["employeeid"]	= $structure["values"][0];
		}
		
		$structure["options"]["autoselect"]	= "on";
		$structure["options"]["width"]		= "600";
		$structure["defaultvalue"]		= $this->employeeid;
		$this->obj_form_employee->add_input($structure);

		
		// hidden values
		$structure = NULL;
		$structure["fieldname"]		= "page";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $_GET["page"];
		$this->obj_form_employee->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"]		= "date";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->date;
		$this->obj_form_employee->add_input($structure);
		
		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Display";
		$this->obj_form_employee->add_input($structure);



		if ($this->employeeid)
		{
		
			/*
				DRAW DAY TABLE

				We need to display a table showing all time booked for the currently
				selected day.
			*/

			// establish a new table object
			$this->obj_table_day = New table;

			$this->obj_table_day->language	= $_SESSION["user"]["lang"];
			$this->obj_table_day->tablename	= "timereg_table";

			// define all the columns and structure
			$this->obj_table_day->add_column("standard", "name_project", "CONCAT_WS(' -- ', projects.code_project, projects.name_project)");
			$this->obj_table_day->add_column("standard", "name_phase", "project_phases.name_phase");
			$this->obj_table_day->add_column("hourmins", "time_booked", "timereg.time_booked");
			$this->obj_table_day->add_column("standard", "description", "timereg.description");

			// defaults
			$this->obj_table_day->columns		= array("name_project", "name_phase", "description", "time_booked");
			$this->obj_table_day->columns_order	= array("name_project", "name_phase");

			// create totals
			$this->obj_table_day->total_columns	= array("time_booked");
		
			// define SQL
			$this->obj_table_day->sql_obj->prepare_sql_settable("timereg");
			$this->obj_table_day->sql_obj->prepare_sql_addfield("id", "timereg.id");
			$this->obj_table_day->sql_obj->prepare_sql_addjoin("LEFT JOIN project_phases ON timereg.phaseid = project_phases.id");
			$this->obj_table_day->sql_obj->prepare_sql_addjoin("LEFT JOIN projects ON project_phases.projectid = projects.id");
			$this->obj_table_day->sql_obj->prepare_sql_addwhere("timereg.employeeid = '". $this->employeeid ."'");
			$this->obj_table_day->sql_obj->prepare_sql_addwhere("timereg.date = '". $this->date ."'");
				
			// execute SQL statement	
			$this->obj_table_day->generate_sql();
			$this->obj_table_day->load_data_sql();
		}
	}



	function render_html()
	{
		// title + summary
		print "<h3>TIME REGISTRATION - ". date("l d F Y", mktime(0,0,0, $this->date_split[1], $this->date_split[2], $this->date_split[0])) ."</h3><br>";


		// links
		$date_previous	= mktime(0,0,0, $this->date_split[1], ($this->date_split[2] - 1), $this->date_split[0]);
		$date_previous	= date("Y-m-d", $date_previous);
		
		$date_next	= mktime(0,0,0, $this->date_split[1], ($this->date_split[2] + 1), $this->date_split[0]);
		$date_next	= date("Y-m-d", $date_next);

		print "<p><b>";
		print "&lt;&lt; <a href=\"index.php?page=timekeeping/timereg-day.php&date=$date_previous&employeeid=". $this->employeeid ."\">Previous Day</a>";

		if ($this->config_timesheet_booktofuture == "disabled")
		{
			if (time_date_to_timestamp($date_next) < mktime())
			{
				print " || <a href=\"index.php?page=timekeeping/timereg-day.php&date=$date_next&employeeid=". $this->employeeid ."\">Next Day</a> &gt;&gt;";
			}
		}
		else
		{
			print " || <a href=\"index.php?page=timekeeping/timereg-day.php&date=$date_next&employeeid=". $this->employeeid ."\">Next Day</a> &gt;&gt;";
		}

		print "</b></p><br>";


		// Employee selection form
		//
		// we use a custom form display method here, since the normal form
		// class will draw a fully styled form in a table.
		//

		if ($this->employeeid)
		{
			print "<table class=\"table_highlight\" width=\"100%\"><tr><td width=\"100%\">";
		}
		else
		{
			print "<table class=\"table_highlight_important\" width=\"100%\"><tr><td width=\"100%\">";
		}
		
		print "<form method=\"get\" action=\"index.php\">";
		print "<p><b>Select an employee to view:</b></p>";
		$this->obj_form_employee->render_field("employeeid");
		$this->obj_form_employee->render_field("date");
		$this->obj_form_employee->render_field("page");
		$this->obj_form_employee->render_field("submit");
		
		print "</form>";
		print "</td></tr></table><br>";



		if ($this->employeeid)
		{
			if (!$this->obj_table_day->data_num_rows)
			{
				format_msgbox("info", "<p><b>There is currently no time registered to this day.</b></p>");
			}
			else
			{
				if (user_permissions_staff_get("timereg_write", $this->employeeid))
				{
					// edit link
					$structure = NULL;
					$structure["id"]["column"]	= "id";
					$structure["date"]["value"]	= $this->date;
					$this->obj_table_day->add_link("edit", "timekeeping/timereg-day-edit.php", $structure);
				
					// delete link
					$structure = NULL;
					$structure["id"]["column"]	= "id";
					$structure["date"]["value"]	= $this->date;
					$structure["full_link"]		= "yes";
					$this->obj_table_day->add_link("delete", "timekeeping/timereg-day-delete-process.php", $structure);
				}
				

				// display table
				$this->obj_table_day->render_table_html();
			
				// display CSV download link
				print "<p align=\"right\"><a href=\"index-export.php?mode=csv&page=timekeeping/timereg-day.php\">Export as CSV</a></p>";
			}

			if (user_permissions_staff_get("timereg_write", $this->employeeid))
			{
				print "<p><b><a href=\"index.php?page=timekeeping/timereg-day-edit.php&date=". $this->date ."\">Add new time entry</a></b></p>";
			}
			else
			{
				print "<p><i>You have read-only access to this employee and therefore can not add any more time.</i></p>";
			}
		}

	}


	function render_csv()
	{
		if ($this->employeeid)
		{
			$this->obj_table_day->render_table_csv();
		}
	}

}


?>
