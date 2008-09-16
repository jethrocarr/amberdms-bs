<?php
/*
	user/user-permissions.php
	
	access: admin only

	Displays all the permmissions of the selected user account
	and allows an administrator to change them.
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
	$_SESSION["nav"]["current"]	= "page=user/user-permissions.php&id=$id";

	$_SESSION["nav"]["title"][]	= "User's Staff Access Rights";
	$_SESSION["nav"]["query"][]	= "page=user/user-staffaccess.php&id=$id";
	
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
		print "<h3>USER PERMISSIONS</h3><br>";
		print "<p>The user permissions system allows you to configure how much program access to give to each user - for example some users will only need access to time keeping, but others may need full access to all the accounts and financial records.</p>";
		print "<p>Note that additional permissions are provided by the User Staff Access Rights configuration, which you can adjust using the link in the menu. This allows you to configure which staff members the users can act on behalf of when entering time, invoices or other records. See that page for further details.</p>";

		$mysql_string	= "SELECT id FROM `users` WHERE id='$id'";
		$mysql_result	= mysql_query($mysql_string);
		$mysql_num_rows	= mysql_num_rows($mysql_result);

		if (!$mysql_num_rows)
		{
			print "<p><b>Error: The requested user does not exist. <a href=\"index.php?page=user/users.php\">Try looking for your user on the user list page.</a></b></p>";
		}
		else
		{
			/*
				Define form structure
			*/
			$form = New form_input;
			$form->formname = "user_permissions";
			$form->language = $_SESSION["user"]["lang"];

			$form->action = "user/user-permissions-process.php";
			$form->method = "post";


			$mysql_string		= "SELECT * FROM `permissions`";
			$mysql_perms_results	= mysql_query($mysql_string);
			
			while ($mysql_perms_data = mysql_fetch_array($mysql_perms_results))
			{
				// define the checkbox
				$structure = NULL;
				$structure["fieldname"]		= $mysql_perms_data["value"];
				$structure["type"]		= "checkbox";
				$structure["options"]["label"]	= $mysql_perms_data["description"];

				// check if the user has this permission
				$mysql_string			= "SELECT id FROM `users_permissions` WHERE userid='$id' AND permid='". $mysql_perms_data["id"] ."'";
				$mysql_userperms_result		= mysql_query($mysql_string);
				$mysql_userperms_num_rows	= mysql_num_rows($mysql_userperms_result);

				if ($mysql_userperms_num_rows)
				{
					$structure["defaultvalue"] = "on";
				}

				// add checkbox
				$form->add_input($structure);

				// add checkbox to subforms
				$form->subforms["user_permissions"][] = $mysql_perms_data["value"];

			}
		
			// user ID (hidden field)
			$structure = NULL;
			$structure["fieldname"]		= "id_user";
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= $id;
			$form->add_input($structure);	
		
			// submit section
			$structure = NULL;
			$structure["fieldname"] 	= "submit";
			$structure["type"]		= "submit";
			$structure["defaultvalue"]	= "Save Changes";
			$form->add_input($structure);
			
			
			// define subforms
			$form->subforms["hidden"]		= array("id_user");
			$form->subforms["submit"]		= array("submit");

			
			/*
				Note: We don't load from error data, since there should never
				be any errors when using this form.
			*/

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
