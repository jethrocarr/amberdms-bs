<?php
/*
	user/user-view.php
	
	access: admin only

	Displays all the details of the user accounts and allows them to be adjusted.
*/

if (user_permissions_get('admin'))
{
	$id = $_GET["id"];
	
	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "User's Details";
	$_SESSION["nav"]["query"][]	= "page=user/user-view.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=user/user-view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "User's Permissions";
	$_SESSION["nav"]["query"][]	= "page=user/user-permissions.php&id=$id";

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
		print "<h3>USER DETAILS</h3><br>";
		print "<p>This page allows you to view and adjust the user account details. <b>Note: if you adjust any of the details on this page, the user will be logged out if they are currently using the system.</b></p>";

		$mysql_string	= "SELECT id FROM `users` WHERE id='$id'";
		$mysql_result	= mysql_query($mysql_string);
		$mysql_num_rows	= mysql_num_rows($mysql_result);

		if (!$mysql_num_rows)
		{
			print "<p><b>Error: The requested user does not exist. <a href=\"index.php?page=user/users.php\">Try looking for your user on the user list page.</a></b></p>";
		}
		else
		{

			// fetch user options from the database
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT name, value FROM users_options WHERE userid='$id'";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				$sql_obj->fetch_array();
				
				// structure the results into a form we can then use to fill the fields in the form
				foreach ($sql_obj->data as $data)
				{
					$options[ $data["name"] ] = $data["value"];
				}
			}



		
			/*
				Define form structure
			*/
			$form = New form_input;
			$form->formname = "user_view";
			$form->language = $_SESSION["user"]["lang"];

			$form->action = "user/user-edit-process.php";
			$form->method = "post";
			

			// general
			$structure = NULL;
			$structure["fieldname"] 	= "id_user";
			$structure["type"]		= "text";
			$structure["defaultvalue"]	= "$id";
			$form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"] 	= "username";
			$structure["type"]		= "input";
			$structure["options"]["req"]	= "yes";
			$form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"]		= "realname";
			$structure["type"]		= "input";
			$structure["options"]["req"]	= "yes";
			$form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"]		= "contact_email";
			$structure["type"]		= "input";
			$structure["options"]["req"]	= "yes";
			$form->add_input($structure);

			// passwords
			$structure = NULL;
			$structure["fieldname"]		= "password_message";
			$structure["type"]		= "message";
			$structure["defaultvalue"]	= "<i>Only input a password if you wish to change the existing one.</i>";
			$form->add_input($structure);
			
			
			$structure = NULL;
			$structure["fieldname"]		= "password";
			$structure["type"]		= "password";
			$form->add_input($structure);
		
			$structure = NULL;
			$structure["fieldname"]		= "password_confirm";
			$structure["type"]		= "password";
			$form->add_input($structure);
		
			
			
			// last login information
			$structure = NULL;
			$structure["fieldname"]		= "time";
			$structure["type"]		= "text";
			$form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"]		= "ipaddress";
			$structure["type"]		= "text";
			$form->add_input($structure);


			// options
			$structure = form_helper_prepare_radiofromdb("option_lang", "SELECT name as id, name as label FROM language_avaliable ORDER BY name");
			$structure["defaultvalue"] = $options["lang"];
			$form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"]		= "option_debug";
			$structure["type"]		= "checkbox";
			$structure["defaultvalue"]	= $options["debug"];
			$structure["options"]["label"]	= "Enable debug logging - this will impact performance a bit but will show a full trail of all functions and SQL queries made</i>";
			$form->add_input($structure);
		
			
		
		
			// submit section
			$structure = NULL;
			$structure["fieldname"] 	= "submit";
			$structure["type"]		= "submit";
			$structure["defaultvalue"]	= "Save Changes";
			$form->add_input($structure);
			
			
			// define subforms
			$form->subforms["user_view"]		= array("id_user", "username", "realname", "contact_email");
			$form->subforms["user_password"]	= array("password_message", "password", "password_confirm");
			$form->subforms["user_info"]		= array("time", "ipaddress");
			$form->subforms["user_options"]		= array("option_lang", "option_debug");
			
			$form->subforms["submit"]		= array("submit");

			
			// fetch the form data
			$form->sql_query = "SELECT id, username, realname, contact_email, time, ipaddress FROM `users` WHERE id='$id' LIMIT 1";
			$form->load_data();

			// convert the last login time to a human readable value
			$form->structure["time"]["defaultvalue"] = date("Y-m-d H:i:s", $form->structure["time"]["defaultvalue"]);

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
