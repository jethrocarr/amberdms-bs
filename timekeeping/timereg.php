<?php
/*
	timekeeping/timereg.php

	access: "timekeeping" group members

	Displays registered hours, and provides links to pages
	to add/remove/edit the hours.

	Note that ISO-8601 numeric representation is used for
	time_dayofweek and time_weekofyear.
	
*/


class page_output
{
	var $employeeid;
	
	var $date_selected_year;
	var $date_selected_weekofyear;
	var $date_selected_daysofweek;
	var $date_selected_start;
	var $date_selected_end;
	
	var $obj_form_employee;
	var $obj_form_goto;

	var $obj_table_week;

	var $config_timesheet_booktofuture;

	var $access_staff_ids;


	function __construct()
	{
		// get selected employee
		$this->employeeid	= @security_script_input('/^[0-9]*$/', $_GET["employeeid"]);

		if ($this->employeeid)
		{
			// save to session vars
			$_SESSION["form"]["timereg"]["employeeid"] = $this->employeeid;
		}
		else
		{
			// load from session vars
			if (isset($_SESSION["form"]["timereg"]["employeeid"]) && ($_SESSION["form"]["timereg"]["employeeid"] != NULL))
				$this->employeeid = $_SESSION["form"]["timereg"]["employeeid"];
		}

		// get the chosen year + week
		$this->date_selected_year		= @security_script_input('/^[0-9]*$/', $_GET["year"]);
		$this->date_selected_weekofyear		= @security_script_input('/^[0-9]*$/', $_GET["weekofyear"]);
	
		if (!$this->date_selected_year)
		{
			if (!empty($_SESSION["timereg"]["year"]))
			{
				$this->date_selected_year = $_SESSION["timereg"]["year"];
			}
			else
			{
				$this->date_selected_year	= date("Y");
			}
		}
		
		if (!$this->date_selected_weekofyear)
		{
			if (!empty($_SESSION["timereg"]["weekofyear"]))
			{
				$this->date_selected_weekofyear = $_SESSION["timereg"]["weekofyear"];
			}
			else
			{
				$this->date_selected_weekofyear = time_calculate_weeknum();

			}
		}

		// save to session vars
		$_SESSION["timereg"]["year"]		= $this->date_selected_year;
		$_SESSION["timereg"]["weekofyear"]	= $this->date_selected_weekofyear;

		
		// get future booking config option
		$this->config_timesheet_booktofuture	= sql_get_singlevalue("SELECT value FROM config WHERE name='TIMESHEET_BOOKTOFUTURE'");
	}

	function check_permissions()
	{
		if (user_permissions_get("timekeeping"))
		{
			// check if user has permissions to view the selected employee
			if ($this->employeeid)
			{
				if (!user_permissions_staff_get("timereg_view", $this->employeeid))
				{
					log_write("error", "page_output", "Sorry, you do not have permissions to view the timesheet for the selected employee");

					// we unset the session variable, this prevents issues when the admin has disabled access to an employee
					// for a specific user, and the session keeping the older user number stuck in memory forces
					// the user to have to logout.
					$_SESSION["form"]["timereg"]["employeeid"] = 0;

					return 0;
				}
			}
		

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
		}
	}

	
	function check_requirements()
	{
		// nothing todo
		return 1;
	}


	function execute()
	{

		/*
			Process Date Options
		*/
			
		// get the start date of the week
		$this->date_selected_start		= time_calculate_weekstart($this->date_selected_weekofyear, $this->date_selected_year);

		// get the dates for each day of the week
		$this->date_selected_daysofweek		= time_calculate_daysofweek($this->date_selected_start);

		// get the end date of the week
		$this->date_selected_end		= $this->date_selected_daysofweek[6];


		/*
			Employee Selection Form
		*/
		
		$this->obj_form_employee = New form_input;
		$this->obj_form_employee->formname = "timereg_employee";
		$this->obj_form_employee->language = $_SESSION["user"]["lang"];


		// employee selection box
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

		$structure				= form_helper_prepare_dropdownfromdb("employeeid", $sql_obj->string);
		$structure["sql"]			= "timereg.employeeid='value'";
		$structure["options"]["autoselect"]	= "on";
		$structure["options"]["search_filter"]	= "yes";
		$this->obj_form_employee->add_input($structure);

		// if there is currently no employee set, and there is only one
		// employee in the selection box, automatically select it and update
		// the session variables.
		
		if (!$this->employeeid && count($structure["values"]) == 1)
		{
			$this->employeeid				= $structure["values"][0];
			$_SESSION["form"]["timereg"]["employeeid"]	= $structure["values"][0];
		}

		// if there is currently no employee set, and the user has configured
		// a default employeeid, automatically select that ID and update the
		// session variables
	
		if (!$this->employeeid && $_SESSION["user"]["default_employeeid"])
		{
			$this->employeeid				= $_SESSION["user"]["default_employeeid"];
			$_SESSION["form"]["timereg"]["employeeid"]	= $_SESSION["user"]["default_employeeid"];
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
		$structure["fieldname"]		= "weekofyear";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->date_selected_weekofyear;
		$this->obj_form_employee->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"]		= "year";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->date_selected_year;
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
				DEFINE WEEK TABLE
				
				We need to create a table showing all time booked for the currently
				selected week.

				1. Get a list of all project from the database that had time booked against
				   them this week.

				2. Fetch total time spent on each project, for each day.

				3. Display into a table, with easy edit + add links.
			*/


			// establish a new table object
			$this->obj_table_week = New table;

			$this->obj_table_week->language	= $_SESSION["user"]["lang"];
			$this->obj_table_week->tablename	= "timereg_list";

			// define all the columns and structure
			$this->obj_table_week->add_column("standard", "projectandphase", "");
			$this->obj_table_week->add_column("hourmins", "monday", "");
			$this->obj_table_week->add_column("hourmins", "tuesday",  "");
			$this->obj_table_week->add_column("hourmins", "wednesday",  "");
			$this->obj_table_week->add_column("hourmins", "thursday",  "");
			$this->obj_table_week->add_column("hourmins", "friday",  "");
			$this->obj_table_week->add_column("hourmins", "saturday",  "");
			$this->obj_table_week->add_column("hourmins", "sunday",  "");

			
			// defaults
			$this->obj_table_week->columns		= array("projectandphase", "monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday");

			// totals
			$this->obj_table_week->total_columns	= array("monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday");
			$this->obj_table_week->total_rows	= array("monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday");


			// define SQL structure
			$this->obj_table_week->sql_obj->prepare_sql_settable("timeregs");


			// map dates to day of the week
			$days[ $this->date_selected_daysofweek[0] ] = "monday";
			$days[ $this->date_selected_daysofweek[1] ] = "tuesday";
			$days[ $this->date_selected_daysofweek[2] ] = "wednesday";
			$days[ $this->date_selected_daysofweek[3] ] = "thursday";
			$days[ $this->date_selected_daysofweek[4] ] = "friday";
			$days[ $this->date_selected_daysofweek[5] ] = "saturday";
			$days[ $this->date_selected_daysofweek[6] ] = "sunday";

			/*
				Fetch the data
			
				This step is too complete to use the automatic SQL generation code in the tables class. What we need to do is:
				 1. Fetch each phase ID for the day
				 2. Create a combined project/phase name value
				 3. Total up all time spent on that project/phase for the day
				 4. Add the data to the $timereg->data[$rowid]["columnname"] structure.
				 5. Draw the table using render_table()
			*/

			$phasearray = array();
		
			// 1. Fetch each phase ID for the day

			// create the query
			$sql_obj = New sql_query;
			$sql_obj->string = "SELECT "
					."phaseid "
					."FROM `timereg` "
					."WHERE employeeid='". $this->employeeid ."' "
					."AND date >= '". $this->date_selected_start ."' "
					."AND date <= '". $this->date_selected_end ."'";

			// fetch the data
			log_debug("timereg", "Fetching all phase IDs for bookings in the week");		
			$sql_obj->execute();
			

			// process any results
			if ($sql_obj->num_rows())
			{
				$sql_obj->fetch_array();
				foreach ($sql_obj->data as $data)
				{
					// create an array of all the phase ids, without any duplicates
					if (!in_array($data["phaseid"], $phasearray))
					{
						$phasearray[] = $data["phaseid"];
					}
				}
			}
			
			$this->obj_table_week->data_num_rows = count($phasearray);
			


			// we have all the phases/projects that have been booked for the day (if any).
			// we now run through them...
			foreach ($phasearray as $phaseid)
			{
				$tmparray = NULL;
				
				
				// 2. Fetch the project and phase name values
				
				// create the query
				$sql_obj = New sql_query;
				$sql_obj->string = "SELECT "
						."project_phases.name_phase, "
						."projects.name_project, "
						."projects.code_project "
						."FROM project_phases "
						."LEFT JOIN projects ON project_phases.projectid = projects.id "
						."WHERE project_phases.id='$phaseid'";

				// fetch the data
				log_debug("timereg", "Fetching project and phase name values for phase $phaseid");
				$sql_obj->execute();
			

				// process the name data
				$sql_obj->fetch_array();
				foreach ($sql_obj->data as $data)
				{
					$tmparray["projectandphase"] = $data["code_project"] ." -- ". $data["name_project"] ." - ". $data["name_phase"];
				}



				// 3. Total up all time spent on that project/phase for the day

				// create the query
				$sql_obj = New sql_query;
				$sql_obj->string = "SELECT "
						."date, "
						."time_booked "
						."FROM timereg "
						."WHERE employeeid='". $this->employeeid ."' "
						."AND date >= '". $this->date_selected_start ."' "
						."AND date <= '". $this->date_selected_end ."' "
						."AND phaseid='$phaseid'";

				// fetch the data
				log_debug("timereg", "Fetching all hours for phase $phaseid");
				$sql_obj->execute();


				// process the data
				$sql_obj->fetch_array();
				foreach ($sql_obj->data as $data)
				{
					@$tmparray[ $days[ $data["date"] ] ] += $data["time_booked"];
				}


				// 4. Add the data to the table data structure to allow rendering
				$this->obj_table_week->data[] = $tmparray;
			}



		} // valid employee selected for viewing



		/*
			Date GOTO form
		*/

		$this->obj_form_goto		= New form_input;
		$this->obj_form_goto->formname	= "timereg_goto";
		$this->obj_form_goto->language	= $_SESSION["user"]["lang"];


		$structure = NULL;
		$structure["fieldname"]		= "date";
		$structure["type"]		= "date";
		$structure["defaultvalue"]	= date("Y-m-d");
		$this->obj_form_goto->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"]		= "page";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= "timekeeping/timereg-day.php";
		$this->obj_form_goto->add_input($structure);
		
		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Goto Date";
		$this->obj_form_goto->add_input($structure);


	} // end execute function


	function render_html()
	{
		// calcuate next/previous week/year
		if ($this->date_selected_weekofyear == 1)
		{
			$date_option_previousyear	= $this->date_selected_year - 1;
			$date_option_previousweek	= 52;

			$date_option_nextyear		= $this->date_selected_year;
			$date_option_nextweek		= 2;
		}
		elseif ($this->date_selected_weekofyear == 52)
		{
			$date_option_previousyear	= $this->date_selected_year;
			$date_option_previousweek	= 51;

			$date_option_nextyear		= $this->date_selected_year + 1;
			$date_option_nextweek		= 1;
		}
		else
		{
			$date_option_previousyear	= $this->date_selected_year;
			$date_option_previousweek	= $this->date_selected_weekofyear - 1;

			$date_option_nextyear		= $this->date_selected_year;
			$date_option_nextweek		= $this->date_selected_weekofyear + 1;
		}

	
		// Week view header
		
		print "<h3>TIME REGISTRATION</h3><br><br>";
		
		/*
			Unbilled Time
		*/
		if (user_permissions_get("projects_timegroup"))
		{
			/*
				Create an array of all unbilled time records. We need to do the following to create this list:
				1. Exclude any internal_only projects.
				2. Include time which belongs to a time_group, but ONLY if the time group has not been added to an invoice.
			*/

			$unbilled_ids = array();


			// select non-internal projects
			$sql_projects_obj		= New sql_query;
			$sql_projects_obj->string	= "SELECT projects.id as projectid, project_phases.id as phaseid FROM project_phases LEFT JOIN projects ON projects.id = project_phases.projectid WHERE projects.internal_only='0'";
			$sql_projects_obj->execute();

			if ($sql_projects_obj->num_rows())
			{
				$sql_projects_obj->fetch_array();

				foreach ($sql_projects_obj->data as $project_data)
				{
					// select non-group time records
					$sql_obj		= New sql_query;
					$sql_obj->string	= "SELECT id FROM timereg WHERE groupid='0' AND phaseid='". $project_data["phaseid"] ."'";
					$sql_obj->execute();

					if ($sql_obj->num_rows())
					{
						$sql_obj->fetch_array();

						foreach ($sql_obj->data as $data_tmp)
						{
							// we store the ID inside an array key, since they are unique
							// and this will prevent us needed to check for the existance of
							// the ID already.
							$unbilled_ids[ $data_tmp["id"] ] = "on";
						}
					}

					unset($sql_obj);


					// select unpaid group IDs
					$sql_obj		= New sql_query;
					$sql_obj->string	= "SELECT id FROM time_groups WHERE projectid='". $project_data["projectid"] ."' AND invoiceid='0'";
					$sql_obj->execute();

					if ($sql_obj->num_rows())
					{
						$sql_obj->fetch_array();

						foreach ($sql_obj->data as $data_group)
						{
							// fetch all the time reg IDs belonging this group, but only select time entries marked as billable - we
							// don't want to report a timegroup with unbillable time as being billed!
							$sql_reg_obj		= New sql_query;
							$sql_reg_obj->string	= "SELECT id FROM timereg WHERE groupid='". $data_group["id"] ."' AND billable='1'";
							$sql_reg_obj->execute();

							if ($sql_reg_obj->num_rows())
							{
								$sql_reg_obj->fetch_array();

								foreach ($sql_reg_obj->data as $data_tmp)
								{
									// we store the ID inside an array key, since they are unique
									// and this will prevent us needed to check for the existance of
									// the ID already.
									$unbilled_ids[ $data_tmp["id"] ] = "on";
								}
							}

							unset($sql_reg_obj);
						}
					}

					unset($sql_obj);
				}
			}
			// fetch amount of unbilled time
			$sql_obj = New sql_query;
			$sql_obj->prepare_sql_settable("timereg");
			$sql_obj->prepare_sql_addfield("timebooked", "SUM(timereg.time_booked)");
		
			if ($this->access_staff_ids)
			{
				$sql_obj->prepare_sql_addwhere("employeeid IN (". format_arraytocommastring($this->access_staff_ids) .")");
			}

			$sql_obj->prepare_sql_addjoin("LEFT JOIN time_groups ON timereg.groupid = time_groups.id");

			// provide list of valid IDs
			$unbilled_ids_keys	= array_keys($unbilled_ids);
			$unbilled_ids_count	= count($unbilled_ids_keys);
			$unbilled_ids_sql	= "";

			if ($unbilled_ids_count)
			{
				$i = 0;
				foreach ($unbilled_ids_keys as $id)
				{
					$i++;

					if ($i == $unbilled_ids_count)
					{
						$unbilled_ids_sql .= "timereg.id='$id' ";
					}
					else
					{
						$unbilled_ids_sql .= "timereg.id='$id' OR ";
					}
				}
						
				$sql_obj->prepare_sql_addwhere("($unbilled_ids_sql)");
				

				$sql_obj->generate_sql();

				$sql_obj->execute();
				$sql_obj->fetch_array();

				list($unbilled_time_hours, $unbilled_time_mins) = explode(":", time_format_hourmins($sql_obj->data[0]["timebooked"]));


				if ($unbilled_time_hours > 0 && $unbilled_time_mins > 0)
				{
					$message = "There are currently $unbilled_time_hours hours and $unbilled_time_mins minutes of unbilled time to be processed. Click here to view.";
				}
				elseif ($unbilled_time_hours > 0)
				{
					$message = "There are currently $unbilled_time_hours hours of unbilled time to be processed. Click here to view.";
				}
				elseif ($unbilled_time_mins > 0)
				{
					$message = "There are currently $unbilled_time_mins minutes of unbilled time to be processed. Click here to view.";
				}

			}
			else
			{
				$message = "There is no unbilled time to be processed.";
			}

			// display
			print "<br>";
			format_linkbox("default", "index.php?page=timekeeping/unbilled.php", "<p><b>UNBILLED TIME</b></p><p>$message</p>");
		
		}/*end unbilled time*/
		

		print "<br />";
		
				/*
		Time booked
		*/
		
		// fetch amount of time booked for today
		$sql_obj = New sql_query;
		$sql_obj->prepare_sql_settable("timereg");
		$sql_obj->prepare_sql_addfield("timebooked", "SUM(timereg.time_booked)");
		$sql_obj->prepare_sql_addwhere("date='". date("Y-m-d") ."'");

		if ($this->access_staff_ids)
		{
			$sql_obj->prepare_sql_addwhere("employeeid IN (". format_arraytocommastring($this->access_staff_ids) .")");
		}

		$sql_obj->generate_sql();

		$sql_obj->execute();
		$sql_obj->fetch_array();


		list($booked_time_hours, $booked_time_mins) = explode(":", time_format_hourmins($sql_obj->data[0]["timebooked"]));

		if ($booked_time_hours > 0 && $booked_time_mins > 0)
		{
			$message = "<b>Time booked for today: $booked_time_hours hours and $booked_time_mins minutes.</b><br />Click here to add more time.";
		}
		elseif ($booked_time_hours > 0)
		{
			$message = "<b>Time booked for today: $booked_time_hours hours.</b><br />Click here to add more time.";
		}
		elseif ($booked_time_mins > 0)
		{
			$message = "<b>Time booked for today: $booked_time_mins minutes.</b><br />Click here to add more time.";
		}
		else
		{
			$message = "<b>No time has been booked for today</b><br />Click here to add time.</b>";
		}
		
		format_linkbox("default", "index.php?page=timekeeping/timereg-day-edit.php","<p>$message</p>");
		
		print "<br />";
		
		print "<table class=\"table_highlight\" width=\"100%\"><tr>";
		
		// Week selection links
		print "<td width=\"70%\">";
		
		print "<b>WEEK ". $this->date_selected_weekofyear .", ". $this->date_selected_year ."</b><br>";
		print "(". time_format_humandate($this->date_selected_start) ." to ". time_format_humandate($this->date_selected_end) .")<br>";
		print "<br>";
	
		
		print "<p><b>";
		print "<a class=\"button\" href=\"index.php?page=timekeeping/timereg.php&employeeid=". $this->employeeid ."&weekofyear=". $date_option_previousweek ."&year=". $date_option_previousyear ."\">&lt;&lt; Previous Week</a>";

		// check for date in the future
		if ($this->config_timesheet_booktofuture == "disabled")
		{
			if (time_date_to_timestamp(time_calculate_weekstart($date_option_nextweek, $date_option_nextyear)) < time())
			{
				// end date is in not in the future
				print " <a class=\"button\" href=\"index.php?page=timekeeping/timereg.php&employeeid=". $this->employeeid ."&weekofyear=". $date_option_nextweek ."&year=". $date_option_nextyear ."\">Next Week &gt;&gt;</a>";
			}
		}
		else
		{
			print " <a class=\"button\" href=\"index.php?page=timekeeping/timereg.php&employeeid=". $this->employeeid ."&weekofyear=". $date_option_nextweek ."&year=". $date_option_nextyear ."\">Next Week &gt;&gt;</a>";
		}

		print "</b></p>";

		print "</td>";




		// goto date form
		print "<td width=\"30%\">";

			print "<form method=\"get\" action=\"index.php\" class=\"form_standard\">";


			$this->obj_form_goto->render_field("date");

			print "<br>";

			$this->obj_form_goto->render_field("page");
			$this->obj_form_goto->render_field("submit");


			print "</form>";

		print "</td>";
		

		print "</tr></table><br>";

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
		
		print "<form method=\"get\" action=\"index.php\" class=\"form_standard\">";
		print "<p><b>Select an employee to view:</b></p>";
		$this->obj_form_employee->render_field("employeeid");
		$this->obj_form_employee->render_field("weekofyear");
		$this->obj_form_employee->render_field("year");
		$this->obj_form_employee->render_field("page");
		$this->obj_form_employee->render_field("submit");
		
		print "</form>";

		print "</td></tr></table><br>";

		
		if ($this->employeeid)
		{
			// custom labels and links
			if ($this->config_timesheet_booktofuture == "disabled")
			{
				if (time_date_to_timestamp($this->date_selected_daysofweek[0]) < time())
					$this->obj_table_week->custom_column_link("monday", "index.php?page=timekeeping/timereg-day.php&date=". $this->date_selected_daysofweek[0] ."");

				if (time_date_to_timestamp($this->date_selected_daysofweek[1]) < time())
					$this->obj_table_week->custom_column_link("tuesday", "index.php?page=timekeeping/timereg-day.php&date=". $this->date_selected_daysofweek[1] ."");

				if (time_date_to_timestamp($this->date_selected_daysofweek[2]) < time())
					$this->obj_table_week->custom_column_link("wednesday", "index.php?page=timekeeping/timereg-day.php&date=". $this->date_selected_daysofweek[2] ."");
				
				if (time_date_to_timestamp($this->date_selected_daysofweek[3]) < time())
					$this->obj_table_week->custom_column_link("thursday", "index.php?page=timekeeping/timereg-day.php&date=". $this->date_selected_daysofweek[3] ."");
				
				if (time_date_to_timestamp($this->date_selected_daysofweek[4]) < time())
					$this->obj_table_week->custom_column_link("friday", "index.php?page=timekeeping/timereg-day.php&date=". $this->date_selected_daysofweek[4] ."");
				
				if (time_date_to_timestamp($this->date_selected_daysofweek[5]) < time())
					$this->obj_table_week->custom_column_link("saturday", "index.php?page=timekeeping/timereg-day.php&date=". $this->date_selected_daysofweek[5] ."");
				
				if (time_date_to_timestamp($this->date_selected_daysofweek[6]) < time())
					$this->obj_table_week->custom_column_link("sunday", "index.php?page=timekeeping/timereg-day.php&date=". $this->date_selected_daysofweek[6] ."");
			}
			else
			{
				// add links
				$this->obj_table_week->custom_column_link("monday", "index.php?page=timekeeping/timereg-day.php&date=". $this->date_selected_daysofweek[0] ."");
				$this->obj_table_week->custom_column_link("tuesday", "index.php?page=timekeeping/timereg-day.php&date=". $this->date_selected_daysofweek[1] ."");
				$this->obj_table_week->custom_column_link("wednesday", "index.php?page=timekeeping/timereg-day.php&date=". $this->date_selected_daysofweek[2] ."");
				$this->obj_table_week->custom_column_link("thursday", "index.php?page=timekeeping/timereg-day.php&date=". $this->date_selected_daysofweek[3] ."");
				$this->obj_table_week->custom_column_link("friday", "index.php?page=timekeeping/timereg-day.php&date=". $this->date_selected_daysofweek[4] ."");
				$this->obj_table_week->custom_column_link("saturday", "index.php?page=timekeeping/timereg-day.php&date=". $this->date_selected_daysofweek[5] ."");
				$this->obj_table_week->custom_column_link("sunday", "index.php?page=timekeeping/timereg-day.php&date=". $this->date_selected_daysofweek[6] ."");
			}
			
			
			// column labels
			$this->obj_table_week->custom_column_label("monday", "Monday<br><font style=\"font-size: 8px;\">(". time_format_humandate($this->date_selected_daysofweek[0]) .")</font>");
			$this->obj_table_week->custom_column_label("tuesday", "Tuesday<br><font style=\"font-size: 8px;\">(". time_format_humandate($this->date_selected_daysofweek[1]) .")</font>");
			$this->obj_table_week->custom_column_label("wednesday", "Wednesday<br><font style=\"font-size: 8px;\">(". time_format_humandate($this->date_selected_daysofweek[2]) .")</font>");
			$this->obj_table_week->custom_column_label("thursday", "Thursday<br><font style=\"font-size: 8px;\">(". time_format_humandate($this->date_selected_daysofweek[3]) .")</font>");
			$this->obj_table_week->custom_column_label("friday", "Friday<br><font style=\"font-size: 8px;\">(". time_format_humandate($this->date_selected_daysofweek[4]) .")</font>");
			$this->obj_table_week->custom_column_label("saturday", "Saturday<br><font style=\"font-size: 8px;\">(". time_format_humandate($this->date_selected_daysofweek[5]) .")</font>");
			$this->obj_table_week->custom_column_label("sunday", "Sunday<br><font style=\"font-size: 8px;\">(". time_format_humandate($this->date_selected_daysofweek[6]) .")</font>");

		
			// display week time table
			$this->obj_table_week->render_table_html();

			print "<table width=\"100%\">";

				// add time link
				if (user_permissions_staff_get("timereg_write", $this->employeeid))
				{
					print "<td align=\"left\" valign=\"top\"><p><a class=\"button\" href=\"index.php?page=timekeeping/timereg-day-edit.php\">Add new time entry</a></p></td>";
				}
				else
				{
					print "<p><i>You have read-only access to this employee and therefore can not add any more time.</i></p>";
				}

				// display CSV/PDF download link
				print "<td align=\"right\">";
				print "<p><a class=\"button_export\" href=\"index-export.php?mode=csv&page=timekeeping/timereg.php\">Export as CSV</a></p>";
				print "<p><a class=\"button_export\" href=\"index-export.php?mode=pdf&page=timekeeping/timereg.php\">Export as PDF</a></p>";
				print "</td>";

			print "</table>";
		}
	}


	function render_csv()
	{
		if ($this->employeeid)
		{
			// custom labels
			$this->obj_table_week->custom_column_label("monday", "Monday (". $this->date_selected_daysofweek[0] .")");
			$this->obj_table_week->custom_column_label("tuesday", "Tuesday (". $this->date_selected_daysofweek[1] .")");
			$this->obj_table_week->custom_column_label("wednesday", "Wednesday (". $this->date_selected_daysofweek[2] .")");
			$this->obj_table_week->custom_column_label("thursday", "Thursday (". $this->date_selected_daysofweek[3] .")");
			$this->obj_table_week->custom_column_label("friday", "Friday (". $this->date_selected_daysofweek[4] .")");
			$this->obj_table_week->custom_column_label("saturday", "Saturday (". $this->date_selected_daysofweek[5] .")");
			$this->obj_table_week->custom_column_label("sunday", "Sunday (". $this->date_selected_daysofweek[6] .")");

			// display week time table
			$this->obj_table_week->render_table_csv();
		}
	}


	function render_pdf()
	{
		if ($this->employeeid)
		{
			// custom labels
			$this->obj_table_week->custom_column_label("monday", "Monday (". $this->date_selected_daysofweek[0] .")");
			$this->obj_table_week->custom_column_label("tuesday", "Tuesday (". $this->date_selected_daysofweek[1] .")");
			$this->obj_table_week->custom_column_label("wednesday", "Wednesday (". $this->date_selected_daysofweek[2] .")");
			$this->obj_table_week->custom_column_label("thursday", "Thursday (". $this->date_selected_daysofweek[3] .")");
			$this->obj_table_week->custom_column_label("friday", "Friday (". $this->date_selected_daysofweek[4] .")");
			$this->obj_table_week->custom_column_label("saturday", "Saturday (". $this->date_selected_daysofweek[5] .")");
			$this->obj_table_week->custom_column_label("sunday", "Sunday (". $this->date_selected_daysofweek[6] .")");

			// display week time table
			$this->obj_table_week->render_table_pdf("amberphplib_table_landscape.tex");
		}
	}

}

?>
