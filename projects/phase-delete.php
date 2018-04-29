<?php
/*
	projects/phase-delete.php
	
	access: projects_write

	Form to delete a project phase.
*/

class page_output
{
	var $id;
	var $phaseid;
	
	var $obj_menu_nav;
	var $obj_form;


	function __construct()
	{
		// fetch variables
		$this->id	= @security_script_input('/^[0-9]*$/', $_GET["id"]);
		$this->phaseid	= @security_script_input('/^[0-9]*$/', $_GET["phaseid"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Project Details", "page=projects/view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Project Phases", "page=projects/phases.php&id=". $this->id ."", TRUE);
		$this->obj_menu_nav->add_item("Timebooked", "page=projects/timebooked.php&id=". $this->id ."");
                $this->obj_menu_nav->add_item("Project Expenses", "page=projects/expenses.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Timebilled/Grouped", "page=projects/timebilled.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Project Journal", "page=projects/journal.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Delete Project", "page=projects/delete.php&id=". $this->id ."");
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
		
		// verify that phase exists and belongs to this project
		if ($this->phaseid)
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT projectid FROM project_phases WHERE id='". $this->phaseid ."' LIMIT 1";
			$sql_obj->execute();

			if (!$sql_obj->num_rows())
			{
				log_write("error", "page_output", "The requested phase (". $this->phaseid .") does not exist - possibly the phase has been deleted.");
				return 0;
			}
			else
			{
				$sql_obj->fetch_array();

				if ($sql_obj->data[0]["projectid"] != $this->id)
				{
					log_write("error", "page_output", "The requested phase (". $this->phaseid .") does not belong to the selected project (". $this->id .")");
					return 0;
				}
			}
			
			unset($sql_obj);
		}

		return 1;
	}


	function execute()
	{
		/*
			Check if the phase can be deleted
		*/
	
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM timereg WHERE phaseid='". $this->phaseid ."' LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$this->locked = 1;
		}



	
		/*
			Define form structure
		*/
		$this->obj_form = New form_input;
		$this->obj_form->formname = "phase_delete";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "projects/phase-delete-process.php";
		$this->obj_form->method = "post";
	
	
		// general
		$structure = NULL;
		$structure["fieldname"] 	= "name_phase";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "description";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);


		// hidden values
		$structure = NULL;
		$structure["fieldname"]		= "projectid";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->id;
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"]		= "phaseid";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->phaseid;
		$this->obj_form->add_input($structure);
		

		// confirm delete
		$structure = NULL;
		$structure["fieldname"] 	= "delete_confirm";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "Yes, I wish to delete this phase and realise that once deleted the data can not be recovered.";
		$this->obj_form->add_input($structure);


		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "delete";
		$this->obj_form->add_input($structure);


		// define subforms
		$this->obj_form->subforms["phase_edit"]		= array("name_phase", "description");
		$this->obj_form->subforms["hidden"]		= array("projectid", "phaseid");

		if ($this->locked)
		{
			$this->obj_form->subforms["submit"]	= array();
		}
		else
		{
			$this->obj_form->subforms["submit"]	= array("delete_confirm", "submit");
		}


		// fetch the form data if editing
		$this->obj_form->sql_query = "SELECT * FROM `project_phases` WHERE id='". $this->phaseid ."' LIMIT 1";
		$this->obj_form->load_data();
	}



	function render_html()
	{
		// Title + Summary
		print "<h3>DELETE PHASE</h3><br>";
		print "<p>This page allows you to delete a project phase.</p>";

		// display the form
		$this->obj_form->render_form();

		if ($this->locked)
		{
			format_msgbox("locked", "<p>This phase can no-longer be deleted since time has now been booked against it.</p>");
		}
	}

}

?>
