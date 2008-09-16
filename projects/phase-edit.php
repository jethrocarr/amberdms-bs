<?php
/*
	projects/phase-edit.php
	
	access: projects_write

	Form to add or edit a project phase.
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
	$_SESSION["nav"]["current"]	= "page=projects/phases.php&id=$id";
	
	$_SESSION["nav"]["title"][]	= "Timebooked";
	$_SESSION["nav"]["query"][]	= "page=projects/timebooked.php&id=$id";
	
	$_SESSION["nav"]["title"][]	= "Project Journal";
	$_SESSION["nav"]["query"][]	= "page=projects/journal.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Delete Project";
	$_SESSION["nav"]["query"][]	= "page=projects/delete.php&id=$id";


	function page_render()
	{
		$projectid	= security_script_input('/^[0-9]*$/', $_GET["projectid"]);
		$phaseid	= security_script_input('/^[0-9]*$/', $_GET["phaseid"]);


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
				// are we editing an existing phase? make sure it exists and belongs to this project
				$mysql_string	= "SELECT projectid FROM `project_phases` WHERE id='$phaseid'";
				$mysql_result	= mysql_query($mysql_string);
				$mysql_num_rows	= mysql_num_rows($mysql_result);

				if (!$mysql_num_rows)
				{
					print "<p><b>Error: The requested phase does not exist.</b></p>";
					$error = 1;
				}
				else
				{
					$mysql_data = mysql_fetch_array($mysql_result);

					if ($mysql_data["projectid"] != $projectid)
					{
						print "<p><b>Error: The requested phase does not match the provided project ID. Potential application bug?</b></p>";
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
			if ($phaseid)
			{
				print "<h3>EDIT PHASE</h3><br>";
				print "<p>This page allows you to modifiy a project phase.</p>";
			}
			else
			{
				print "<h3>ADD NEW PHASE</h3><br>";
				print "<p>This page allows you to add a new phase to a project.</p>";
			}
			

			/*
				Define form structure
			*/
			$form = New form_input;
			$form->formname = "phase_view";
			$form->language = $_SESSION["user"]["lang"];

			$form->action = "projects/phase-edit-process.php";
			$form->method = "post";
		
		
			// general
			$structure = NULL;
			$structure["fieldname"] 	= "name_phase";
			$structure["type"]		= "input";
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
			$structure["fieldname"]		= "phaseid";
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= $phaseid;
			$form->add_input($structure);
			


			// submit button
			$structure = NULL;
			$structure["fieldname"] 	= "submit";
			$structure["type"]		= "submit";
			if ($phaseid)
			{
				$structure["defaultvalue"]	= "Save Changes";
			}
			else
			{
				$structure["defaultvalue"]	= "Create Phase";
			}
			$form->add_input($structure);


			// define subforms
			$form->subforms["phase_edit"]		= array("name_phase", "description");
			$form->subforms["hidden"]		= array("projectid", "phaseid");
			$form->subforms["submit"]		= array("submit");
	

			// fetch the form data if editing
			if ($phaseid)
			{
				$form->sql_query = "SELECT * FROM `project_phases` WHERE id='$phaseid' LIMIT 1";
				$form->load_data();
			}
			else
			{
				// load any data returned due to errors
				$form->load_data_error();
			}


			// display the form
			$form->render_form();

		} // end if valid options

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
