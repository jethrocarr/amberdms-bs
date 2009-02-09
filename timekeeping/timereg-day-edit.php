<?php
/*
	timekeeping/timereg-day-edit.php
	
	access: timereg_edit

	Allows a time record to be added or adjusted.
*/


// custom includes
require("include/user/permissions_staff.php");



class page_output
{

	var $id;
	var $employeeid;
	
	var $date;

	var $obj_menu_nav;
	var $obj_form;

	var $locked;
	var $groupid;
	

	function page_output()
	{
		// get time record ID to edit
		$this->id	= security_script_input('/^[0-9]*$/', $_GET["id"]);
		
		// get selected employee
		$this->employeeid	= security_script_input('/^[0-9]*$/', $_GET["employeeid"]);

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
		$this->date	= security_script_input('/^\S*$/', $_GET["date"]);

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

		if ($_SESSION["timereg"]["year"])
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
		return user_permissions_get("timekeeping");

		// we do an additional check in the check_requirements function
	}



	function check_requirements()
	{
		// make sure the user actually has access to some employees - if not,
		// it means that they can not book or view time
		
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM `users_permissions_staff` WHERE userid='". $_SESSION["user"]["id"] ."'";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "Sorry, you are currently unable to book time - you need your administrator to configure you with staff access rights.");
			return 0;
		}

		unset($sql_obj);


		// check if user has permissions to write as the selected employee
		if ($this->employeeid)
		{
			if (!user_permissions_staff_get("timereg_write", $this->employeeid))
			{
				log_write("error", "page_output", "Sorry, you do not have permissions to adjust the timesheet for the selected employee");
				return 0;
			}
		}
		
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
		$sql_string = "SELECT "
				."staff.id as id, "
				."staff.name_staff as label "
				."FROM users_permissions_staff "
				."LEFT JOIN staff ON staff.id = users_permissions_staff.staffid "
				."WHERE users_permissions_staff.userid='". $_SESSION["user"]["id"] ."' "
				."GROUP BY users_permissions_staff.staffid "
				."ORDER BY staff.name_staff";
				
		$structure = form_helper_prepare_dropdownfromdb("employeeid", $sql_string);

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


		// project/phase dropdown
		$structure = form_helper_prepare_dropdownfromdb("phaseid", "SELECT projects.name_project as label,
											project_phases.id as id, 
											project_phases.name_phase as label1
											FROM `projects` 
											LEFT JOIN project_phases ON project_phases.projectid = projects.id
											ORDER BY projects.name_project, project_phases.name_phase");

		$structure["options"]["autoselect"]	= "on";
		$structure["options"]["width"]		= "600";

		if (count($structure["values"]) == 0)
		{
			$structure["defaultvalue"] = "You need to create a project and add a phase to it in order to be able to book time.";
			$_SESSION["error"]["phaseid-error"] = 1;
		}

		$this->obj_form->add_input($structure);
		
						
		// submit section
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Save Changes";
		$this->obj_form->add_input($structure);
		
		
		// define subforms
		$this->obj_form->subforms["timereg_day"]	= array("employeeid", "phaseid", "date", "time_booked", "description");
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
