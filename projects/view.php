<?php
/*
	projects/view.php
	
	access: projects_view (read-only)
		projects_write (write access)

	Displays all the details for the project and if the user has correct
	permissions allows the project to be updated.
*/


class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_form;


	function page_output()
	{
		// fetch variables
		$this->id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Project Details", "page=projects/view.php&id=". $this->id ."", TRUE);
		$this->obj_menu_nav->add_item("Project Phases", "page=projects/phases.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Timebooked", "page=projects/timebooked.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Timebilled/Grouped", "page=projects/timebilled.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Project Journal", "page=projects/journal.php&id=". $this->id ."");

		if (user_permissions_get("projects_write"))
		{
			$this->obj_menu_nav->add_item("Delete Project", "page=projects/delete.php&id=". $this->id ."");
		}
	}



	function check_permissions()
	{
		return user_permissions_get("projects_view");
	}



	function check_requirements()
	{
		// verify that project exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM projects WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested project (". $this->id .") does not exist - possibly the project has been deleted.");
			return 0;
		}

		unset($sql_obj);


		return 1;
	}


	function execute()
	{
		/*
			Define form structure
		*/
		$this->obj_form = New form_input;
		$this->obj_form->formname = "project_view";
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
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "date_end";
		$structure["type"]		= "date";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "internal_only";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "This is an internal project - do not alert to unbilled hours";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "details";
		$structure["type"]		= "textarea";
		$this->obj_form->add_input($structure);


		// submit section
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Save Changes";
		$this->obj_form->add_input($structure);
		
		// hidden
		$structure = NULL;
		$structure["fieldname"] 	= "id_project";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->id;
		$this->obj_form->add_input($structure);


		
		// define subforms
		$this->obj_form->subforms["project_view"]		= array("code_project", "name_project", "date_start", "date_end", "internal_only", "details");
		$this->obj_form->subforms["hidden"]			= array("id_project");
		
		if (user_permissions_get("projects_write"))
		{
			$this->obj_form->subforms["submit"]		= array("submit");
		}
		else
		{
			$this->obj_form->subforms["submit"]		= array();
		}

		
		// fetch the form data
		$this->obj_form->sql_query = "SELECT * FROM `projects` WHERE id='". $this->id ."' LIMIT 1";
		$this->obj_form->load_data();
	}
	

	function render_html()
	{
		// title + summary
		print "<h3>PROJECT DETAILS</h3><br>";
		print "<p>This page allows you to view and adjust the project's records.</p>";

		// display the form
		$this->obj_form->render_form();
		
		if (!user_permissions_get("projects_write"))
		{
			format_msgbox("locked", "<p>Sorry, you do not have permissions to adjust the project details.</p>");
		}
	}
}

?>
