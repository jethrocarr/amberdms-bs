<?php
/*
	staff.php
	
	access: "staff_view" group members

	Displays a list of all the staff on the system.
*/

class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_table;


	function check_permissions()
	{
		return user_permissions_get("staff_view");
	}

	function check_requirements()
	{
		// nothing todo
		return 1;
	}


	function execute()
	{
		// establish a new table object
		$this->obj_table = New table;

		$this->obj_table->language	= $_SESSION["user"]["lang"];
		$this->obj_table->tablename	= "staff_list";

		// define all the columns and structure
		$this->obj_table->add_column("standard", "staff_code", "staff_code");
		$this->obj_table->add_column("standard", "name_staff", "name_staff");
		$this->obj_table->add_column("standard", "staff_position", "staff_position");
		$this->obj_table->add_column("standard", "contact_phone", "contact_phone");
		$this->obj_table->add_column("standard", "contact_email", "contact_email");
		$this->obj_table->add_column("standard", "contact_fax", "contact_fax");
		$this->obj_table->add_column("date", "date_start", "date_start");
		$this->obj_table->add_column("date", "date_end", "date_end");

		// defaults
		$this->obj_table->columns		= array("name_staff", "staff_code", "staff_position", "contact_phone", "contact_email");
		$this->obj_table->columns_order		= array("name_staff");
		$this->obj_table->columns_order_options	= array("name_staff", "staff_code", "staff_position", "contact_phone", "contact_email", "contact_fax", "date_start", "date_end");

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("staff");
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "");


		// acceptable filter options
		$structure = NULL;
		$structure["fieldname"] = "date_start";
		$structure["type"]	= "date";
		$structure["sql"]	= "date_start >= 'value'";
		$this->obj_table->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] = "date_end";
		$structure["type"]	= "date";
		$structure["sql"]	= "date_end <= 'value' AND date_end != '0000-00-00'";
		$this->obj_table->add_filter($structure);
		
		$structure = NULL;
		$structure["fieldname"] = "searchbox";
		$structure["type"]	= "input";
		$structure["sql"]	= "staff_code LIKE '%value%' OR name_staff LIKE '%value%' OR staff_position LIKE '%value%' OR contact_email LIKE '%value%' OR contact_phone LIKE '%value%' OR contact_fax LIKE '%fax%'";
		$this->obj_table->add_filter($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "hide_ex_employees";
		$structure["type"]		= "checkbox";
		$structure["sql"]		= "date_end='0000-00-00'";
		$structure["defaultvalue"]	= "on";
		$structure["options"]["label"]	= "Hide any ex-employees";
		$this->obj_table->add_filter($structure);
	
		// load options
		$this->obj_table->load_options_form();
		
		// fetch all the staff information
		$this->obj_table->generate_sql();
		$this->obj_table->load_data_sql();
	}



	function render_html()
	{
		// heading
		print "<h3>STAFF LIST</h3><br><br>";

		// display options form
		$this->obj_table->render_options_form();
		
		// display data
		if (!count($this->obj_table->columns))
		{
			format_msgbox("important", "<p>Please select some valid options to display.</p>");
		}
		elseif (!$this->obj_table->data_num_rows)
		{
			format_msgbox("info", "<p>You currently have no staff in your database.</p>");
		}
		else
		{
			// links
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("tbl_lnk_details", "hr/staff-view.php", $structure);

			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("tbl_lnk_timesheet", "hr/staff-timebooked.php", $structure);


			// display the table
			$this->obj_table->render_table_html();

			// display CSV download link
			print "<p align=\"right\"><a href=\"index-export.php?mode=csv&page=hr/staff.php\">Export as CSV</a></p>";
		}
	}


	function render_csv()
	{
		// display the table
		$this->obj_table->render_table_csv();
		
	}
	
	
	
} // end of page_output class

?>
