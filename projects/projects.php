<?php
/*
	projects.php
	
	access: "projects_view" group members

	Displays a list of all the projects on the system.
*/

class page_output
{
	var $obj_table;


	function check_permissions()
	{
		return user_permissions_get("projects_view");
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
		$this->obj_table->tablename	= "project_list";

		// define all the columns and structure
		$this->obj_table->add_column("standard", "code_project", "");
		$this->obj_table->add_column("standard", "name_project", "");
		$this->obj_table->add_column("standard", "project_quote", "");
		$this->obj_table->add_column("date", "date_start", "");
		$this->obj_table->add_column("date", "date_end", "");

		// defaults
		$this->obj_table->columns		= array("code_project", "name_project", "date_start", "date_end");
		$this->obj_table->columns_order		= array("name_project");
		$this->obj_table->columns_order_options	= array("code_project", "name_project", "date_start", "date_end");

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("projects");
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "");

		// filter options
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
		$structure["sql"]	= "(code_project LIKE '%value%' OR name_project LIKE '%value%' OR project_quote LIKE '%value%')";
		$this->obj_table->add_filter($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "hide_ex_projects";
		$structure["type"]		= "checkbox";
		$structure["sql"]		= "date_end='0000-00-00'";
		$structure["defaultvalue"]	= "on";
		$structure["options"]["label"]	= "Hide completed projects";
		$this->obj_table->add_filter($structure);

		// load options
		$this->obj_table->load_options_form();
		
	
		// fetch all the project information
		$this->obj_table->generate_sql();
		$this->obj_table->load_data_sql();

	}

	function render_html()
	{
		// heading
		print "<h3>PROJECT LIST</h3><br><br>";


		// display options form
		$this->obj_table->render_options_form();


		// display table data
		if (!count($this->obj_table->columns))
		{
			format_msgbox("important", "<p>Please select some valid options to display.</p>");
		}
		elseif (!$this->obj_table->data_num_rows)
		{
			format_msgbox("info", "<p>You currently have no projects in your database.</p>");
		}
		else
		{
			// details link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("details", "projects/view.php", $structure);
			
			// phases link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("phases", "projects/phases.php", $structure);
			
			// timebooked link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("timebooked", "projects/timebooked.php", $structure);

			// expenses link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("expenses", "projects/expenses.php", $structure);
			
			// timebilled link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("timebilled", "projects/timebilled.php", $structure);



			// display the table
			$this->obj_table->render_table_html();

			// display CSV/PDF download link
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=csv&page=projects/projects.php\">Export as CSV</a></p>";
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=pdf&page=projects/projects.php\">Export as PDF</a></p>";
		}

	}


	function render_pdf()
	{
		$this->obj_table->render_table_pdf();
	}


	function render_csv()
	{
		$this->obj_table->render_table_csv();
	}

}

?>
