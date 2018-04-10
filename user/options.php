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


	function __construct()
	{
		//$this->id = @security_script_input('/^[0-9]*$/', $_GET["id"]);
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


		// OPTIONS:

		// language
		$structure = form_helper_prepare_radiofromdb("option_lang", "SELECT name as id, name as label FROM language_avaliable ORDER BY name");
		$structure["defaultvalue"] = $options["lang"];
		$this->obj_form->add_input($structure);
			
		$this->obj_form->subforms["user_options"][]	= "option_lang";


		// date format
		$structure = NULL;
		$structure["fieldname"]		= "option_dateformat";
		$structure["type"]		= "radio";
		$structure["values"]		= array("yyyy-mm-dd", "mm-dd-yyyy", "dd-Mmm-yyyy", "dd-mm-yyyy");
		$structure["defaultvalue"]	= $options["dateformat"];
		$this->obj_form->add_input($structure);
			
		$this->obj_form->subforms["user_options"][]	= "option_dateformat";


		// timezone
		$structure 				= form_helper_prepare_timezonedropdown("option_timezone");
		$structure["defaultvalue"]		= $options["timezone"];
		$structure["options"]["width"]		= "600";
		$structure["options"]["search_filter"]	= "enabled";
		$this->obj_form->add_input($structure);
		
		$this->obj_form->subforms["user_options"][]	= "option_timezone";


		// table options form shrink configuration
		$structure = NULL;
		$structure["fieldname"]		= "option_shrink_tableoptions";
		$structure["type"]		= "checkbox";
		$structure["defaultvalue"]	= $options["shrink_tableoptions"];
		$structure["options"]["label"]	= "Automatically hide the options table when using defaults";
		$this->obj_form->add_input($structure);

		$this->obj_form->subforms["user_options"][]	= "option_shrink_tableoptions";

		$structure = NULL;
		$structure["fieldname"]		= "option_table_limit";
		$structure["type"]		= "input";
		$structure["defaultvalue"]	= $options["table_limit"];
		$structure["options"]["label"]	= " ". lang_trans("help_table_limit");
		$structure["options"]["width"]	= "100";
		$this->obj_form->add_input($structure);

		$this->obj_form->subforms["user_options"][]	= "option_table_limit";



		// default employee
		$structure = form_helper_prepare_dropdownfromdb("option_default_employeeid", "SELECT id, staff_code as label, name_staff as label1 FROM staff ORDER BY name_staff");
		$structure["options"]["autoselect"]	= "yes";
		$structure["options"]["width"]		= "600";
		$structure["options"]["search_filter"]	= "enabled";
		$structure["defaultvalue"]		= $options["default_employeeid"];
		$this->obj_form->add_input($structure);

		$this->obj_form->subforms["user_options"][]	= "option_default_employeeid";

		//custom theme
		$structure = form_helper_prepare_dropdownfromdb("option_theme", "SELECT id, theme_name as label FROM themes ORDER BY theme_name");
		$structure["options"]["autoselect"] = "yes";
		$structure["options"]["width"] = "600";
		$structure["options"]["search_filter"]	= "enabled";
		$structure["defaultvalue"] = $options["theme"];
		$this->obj_form->add_input($structure);
		
		$this->obj_form->subforms["user_options"][] = "option_theme";


		// administrator-only option
		if (user_permissions_get("admin"))
		{
			// debugging
			$structure = NULL;
			$structure["fieldname"]		= "option_debug";
			$structure["type"]		= "checkbox";
			$structure["defaultvalue"]	= $options["debug"];
			$structure["options"]["label"]	= "Enable debug logging - this will impact performance a bit but will show a full trail of all functions and SQL queries made <i>(note: this option is only avaliable to administrators)</i>";
			$this->obj_form->add_input($structure);

			// concurrent logins
			$structure = NULL;
			$structure["fieldname"]		= "option_concurrent_logins";
			$structure["type"]		= "checkbox";
			$structure["defaultvalue"]	= $options["concurrent_logins"];
			$structure["options"]["label"]	= "Permit this user to make multiple simultaneous logins (recommended for SOAP users)</i>";
			$this->obj_form->add_input($structure);
			
			$this->obj_form->subforms["user_options"][]	= "option_debug";
			$this->obj_form->subforms["user_options"][]	= "option_concurrent_logins";
		}
		
		//translation options
		if (user_permissions_get("devel_translate"))
		{
			$structure = NULL;
			$structure["fieldname"]		= "option_translation";
			$structure["type"]		= "radio";
			$structure["defaultvalue"]	= $options["translation"];
			$structure["values"]		= array("off", "show_all_translatable_fields", "show_only_non-translated_fields");

			$this->obj_form->add_input($structure);
				
			$this->obj_form->subforms["user_options"][]	= "option_translation";
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
