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
			$this->locked = sql_get_singlevalue("SELECT locked as value FROM `timereg` WHERE id='". $this->id ."' LIMIT 1");
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
		
		$structure = NULL;
		$structure["fieldname"] 	= "id_employee";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->employeeid;
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
		$this->obj_form->add_input($structure);


		// TODO: Update this to use the new helper functions

		// get data from DB and create project/phase dropdown
		//
		// Note: this hasn't yet been reduced to use the form_helper_prepare_dropdownfromdb function
		// because of the fact that it needs to merge two different fields to make the drop down label.
		//
		// This a pretty rare occurance, so it's likely this will be left as it is.
		//
		$structure = NULL;
		$structure["fieldname"] 	= "phaseid";
		$structure["type"]		= "dropdown";
		$structure["options"]["req"]	= "yes";

		$sql_obj = New sql_query;
		$sql_obj->string = "SELECT "
				."projects.name_project, "
				."project_phases.id as phaseid, "
				."project_phases.name_phase "
				."FROM `projects` "
				."LEFT JOIN project_phases ON project_phases.projectid = projects.id "
				."ORDER BY "
				."projects.name_project, "
				."project_phases.name_phase";
		
		$sql_obj->execute();	
		
		if ($sql_obj->num_rows())
		{
			$sql_obj->fetch_array();
			foreach ($sql_obj->data as $data)
			{
				// only add a project if there is a phaseid for it
				if ($data["phaseid"])
				{
					$structure["values"][]				= $data["phaseid"];
					$structure["translations"][ $data["phaseid"] ]	= $data["name_project"] ." - ". $data["name_phase"];
				}
			}
		}
				
		$this->obj_form->add_input($structure);
		
						
		// submit section
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Save Changes";
		$this->obj_form->add_input($structure);
		
		
		// define subforms
		$this->obj_form->subforms["timereg_day"]	= array("phaseid", "date", "time_booked", "description");
		$this->obj_form->subforms["hidden"]		= array("id_timereg", "id_employee");

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
			print "<h3>ADJUST TIME RECORD</h3>";
		}
		else
		{
			print "<h3>ADD TIME RECORD</h3>";
		}

		// display the form
		$this->obj_form->render_form();

		if ($this->locked)
		{
			format_msgbox("locked", "<p>This time entry is part of a time group and has now been locked</p>");
		}

	}

}


?>
