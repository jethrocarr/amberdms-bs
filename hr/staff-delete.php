<?php
/*
	staff/delete.php
	
	access:	staff_write

	Allows an unwanted employee to be deleted.
*/

// custom includes
require("include/hr/inc_staff.php");


class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_form;

	var $locked;


	function page_output()
	{
		// fetch variables
		$this->id = @security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Employee's Details", "page=hr/staff-view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Timesheet", "page=hr/staff-timebooked.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Employee's Journal", "page=hr/staff-journal.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Delete Employee", "page=hr/staff-delete.php&id=". $this->id ."", TRUE);
	}



	function check_permissions()
	{
		return user_permissions_get("staff_write");
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
			Check that the employee can be deleted
		*/

		$obj_employee		= New hr_staff;
		$obj_employee->id	= $this->id;

		$this->locked		= $obj_employee->check_lock();

		unset($obj_employee);

		
	
		/*
			Define form structure
		*/
		$this->obj_form = New form_input;
		$this->obj_form->formname = "staff_delete";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "hr/staff-delete-process.php";
		$this->obj_form->method = "post";
		

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "name_staff";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);


		// hidden
		$structure = NULL;
		$structure["fieldname"] 	= "id_staff";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->id;
		$this->obj_form->add_input($structure);
		
		
		// confirm delete
		$structure = NULL;
		$structure["fieldname"] 	= "delete_confirm";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "Yes, I wish to delete this employee and realise that once deleted the data can not be recovered.";
		$this->obj_form->add_input($structure);





		// define submit field
		$structure = NULL;
		$structure["fieldname"] = "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "delete";
		$this->obj_form->add_input($structure);


		
		// define subforms
		$this->obj_form->subforms["staff_delete"]	= array("name_staff");
		$this->obj_form->subforms["hidden"]		= array("id_staff");

		if ($this->locked)
		{
			$this->obj_form->subforms["submit"]	= array();
		}
		else
		{
			$this->obj_form->subforms["submit"]	= array("delete_confirm", "submit");
		}

		
		// fetch the form data
		$this->obj_form->sql_query = "SELECT name_staff FROM `staff` WHERE id='". $this->id ."' LIMIT 1";
		$this->obj_form->load_data();
	}


	function render_html()
	{
		// Title + Summary
		print "<h3>DELETE EMPLOYEE</h3><br>";
		print "<p>This page allows you to delete an unwanted employee. Note that it is only possible to delete a employee if they have had no payments and have not booked any time. If they do, you can not delete the employee, but instead you can disable the employee by setting the date_end field.</p>";

		// display the form
		$this->obj_form->render_form();

		if ($this->locked)
		{
			format_msgbox("locked", "<p>This employee has made various entries in the billing system and can no-longer be removed. Set the End Date field on the Employee Details page to show that the employee has left instead.</p>");
		}
	}

} // end of page_output

?>
