<?php
/*
	projects/delete.php
	
	access: 	projects_write

	Tool to allow a project to be deleted, provided that there is no time booked
	to this project.
*/


class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_form;

	var $locked;
	

	function __construct()
	{
		// fetch variables
		$this->id = @security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Project Details", "page=projects/view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Project Phases", "page=projects/phases.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Timebooked", "page=projects/timebooked.php&id=". $this->id ."");
                $this->obj_menu_nav->add_item("Project Expenses", "page=projects/expenses.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Timebilled/Grouped", "page=projects/timebilled.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Project Journal", "page=projects/journal.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Delete Project", "page=projects/delete.php&id=". $this->id ."", TRUE);
	}



	function check_permissions()
	{
		return user_permissions_get("projects_write");
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
			Check if the project can be deleted or not
		*/

		// see if any time has been booked to any of the phases
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM project_phases WHERE projectid='". $this->id ."'";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$sql_obj->fetch_array();

			foreach ($sql_obj->data as $phase_data)
			{
				$sql_phase_obj			= New sql_query;
				$sql_phase_obj->string		= "SELECT id FROM timereg WHERE phaseid='". $phase_data["id"] ."' LIMIT 1";
				$sql_phase_obj->execute();

				if ($sql_phase_obj->num_rows())
				{
					$this->locked = 1;
				}
			}
		}

	
		/*
			Define form structure
		*/
		$this->obj_form = New form_input;
		$this->obj_form->formname = "project_delete";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "projects/delete-process.php";
		$this->obj_form->method = "post";
		

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "name_project";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "code_project";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);


		// hidden
		$structure = NULL;
		$structure["fieldname"] 	= "id_project";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->id;
		$this->obj_form->add_input($structure);

		
		// confirm delete
		$structure = NULL;
		$structure["fieldname"] 	= "delete_confirm";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "Yes, I wish to delete this project and realise that once deleted the data can not be recovered.";
		$this->obj_form->add_input($structure);


		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "delete";
		$this->obj_form->add_input($structure);

	
		// define subforms
		$this->obj_form->subforms["project_delete"]	= array("code_project", "name_project");
		$this->obj_form->subforms["hidden"]		= array("id_project");

		if ($this->locked)
		{
			$this->obj_form->subforms["submit"]	= array();
		}
		else
		{
			$this->obj_form->subforms["submit"]	= array("delete_confirm", "submit");
		}

		
		// fetch the form data
		$this->obj_form->sql_query = "SELECT * FROM `projects` WHERE id='". $this->id ."' LIMIT 1";
		$this->obj_form->load_data();

	}


	function render_html()
	{
		// Title + Summary
		print "<h3>PROJECT DELETE</h3><br>";
		print "<p>This page allows you to delete an unwanted project.</p>";

		// display the form
		$this->obj_form->render_form();

		if ($this->locked)
		{
			format_msgbox("locked", "<p>This project now has time booked to it and can not be deleted. If the project has been finished, you can close it by setting the End Date field on the details page.</p>");
		}
	}
}


?>
