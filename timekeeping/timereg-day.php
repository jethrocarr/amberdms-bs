<?php
/*
	timekeeping/timereg-day.php
	
	access: time_keeping

	Displays all the details of the selected day, and allows additions.
*/

if (user_permissions_get('timekeeping'))
{
	$date = $_GET["date"];
	
	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "Weekview";
	$_SESSION["nav"]["query"][]	= "page=timekeeping/timereg.php&year=". $_SESSION["timereg"]["year"] ."&weekofyear=". $_SESSION["timereg"]["weekofyear"]."";

	$_SESSION["nav"]["title"][]	= "Day View";
	$_SESSION["nav"]["query"][]	= "page=timekeeping/timereg.php&date=$date";
	$_SESSION["nav"]["current"]	= "page=timekeeping/timereg.php&date=$date";


	function page_render()
	{
		$editid		= security_script_input('/^[0-9]*$/', $_GET["editid"]);
		$date		= security_script_input('/^[0-9-]*$/', $_GET["date"]);
		$employeeid	= user_information("employeeid");


		/*
			Title + Summary
		*/
		print "<h3>TIME REGISTRATION - $date</h3><br><br>";


	
		/*
			DRAW DAY TABLE

			We need to display a table showing all time booked for the currently
			selected day.
		*/

		// establish a new table object
		$timereg_table = New table;

		$timereg_table->language	= $_SESSION["user"]["lang"];
		$timereg_table->tablename	= "timereg_table";
		$timereg_table->sql_table	= "timereg";

		// define all the columns and structure
		$timereg_table->add_column("standard", "code_project", "");
		$timereg_table->add_column("standard", "name_project", "");
		$timereg_table->add_column("hourmins", "time_booked", "");
		$timereg_table->add_column("standard", "description", "");

		// defaults
		$timereg_table->columns		= array("code_project", "name_project", "description", "time_booked");
		$timereg_table->columns_order	= array("code_project");

		// create totals
		$timereg_table->total_columns	= array("time_booked");
		

		// fetch data from both the projects and timereg table with a custom query
		$timereg_table->sql_query = "SELECT timereg.id, timereg.time_booked, timereg.description, projects.code_project, projects.name_project FROM timereg LEFT JOIN projects ON timereg.projectid = projects.id WHERE timereg.employeeid='$employeeid' AND timereg.date='$date'";	
		$timereg_table->load_data_sql();

		if (!$timereg_table->data_num_rows)
		{
			print "<p><b>There is currently no time registered to this day.</b></p>";
		}
		else
		{
			// translate the column labels
			$timereg_table->render_column_names();
		
			// display header row
			print "<table class=\"table_content\" width=\"100%\">";
			print "<tr>";
			
				foreach ($timereg_table->render_columns as $columns)
				{
					print "<td class=\"header\"><b>". $columns ."</b></td>";
				}
				
				print "<td class=\"header\"></td>";	// filler for link column
				
			print "</tr>";
		
			// display data
			for ($i=0; $i < $timereg_table->data_num_rows; $i++)
			{
				print "<tr>";

				foreach ($timereg_table->columns as $columns)
				{
					print "<td>". $timereg_table->data[$i]["$columns"] ."</td>";
				}
				print "<td><a href=\"index.php?page=timekeeping/timereg-day.php&date=$date&edit=". $timereg_table->data[$i]["id"] ."\">edit</td>";
				
				print "</tr>";
			}

/*
			// display totals
			print "<table class=\"table_content\" width=\"100%\">";
			print "<tr>";
			
				foreach ($timereg_table->render_columns as $columns)
				{
					print "<td class=\"header\">";
					
					if (in_array($columns, $timereg_table->total_columns))
					{
						for ($i=0; $i < $timereg_table->data_num_rows; $i++)
						{
							$timereg_table->raw_data[$i]
						
						print "<b>". $columns ."</b></td>";
					}
					
					print "</td>";
				}
				
				print "<td class=\"header\"></td>";	// filler for link column
				
			print "</tr>";
*/
		
			print "</table>";

			// TODO: display CSV download link

		}



		/*
			Input Form

			Allows the creation of a new entry for the day, or the adjustment of an existing one.
		*/
	
		print "<br><br>";
		
		if ($editid)
		{
			print "<h3>ADJUST TIME RECORD:</h3>";
		}
		else
		{
			print "<h3>CREATE TIME RECORD:</h3>";
		}

		
		$form = New form_input;
		$form->formname = "timereg_day";
		$form->language = $_SESSION["user"]["lang"];
		
		$form->action = "timekeeping/timereg-day-process.php";
		$form->method = "post";
			
			
		// general
		$structure = NULL;
		$structure["fieldname"] 	= "id_timereg";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= "$editid";
		$form->add_input($structure);
			
		$structure = NULL;
		$structure["fieldname"] 	= "time_booked";
		$structure["type"]		= "hoursmins";
		$structure["options"]["req"]	= "yes";
		$form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]		= "description";
		$structure["type"]		= "textarea";
		$form->add_input($structure);

		// get data from DB and create project dropdown
		$structure = NULL;
		$structure["fieldname"] 	= "projectid";
		$structure["type"]		= "dropdown";
		$structure["options"]["req"]	= "yes";

		$mysql_string	= "SELECT id, code_project, name_project FROM `projects` ORDER BY code_project, name_project";
		$mysql_result	= mysql_query($mysql_string);
		$mysql_num_rows	= mysql_num_rows($mysql_result);

		while ($mysql_data = mysql_fetch_array($mysql_result))
		{
			$structure["values"][]					= $mysql_data["id"];
			$structure["translations"][ $mysql_data["id"] ]		= $mysql_data["code_project"] ." (". $mysql_data["name_project"] .")";
		}

				
		$form->add_input($structure);

					
		// submit section
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Save Changes";
		$form->add_input($structure);
		
		
		// define subforms
		$form->subforms["timereg_day"]		= array("projectid", "time_booked", "description");
		$form->subforms["hidden"]		= array("id_timereg");
		$form->subforms["submit"]		= array("submit");

			
		$mysql_string	= "SELECT id FROM `timereg` WHERE id='$editid'";
		$mysql_result	= mysql_query($mysql_string);
		$mysql_num_rows	= mysql_num_rows($mysql_result);

		if ($mysql_num_rows)
		{
			// fetch the form data
			$form->sql_query = "SELECT * FROM `customers` WHERE id='$id' LIMIT 1";		
			$form->load_data();
		}

		// display the form
		$form->render_form();

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
