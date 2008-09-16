<?php
/*
	user/add.php
	
	access: admin only

	Allows the creation of new user accounts
*/

if (user_permissions_get('admin'))
{
	function page_render()
	{
		/*
			Title + Summary
		*/
		print "<h3>ADD USER ACCOUNT</h3><br>";
		print "<p>This page allows you to create new user accounts.</b></p>";

		/*
			Define form structure
		*/
		$form = New form_input;
		$form->formname = "user_add";
		$form->language = $_SESSION["user"]["lang"];

		$form->action = "user/user-edit-process.php";
		$form->method = "post";
		

		// general
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
		$structure["fieldname"]		= "password";
		$structure["type"]		= "password";
		$structure["options"]["req"]	= "yes";
		$form->add_input($structure);
	
		$structure = NULL;
		$structure["fieldname"]		= "password_confirm";
		$structure["type"]		= "password";
		$structure["options"]["req"]	= "yes";
		$form->add_input($structure);
	
		
	
		// submit section
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Save Changes";
		$form->add_input($structure);
		
		
		// define subforms
		$form->subforms["user_view"]		= array("username", "realname", "contact_email");
		$form->subforms["user_password"]	= array("password", "password_confirm");
		
		$form->subforms["submit"]		= array("submit");

		
		// load any data returned due to errors
		$form->load_data_error();

		// display the form
		$form->render_form();


	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
