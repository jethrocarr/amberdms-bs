<?php
/*
	timekeeping/timereg-day.php
	
	access: time_keeping

	Displays all the details of the selected day, and allows additions.
*/


// custom includes
include("include/user/permissions_staff.php");


if (user_permissions_get('timekeeping'))
{
	$date = $_GET["date"];
	
	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "Weekview";
	$_SESSION["nav"]["query"][]	= "page=timekeeping/timereg.php&year=". $_SESSION["timereg"]["year"] ."&weekofyear=". $_SESSION["timereg"]["weekofyear"]."";

	$_SESSION["nav"]["title"][]	= "Day View";
	$_SESSION["nav"]["query"][]	= "page=timekeeping/timereg-day.php&date=$date";
	$_SESSION["nav"]["current"]	= "page=timekeeping/timereg-day.php&date=$date";


	function page_render()
	{
		$editid		= security_script_input('/^[0-9]*$/', $_GET["editid"]);
		$date		= security_script_input('/^[0-9-]*$/', $_GET["date"]);
		$date_split	= split("-", $date);

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
			Title + Summary
		*/
		print "<h3>TIME REGISTRATION - ". date("l d F Y", mktime(0,0,0, $date_split[1], $date_split[2], $date_split[0])) ."</h3><br>";



		// links
		$date_previous	= mktime(0,0,0, $date_split[1], ($date_split[2] - 1), $date_split[0]);
		$date_previous	= date("Y-m-d", $date_previous);
		
		$date_next	= mktime(0,0,0, $date_split[1], ($date_split[2] + 1), $date_split[0]);
		$date_next	= date("Y-m-d", $date_next);

		print "<p><b>";
		print "<a href=\"index.php?page=timekeeping/timereg-day.php&date=$date_previous&employeeid=$employeeid\">Previous Day</a> || ";
		print "<a href=\"index.php?page=timekeeping/timereg-day.php&date=$date_next&employeeid=$employeeid\">Next Day</a>";
		print "</b></p><br>";
		



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
		$structure["fieldname"]		= "date";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $date;
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
		$form->render_field("date");
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
				DRAW DAY TABLE

				We need to display a table showing all time booked for the currently
				selected day.
			*/

			// establish a new table object
			$timereg_table = New table;

			$timereg_table->language	= $_SESSION["user"]["lang"];
			$timereg_table->tablename	= "timereg_table";

			// define all the columns and structure
			$timereg_table->add_column("standard", "name_project", "projects.name_project");
			$timereg_table->add_column("standard", "name_phase", "project_phases.name_phase");
			$timereg_table->add_column("hourmins", "time_booked", "timereg.time_booked");
			$timereg_table->add_column("standard", "description", "timereg.description");

			// defaults
			$timereg_table->columns		= array("name_project", "name_phase", "description", "time_booked");
			$timereg_table->columns_order	= array("name_project", "name_phase");

			// create totals
			$timereg_table->total_columns	= array("time_booked");
		
			// define SQL
			$timereg_table->sql_obj->prepare_sql_settable("timereg");
			$timereg_table->sql_obj->prepare_sql_addfield("id", "timereg.id");
			$timereg_table->sql_obj->prepare_sql_addjoin("LEFT JOIN project_phases ON timereg.phaseid = project_phases.id");
			$timereg_table->sql_obj->prepare_sql_addjoin("LEFT JOIN projects ON project_phases.projectid = projects.id");
			$timereg_table->sql_obj->prepare_sql_addwhere("timereg.employeeid = '$employeeid'");
			$timereg_table->sql_obj->prepare_sql_addwhere("timereg.date = '$date'");
				
			// execute SQL statement	
			$timereg_table->generate_sql();
			$timereg_table->load_data_sql();

			if (!$timereg_table->data_num_rows)
			{
				print "<p><b>There is currently no time registered to this day.</b></p>";
			}
			else
			{
				$structure = NULL;
				$structure["editid"]["column"]	= "id";
				$structure["date"]["value"]	= "$date#form";
				$timereg_table->add_link("edit", "timekeeping/timereg-day.php", $structure);

				$timereg_table->render_table();
			}



			if (!user_permissions_staff_get("timereg_write", $employeeid))
			{
				print "<p><b>You do not have permissions to make any changes to the time booked by this employee.</b></p>";
			}
			else
			{

				/*
					Input Form

					Allows the creation of a new entry for the day, or the adjustment of an existing one.
				*/
			
				print "<a name=\"form\"></a><br><br>";
				print "<table width=\"100%\" class=\"table_highlight\"><tr><td>";
				
				if ($editid)
				{
					print "<h3>ADJUST TIME RECORD:</h3>";
				}
				else
				{
					print "<h3>BOOK TIME:</h3>";
				}
				print "<br><br>";

				
				$form = New form_input;
				$form->formname = "timereg_day";
				$form->language = $_SESSION["user"]["lang"];
				
				$form->action = "timekeeping/timereg-day-process.php";
				$form->method = "post";
					
					
				// hidden stuff
				$structure = NULL;
				$structure["fieldname"] 	= "id_timereg";
				$structure["type"]		= "hidden";
				$structure["defaultvalue"]	= "$editid";
				$form->add_input($structure);
				
				$structure = NULL;
				$structure["fieldname"] 	= "id_employee";
				$structure["type"]		= "hidden";
				$structure["defaultvalue"]	= "$employeeid";
				$form->add_input($structure);
							

				// general
				$structure = NULL;
				$structure["fieldname"] 	= "date";
				$structure["type"]		= "date";
				$structure["defaultvalue"]	= $date;
				$structure["options"]["req"]	= "yes";
				$form->add_input($structure);
				
				$structure = NULL;
				$structure["fieldname"] 	= "time_booked";
				$structure["type"]		= "hourmins";
				$structure["options"]["req"]	= "yes";
				$form->add_input($structure);

				$structure = NULL;
				$structure["fieldname"]		= "description";
				$structure["type"]		= "textarea";
				$structure["options"]["req"]	= "yes";
				$form->add_input($structure);


				// TODO: Update this to use the new helper functions

				// get data from DB and create project/phase dropdown
				//
				// Note: this hasn't yet been reduced to use the form_helper_prepare_dropdownfromdb function
				// because of the fact that it needs to merge two different fields to make the drop down label.
				//
				// This a pretty rare occurance, so it's likely this will be left as it is.
				//
				$structure = NULL;
				$structure["fieldname"] 	= "phaseid";
				$structure["type"]		= "dropdown";
				$structure["options"]["req"]	= "yes";

				$sql_obj = New sql_query;
				$sql_obj->string = "SELECT "
						."projects.name_project, "
						."project_phases.id as phaseid, "
						."project_phases.name_phase "
						."FROM `projects` "
						."LEFT JOIN project_phases ON project_phases.projectid = projects.id "
						."ORDER BY "
						."projects.name_project, "
						."project_phases.name_phase";
				
				$sql_obj->execute();	
				
				if ($sql_obj->num_rows())
				{
					$sql_obj->fetch_array();
					foreach ($sql_obj->data as $data)
					{
						// only add a project if there is a phaseid for it
						if ($data["phaseid"])
						{
							$structure["values"][]				= $data["phaseid"];
							$structure["translations"][ $data["phaseid"] ]	= $data["name_project"] ." - ". $data["name_phase"];
						}
					}
				}
						
				$form->add_input($structure);
				
				
				if ($editid)
				{
					$locked = sql_get_singlevalue("SELECT locked as value FROM `timereg` WHERE id='$editid' LIMIT 1");
				}

							
				// submit section
				$structure = NULL;
				$structure["fieldname"] 	= "submit";

				if ($locked)
				{
					$structure["type"]		= "message";
					$structure["defaultvalue"]	= "This time record is locked and can no-longer be adjusted.";
				}
				else
				{
					$structure["type"]		= "submit";
					$structure["defaultvalue"]	= "Save Changes";
				}
				$form->add_input($structure);
				
				
				// define subforms
				$form->subforms["timereg_day"]		= array("phaseid", "date", "time_booked", "description");
				$form->subforms["hidden"]		= array("id_timereg", "id_employee");
				$form->subforms["submit"]		= array("submit");

					
				$sql_obj		= New sql_query;
				$sql_obj->string	= "SELECT id FROM `timereg` WHERE id='$editid'";
				
				$sql_obj->execute();
				
				if ($sql_obj->num_rows())
				{
					// fetch the form data
					$form->sql_query = "SELECT * FROM `timereg` WHERE id='$editid' LIMIT 1";
					$form->load_data();
				}
				else
				{
					// load any data returned due to errors
					$form->load_data_error();
				}

				// display the form
				$form->render_form();




				/*
					Delete Form

					If the user is editing an option, offer a delete option.
				*/
			
				if ($editid)
				{
					print "<br><br>";
					print "<h3>DELETE TIME RECORD:</h3>";
					print "<br><br>";

					
					$form_del = New form_input;
					$form_del->formname = "timereg_delete";
					$form_del->language = $_SESSION["user"]["lang"];
					
					$form_del->action = "timekeeping/timereg-day-delete-process.php";
					$form_del->method = "post";
						
						
					// hidden stuff
					$structure = NULL;
					$structure["fieldname"] 	= "id_timereg";
					$structure["type"]		= "hidden";
					$structure["defaultvalue"]	= "$editid";
					$form_del->add_input($structure);

					$structure = NULL;
					$structure["fieldname"] 	= "id_employee";
					$structure["type"]		= "hidden";
					$structure["defaultvalue"]	= "$employeeid";
					$form->add_input($structure);
					
					$structure = NULL;
					$structure["fieldname"] 	= "date";
					$structure["type"]		= "hidden";
					$structure["defaultvalue"]	= "$date";
					$form_del->add_input($structure);
					
					
					// general
					$structure = NULL;
					$structure["fieldname"] 	= "message";
					$structure["type"]		= "message";
					$structure["defaultvalue"]	= "If you no longer require this time entry, you can delete it using the button below";
					$form_del->add_input($structure);
					
					
					// submit section
					$structure = NULL;
					$structure["fieldname"] 	= "submit";

					if ($locked)
					{
						$structure["type"]		= "message";
						$structure["defaultvalue"]	= "This time record is locked and can no-longer be adjusted.";
					}
					else
					{
						$structure["type"]		= "submit";
						$structure["defaultvalue"]	= "Delete Time Entry";
					}
					
					$form_del->add_input($structure);
				

				
					
					// define subforms
					$form_del->subforms["hidden"]		= array("id_timereg", "id_employee", "date");
					$form_del->subforms["timereg_delete"]	= array("message", "submit");

					
					$sql_obj 		= New sql_query();
					$sql_obj->string	= "SELECT id FROM `timereg` WHERE id='$editid'";
					
					$sql_obj->execute();
					
					if ($sql_obj->num_rows())
					{
						// fetch the form data
						$form_del->sql_query = "SELECT id, date FROM `timereg` WHERE id='$editid' LIMIT 1";
						$form_del->load_data();
					}
					
					// display the form
					$form_del->render_form();
				}

				print "</td></tr></table>";

			} // end if user has write permissions

		} // end if user has employee access permissions

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
