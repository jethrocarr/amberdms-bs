<?php
/*
	projects/view.php
	
	access: projects_view (read-only)
		projects_write (write access)

	Displays all the details for the project and if the user has correct
	permissions allows the project to be updated.
*/

if (user_permissions_get('projects_view'))
{
	$id = $_GET["id"];
	
	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "Project Details";
	$_SESSION["nav"]["query"][]	= "page=projects/view.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=projects/view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Timebooked";
	$_SESSION["nav"]["query"][]	= "page=projects/timebooked.php&id=$id";
	
	$_SESSION["nav"]["title"][]	= "Project Journal";
	$_SESSION["nav"]["query"][]	= "page=projects/journal.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Delete Project";
	$_SESSION["nav"]["query"][]	= "page=projects/delete.php&id=$id";


	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		print "<h3>PROJECT DETAILS</h3><br>";
		print "<p>This page allows you to view and adjust the project's records.</p>";

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
			$form->formname = "project_view";
			$form->language = $_SESSION["user"]["lang"];

			$form->action = "projects/edit-process.php";
			$form->method = "post";
			

			// general
			$structure = NULL;
			$structure["fieldname"] 	= "id_project";
			$structure["type"]		= "text";
			$structure["defaultvalue"]	= "$id";
			$form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"] 	= "name_project";
			$structure["type"]		= "input";
			$structure["options"]["req"]	= "yes";
			$form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"] 	= "code_project";
			$structure["type"]		= "input";
			$form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"] = "date_start";
			$structure["type"]	= "date";
			$form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"] = "date_end";
			$structure["type"]	= "date";
			$form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"] 	= "details";
			$structure["type"]		= "textarea";
			$form->add_input($structure);


			// submit section
			if (user_permissions_get("projects_write"))
			{
				$structure = NULL;
				$structure["fieldname"] 	= "submit";
				$structure["type"]		= "submit";
				$structure["defaultvalue"]	= "Save Changes";
				$form->add_input($structure);
			
			}
			else
			{
				$structure = NULL;
				$structure["fieldname"] 	= "submit";
				$structure["type"]		= "message";
				$structure["defaultvalue"]	= "<p><i>Sorry, you don't have permissions to make changes to project records.</i></p>";
				$form->add_input($structure);
			}
			
			
			// define subforms
			$form->subforms["project_view"]		= array("id_project", "code_project", "name_project", "date_start", "date_end", "details");
			$form->subforms["submit"]		= array("submit");

			
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
