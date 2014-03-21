<?php
/*
	hr/staff-view.php

	access: staff_view (read-only)
		staff_write (write access)

	Displays the details of the selected staff member, and if the user
	has write permissions allows the staff member to be adjusted.
*/

class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_form;


	function page_output()
	{
		// fetch variables
		$this->id = @security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Employee's Details", "page=hr/staff-view.php&id=". $this->id ."", TRUE);
		$this->obj_menu_nav->add_item("Timesheet", "page=hr/staff-timebooked.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Employee's Journal", "page=hr/staff-journal.php&id=". $this->id ."");

		if (user_permissions_get("staff_write"))
		{
			$this->obj_menu_nav->add_item("Delete Employee", "page=hr/staff-delete.php&id=". $this->id ."");
		}
	}



	function check_permissions()
	{
		return user_permissions_get("staff_view");
	}



	function check_requirements()
	{
		// verify that staff exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM staff WHERE id='". $this->id ."'";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested employee (". $this->id .") does not exist - possibly the employee has been deleted.");
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
		$this->obj_form->formname = "staff_view";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "hr/staff-edit-process.php";
		$this->obj_form->method = "post";
		

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "name_staff";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] = "staff_code";
		$structure["type"]	= "input";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] = "staff_position";
		$structure["type"]	= "input";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] = "contact_email";
		$structure["type"]	= "input";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] = "contact_phone";
		$structure["type"]	= "input";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] = "contact_fax";
		$structure["type"]	= "input";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]		= "date_start";
		$structure["type"]		= "date";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] = "date_end";
		$structure["type"]	= "date";
		$this->obj_form->add_input($structure);


		// hidden section
		$structure = NULL;
		$structure["fieldname"] 	= "id_staff";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->id;
		$this->obj_form->add_input($structure);
		

		// submit section
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Save Changes";
		$this->obj_form->add_input($structure);
	
	
		// define subforms
		$this->obj_form->subforms["staff_view"]		= array("name_staff", "staff_code", "staff_position", "contact_phone", "contact_fax", "contact_email", "date_start", "date_end");
		$this->obj_form->subforms["hidden"]		= array("id_staff");
		
		if (user_permissions_get("staff_write"))
		{
			$this->obj_form->subforms["submit"] = array("submit");
		}
		else
		{
			$this->obj_form->subforms["submit"] = array();
		}

		
		// fetch the form data
		$this->obj_form->sql_query = "SELECT * FROM `staff` WHERE id='". $this->id ."' LIMIT 1";
		$this->obj_form->load_data();

	}



	function render_html()
	{
		// Title + Summary
		print "<h3>EMPLOYEE'S DETAILS</h3><br>";
		print "<p>This page allows you to view and adjust the employee's records.</p>";
		
		// display the form
		$this->obj_form->render_form();

		if (!user_permissions_get("staff_write"))
		{
			format_msgbox("locked", "<p>Sorry, you do not have permissions to adjust employee information.</p>");
		}
	}
	
} // end of page_output class


?>
