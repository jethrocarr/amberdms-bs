<?php
/*
	hr/staff-add.php

	access: staff_write

	Allows the creation of a new employee - this form
	requests mininal details, then directs the user to the view/edit form
	so that they can complete the changes.
*/


class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_form;

	function check_permissions()
	{
		return user_permissions_get("staff_view");
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
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Save Changes";
		$this->obj_form->add_input($structure);
		
		// define subforms
		$this->obj_form->subforms["staff_view"]		= array("name_staff", "staff_code", "staff_position", "contact_phone", "contact_fax", "contact_email", "date_start", "date_end");
		$this->obj_form->subforms["submit"]		= array("submit");
	}


	function render_html()
	{
		// title + summary
		print "<h3>ADD EMPLOYEE</h3><br>";
		print "<p>This page allows you to add a new employee to the database.</p>";
	
		// display the form
		$this->obj_form->render_form();
	}


} // end of page_output

?>
