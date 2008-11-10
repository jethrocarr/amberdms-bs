<?php
/*
	hr/staff-view.php

	access: staff_view (read-only)
		staff_write (write access)

	Displays the details of the selected staff member, and if the user
	has write permissions allows the staff member to be adjusted.
*/

if (user_permissions_get('staff_view'))
{
	$id = $_GET["id"];
	
	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "Employee's Details";
	$_SESSION["nav"]["query"][]	= "page=hr/staff-view.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=hr/staff-view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Employee's Journal";
	$_SESSION["nav"]["query"][]	= "page=hr/staff-journal.php&id=$id";

	if (user_permissions_get('staff_write'))
	{
		$_SESSION["nav"]["title"][]	= "Delete Employee";
		$_SESSION["nav"]["query"][]	= "page=hr/staff-delete.php&id=$id";
	}



	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		print "<h3>EMPLOYEE'S DETAILS</h3><br>";
		print "<p>This page allows you to view and adjust the employee's records.</p>";

		$mysql_string	= "SELECT id FROM `staff` WHERE id='$id'";
		$mysql_result	= mysql_query($mysql_string);
		$mysql_num_rows	= mysql_num_rows($mysql_result);

		if (!$mysql_num_rows)
		{
			print "<p><b>Error: The requested employee does not exist. <a href=\"index.php?page=hr/staff.php\">Try looking for your employee on the staff list page.</a></b></p>";
		}
		else
		{
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
			$structure["fieldname"] 	= "id_staff";
			$structure["type"]		= "text";
			$structure["defaultvalue"]	= "$id";
			$form->add_input($structure);
			
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

			// submit section
			if (user_permissions_get("staff_write"))
			{
				$structure = NULL;
				$structure["fieldname"] 	= "submit";
				$structure["type"]		= "submit";
				$structure["defaultvalue"]	= "Save Changes";
				$form->add_input($structure);
			
			}
			else
			{
				$structure = NULL;
				$structure["fieldname"] 	= "submit";
				$structure["type"]		= "message";
				$structure["defaultvalue"]	= "<p><i>Sorry, you don't have permissions to make changes to staff records.</i></p>";
				$form->add_input($structure);
			}
			
			
			// define subforms
			$form->subforms["staff_view"]		= array("id_staff", "name_staff", "staff_code", "staff_position", "contact_phone", "contact_fax", "contact_email", "date_start", "date_end");
			$form->subforms["submit"]		= array("submit");

			
			// fetch the form data
			$form->sql_query = "SELECT * FROM `staff` WHERE id='$id' LIMIT 1";		
			$form->load_data();

			// display the form
			$form->render_form();

		}

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
