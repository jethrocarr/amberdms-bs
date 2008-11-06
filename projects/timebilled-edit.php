<?php
/*
	projects/timebilled-edit.php
	
	access: projects_write

	Form to add or edit a time grouping.
*/

if (user_permissions_get('projects_write'))
{
	$id = $_GET["projectid"];
	
	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "Project Details";
	$_SESSION["nav"]["query"][]	= "page=projects/view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Project Phases";
	$_SESSION["nav"]["query"][]	= "page=projects/phases.php&id=$id";
	
	$_SESSION["nav"]["title"][]	= "Timebooked";
	$_SESSION["nav"]["query"][]	= "page=projects/timebooked.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Timebilled/Grouped";
	$_SESSION["nav"]["query"][]	= "page=projects/timebilled.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=projects/timebilled.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Project Journal";
	$_SESSION["nav"]["query"][]	= "page=projects/journal.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Delete Project";
	$_SESSION["nav"]["query"][]	= "page=projects/delete.php&id=$id";


	function page_render()
	{
		$projectid	= security_script_input('/^[0-9]*$/', $_GET["projectid"]);
		$groupid		= security_script_input('/^[0-9]*$/', $_GET["groupid"]);


		/*
			Perform verification tasks
		*/
		$error = 0;
		
		// check that the specified project actually exists
		$mysql_string	= "SELECT id FROM `projects` WHERE id='$projectid'";
		$mysql_result	= mysql_query($mysql_string);
		$mysql_num_rows	= mysql_num_rows($mysql_result);

		if (!$mysql_num_rows)
		{
			print "<p><b>Error: The requested project does not exist. <a href=\"index.php?page=projects/projects.php\">Try looking for your project on the project list page.</a></b></p>";
			$error = 1;
		}
		else
		{
			if ($phaseid)
			{
				// are we editing an existing group? make sure it exists and belongs to this project
				$mysql_string	= "SELECT projectid FROM time_groups WHERE id='$groupid'";
				$mysql_result	= mysql_query($mysql_string);
				$mysql_num_rows	= mysql_num_rows($mysql_result);

				if (!$mysql_num_rows)
				{
					print "<p><b>Error: The requested time group does not exist.</b></p>";
					$error = 1;
				}
				else
				{
					$mysql_data = mysql_fetch_array($mysql_result);

					if ($mysql_data["projectid"] != $projectid)
					{
						print "<p><b>Error: The requested time group does not match the provided project ID. Potential application bug?</b></p>";
						$error = 1;
					}
					
				}
			}
		}

	
		/*
			Display Form
		*/
		if (!$error)
		{
			/*
				Title + Summary
			*/
			if ($groupid)
			{
				print "<h3>EDIT TIME GROUP</h3><br>";
				print "<p>This page allows you to modify a time grouping.</p>";
			}
			else
			{
				print "<h3>ADD NEW TIME GROUP</h3><br>";
				print "<p>This page allows you to add a new time group entry to a project.</p>";
			}
			

			/*
				Define form structure
			*/
			$form = New form_input;
			$form->formname = "timebilled_view";
			$form->language = $_SESSION["user"]["lang"];

			$form->action = "projects/timebilled-edit-process.php";
			$form->method = "post";
		
		
			// general
			$structure = NULL;
			$structure["fieldname"] 	= "name_group";
			$structure["type"]		= "input";
			$structure["options"]["req"]	= "yes";
			$form->add_input($structure);

			$structure = form_helper_prepare_dropdownfromdb("customerid", "SELECT id, name_customer as label FROM customers");
			$structure["options"]["req"]	= "yes";
			$form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"] 	= "description";
			$structure["type"]		= "textarea";
			$form->add_input($structure);


			// hidden values
			$structure = NULL;
			$structure["fieldname"]		= "projectid";
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= $projectid;
			$form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"]		= "groupid";
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= $groupid;
			$form->add_input($structure);
			

			/*
				Define checkboxes for all unassigned time entries
			*/

			$sql_entries_obj = New sql_query;
			
			$sql_entries_obj->prepare_sql_settable("timereg");
			
			$sql_entries_obj->prepare_sql_addfield("id", "timereg.id");
			$sql_entries_obj->prepare_sql_addfield("date", "timereg.date");
			$sql_entries_obj->prepare_sql_addfield("name_phase", "project_phases.name_phase");
			$sql_entries_obj->prepare_sql_addfield("name_staff", "staff.name_staff");
			$sql_entries_obj->prepare_sql_addfield("description", "timereg.description");
			$sql_entries_obj->prepare_sql_addfield("time_booked", "timereg.time_booked");
			$sql_entries_obj->prepare_sql_addfield("groupid", "timereg.groupid");
			$sql_entries_obj->prepare_sql_addfield("billable", "timereg.billable");
			
			$sql_entries_obj->prepare_sql_addjoin("LEFT JOIN staff ON timereg.employeeid = staff.id");
			$sql_entries_obj->prepare_sql_addjoin("LEFT JOIN project_phases ON timereg.phaseid = project_phases.id");

			if ($groupid)
			{
				$sql_entries_obj->prepare_sql_addwhere("groupid='$groupid' OR !groupid");
			}
			else
			{
				$sql_entries_obj->prepare_sql_addwhere("!groupid");
			}

			$sql_entries_obj->generate_sql();
			$sql_entries_obj->execute();

			if ($sql_entries_obj->num_rows())
			{
				$sql_entries_obj->fetch_array();

				foreach ($sql_entries_obj->data as $data)
				{
					// define the billable check box
					$structure = NULL;
					$structure["fieldname"]		= "time_". $data["id"] ."_bill";
					$structure["type"]		= "checkbox";
					$structure["options"]["label"]	= " ";

					if ($data["groupid"] == $groupid && $data["billable"] == "1")
					{
						$structure["defaultvalue"] = "on";
					}
					
					$form->add_input($structure);

					// define the nobill check box
					$structure = NULL;
					$structure["fieldname"]		= "time_". $data["id"] ."_nobill";
					$structure["type"]		= "checkbox";
					$structure["options"]["label"]	= " ";

					if ($data["groupid"] == $groupid && $data["billable"] == "0")
					{
						$structure["defaultvalue"] = "on";
					}
					
					$form->add_input($structure);

				}
			}


			// submit button
			$structure = NULL;
			$structure["fieldname"] 	= "submit";
			$structure["type"]		= "submit";
			if ($groupid)
			{
				$structure["defaultvalue"]	= "Save Changes";
			}
			else
			{
				$structure["defaultvalue"]	= "Create Time Group";
			}
			$form->add_input($structure);


			// fetch the form data if editing
			if ($groupid)
			{
				$form->sql_query = "SELECT * FROM time_groups WHERE id='$groupid' LIMIT 1";
				$form->load_data();
			}
			else
			{
				// load any data returned due to errors
				$form->load_data_error();
			}



			/*
				Display the form

				Because we need all the columns for the different time items, we have to do
				a custom display for this form.
			*/

			// start form
			print "<form enctype=\"multipart/form-data\" method=\"". $form->method ."\" action=\"". $form->action ."\" class=\"form_standard\">";


			// GENERAL INPUTS
			
			// start table
			print "<table class=\"form_table\" width=\"100%\">";

			// form header
			print "<tr class=\"header\">";
			print "<td colspan=\"2\"><b>". language_translate_string($form->language, "timebilled_details") ."</b></td>";
			print "</tr>";

			// display all the rows
			$form->render_row("name_group");
			$form->render_row("customerid");
			$form->render_row("description");


			// end table
			print "</table><br>";



			// TIME SELECTION

			print "<table class=\"form_table\" width=\"100%\">";
			print "<tr class=\"header\">";
			print "<td colspan=\"2\"><b>". language_translate_string($form->language, "timebilled_selection") ."</b></td>";
			print "</tr>";
			print "</table>";

			
			if ($sql_entries_obj->num_rows())
			{
				// start table
				print "<p>Select all the time that should belong to this group from the list below - this list only shows time currently unassigned to any group.</p>";
				print "<p>You can choose whether to add the time as billable or as unbillable. This is used to group hours that are unbilled, eg: internal paper work
				for the customer's account or other administrative overheads so that they won't continue to show in this list.</p>";

				
				print "<table class=\"table_content\" width=\"100%\">";

				// form header
				print "<tr class=\"header\">";
				print "<td><b>". language_translate_string($form->language, "date") ."</b></td>";
				print "<td><b>". language_translate_string($form->language, "name_phase") ."</b></td>";
				print "<td><b>". language_translate_string($form->language, "name_staff") ."</b></td>";
				print "<td><b>". language_translate_string($form->language, "description") ."</b></td>";
				print "<td><b>". language_translate_string($form->language, "time_booked") ."</b></td>";
				print "<td><b>". language_translate_string($form->language, "time_bill") ."</b></td>";
				print "<td><b>". language_translate_string($form->language, "time_nobill") ."</b></td>";
				print "</tr>";

				// display all the rows
				foreach ($sql_entries_obj->data as $data)
				{
					print "<tr>";
						print "<td>". $data["date"] ."</td>";
						print "<td>". $data["name_phase"] ."</td>";
						print "<td>". $data["name_staff"] ."</td>";
						print "<td>". $data["description"] ."</td>";
						print "<td>". time_format_hourmins($data["time_booked"]) ."</td>";

						print "<td>";
						$form->render_field("time_". $data["id"] ."_bill");
						print "</td>";

						print "<td>";
						$form->render_field("time_". $data["id"] ."_nobill");
						print "</td>";
						
					print "</tr>";
				}

				// end table
				print "</table><br>";
			}
			else
			{
				print "<p><i>There is currently no un-grouped time that can be selected.</i></p>";
			}





			// HIDDEN FIELDS
			$form->render_field("projectid");
			$form->render_field("groupid");


			// SUBMIT
			
			// start table
			print "<table class=\"form_table\" width=\"100%\">";

			// form header
			print "<tr class=\"header\">";
			print "<td colspan=\"2\"><b>". language_translate_string($form->language, "submit") ."</b></td>";
			print "</tr>";

			// display all the rows
			$form->render_row("submit");


			// end table
			print "</table><br>";


			// end form
			print "</form>";


		} // end if valid options

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
