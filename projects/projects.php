<?php
/*
	projects.php
	
	access: "projects_view" group members

	Displays a list of all the projects on the system.
*/

if (user_permissions_get('projects_view'))
{
	function page_render()
	{
		// establish a new table object
		$project_list = New table;

		$project_list->language	= $_SESSION["user"]["lang"];
		$project_list->tablename	= "project_list";

		// define all the columns and structure
		$project_list->add_column("standard", "code_project", "");
		$project_list->add_column("standard", "name_project", "");
		$project_list->add_column("date", "date_start", "");
		$project_list->add_column("date", "date_end", "");

		// defaults
		$project_list->columns		= array("code_project", "name_project", "date_start", "date_end");
		$project_list->columns_order	= array("name_project");

		// define SQL structure
		$project_list->sql_obj->prepare_sql_settable("projects");
		$project_list->sql_obj->prepare_sql_addfield("id", "");

		// filter options
		$structure = NULL;
		$structure["fieldname"] = "date_start";
		$structure["type"]	= "date";
		$structure["sql"]	= "date_start >= 'value'";
		$project_list->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] = "date_end";
		$structure["type"]	= "date";
		$structure["sql"]	= "date_end <= 'value' AND date_end != '0000-00-00'";
		$project_list->add_filter($structure);
			
		$structure = NULL;
		$structure["fieldname"] = "searchbox";
		$structure["type"]	= "input";
		$structure["sql"]	= "code_project LIKE '%value%' OR name_project LIKE '%value%'";
		$project_list->add_filter($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "hide_ex_projects";
		$structure["type"]		= "checkbox";
		$structure["sql"]		= "date_end='0000-00-00'";
		$structure["defaultvalue"]	= "on";
		$structure["options"]["label"]	= "Hide completed projects";
		$project_list->add_filter($structure);







		// heading
		print "<h3>PROJECT LIST</h3><br><br>";


		// options form
		$project_list->load_options_form();
		$project_list->render_options_form();


		// fetch all the project information
		$project_list->generate_sql();
		$project_list->load_data_sql();

		if (!count($project_list->columns))
		{
			print "<p><b>Please select some valid options to display.</b></p>";
		}
		elseif (!$project_list->data_num_rows)
		{
			print "<p><b>You currently have no projects in your database.</b></p>";
		}
		else
		{
			// view link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$project_list->add_link("view", "projects/view.php", $structure);

			// display the table
			$project_list->render_table();

			// TODO: display CSV download link

		}

		
	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
