<?php
/*
	staff/delete.php
	
	access:	staff_write

	Allows an unwanted employee to be deleted.
*/

if (user_permissions_get('staff_write'))
{
	$id = $_GET["id"];

	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "Employee's Details";
	$_SESSION["nav"]["query"][]	= "page=hr/staff-view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Employee's Journal";
	$_SESSION["nav"]["query"][]	= "page=hr/staff-journal.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Delete Employee";
	$_SESSION["nav"]["query"][]	= "page=hr/staff-delete.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=hr/staff-delete.php&id=$id";



	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		print "<h3>DELETE EMPLOYEE</h3><br>";
		print "<p>This page allows you to delete an unwanted employee. Note that it is only possible to delete a employee if they have had no payments and have not booked any time. If they do, you can not delete the employee, but instead you can disable the employee by setting the date_end field.</p>";

		$mysql_string	= "SELECT id FROM `staff` WHERE id='$id'";
		$mysql_result	= mysql_query($mysql_string);
		$mysql_num_rows	= mysql_num_rows($mysql_result);

		if (!$mysql_num_rows)
		{
			print "<p><b>Error: The requested employee does not exist. <a href=\"index.php?page=staff/staff.php\">Try looking for your employee on the employee list page.</a></b></p>";
		}
		else
		{
			/*
				Define form structure
			*/
			$form = New form_input;
			$form->formname = "staff_delete";
			$form->language = $_SESSION["user"]["lang"];

			$form->action = "hr/staff-delete-process.php";
			$form->method = "post";
			

			// general
			$structure = NULL;
			$structure["fieldname"] 	= "name_staff";
			$structure["type"]		= "text";
			$form->add_input($structure);


			// hidden
			$structure = NULL;
			$structure["fieldname"] 	= "id_staff";
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= "$id";
			$form->add_input($structure);
			
			
			// confirm delete
			$structure = NULL;
			$structure["fieldname"] 	= "delete_confirm";
			$structure["type"]		= "checkbox";
			$structure["options"]["label"]	= "Yes, I wish to delete this employee and realise that once deleted the data can not be recovered.";
			$form->add_input($structure);



			/*
				Check that the employee can be deleted
			*/

			$locked = 0;
			

			// make sure employee does not belong to any AR invoices
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM account_ar WHERE employeeid='$id'";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				$locked = 1;
			}

			// make sure employee does not belong to any AP invoices
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM account_ap WHERE employeeid='$id'";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				$locked = 1;
			}


			// make sure employee has no time booked
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM timereg WHERE employeeid='$id'";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				$locked = 1;
			}

	
			// define submit field
			$structure = NULL;
			$structure["fieldname"] = "submit";

			if ($locked)
			{
				$structure["type"]		= "message";
				$structure["defaultvalue"]	= "<i>This employee can not be deleted because it belongs to an invoice or has time booked.</i>";
			}
			else
			{
				$structure["type"]		= "submit";
				$structure["defaultvalue"]	= "delete";
			}
					
			$form->add_input($structure);


			
			// define subforms
			$form->subforms["staff_delete"]		= array("name_staff");
			$form->subforms["hidden"]		= array("id_staff");
			$form->subforms["submit"]		= array("delete_confirm", "submit");

			
			// fetch the form data
			$form->sql_query = "SELECT name_staff FROM `staff` WHERE id='$id' LIMIT 1";		
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
