<?php
/*
	timekeeping/timereg.php

	access: "timekeeping" group members

	Displays registered hours, and provides links to pages
	to add/remove/edit the hours.

	Note that ISO-8601 numeric representation is used for
	time_dayofweek and time_weekofyear.
	
*/

// custom includes
include("include/user/permissions_staff.php");


if (user_permissions_get('timekeeping'))
{
	function page_render()
	{
		// get selected employee
		$employeeid	= security_script_input('/^[0-9]*$/', $_GET["employeeid"]);

		if ($employeeid)
		{
			$_SESSION["form"][$this->tablename]["employeeid"] = $employeeid;
		}
		else
		{
			if ($_SESSION["form"][$this->tablename]["employeeid"])
				$employeeid = $_SESSION["form"][$this->tablename]["employeeid"];
		}
		

		/*
			Process Date Options
		*/
	
		// get the chosen year + week
		$date_selected_year		= security_script_input('/^[0-9]*$/', $_GET["year"]);
		$date_selected_weekofyear	= security_script_input('/^[0-9]*$/', $_GET["weekofyear"]);

		if (!$date_selected_year)
			$date_selected_year		= date("Y");

		if (!$date_selected_weekofyear)
			$date_selected_weekofyear	= date("W");

		// save to session vars
		$_SESSION["timereg"]["year"]		= $date_selected_year;
		$_SESSION["timereg"]["weekofyear"]	= $date_selected_weekofyear;
			

		// get the start date of the week
		$date_selected_start		= time_calculate_weekstart($date_selected_weekofyear, $date_selected_year);

		// get the dates for each day of the week
		$date_selected_daysofweek	= time_calculate_daysofweek($date_selected_start);

		// get the end date of the week
		$date_selected_end	= $date_selected_daysofweek[6];



		/*
			Week view header
		*/

		// make sure the user actually has access to any employees
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM `users_permissions_staff` WHERE userid='". $_SESSION["user"]["id"] ."'";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			// TODO: have nicer error message here? perhaps link to documentation?
			print "<p><b>Sorry, you are currently unable to book time - you need your administrator to configure you with staff access rights.</b></p>";
		}
		else
		{
			/// PAGE HEADING

			print "<h3>TIME REGISTRATION</h3><br><br>";
			

			/// WEEK/YEAR SELECTION OPTION LINKS
			print "<table class=\"table_highlight\" width=\"100%\"><tr><td width=\"100%\">";
			
			print "<b>WEEK $date_selected_weekofyear, $date_selected_year</b><br>";
			print "($date_selected_start to $date_selected_end)<br>";
			print "<br>";




			if ($date_selected_weekofyear == 1)
			{
				$date_option_previousyear	= $date_selected_year - 1;
				$date_option_previousweek	= 52;

				$date_option_nextyear		= $date_selected_year;
				$date_option_nextweek		= 2;
			}
			elseif ($date_selected_weekofyear == 52)
			{
				$date_option_previousyear	= $date_selected_year;
				$date_option_previousweek	= 51;

				$date_option_nextyear		= $date_selected_year + 1;
				$date_option_nextweek		= 1;
			}
			else
			{
				$date_option_previousyear	= $date_selected_year;
				$date_option_previousweek	= $date_selected_weekofyear - 1;

				$date_option_nextyear		= $date_selected_year;
				$date_option_nextweek		= $date_selected_weekofyear + 1;
			}
			
			print "<p><b>";
			print "<a href=\"index.php?page=timekeeping/timereg.php&employeeid=$employeeid&weekofyear=$date_option_previousweek&year=$date_option_previousyear\">Previous Week</a> || ";
			print "<a href=\"index.php?page=timekeeping/timereg.php&employeeid=$employeeid&weekofyear=$date_option_nextweek&year=$date_option_nextyear\">Next Week</a>";
			print "</b></p>";
			
			print "</td></tr></table><br>";



			/*
				Employee Selection Form
			*/
			$form = New form_input;
			$form->formname = "timereg_employee";
			$form->language = $_SESSION["user"]["lang"];


			// employee selection box
			$sql_string = "SELECT "
					."staff.id as id, "
					."staff.name_staff as label "
					."FROM users_permissions_staff "
					."LEFT JOIN staff ON staff.id = users_permissions_staff.staffid "
					."WHERE users_permissions_staff.userid='". $_SESSION["user"]["id"] ."' "
					."GROUP BY users_permissions_staff.staffid "
					."ORDER BY staff.name_staff";
					
			$structure = form_helper_prepare_dropdownfromdb("employeeid", $sql_string);


			// if there is only one employee, automatically select it if
			// it hasn't been already
			if (!$employeeid && count($structure["values"]) == 1)
			{
				$sql = New sql_query;
				$sql->string = $sql_string;
				$sql->execute();
				$sql->fetch_array();
				
				$employeeid = $sql->data[0]["id"];
			}
			
			$structure["defaultvalue"] = $employeeid;
			$form->add_input($structure);

			
			// hidden values
			$structure = NULL;
			$structure["fieldname"]		= "page";
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= $_GET["page"];
			$form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"]		= "editid";
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= $editid;
			$form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"]		= "weekofyear";
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= $date_selected_weekofyear;
			$form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"]		= "year";
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= $date_selected_year;
			$form->add_input($structure);

			
			// submit button
			$structure = NULL;
			$structure["fieldname"] 	= "submit";
			$structure["type"]		= "submit";
			$structure["defaultvalue"]	= "Display";
			$form->add_input($structure);


			// display the form
			// we use a custom form display method here, since the normal form
			// class will draw a fully styled form in a table.
			print "<table class=\"table_highlight\" width=\"100%\"><tr><td width=\"100%\">";
			print "<form method=\"get\" action=\"index.php\">";
			print "<p><b>Select an employee to view:</b></p>";
			$form->render_field("employeeid");
			$form->render_field("editid");
			$form->render_field("weekofyear");
			$form->render_field("year");
			$form->render_field("page");
			$form->render_field("submit");
			
			print "</form>";
			print "</td></tr></table><br>";



			// make sure the user has selected a valid employee to view, who they have access to
			if (!$employeeid)
			{
				print "<p><b>Please select an employee to view.</b></p>";
			}
			elseif (!user_permissions_staff_get("timereg_view", $employeeid))
			{
				print "<p><b>Sorry, you do not have correct access permissions to view this employee.</b></p>";
			}
			else
			{
				/*
					DRAW WEEK TABLE

					We need to display a table showing all time booked for the currently
					selected week.

					1. Get a list of all project from the database that had time booked against
					   them this week.

					2. Fetch total time spent on each project, for each day.

					3. Display into a table, with easy edit + add links.


					TODO: Re-write this section of code to be compliant with the new table class structure - this
					will allow future support of alternate output formats such as CSV or PDF
				*/


				// establish a new table object
				$timereg_list = New table;

				$timereg_list->language	= $_SESSION["user"]["lang"];
				$timereg_list->tablename	= "timereg_list";

				// define all the columns and structure
				$timereg_list->add_column("standard", "projectandphase", "");
				$timereg_list->add_column("hourmins", "monday", "");
				$timereg_list->add_column("hourmins", "tuesday",  "");
				$timereg_list->add_column("hourmins", "wednesday",  "");
				$timereg_list->add_column("hourmins", "thursday",  "");
				$timereg_list->add_column("hourmins", "friday",  "");
				$timereg_list->add_column("hourmins", "saturday",  "");
				$timereg_list->add_column("hourmins", "sunday",  "");


				// custom labels and links
				$timereg_list->custom_column_label("monday", "Monday<br><font style=\"font-size: 8px;\">(". $date_selected_daysofweek[0] .")</font>");
				$timereg_list->custom_column_link("monday", "index.php?page=timekeeping/timereg-day.php&date=". $date_selected_daysofweek[0] ."");

				$timereg_list->custom_column_label("tuesday", "Tuesday<br><font style=\"font-size: 8px;\">(". $date_selected_daysofweek[1] .")</font>");
				$timereg_list->custom_column_link("tuesday", "index.php?page=timekeeping/timereg-day.php&date=". $date_selected_daysofweek[1] ."");

				$timereg_list->custom_column_label("wednesday", "Wednesday<br><font style=\"font-size: 8px;\">(". $date_selected_daysofweek[2] .")</font>");
				$timereg_list->custom_column_link("wednesday", "index.php?page=timekeeping/timereg-day.php&date=". $date_selected_daysofweek[2] ."");
				
				$timereg_list->custom_column_label("thursday", "Thursday<br><font style=\"font-size: 8px;\">(". $date_selected_daysofweek[3] .")</font>");
				$timereg_list->custom_column_link("thursday", "index.php?page=timekeeping/timereg-day.php&date=". $date_selected_daysofweek[3] ."");
				
				$timereg_list->custom_column_label("friday", "Friday<br><font style=\"font-size: 8px;\">(". $date_selected_daysofweek[4] .")</font>");
				$timereg_list->custom_column_link("friday", "index.php?page=timekeeping/timereg-day.php&date=". $date_selected_daysofweek[4] ."");
				
				$timereg_list->custom_column_label("saturday", "Saturday<br><font style=\"font-size: 8px;\">(". $date_selected_daysofweek[5] .")</font>");
				$timereg_list->custom_column_link("saturday", "index.php?page=timekeeping/timereg-day.php&date=". $date_selected_daysofweek[5] ."");
				
				$timereg_list->custom_column_label("sunday", "Sunday<br><font style=\"font-size: 8px;\">(". $date_selected_daysofweek[6] .")</font>");
				$timereg_list->custom_column_link("sunday", "index.php?page=timekeeping/timereg-day.php&date=". $date_selected_daysofweek[6] ."");
				
				
				// defaults
				$timereg_list->columns		= array("projectandphase", "monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday");

				// totals
				$timereg_list->total_columns	= array("monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday");
				$timereg_list->total_rows	= array("monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday");


				// define SQL structure
				$timereg_list->sql_obj->prepare_sql_settable("timeregs");


				// map dates to day of the week
				$days[ $date_selected_daysofweek[0] ] = "monday";
				$days[ $date_selected_daysofweek[1] ] = "tuesday";
				$days[ $date_selected_daysofweek[2] ] = "wednesday";
				$days[ $date_selected_daysofweek[3] ] = "thursday";
				$days[ $date_selected_daysofweek[4] ] = "friday";
				$days[ $date_selected_daysofweek[5] ] = "saturday";
				$days[ $date_selected_daysofweek[6] ] = "sunday";

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
						."WHERE employeeid='$employeeid' "
						."AND date >= '$date_selected_start' "
						."AND date <= '$date_selected_end'";

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
				
				$timereg_list->data_num_rows = count($phasearray);
				


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
							."projects.name_project "
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
						$tmparray["projectandphase"] = $data["name_project"] ." - ". $data["name_phase"];
					}



					// 3. Total up all time spent on that project/phase for the day

					// create the query
					$sql_obj = New sql_query;
					$sql_obj->string = "SELECT "
							."date, "
							."time_booked "
							."FROM timereg "
							."WHERE employeeid='$employeeid' "
							."AND date >= '$date_selected_start' "
							."AND date <= '$date_selected_end' "
							."AND phaseid='$phaseid'";

					// fetch the data
					log_debug("timereg", "Fetching all hours for phase $phaseid");
					$sql_obj->execute();


					// process the data
					$sql_obj->fetch_array();
					foreach ($sql_obj->data as $data)
					{
						$tmparray[ $days[ $data["date"] ] ] += $data["time_booked"];
					}


					// 4. Add the data to the table data structure to allow rendering
					$timereg_list->data[] = $tmparray;
				}



				// 5. Draw the table
				$timereg_list->render_table();


			} // valid employee selected for viewing


		} // end if user has staff access rights


	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
