<?php
/*
	user/delete.php
	
	access:	admin only

	Allows an unwanted user to be deleted.
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
	
	$_SESSION["nav"]["title"][]	= "User's Journal";
	$_SESSION["nav"]["query"][]	= "page=user/user-journal.php&id=$id";
	
	$_SESSION["nav"]["title"][]	= "Delete User";
	$_SESSION["nav"]["query"][]	= "page=user/user-delete.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=user/user-delete.php&id=$id";



	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		print "<h3>DELETE USER</h3><br>";
		print "<p>This page allows you to delete the selected user account.</p>";

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
			$form->formname = "user_delete";
			$form->language = $_SESSION["user"]["lang"];

			$form->action = "user/user-delete-process.php";
			$form->method = "post";
			

			// general
			$structure = NULL;
			$structure["fieldname"] 	= "username";
			$structure["type"]		= "text";
			$form->add_input($structure);


			// hidden
			$structure = NULL;
			$structure["fieldname"] 	= "id_user";
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= "$id";
			$form->add_input($structure);
			
			
			// confirm delete
			$structure = NULL;
			$structure["fieldname"] 	= "delete_confirm";
			$structure["type"]		= "checkbox";
			$structure["options"]["label"]	= "Yes, I wish to delete this user and realise that once deleted the data can not be recovered.";
			$form->add_input($structure);



			// define submit field
			$structure = NULL;
			$structure["fieldname"]		= "submit";
			$structure["type"]		= "submit";
			$structure["defaultvalue"]	= "delete";
			$form->add_input($structure);


			
			// define subforms
			$form->subforms["user_delete"]		= array("username");
			$form->subforms["hidden"]		= array("id_user");
			$form->subforms["submit"]		= array("delete_confirm", "submit");

			
			// fetch the form data
			$form->sql_query = "SELECT username FROM `users` WHERE id='$id' LIMIT 1";		
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
