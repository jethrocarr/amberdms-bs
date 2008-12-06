<?php
/*
	projects/add.php
	
	access: projects_write

	Form to add a new project to the database.

*/

class page_output
{
	var $obj_form;	// page form


	function check_permissions()
	{
		return user_permissions_get("projects_write");
	}

	function check_requirements()
	{
		// nothing todo
		return 1;
	}


	function execute()
	{	
		/*
			Define form structure
		*/
		$this->obj_form = New form_input;
		$this->obj_form->formname = "project_add";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "projects/edit-process.php";
		$this->obj_form->method = "post";
	
	
		// general
		$structure = NULL;
		$structure["fieldname"] 	= "name_project";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "code_project";
		$structure["type"]		= "input";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "date_start";
		$structure["type"]		= "date";
		$structure["defaultvalue"]	= date("Y-m-d");
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] = "date_end";
		$structure["type"]	= "date";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "details";
		$structure["type"]		= "textarea";
		$this->obj_form->add_input($structure);


		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Create Project";
		$this->obj_form->add_input($structure);
		

		// define subforms
		$this->obj_form->subforms["project_view"]	= array("code_project", "name_project", "date_start", "date_end", "details");
		$this->obj_form->subforms["submit"]		= array("submit");
		
		// load any data returned due to errors
		$this->obj_form->load_data_error();

	}

	function render_html()
	{
		// Title + Summary
		print "<h3>ADD NEW PROJECT</h3><br>";
		print "<p>This page allows you to add a new project.</p>";


		// display the form
		$this->obj_form->render_form();
	}

}

?>
