<?php
/*
	timebooked.php
	
	access: "projects_view" group members

	Displays all the time recorded against the selected project and all it's phases.
*/

if (user_permissions_get('projects_view'))
{
	$projectid = $_GET["id"];
	
	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "Project Details";
	$_SESSION["nav"]["query"][]	= "page=projects/view.php&id=$projectid";

	$_SESSION["nav"]["title"][]	= "Project Phases";
	$_SESSION["nav"]["query"][]	= "page=projects/phases.php&id=$projectid";
	
	$_SESSION["nav"]["title"][]	= "Timebooked";
	$_SESSION["nav"]["query"][]	= "page=projects/timebooked.php&id=$projectid";
	$_SESSION["nav"]["current"]	= "page=projects/timebooked.php&id=$projectid";
	
	$_SESSION["nav"]["title"][]	= "Project Journal";
	$_SESSION["nav"]["query"][]	= "page=projects/journal.php&id=$projectid";

	$_SESSION["nav"]["title"][]	= "Delete Project";
	$_SESSION["nav"]["query"][]	= "page=projects/delete.php&id=$projectid";


	function page_render()
	{
		$projectid = security_script_input('/^[0-9]*$/', $_GET["id"]);

		// check that the specified project actually exists
		$mysql_string	= "SELECT id, name_project FROM `projects` WHERE id='$projectid'";
		$mysql_result	= mysql_query($mysql_string);
		$mysql_num_rows	= mysql_num_rows($mysql_result);

		if (!$mysql_num_rows)
		{
			print "<p><b>Error: The requested project does not exist. <a href=\"index.php?page=projects/projects.php\">Try looking for your project on the project list page.</a></b></p>";
		}
		else
		{
			$mysql_data = mysql_fetch_array($mysql_result);
			
			// heading
			print "<h3>TIME BOOKED TO PROJECT</h3>";

			print "<p>This page shows all the time that has been booked to the ". $mysql_data["name_project"] ." project.</p>";
		
		
			/// Basic Table Structure

			// establish a new table object
			$timereg_table = New table;

			$timereg_table->language	= $_SESSION["user"]["lang"];
			$timereg_table->tablename	= "timereg_table";
			$timereg_table->sql_table	= "timereg";

			// define all the columns and structure
			$timereg_table->add_column("date", "date", "timereg.date");
			$timereg_table->add_column("standard", "name_phase", "project_phases.name_phase");
			$timereg_table->add_column("standard", "name_staff", "staff.name_staff");
			$timereg_table->add_column("standard", "description", "timereg.description");
			$timereg_table->add_column("hourmins", "time_booked", "timereg.time_booked");

			// defaults
			$timereg_table->columns		= array("date", "name_phase", "name_staff", "description", "time_booked");
			$timereg_table->columns_order	= array("date", "name_phase");

			
			
			/// Filtering/Display Options

			// fixed options
			$timereg_table->add_fixed_option("id", $projectid);


			// acceptable filter options
			$structure = NULL;
			$structure["fieldname"] = "date_start";
			$structure["type"]	= "date";
			$timereg_table->add_filter($structure);


	
			// create totals
			$timereg_table->total_columns	= array("time_booked");

		
			// options form
			$timereg_table->load_options_form();
			$timereg_table->render_options_form();
			


			/// Generate & execute SQL query

			// fetch data from both the projects and timereg table with a custom query
			/*
			$timereg_table->sql_query =	"SELECT id, "
							."timereg.date, "
							."timereg.time_booked, "
							."timereg.description, "
							."project_phases.name_phase, "
							."staff.name_staff "
							."FROM timereg "
							."LEFT JOIN staff ON timereg.employeeid = staff.id "
							."LEFT JOIN project_phases ON timereg.phaseid = project_phases.id "
							."LEFT JOIN projects ON project_phases.projectid = projects.id "
							."WHERE "
							."projects.id = '$projectid'";
			*/	
			$timereg_table->prepare_sql_addfield("timereg.id", "");
			$timereg_table->prepare_sql_addjoin("LEFT JOIN staff ON timereg.employeeid = staff.id");
			$timereg_table->prepare_sql_addjoin("LEFT JOIN project_phases ON timereg.phaseid = project_phases.id");
			$timereg_table->prepare_sql_addjoin("LEFT JOIN projects ON project_phases.projectid = projects.id");
			$timereg_table->prepare_sql_addwhere("projects.id = '$projectid'");
			

			$timereg_table->generate_sql();
			$timereg_table->load_data_sql();



			/// Display table data

			if (!$timereg_table->data_num_rows)
			{
				print "<p><b>There is currently no time registered to this project.</b></p>";
			}
			else
			{
				$structure = NULL;
				$structure["editid"]["column"]	= "id";
				$structure["date"]["column"]	= "date";
				$timereg_table->add_link("view/edit", "timekeeping/timereg-day.php", $structure);

				$timereg_table->render_table();
			}






		} // end if project exists
		
	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
