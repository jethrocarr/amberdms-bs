<?php
/*
	user/user-staffaccess-edit.php
	
	access: admin only

	Displays all the access permissions the user has for a particular staff member and allows an administrator
	to change them,
*/

if (user_permissions_get('admin'))
{
	$id = $_GET["id"];
	
	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "User's Details";
	$_SESSION["nav"]["query"][]	= "page=user/user-view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "User's Permissions";
	$_SESSION["nav"]["query"][]	= "page=user/user-permissions.php&id=$id";

	$_SESSION["nav"]["title"][]	= "User's Staff Access Rights";
	$_SESSION["nav"]["query"][]	= "page=user/user-staffaccess.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=user/user-staffaccess.php&id=$id";
	
	$_SESSION["nav"]["title"][]	= "User's Journal";
	$_SESSION["nav"]["query"][]	= "page=user/user-journal.php&id=$id";
	
	$_SESSION["nav"]["title"][]	= "Delete User";
	$_SESSION["nav"]["query"][]	= "page=user/user-delete.php&id=$id";


	function page_render()
	{
		$id		= security_script_input('/^[0-9]*$/', $_GET["id"]);
		$staffid	= security_script_input('/^[0-9]*$/', $_GET["staffid"]);

		// check that the user exists
		$mysql_string	= "SELECT id FROM `users` WHERE id='$id'";
		$mysql_result	= mysql_query($mysql_string);
		$mysql_num_rows	= mysql_num_rows($mysql_result);

		if (!$mysql_num_rows)
		{
			print "<p><b>Error: The requested user does not exist. <a href=\"index.php?page=user/users.php\">Try looking for your user on the user list page.</a></b></p>";
		}
		else
		{
			// check that the specified staff member exists
			$mysql_string	= "SELECT name_staff, id FROM `staff` WHERE id='$staffid'";
			$mysql_result	= mysql_query($mysql_string);
			$mysql_num_rows	= mysql_num_rows($mysql_result);

			if (!$mysql_num_rows)
			{
				print "<p><b>Error: The requested staff member does not exist. <a href=\"index.php?page=hr/staff.php\">Try looking for the employee on the staff list page.</a></b></p>";
			}
			else
			{
				$mysql_data = mysql_fetch_array($mysql_result);
				
				/*
					Title + Summary
				*/
				print "<h3>USER STAFF ACCESS RIGHTS</h3><br>";

				print "<p>Use this page to define what permissions you wish to give the user for staff member \"". $mysql_data["name_staff"] ."\"</p>";

				print "<p><b>If you wish to remove access to this staff member completely, simply unselect all the tick boxes.</b></p>";

			
				/*
					Define form structure
				*/
				$form = New form_input;
				$form->formname = "users_permissions_staff";
				$form->language = $_SESSION["user"]["lang"];

				$form->action = "user/user-staffaccess-edit-process.php";
				$form->method = "post";


				$mysql_string = "SELECT * FROM `permissions_staff`";
				log_debug("user-staffaccess", "SQL: $mysql_string");

				if (!$mysql_perms_results = mysql_query($mysql_string))
					log_debug("user-staffaccess", "FATAL SQL: ". mysql_error());
		
				while ($mysql_perms_data = mysql_fetch_array($mysql_perms_results))
				{
					// define the checkbox
					$structure = NULL;
					$structure["fieldname"]		= $mysql_perms_data["value"];
					$structure["type"]		= "checkbox";
					$structure["options"]["label"]	= $mysql_perms_data["description"];


					// check the database to see if this checkbox is selected
					$mysql_string = "SELECT "
							."id "
							."FROM `users_permissions_staff` "
							."WHERE "
							."userid='$id' "
							."AND permid='". $mysql_perms_data["id"] ."' "
							."AND staffid='$staffid'";
							
					log_debug("user-staffaccess", "SQL: $mysql_string");

					if (!$mysql_userperms_result = mysql_query($mysql_string))
						log_debug("user-staffaccess", "FATAL SQL: ". mysql_error());

					$mysql_userperms_num_rows = mysql_num_rows($mysql_userperms_result);
					if ($mysql_userperms_num_rows)
					{
						$structure["defaultvalue"] = "on";
					}


					// add checkbox
					$form->add_input($structure);

					// add checkbox to subforms
					$form->subforms["user_permissions_staff"][] = $mysql_perms_data["value"];

				}
			

				// hidden fields
				$structure = NULL;
				$structure["fieldname"]		= "id_user";
				$structure["type"]		= "hidden";
				$structure["defaultvalue"]	= $id;
				$form->add_input($structure);
				
				$structure = NULL;
				$structure["fieldname"]		= "id_staff";
				$structure["type"]		= "hidden";
				$structure["defaultvalue"]	= $staffid;
				$form->add_input($structure);


				// submit section
				$structure = NULL;
				$structure["fieldname"] 	= "submit";
				$structure["type"]		= "submit";
				$structure["defaultvalue"]	= "Save Changes";
				$form->add_input($structure);
				
				
				// define subforms
				$form->subforms["hidden"]		= array("id_user", "id_staff");
				$form->subforms["submit"]		= array("submit");

				/*
					Note: We don't load from error data, since there should never
					be any errors when using this form.
				*/

				// display the form
				$form->render_form();


			} // end if employee exists

		} // end if user exists

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
