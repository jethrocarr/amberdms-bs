<?php
/*
	timekeeping/timereg-day-edit.php

	access:
		timekeeping + timereg_write/employee	FULL ACCESS (for specific employees)
		timekeeping_all_write			FULL ACCESS

	optional:
		projects_write				Displays optional add project/phase form


	Allows a time record to be added or adjusted.
*/


class page_output
{

	var $id;
	var $employeeid;
	
	var $date;

	var $obj_menu_nav;
	var $obj_form;

	var $locked;
	var $groupid;
	var $access_staff_ids_write;
	

	function page_output()
	{
		//include required JS and CSS
		$this->requires["javascript"][]		= "include/timekeeping/javascript/timereg-day-edit.js";
		$this->requires["css"][]		= "include/timekeeping/css/timereg-day-edit.css";
		
		// get time record ID to edit
		$this->id	= @security_script_input('/^[0-9]*$/', $_GET["id"]);
		
		// get selected employee
		$this->employeeid	= @security_script_input('/^[0-9]*$/', $_GET["employeeid"]);

		if ($this->employeeid)
		{
			// save to session vars
			$_SESSION["form"]["timereg"]["employeeid"] = $this->employeeid;
		}
		else
		{
			// load from session vars
			if ($_SESSION["form"]["timereg"]["employeeid"])
				$this->employeeid = $_SESSION["form"]["timereg"]["employeeid"];
		}


		// get selected date (optional)
		$this->date	= @security_script_input('/^\S*$/', $_GET["date"]);

		if ($this->date)
		{
			// save to session vars
			$_SESSION["timereg"]["date"] = $this->date;
		}
		else
		{
			// load from session vars
			if ($_SESSION["timereg"]["date"])
				$this->date = $_SESSION["timereg"]["date"];
		}


		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		if (!empty($_SESSION["timereg"]["year"]))
		{
			$this->obj_menu_nav->add_item("Weekview", "page=timekeeping/timereg.php&year=". $_SESSION["timereg"]["year"] ."&weekofyear=". $_SESSION["timereg"]["weekofyear"]."");
		}

		if ($this->date)
		{
			$this->obj_menu_nav->add_item("Day View", "page=timekeeping/timereg-day.php&date=". $this->date ."", TRUE);
		}
	}



	function check_permissions()
	{
		if (user_permissions_get("timekeeping"))
		{
			// check if user has permissions to write as the selected employee
			if ($this->employeeid)
			{
				if (!user_permissions_staff_get("timereg_write", $this->employeeid))
				{
					log_write("error", "page_output", "Sorry, you do not have permissions to adjust the timesheet for the selected employee");
					return 0;
				}
			}

	
			// accept user if they have write access to all staff
			if (user_permissions_get("timekeeping_all_write"))
			{
				return 1;
			}

			// select the IDs that the user does have write access to
			if ($this->access_staff_ids_write = user_permissions_staff_getarray("timereg_write"))
			{
				return 1;
			}
			else
			{
				log_render("error", "page", "Before you can add or edit timesheet hours, your administrator must configure the staff accounts you may access, or set the timekeeping_all_write permission.");
			}
		}
	}



	function check_requirements()
	{
		// nothing todo
		return 1;
	}



	function execute()
	{
		/*
			Check if time entry can be adjusted
		*/
		if ($this->id)
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT locked, groupid FROM `timereg` WHERE id='". $this->id ."' LIMIT 1";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				$sql_obj->fetch_array();

				$this->locked	= $sql_obj->data[0]["locked"];		// so we can tell if the time is locked
				$this->groupid	= $sql_obj->data[0]["groupid"];		// tells us what group id the time belongs to
			}

			unset($sql_obj);
		}


	
		/*
			Input Form

			Allows the creation of a new entry for the day, or the adjustment of an existing one.
		*/
	
		
		$this->obj_form = New form_input;
		$this->obj_form->formname = "timereg_day";
		$this->obj_form->language = $_SESSION["user"]["lang"];
		
		$this->obj_form->action = "timekeeping/timereg-day-edit-process.php";
		$this->obj_form->method = "post";
			
			
		// hidden stuff
		$structure = NULL;
		$structure["fieldname"] 	= "id_timereg";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->id;
		$this->obj_form->add_input($structure);
	

		// employee selection box
		$sql_obj = New sql_query;
		$sql_obj->prepare_sql_settable("staff");
		$sql_obj->prepare_sql_addfield("id", "id");
		$sql_obj->prepare_sql_addfield("label", "staff_code");
		$sql_obj->prepare_sql_addfield("label1", "name_staff");
		
		if ($this->access_staff_ids_write)
		{
			$sql_obj->prepare_sql_addwhere("id IN (". format_arraytocommastring($this->access_staff_ids_write) .")");
		}

		$sql_obj->generate_sql();

		$structure = form_helper_prepare_dropdownfromdb("employeeid", $sql_obj->string);

		// if there is currently no employee set, and there is only one
		// employee in the selection box, automatically select it and update
		// the session variables.
		
		if (!$this->employeeid && count($structure["values"]) == 1)
		{
			$this->employeeid				= $structure["values"][0];
			$_SESSION["form"]["timereg"]["employeeid"]	= $structure["values"][0];
		}
		
		$structure["options"]["autoselect"]	= "on";
		$structure["options"]["width"]		= "600";
		$structure["options"]["search_filter"]	= "yes";
		$structure["defaultvalue"]		= $this->employeeid;
		$this->obj_form->add_input($structure);
				

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "date";
		$structure["type"]		= "date";
		$structure["defaultvalue"]	= $this->date;
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "time_booked";
		$structure["type"]		= "hourmins";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]		= "description";
		$structure["type"]		= "textarea";
		$structure["options"]["req"]	= "yes";
		$structure["options"]["width"]	= "600";
		$structure["options"]["height"]	= "60";
		$this->obj_form->add_input($structure);

		//project dropdown
		$sql_struct_obj	= New sql_query;
		$sql_struct_obj->prepare_sql_settable("projects");
		$sql_struct_obj->prepare_sql_addfield("id", "projects.id");
		$sql_struct_obj->prepare_sql_addfield("label", "projects.code_project");
		$sql_struct_obj->prepare_sql_addfield("label1", "projects.name_project");
		$sql_struct_obj->prepare_sql_addorderby("code_project");
		$sql_struct_obj->prepare_sql_addwhere("id = 'CURRENTID' OR date_end = '0000-00-00'");
		
		$structure = form_helper_prepare_dropdownfromobj("projectid", $sql_struct_obj);
		$structure["options"]["autoselect"]	= "on";
		$structure["options"]["width"]		= "600";
		$structure["options"]["search_filter"]	= "yes";

		if (count($structure["values"]) == 0)
		{
			$structure["defaultvalue"] = "You need to create a project and add a phase to it in order to be able to book time.";
			$_SESSION["error"]["phaseid-error"] = 1;
		}

		$this->obj_form->add_input($structure);
		
		
		//phase dropdown
		$structure = NULL;
		$structure["fieldname"]			= "phaseid";
		$structure["type"]			= "dropdown";
		$structure["values"]			= array("");
		$structure["options"]["width"]		= "600";
		$structure["options"]["disabled"]	= "yes";
		$structure["options"]["search_filter"]	= "yes";
		$this->obj_form->add_input($structure);
		
		//add project field
		$structure = NULL;
		$structure["fieldname"]			= "add_project";
		$structure["type"]			= "input";
		$structure["options"]["no_fieldname"]	= "yes";
		$structure["options"]["no_shift"]	= "yes";
		$structure["options"]["prelabel"]	= "<div id=\"add_project_box\"><span id=\"toggle_add_project\">
								<strong>Add New Project</strong>
								<div class=\"half_sized_break_line\"><br/></div>
								New Project: ";
		$structure["options"]["label"] 		= "&nbsp;<a class=\"insert_project_phase button_small\" id=\"insert_project\" href=\"\">Add</a>
								</span><div class=\"half_sized_break_line\"><br/></div>
								<strong><a id=\"project_add_cancel\" class=\"add_link\" href=\"\">Add New Project</a></strong></div>";
		$this->obj_form->add_input($structure);	

		//add phase field
		$structure = NULL;
		$structure["fieldname"]			= "add_phase";
		$structure["type"]			= "input";
		$structure["options"]["no_fieldname"]	= "yes";
		$structure["options"]["no_shift"]	= "yes";
		$structure["options"]["prelabel"]	= "<div id=\"add_phase_box\"><span id=\"toggle_add_phase\">
								<strong>Add Phase to Current Project</strong>
								<div class=\"half_sized_break_line\"><br/></div>
								New Phase: ";
		$structure["options"]["label"] 		= "&nbsp;<a class=\"insert_project_phase button_small\" id=\"insert_phase\" href=\"\">Add</a>
								</span><div class=\"half_sized_break_line\"><br/></div>
								<strong><a id=\"phase_add_cancel\" class=\"add_link\" href=\"\">Add Phase to Current Project</a></strong></div>";
		$this->obj_form->add_input($structure);	
		
						
		// submit section
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Save Changes";
		$this->obj_form->add_input($structure);
		
		
		// define subforms
		if (user_permissions_get("projects_write"))
		{
			$this->obj_form->subforms["timereg_day"]	= array("employeeid", "projectid", "phaseid", "add_project", "add_phase", "date", "time_booked", "description");
		}
		else
		{
			$this->obj_form->subforms["timereg_day"]	= array("employeeid", "projectid", "phaseid", "date", "time_booked", "description");
		}

		$this->obj_form->subforms["hidden"]		= array("id_timereg");

		if ($this->locked)
		{
			$this->obj_form->subforms["submit"]	= array();
		}
		else
		{
			$this->obj_form->subforms["submit"]	= array("submit");
		}
		
		
		
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM `timereg` WHERE id='". $this->id ."' LIMIT 1";
		
		$sql_obj->execute();
		
		if ($sql_obj->num_rows())
		{
			// fetch the form data
			$this->obj_form->sql_query = "SELECT * FROM `timereg` WHERE id='". $this->id ."' LIMIT 1";
			$this->obj_form->load_data();

			$this->obj_form->structure['projectid']['defaultvalue'] = sql_get_singlevalue("SELECT project_phases.projectid AS value FROM timereg LEFT JOIN project_phases ON project_phases.id = timereg.phaseid WHERE timereg.id='". $this->id ."'");
		}
		else
		{
			// load any data returned due to errors
			$this->obj_form->load_data_error();
		}

	}



	function render_html()
	{
		// title + summary
		if ($this->id)
		{
			print "<h3>ADJUST TIME RECORD</h3><br><br>";
		}
		else
		{
			print "<h3>ADD TIME RECORD</h3><br><br>";
		}

		// display the form
		$this->obj_form->render_form();

		if ($this->locked)
		{
			if ($this->groupid)
			{
				// fetch the details about the group
				$sql_obj		= New sql_query;
				$sql_obj->string	= "SELECT name_group, projectid FROM time_groups WHERE id='". $this->groupid ."' LIMIT 1";
				$sql_obj->execute();
				$sql_obj->fetch_array();

				format_msgbox("locked", "<p>This time entry is part of <a href=\"index.php?page=projects/timebilled-edit.php&id=". $sql_obj->data[0]["projectid"] ."&groupid=". $this->groupid ."\">time group \"". $sql_obj->data[0]["name_group"] ."\"</a> and can no longer be edited.</p>");
			}
			else
			{
				format_msgbox("locked", "<p>This time entry has now been locked and can no longer be adjusted.</p>");
			}
		}

	}

}


?>
