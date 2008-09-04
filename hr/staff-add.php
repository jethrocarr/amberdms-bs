<?php
/*
	hr/staff-add.php

	access: staff_write

	Allows the creation of a new employee - this form
	requests mininal details, then directs the user to the view/edit form
	so that they can complete the changes.
*/

if (user_permissions_get('staff_write'))
{
	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		print "<h2>ADD EMPLOYEE</h2><br>";
		print "<p>This page allows you to add a new employee to the database.</p>";

		/*
			Define form structure
		*/
		$form = New form_input;
		$form->formname = "staff_view";
		$form->language = $_SESSION["user"]["lang"];

		$form->action = "hr/staff-edit-process.php";
		$form->method = "post";


		// general
		$structure = NULL;
		$structure["fieldname"] 	= "name_staff";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] = "staff_code";
		$structure["type"]	= "input";
		$form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] = "staff_position";
		$structure["type"]	= "input";
		$form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] = "contact_email";
		$structure["type"]	= "input";
		$form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] = "contact_phone";
		$structure["type"]	= "input";
		$form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] = "contact_fax";
		$structure["type"]	= "input";
		$form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] = "date_start";
		$structure["type"]	= "date";
		$form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] = "date_end";
		$structure["type"]	= "date";
		$form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Save Changes";
		$form->add_input($structure);
		
		// define subforms
		$form->subforms["staff_view"]		= array("name_staff", "staff_code", "staff_position", "contact_phone", "contact_fax", "contact_email", "date_start", "date_end");
		$form->subforms["submit"]		= array("submit");

		// display the form
		$form->render_form();

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
