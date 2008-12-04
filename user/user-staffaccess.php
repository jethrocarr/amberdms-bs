<?php
/*
	user/user-staffaccess.php
	
	access: admin users only

	Allows the configuration of user's access rights to staff member accounts.
*/

class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_table;


	function page_output()
	{
		// fetch variables
		$this->id = security_script_input('/^[0-9]*$/', $_GET["id"]);

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
		// establish a new table object
		$this->obj_table = New table;

		$this->obj_table->language	= $_SESSION["user"]["lang"];
		$this->obj_table->tablename	= "userstaff_list";

		// define all the columns and structure
		$this->obj_table->add_column("standard", "staff_code", "staff.staff_code");
		$this->obj_table->add_column("standard", "name_staff", "staff.name_staff");
		$this->obj_table->add_column("standard", "staff_position", "staff.staff_position");

		// defaults
		$this->obj_table->columns	= array("staff_code", "name_staff", "staff_position");
		$this->obj_table->columns_order	= array("name_staff");


		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("users_permissions_staff");
		$this->obj_table->sql_obj->prepare_sql_addfield("staffid", "staff.id");
		$this->obj_table->sql_obj->prepare_sql_addwhere("userid = '". $this->id ."'");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN staff ON staff.id = users_permissions_staff.staffid");
		$this->obj_table->sql_obj->prepare_sql_addgroupby("users_permissions_staff.staffid");

		// run SQL query
		$this->obj_table->generate_sql();
		$this->obj_table->load_data_sql();
	}


	function render_html()
	{
		// Title + Summary
		print "<h3>USER STAFF ACCESS RIGHTS</h3><br>";
		print "<p>The Amberdms Billing System allows user accounts to be in charge of multiple staff members
			- what this means, is that you can configure which staff members the user can act on behalf
			of when entering time, invoices or other records.</p>";

		print "<p>This feature is useful for doing things such as assigning a secretary to be able to fill
			in timesheet for both themselves and their manager, or allowing accounting staff to be
			able to edit all staff member's timesheets in order to correct mistakes at billing time.</p>";
		

		// display table
		if (!$this->obj_table->data_num_rows)
		{
			print "<br><p><b>This user currently has no staff access rights.</b></p><br>";
		}
		else
		{
			// edit link
			$structure = NULL;
			$structure["id"]["value"]		= $this->id;
			$structure["staffid"]["column"]		= "staffid";
			$this->obj_table->add_link("full details", "user/user-staffaccess-edit.php", $structure);

			// display the table
			$this->obj_table->render_table_html();
		}

		// add link
		print "<p><b><a href=\"index.php?page=user/user-staffaccess-add.php&id=". $this->id ."\">Click here to add new staff access rights</a>.</b></p>";

	}	
}

?>
