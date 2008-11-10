<?php
/*
	projects/delete.php
	
	access: 	projects_write (write access)

	Tool to allow a project to be deleted, provided that there is no time booked
	to this project.
*/

if (user_permissions_get('projects_write'))
{
	$id = $_GET["id"];
	
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

	$_SESSION["nav"]["title"][]	= "Project Journal";
	$_SESSION["nav"]["query"][]	= "page=projects/journal.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Delete Project";
	$_SESSION["nav"]["query"][]	= "page=projects/delete.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=projects/delete.php&id=$id";


	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		print "<h3>PROJECT DELETE</h3><br>";
		print "<p>This page allows you to delete an unwanted project.</p>";

		$mysql_string	= "SELECT id FROM `projects` WHERE id='$id'";
		$mysql_result	= mysql_query($mysql_string);
		$mysql_num_rows	= mysql_num_rows($mysql_result);

		if (!$mysql_num_rows)
		{
			print "<p><b>Error: The requested project does not exist. <a href=\"index.php?page=projects/projects.php\">Try looking for your project on the project list page.</a></b></p>";
		}
		else
		{
			/*
				Define form structure
			*/
			$form = New form_input;
			$form->formname = "project_delete";
			$form->language = $_SESSION["user"]["lang"];

			$form->action = "projects/delete-process.php";
			$form->method = "post";
			

			// general
			$structure = NULL;
			$structure["fieldname"] 	= "name_project";
			$structure["type"]		= "text";
			$form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"] 	= "code_project";
			$structure["type"]		= "text";
			$form->add_input($structure);


			// hidden
			$structure = NULL;
			$structure["fieldname"] 	= "id_project";
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= "$id";
			$form->add_input($structure);

			
			// confirm delete
			$structure = NULL;
			$structure["fieldname"] 	= "delete_confirm";
			$structure["type"]		= "checkbox";
			$structure["options"]["label"]	= "Yes, I wish to delete this project and realise that once deleted the data can not be recovered.";
			$form->add_input($structure);


			// submit button
			// We check if any time bookings have been made against this project
			// and either provide a button or a message.
			$locked = 0;
			
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM project_phases WHERE projectid='$id'";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				$sql_obj->fetch_array();

				foreach ($sql_obj->data as $phase_data)
				{
					$sql_phase_obj			= New sql_query;
					$sql_phase_obj->string		= "SELECT id FROM timereg WHERE phaseid='". $phase_data["id"] ."'";
					$sql_phase_obj->execute();

					if ($sql_phase_obj->num_rows())
					{
						$locked = 1;
					}
				}
			}

			$structure = NULL;
			$structure["fieldname"] 	= "submit";

			if ($locked)
			{
				$structure["type"]		= "message";
				$structure["defaultvalue"]	= "<i>This project can not be deleted because time entries have been assigned to it.</i>";
			}
			else
			{
				$structure["type"]		= "submit";
				$structure["defaultvalue"]	= "delete";
			}
			
			$form->add_input($structure);

		
			// define subforms
			$form->subforms["project_delete"]	= array("code_project", "name_project");
			$form->subforms["hidden"]		= array("id_project");
			$form->subforms["submit"]		= array("delete_confirm", "submit");

			
			// fetch the form data
			$form->sql_query = "SELECT * FROM `projects` WHERE id='$id' LIMIT 1";		
			$form->load_data();

			// display the form
			$form->render_form();

		}

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
