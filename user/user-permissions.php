<?php
/*
	user/user-permissions.php
	
	access: admin only

	Displays all the permmissions of the selected user account
	and allows an administrator to change them.
*/


class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_form;


	function page_output()
	{
		// fetch variables
		$this->id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("User's Details", "page=user/user-view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("User's Journal", "page=user/user-journal.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("User's Permissions", "page=user/user-permissions.php&id=". $this->id ."", TRUE);
		$this->obj_menu_nav->add_item("User's Staff Access Rights", "page=user/user-staffaccess.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Delete User", "page=user/user-delete.php&id=". $this->id ."");
	}


	function check_permissions()
	{
		return user_permissions_get("admin");
	}


	function check_requirements()
	{
		// verify that user exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM users WHERE id='". $this->id ."'";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested user (". $this->id .") does not exist - possibly the user has been deleted.");
			return 0;
		}

		unset($sql_obj);


		return 1;
	}



	function execute()
	{
		/*
			Define form structure
		*/
		$this->obj_form = New form_input;
		$this->obj_form->formname = "user_permissions";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "user/user-permissions-process.php";
		$this->obj_form->method = "post";


		$sql_perms_obj		= New sql_query;
		$sql_perms_obj->string	= "SELECT * FROM `permissions` ORDER BY value='disabled' DESC, value='admin' DESC, value";
		$sql_perms_obj->execute();
		
		for ($sql_perms_obj->data as $data_perms)
		{
			// define the checkbox
			$structure = NULL;
			$structure["fieldname"]				= $data_perms["value"];
			$structure["type"]				= "checkbox";
			$structure["options"]["label"]			= $data_perms["description"];
			$structure["options"]["no_translate_fieldname"]	= "yes";

			// check if the user has this permission
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM `users_permissions` WHERE userid='". $this->id ."' AND permid='". $data_perms["id"] ."'";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				$structure["defaultvalue"] = "on";
			}

			// add checkbox
			$this->obj_form->add_input($structure);

			// add checkbox to subforms
			$this->obj_form->subforms["user_permissions"][] = $data_perms["value"];

		}
	
		// user ID (hidden field)
		$structure = NULL;
		$structure["fieldname"]		= "id_user";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->id;
		$this->obj_form->add_input($structure);	
	
		// submit section
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Save Changes";
		$this->obj_form->add_input($structure);
		
		
		// define subforms
		$this->obj_form->subforms["hidden"]		= array("id_user");
		$this->obj_form->subforms["submit"]		= array("submit");

		
		/*
			Note: We don't load from error data, since there should never
			be any errors when using this form.
		*/
	}


	function render_html()
	{
		// title + summary
		print "<h3>USER PERMISSIONS</h3><br>";
		print "<p>The user permissions system allows you to configure how much program access to give to each user - for example some users will only need access to time keeping, but others may need full access to all the accounts and financial records.</p>";
		print "<p>Note that additional permissions are provided by the User Staff Access Rights configuration, which you can adjust using the link in the menu. This allows you to configure which staff members the users can act on behalf of when entering time, invoices or other records. See that page for further details.</p>";


		// display the form
		$this->obj_form->render_form();

	}

}

?>
