<?php
/*
	user/options.php
	
	access: all users

	Allows users to adjust their account options as well as passwords.
*/

if (user_online())
{
	$id = $_GET["id"];

	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		
		print "<h3>USER ACCOUNT OPTIONS</h3><br>";
		print "<p>This page allows you to adjust your account options. Any changes that you make will be active as soon as you save the changes, you do not need to log back in.</p>";


		/*
			Define form structure
		*/
		
		$form = New form_input;
		$form->formname = "user_options";
		$form->language = $_SESSION["user"]["lang"];

		$form->action = "user/options-process.php";
		$form->method = "post";


		// fetch user options from the database
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT name, value FROM users_options WHERE userid='". $_SESSION["user"]["id"] . "'";
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


		// general
		$structure = NULL;
		$structure["fieldname"] 	= "username";
		$structure["type"]		= "text";
		$structure["defaultvalue"]	= $_SESSION["user"]["name"];
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
		
		
		// submit section
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Save Changes";
		$form->add_input($structure);
		
		
		// define main subforms
		$form->subforms["user_view"]		= array("username", "realname", "contact_email");
		$form->subforms["user_password"]	= array("password_message", "password", "password_confirm");
		$form->subforms["user_info"]		= array("time", "ipaddress");


		// options
		$structure = form_helper_prepare_radiofromdb("option_lang", "SELECT name as id, name as label FROM language_avaliable ORDER BY name");
		$structure["defaultvalue"] = $options["lang"];
		$form->add_input($structure);
			
		$form->subforms["user_options"][]	= "option_lang";


		if (user_permissions_get("admin"))
		{
			// only administrators can enable debugging
			$structure = NULL;
			$structure["fieldname"]		= "option_debug";
			$structure["type"]		= "checkbox";
			$structure["defaultvalue"]	= $options["debug"];
			$structure["options"]["label"]	= "Enable debug logging - this will impact performance a bit but will show a full trail of all functions and SQL queries made <i>(note: this option is only avaliable to administrators)</i>";
			$form->add_input($structure);
			
			$form->subforms["user_options"][]	= "option_debug";
		}


		
		



		// remaining subforms		
		$form->subforms["submit"]		= array("submit");

			
		// fetch the form data
		$form->sql_query = "SELECT id, username, realname, contact_email, time, ipaddress FROM `users` WHERE id='". $_SESSION["user"]["id"] ."' LIMIT 1";
		$form->load_data();

		// convert the last login time to a human readable value
		$form->structure["time"]["defaultvalue"] = date("Y-m-d H:i:s", $form->structure["time"]["defaultvalue"]);

		// display the form
		$form->render_form();


	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
