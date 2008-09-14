<?php
/*
	phases.php
	
	access: "projects_view" group members

	Displays a list of all the phases belonging to the selected project.
*/

if (user_permissions_get('projects_view'))
{
	$projectid = $_GET["id"];
	
	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "Project Details";
	$_SESSION["nav"]["query"][]	= "page=projects/view.php&id=$projectid";

	$_SESSION["nav"]["title"][]	= "Project Phases";
	$_SESSION["nav"]["query"][]	= "page=projects/phases.php&id=$projectid";
	$_SESSION["nav"]["current"]	= "page=projects/phases.php&id=$projectid";
	
	$_SESSION["nav"]["title"][]	= "Timebooked";
	$_SESSION["nav"]["query"][]	= "page=projects/timebooked.php&id=$projectid";
	
	$_SESSION["nav"]["title"][]	= "Project Journal";
	$_SESSION["nav"]["query"][]	= "page=projects/journal.php&id=$projectid";

	$_SESSION["nav"]["title"][]	= "Delete Project";
	$_SESSION["nav"]["query"][]	= "page=projects/delete.php&id=$projectid";

	function page_render()
	{
		$projectid = security_script_input('/^[0-9]*$/', $_GET["id"]);

		// check that the specified project actually exists
		$mysql_string	= "SELECT id FROM `projects` WHERE id='$projectid'";
		$mysql_result	= mysql_query($mysql_string);
		$mysql_num_rows	= mysql_num_rows($mysql_result);

		if (!$mysql_num_rows)
		{
			print "<p><b>Error: The requested project does not exist. <a href=\"index.php?page=projects/projects.php\">Try looking for your project on the project list page.</a></b></p>";
		}
		else
		{
			// heading
			print "<h3>PROJECT PHASES</h3>";
			print "<p>All projects need to have at least one project phase, which staff can then use to book time to. A typical usage example is to have different phases
			for different sections of work on the project - eg: \"design phase\", \"implementation phase\" and \"testing phase\".</p>";

			print "<p>You can check what time has been booked to the phases, by using the \"Timebooked\" button on the menu above.</p>";
		
		
			// establish a new table object
			$phase_list = New table;

			$phase_list->language	= $_SESSION["user"]["lang"];
			$phase_list->tablename	= "phase_list";
			$phase_list->sql_table	= "project_phases";

			// define all the columns and structure
			$phase_list->add_column("standard", "name_phase", "");
			$phase_list->add_column("standard", "description", "");

			// defaults
			$phase_list->columns		= array("name_phase", "description");
			$phase_list->columns_order	= array("name_phase");


			// additional SQL query options
			$phase_list->prepare_sql_addfield("id", "");
			$phase_list->prepare_sql_addwhere("projectid = '$projectid'");

			// run SQL query
			$phase_list->generate_sql();
			$phase_list->load_data_sql();

			if (!$phase_list->data_num_rows)
			{
				print "<p><b>You currently have no phases belonging to this project. <a href=\"index.php?page=projects/phase-edit.php&projectid=$projectid\">Click here to add a phase to your project</a>.</b></p>";
			}
			else
			{
				// edit link
				$structure = NULL;
				$structure["projectid"]["value"]	= $projectid;
				$structure["phaseid"]["column"]		= "id";
				$phase_list->add_link("edit", "projects/phase-edit.php", $structure);

				// display the table
				$phase_list->render_table();

				
				print "<p><b><a href=\"index.php?page=projects/phase-edit.php&projectid=$projectid\">Click here to add a new phase to your project</a>.</b></p>";
			}

		} // end if project exists
		
	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
