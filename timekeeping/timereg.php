<?php
/*
	timekeeping/timereg.php

	access: "timekeeping" group members

	Displays registered hours, and provides links to pages
	to add/remove/edit the hours.

	Note that ISO-8601 numeric representation is used for
	time_dayofweek and time_weekofyear.
	
*/

if (user_permissions_get('timekeeping'))
{
	function page_render()
	{
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
			
		// selected user ID
		// TODO: expand this to be a selectable option depending on the user's permissions
		$employeeid = user_information("employeeid");


		// get the start date of the week
		$date_selected_start		= time_calculate_weekstart($date_selected_weekofyear, $date_selected_year);

		// get the dates for each day of the week
		$date_selected_daysofweek	= time_calculate_daysofweek($date_selected_start);

		// get the end date of the week
		$date_selected_end	= $date_selected_daysofweek[6];


		/// PAGE HEADING

		print "<h3>TIME REGISTRATION</h3><br><br>";
		
		print "<b>WEEK $date_selected_weekofyear, $date_selected_year</b><br>";
		print "($date_selected_start to $date_selected_end)<br>";
		print "<br>";



		/// WEEK/YEAR SELECTION OPTION FORM

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
		print "<a href=\"index.php?page=timekeeping/timereg.php&weekofyear=$date_option_previousweek&year=$date_option_previousyear\">Previous Week</a> || ";
		print "<a href=\"index.php?page=timekeeping/timereg.php&weekofyear=$date_option_nextweek&year=$date_option_nextyear\">Next Week</a>";
		print "</b></p>";
		



		
		

	
		/*
			DRAW WEEK TABLE

			We need to display a table showing all time booked for the currently
			selected week.

			1. Get a list of all project from the database that had time booked against
			   them this week.

			2. Fetch total time spent on each project, for each day.

			3. Display into a table, with easy edit + add links.
		*/




		//// 1. Get list of all projects
		$projects = array();

		// fetch IDs with booked time from DB
		$mysql_string		= "SELECT projectid FROM `timereg` WHERE employeeid='$employeeid' AND date >= '$date_selected_start' AND date <= '$date_selected_end'";
		$mysql_result		= mysql_query($mysql_string);
		$mysql_num_rows 	= mysql_num_rows($mysql_result);

		if ($mysql_num_rows)
		{
			while ($mysql_data = mysql_fetch_array($mysql_result))
			{
				// create an array of all the project ids, without any duplicates
				if (!in_array($mysql_data["projectid"], $projects))
				{
					$projects[] = $mysql_data["projectid"];
				}
			}
		}

		
		//// 2. Fetch total time spent on each project, for each day
		$structure = NULL;
		
		foreach ($projects as $projectid)
		{
			$mysql_string		= "SELECT date, time_booked FROM `timereg` WHERE employeeid='$employeeid' AND date >= '$date_selected_start' AND date <= '$date_selected_end' AND projectid='$projectid'";
			$mysql_result 		= mysql_query($mysql_string);

			while ($mysql_data = mysql_fetch_array($mysql_result))
			{
				$structure[$projectid][ $mysql_data["date"] ] += $mysql_data["time_booked"];
				$structure[$projectid]["total"] += $mysql_data["time_booked"];
			}
		}


		//// 3. Display table

		// display header row
		print "<table class=\"table_content\" width=\"100%\">";
		print "<tr>";
			
			print "<td class=\"header\"><b>Project</b></td>";
			print "<td class=\"header\"><a style=\"color: #ffffff;\" title=\"Click for full details for this date\" href=\"index.php?page=timekeeping/timereg-day.php&date=". $date_selected_daysofweek[0] ."\"><b>Monday</b><br>(". $date_selected_daysofweek[0] .")</a></td>";
			print "<td class=\"header\"><a style=\"color: #ffffff;\" title=\"Click for full details for this date\" href=\"index.php?page=timekeeping/timereg-day.php&date=". $date_selected_daysofweek[1] ."\"><b>Tuesday</b><br>(". $date_selected_daysofweek[1] .")</a></td>";
			print "<td class=\"header\"><a style=\"color: #ffffff;\" title=\"Click for full details for this date\" href=\"index.php?page=timekeeping/timereg-day.php&date=". $date_selected_daysofweek[2] ."\"><b>Wednesday</b><br>(". $date_selected_daysofweek[2] .")</a></td>";
			print "<td class=\"header\"><a style=\"color: #ffffff;\" title=\"Click for full details for this date\" href=\"index.php?page=timekeeping/timereg-day.php&date=". $date_selected_daysofweek[3] ."\"><b>Thursday</b><br>(". $date_selected_daysofweek[3] .")</a></td>";
			print "<td class=\"header\"><a style=\"color: #ffffff;\" title=\"Click for full details for this date\" href=\"index.php?page=timekeeping/timereg-day.php&date=". $date_selected_daysofweek[4] ."\"><b>Friday</b><br>(". $date_selected_daysofweek[4] .")</a></td>";
			print "<td class=\"header\"><a style=\"color: #ffffff;\" title=\"Click for full details for this date\" href=\"index.php?page=timekeeping/timereg-day.php&date=". $date_selected_daysofweek[5] ."\"><b>Saturday</b><br>(". $date_selected_daysofweek[5] .")</a></td>";
			print "<td class=\"header\"><a style=\"color: #ffffff;\" title=\"Click for full details for this date\" href=\"index.php?page=timekeeping/timereg-day.php&date=". $date_selected_daysofweek[6] ."\"><b>Sunday</b><br>(". $date_selected_daysofweek[6] .")</a></td>";
			print "<td class=\"header\"><b>Total:</b></td>";
		print "</tr>";
		
		// display data
		foreach ($projects as $projectid)
		{
			print "<tr>";

			// project name
			$mysql_string	= "SELECT name_project FROM `projects` WHERE id='$projectid' LIMIT 1";
			$mysql_result	= mysql_query($mysql_string);
			$mysql_data	= mysql_fetch_array($mysql_result);

			print "<td><a href=\"index.php?page=projects/view.php&id=$projectid\">". $mysql_data["name_project"] ."</a></td>";

			
			for ($i = 0; $i < 7; $i++)
			{
				print "<td>". time_format_hourmins($structure[$projectid][ $date_selected_daysofweek[$i] ]) ."</td>";
			}
			
			print "<td><b>". time_format_hourmins($structure[$projectid]["total"]) ."</b></td>";
				
			print "</tr>";
		}

		

		// display totals row
		print "<tr>";
			print "<td class=\"header\"><b>Totals:</b></td>";
			
			$totalweek = 0;
	
			// totals for each day
			for ($i = 0; $i < 7; $i++)
			{
				$totalday = 0;
				foreach ($projects as $projectid)
				{
					$totalday += $structure[$projectid][ $date_selected_daysofweek[$i] ];
				}
				
				print "<td class=\"header\"><b>". time_format_hourmins($totalday) ."</b></td>";

				$totalweek += $totalday;
			}

			// total for week
			print "<td class=\"header\"><b>". time_format_hourmins($totalweek) ."</b></td>";

		print "</tr>";
		print "</table>";


		// TODO: display CSV download link


	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
