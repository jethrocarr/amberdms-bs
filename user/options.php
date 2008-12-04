<?php
/*
	user/options.php
	
	access: all users

	Allows users to adjust their account options as well as passwords.
*/

class page_output
{
	var $id;
	var $obj_form;


	function page_output()
	{
		//$this->id = security_script_input('/^[0-9]*$/', $_GET["id"]);
		$this->id = $_SESSION["user"]["id"];
	}


	function check_permissions()
	{
		return user_online();
	}

	function check_requirements()
	{
		// nothing to do
		return 1;
	}




	function execute()
	{
		/*
			Define form structure
		*/
		
		$this->obj_form = New form_input;
		$this->obj_form->formname = "user_options";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "user/options-process.php";
		$this->obj_form->method = "post";


		// fetch user options from the database
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT name, value FROM users_options WHERE userid='". $this->id . "'";
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
		$this->obj_form->add_input($structure);
							
		$structure = NULL;
		$structure["fieldname"]		= "realname";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]		= "contact_email";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);

		// passwords
		$structure = NULL;
		$structure["fieldname"]		= "password_message";
		$structure["type"]		= "message";
		$structure["defaultvalue"]	= "<i>Only input a password if you wish to change the existing one.</i>";
		$this->obj_form->add_input($structure);
			
			
		$structure = NULL;
		$structure["fieldname"]		= "password";
		$structure["type"]		= "password";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"]		= "password_confirm";
		$structure["type"]		= "password";
		$this->obj_form->add_input($structure);
		
			
			
		// last login information
		$structure = NULL;
		$structure["fieldname"]		= "time";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]		= "ipaddress";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);
		
		
		// submit section
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Save Changes";
		$this->obj_form->add_input($structure);
		
		
		// define main subforms
		$this->obj_form->subforms["user_view"]		= array("username", "realname", "contact_email");
		$this->obj_form->subforms["user_password"]	= array("password_message", "password", "password_confirm");
		$this->obj_form->subforms["user_info"]		= array("time", "ipaddress");


		// options
		$structure = form_helper_prepare_radiofromdb("option_lang", "SELECT name as id, name as label FROM language_avaliable ORDER BY name");
		$structure["defaultvalue"] = $options["lang"];
		$this->obj_form->add_input($structure);
			
		$this->obj_form->subforms["user_options"][]	= "option_lang";


		if (user_permissions_get("admin"))
		{
			// only administrators can enable debugging
			$structure = NULL;
			$structure["fieldname"]		= "option_debug";
			$structure["type"]		= "checkbox";
			$structure["defaultvalue"]	= $options["debug"];
			$structure["options"]["label"]	= "Enable debug logging - this will impact performance a bit but will show a full trail of all functions and SQL queries made <i>(note: this option is only avaliable to administrators)</i>";
			$this->obj_form->add_input($structure);
			
			$this->obj_form->subforms["user_options"][]	= "option_debug";
		}


		// remaining subforms		
		$this->obj_form->subforms["submit"]		= array("submit");

			
		// fetch the form data
		$this->obj_form->sql_query = "SELECT id, username, realname, contact_email, time, ipaddress FROM `users` WHERE id='". $this->id ."' LIMIT 1";
		$this->obj_form->load_data();

		// convert the last login time to a human readable value
		$this->obj_form->structure["time"]["defaultvalue"] = date("Y-m-d H:i:s", $this->obj_form->structure["time"]["defaultvalue"]);

	}



	function render_html()
	{
		// Title + Summary
		print "<h3>USER ACCOUNT OPTIONS</h3><br>";
		print "<p>This page allows you to adjust your account options. Any changes that you make will be active as soon as you save the changes, you do not need to log back in.</p>";

	
		// display the form
		$this->obj_form->render_form();
	}

	
}

?>
