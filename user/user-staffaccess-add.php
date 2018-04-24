<?php
/*
	user/user-staffaccess-add.php
	
	access: admin only

	Allows the administrator to add new access rights for a staff member to a user's account.
*/


class page_output
{
	var $id;
	
	var $obj_menu_nav;
	var $obj_form;


	function __construct()
	{
		// fetch variables
		$this->id	= @security_script_input('/^[0-9]*$/', $_GET["id"]);

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


		// staff member dropdown
		$structure = form_helper_prepare_dropdownfromdb("id_staff", "SELECT id, staff_code as label, name_staff as label1 FROM `staff` ORDER BY name_staff");
		$this->obj_form->add_input($structure);
		
		$this->obj_form->subforms["user_permissions_selectstaff"]	= array("id_staff");



		/*
			Permissions sub-form
		*/
		
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
	
		// submit section
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Save Changes";
		$this->obj_form->add_input($structure);
		
		
		// define subforms
		$this->obj_form->subforms["hidden"]			= array("id_user");
		$this->obj_form->subforms["submit"]			= array("submit");

		/*
			Note: We don't load from error data, since there should never
			be any errors when using this form.
		*/


	}


	function render_html()
	{
		// Title + Summary
		print "<h3>USER STAFF ACCESS RIGHTS</h3><br>";

		print "<p>Use this page to assign access rights to a staff member for the selected user account.</p>";

		// display the form
		$this->obj_form->render_form();
	}
	
}


?>
