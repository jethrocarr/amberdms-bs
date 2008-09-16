<?php
/*
	user/user-staffaccess.php
	
	access: admin users only

	Allows the configuration of user's access rights to staff member accounts.
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
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		print "<h3>USER STAFF ACCESS RIGHTS</h3><br>";
		print "<p>The Amberdms Billing System allows user accounts to be in charge of multiple staff members
			- what this means, is that you can configure which staff members the user can act on behalf
			of when entering time, invoices or other records.</p>";

		print "<p>This feature is useful for doing things such as assigning a secretary to be able to fill
			in timesheet for both themselves and their manager, or allowing accounting staff to be
			able to edit all staff member's timesheets in order to correct mistakes at billing time.</p>";
		

		$mysql_string	= "SELECT id FROM `users` WHERE id='$id'";
		$mysql_result	= mysql_query($mysql_string);
		$mysql_num_rows	= mysql_num_rows($mysql_result);

		if (!$mysql_num_rows)
		{
			print "<p><b>Error: The requested user does not exist. <a href=\"index.php?page=user/users.php\">Try looking for your user on the user list page.</a></b></p>";
		}
		else
		{
		
			// establish a new table object
			$userstaff_list = New table;

			$userstaff_list->language	= $_SESSION["user"]["lang"];
			$userstaff_list->tablename	= "userstaff_list";
			$userstaff_list->sql_table	= "users_permissions_staff";

			// define all the columns and structure
			$userstaff_list->add_column("standard", "staff_code", "staff.staff_code");
			$userstaff_list->add_column("standard", "name_staff", "staff.name_staff");
			$userstaff_list->add_column("standard", "staff_position", "staff.staff_position");

			// defaults
			$userstaff_list->columns	= array("staff_code", "name_staff", "staff_position");
			$userstaff_list->columns_order	= array("name_staff");


			// additional SQL query options
			$userstaff_list->prepare_sql_addfield("staffid", "staff.id");
			$userstaff_list->prepare_sql_addwhere("userid = '$id'");

			$userstaff_list->prepare_sql_addjoin("LEFT JOIN staff ON staff.id = users_permissions_staff.staffid");
			$userstaff_list->prepare_sql_addgroupby("users_permissions_staff.staffid");
			

			// run SQL query
			$userstaff_list->generate_sql();
			$userstaff_list->load_data_sql();

			if (!$userstaff_list->data_num_rows)
			{
				print "<br><p><b>This user currently has no staff access rights.</b></p><br>";
			}
			else
			{
				// edit link
				$structure = NULL;
				$structure["id"]["value"]		= $id;
				$structure["staffid"]["column"]		= "staffid";
				$userstaff_list->add_link("full details", "user/user-staffaccess-edit.php", $structure);

				// display the table
				$userstaff_list->render_table();

			}

			// add link
			print "<p><b><a href=\"index.php?page=user/user-staffaccess-add.php&id=$id\">Click here to add new staff access rights</a>.</b></p>";

		} // end if user exists
		
	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
