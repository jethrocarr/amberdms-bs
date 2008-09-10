<?php
/*
	projects/add.php
	
	access: projects_write

	Form to add a new project to the database.

*/

if (user_permissions_get('projects_write'))
{
	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		print "<h3>ADD NEW PROJECT</h3><br>";
		print "<p>This page allows you to add a new project.</p>";

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


		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Create Project";
		$form->add_input($structure);
		

		// define subforms
		$form->subforms["project_view"]		= array("code_project", "name_project", "date_start", "date_end", "details");
		$form->subforms["submit"]		= array("submit");
		
		// display the form
		$form->render_form();

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
