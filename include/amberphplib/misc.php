<?php
/*
	misc.php
	
	Various one-off functions
*/


/* TIME FUNCTION */

/*
	time_format_hourmins($seconds)
	returns the number of hours, and the number of minutes in the form of H:MM
*/
function time_format_hourmins($seconds)
{
 	$minutes	= $seconds / 60;
	$hours		= sprintf("%d",$minutes / 60);

	$excess_minutes = sprintf("%02d", $minutes - ($hours * 60));

	return "$hours:$excess_minutes";
}


/*
	time_calculate_weekstart($date_selected_weekofyear, $date_selected_year)

	returns the start date of the week in format YYYY-MM-DD
	
*/
function time_calculate_weekstart($date_selected_weekofyear, $date_selected_year)
{
	// work out the start date of the current week
	$date_curr_weekofyear	= date("W");
	$date_curr_year		= date("Y");
	$date_curr_start	= mktime(0, 0, 0, date("m"), ((date("d") - date("w")) + 1) , $date_curr_year);

	// work out the difference in the number of weeks desired
	$date_selected_weekdiff	= ($date_curr_year - $date_selected_year) * 52;
	$date_selected_weekdiff += ($date_curr_weekofyear - $date_selected_weekofyear);

	// work out the difference in seconds (1 week == 604800 seconds)
	$date_selected_seconddiff = $date_selected_weekdiff * 604800;

	// timestamp of the first day in the week.
	$date_selected_start = $date_curr_start - $date_selected_seconddiff;

	return date("Y-m-d", $date_selected_start);
}


/*
	time_calculate_daysofweek($date_selected_start_ts)

	Passing YYYY-MM-DD of the first day of the week will
	return an array containing date of each day in YYYY-MM-DD format
*/
function time_calculate_daysofweek($date_selected_start)
{
	$days = array();

	// get the start day, month + year
	$dates = split("-", $date_selected_start);
	
	// get the value for all the days
	for ($i=0; $i < 7; $i++)
	{
		$days[$i] = date("Y-m-d", mktime(0,0,0,$dates[1], ($dates[2] + $i), $dates[0]));
	}

	return $days;
}



/* HELP FUNCTIONS */

/*
	helplink( id )
	returns an html string, including a help icon, with a hyperlink to the help page specified by id.
*/

function helplink($id)
{
	return "<a href=\"help/viewer.php?id=$id\" target=\"new\" title=\"Click here for a popup help box\"><img src=\"images/icons/help.gif\" alt=\"?\" border=\"0\"></a>";
}



?>
