<?php
/*
	user/user-staffaccess-edit.php
	
	access: admin only

	Displays all the access permissions the user has for a particular staff member and allows an administrator
	to change them,
*/


class page_output
{
	var $id;
	var $staffid;

	var $name_staff;
	
	var $obj_menu_nav;
	var $obj_form;


	function page_output()
	{
		// fetch variables
		$this->id	= @security_script_input('/^[0-9]*$/', $_GET["id"]);
		$this->staffid	= @security_script_input('/^[0-9]*$/', $_GET["staffid"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("User's Details", "page=user/user-view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("User's Journal", "page=user/user-journal.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("User's Permissions", "page=user/user-permissions.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("User's Staff Access Rights", "page=user/user-staffaccess.php&id=". $this->id ."", TRUE);
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
		$sql_obj->string	= "SELECT id FROM users WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested user (". $this->id .") does not exist - possibly the user has been deleted.");
			return 0;
		}

		unset($sql_obj);


		// verify that the specified staff member exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id, name_staff FROM staff WHERE id='". $this->staffid ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested employee (". $this->staffid .") does not exist - possibly the employeee has been deleted.");
			return 0;
		}
		else
		{
			$sql_obj->fetch_array();
			
			$this->name_staff = $sql_obj->data[0]["name_staff"];
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
		$this->obj_form->formname = "users_permissions_staff";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "user/user-staffaccess-edit-process.php";
		$this->obj_form->method = "post";


		// run through all the avaliable permissions
		$sql_perms_obj			= New sql_query;
		$sql_perms_obj->string		= "SELECT * FROM `permissions_staff`";
		$sql_perms_obj->execute();

		if ($sql_perms_obj->num_rows())
		{
			$sql_perms_obj->fetch_array();

			foreach ($sql_perms_obj->data as $data_perms)
			{
				// define the checkbox
				$structure = NULL;
				$structure["fieldname"]		= $data_perms["value"];
				$structure["type"]		= "checkbox";
				$structure["options"]["label"]	= $data_perms["description"];


				// check the database to see if this checkbox is selected
				$sql_obj		= New sql_query;
				$sql_obj->string	= "SELECT "
							."id "
							."FROM `users_permissions_staff` "
							."WHERE "
							."userid='". $this->id ."' "
							."AND permid='". $data_perms["id"] ."' "
							."AND staffid='". $this->staffid ."'";
						
				$sql_obj->execute();

				if ($sql_obj->num_rows())
				{
					$structure["defaultvalue"] = "on";
				}


				// add checkbox
				$this->obj_form->add_input($structure);

				// add checkbox to subforms
				$this->obj_form->subforms["user_permissions_staff"][] = $data_perms["value"];
			}
		}
	

		// hidden fields
		$structure = NULL;
		$structure["fieldname"]		= "id_user";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->id;
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"]		= "id_staff";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->staffid;
		$this->obj_form->add_input($structure);


		// submit section
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Save Changes";
		$this->obj_form->add_input($structure);
		
		
		// define subforms
		$this->obj_form->subforms["hidden"]		= array("id_user", "id_staff");
		$this->obj_form->subforms["submit"]		= array("submit");

		/*
			Note: We don't load from error data, since there should never
			be any errors when using this form.
		*/
	}


	function render_html()
	{
		// Title + Summary
		print "<h3>USER STAFF ACCESS RIGHTS</h3><br>";

		print "<p>Use this page to define what permissions you wish to give the user for staff member \"". $this->name_staff ."\"</p>";

		print "<p><b>If you wish to remove access to this staff member completely, simply unselect all the tick boxes.</b></p>";


		// display the form
		$this->obj_form->render_form();
	}
}

?>
