<?php
/*
	timekeeping/timekeeping.php

	Summary page for booked time for either the currently viewing employee, or all employees
	if the user has the correct permissions level.
*/

class page_output
{
	function check_permissions()
	{
		return user_online();
	}

	function check_requirements()
	{
		// nothing todo
		return 1;
	}

	function execute()
	{
		// nothing todo
		return 1;
	}

	function render_html()
	{
		print "<h3>TIME KEEPING</h3>";
		print "<br>";


		/*
			Time Booked
		*/

		// fetch amount of time booked for today
		$sql_obj = New sql_query;
		$sql_obj->prepare_sql_settable("timereg");
		$sql_obj->prepare_sql_addfield("timebooked", "SUM(timereg.time_booked)");
		$sql_obj->prepare_sql_addwhere("date='". date("Y-m-d") ."'");
		$sql_obj->generate_sql();

		$sql_obj->execute();
		$sql_obj->fetch_array();


		list($booked_time_hours, $booked_time_mins) = split(":", time_format_hourmins($sql_obj->data[0]["timebooked"]));

		if ($booked_time_hours > 0 && $booked_time_mins > 0)
		{
			$message = "Time booked for today: $booked_time_hours hours and $booked_time_mins minutes.";
		}
		elseif ($booked_time_hours > 0)
		{
			$message = "Time booked for today: $booked_time_hours hours.";
		}
		elseif ($unbilled_time_mins > 0)
		{
			$message = "Time booked for today: $booked_time_mins minutes.";
		}
		else
		{
			$message = "<b>No time has been booked for today - click to add time.</b>";
		}

		// display
		print "<br>";
		format_linkbox("default", "index.php?page=timekeeping/timereg-day.php&date=". date("Y-m-d"), "<p><b>TIME BOOKED</b></p><p>$message</p>");



		/*
			Unbilled Time
		*/
		if (user_permissions_get("projects_timegroup"))
		{
			// fetch amount of unbilled time
			$sql_obj = New sql_query;
			$sql_obj->prepare_sql_settable("timereg");
			$sql_obj->prepare_sql_addfield("timebooked", "SUM(timereg.time_booked)");
			$sql_obj->prepare_sql_addjoin("LEFT JOIN time_groups ON timereg.groupid = time_groups.id");
			$sql_obj->prepare_sql_addwhere("(time_groups.invoiceid='' || time_groups.invoiceid='0')");
			$sql_obj->generate_sql();

			$sql_obj->execute();
			$sql_obj->fetch_array();


			list($unbilled_time_hours, $unbilled_time_mins) = split(":", time_format_hourmins($sql_obj->data[0]["timebooked"]));

			if ($unbilled_time_hours > 0 && $unbilled_time_mins > 0)
			{
				$message = "There is currently $unbilled_time_hours hours and $unbilled_time_mins minutes of unbilled time to be processed.";
			}
			elseif ($unbilled_time_hours > 0)
			{
				$message = "There is currently $unbilled_time_hours hours of unbilled time to be processed.";
			}
			elseif ($unbilled_time_mins > 0)
			{
				$message = "There is currently $unbilled_time_mins minutes of unbilled time to be processed.";
			}
			else
			{
				$message = "There is no unbilled time to be processed.";
			}


			// display
			print "<br>";
			format_linkbox("default", "index.php?page=timekeeping/unbilled.php", "<p><b>UNBILLED TIME</b></p><p>$message</p>");
		
		}
	}
}

?>	
