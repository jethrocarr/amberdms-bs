<?php
/*
	timebilled.php
	
	access: "projects_view" group members

	Displays groups of time for invoicing purposes.
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
	
	$_SESSION["nav"]["title"][]	= "Timebooked";
	$_SESSION["nav"]["query"][]	= "page=projects/timebooked.php&id=$projectid";
	
	$_SESSION["nav"]["title"][]	= "Timebilled/Grouped";
	$_SESSION["nav"]["query"][]	= "page=projects/timebilled.php&id=$projectid";
	$_SESSION["nav"]["current"]	= "page=projects/timebilled.php&id=$projectid";
	
	$_SESSION["nav"]["title"][]	= "Project Journal";
	$_SESSION["nav"]["query"][]	= "page=projects/journal.php&id=$projectid";

	$_SESSION["nav"]["title"][]	= "Delete Project";
	$_SESSION["nav"]["query"][]	= "page=projects/delete.php&id=$projectid";


	function page_render()
	{
		$projectid = security_script_input('/^[0-9]*$/', $_GET["id"]);

		// check that the specified project actually exists
		$mysql_string	= "SELECT id, name_project FROM `projects` WHERE id='$projectid'";
		$mysql_result	= mysql_query($mysql_string);
		$mysql_num_rows	= mysql_num_rows($mysql_result);

		if (!$mysql_num_rows)
		{
			print "<p><b>Error: The requested project does not exist. <a href=\"index.php?page=projects/projects.php\">Try looking for your project on the project list page.</a></b></p>";
		}
		else
		{
			$mysql_data = mysql_fetch_array($mysql_result);
			
			// heading
			print "<h3>TIME BILLED/GROUPED</h3>";

			// TODO: add more details explaining how to use time grouping
			print "<p>This page shows all the time that has been grouped and invoiced for the ". $mysql_data["name_project"] ." project.</p>";
		
		
			/// Basic Table Structure

			// establish a new table object
			$timereg_table = New table;

			$timereg_table->language	= $_SESSION["user"]["lang"];
			$timereg_table->tablename	= "time_billed";

			// define all the columns and structure
			$timereg_table->add_column("standard", "name_group", "time_groups.name_group");
			$timereg_table->add_column("standard", "name_customer", "customers.name_customer");
			$timereg_table->add_column("standard", "invoiceid", "time_groups.invoiceid");
			$timereg_table->add_column("standard", "description", "time_groups.description");
			$timereg_table->add_column("hourmins", "time_billed", "NONE");
			$timereg_table->add_column("hourmins", "time_not_billed", "NONE");

			// defaults
			$timereg_table->columns		= array("name_group", "name_customer", "invoiceid", "description", "time_billed", "time_not_billed");
			$timereg_table->columns_order	= array("name_customer", "name_group");

			// define SQL structure
			$timereg_table->sql_obj->prepare_sql_settable("time_groups");
			$timereg_table->sql_obj->prepare_sql_addfield("id", "time_groups.id");
			$timereg_table->sql_obj->prepare_sql_addjoin("LEFT JOIN customers ON time_groups.customerid = customers.id");
			$timereg_table->sql_obj->prepare_sql_addwhere("time_groups.projectid = '$projectid'");
			
			
			/// Filtering/Display Options

			// fixed options
			$timereg_table->add_fixed_option("id", $projectid);


			// acceptable filter options
			$structure = NULL;
			$structure["fieldname"] = "date_start";
			$structure["type"]	= "date";
			$structure["sql"]	= "date >= 'value'";
			$timereg_table->add_filter($structure);

			$structure = NULL;
			$structure["fieldname"] = "date_end";
			$structure["type"]	= "date";
			$structure["sql"]	= "date <= 'value'";
			$timereg_table->add_filter($structure);
			
			$structure		= form_helper_prepare_dropdownfromdb("customerid", "SELECT id, name_customer as label FROM customers ORDER BY name_customer ASC");
			$structure["sql"]	= "time_groups.customerid='value'";
			$timereg_table->add_filter($structure);

			$structure = NULL;
			$structure["fieldname"] = "searchbox";
			$structure["type"]	= "input";
			$structure["sql"]	= "time_groups.description LIKE '%value%' OR time_groups.name_group LIKE '%value%'";
			$timereg_table->add_filter($structure);



			// create totals
			$timereg_table->total_columns	= array("time_booked");
	
	
			// options form
			$timereg_table->load_options_form();
			$timereg_table->render_options_form();
			


			// generate & execute SQL query			
			$timereg_table->generate_sql();
			$timereg_table->load_data_sql();


			/// Display table data

			if (!$timereg_table->data_num_rows)
			{
				print "<p><b>There is currently no time registered to this project that matches your filter options.</b></p>";
			}
			else
			{
				// fetch the time totals
				// (because we have to do two different sums, we can't use a join)
				for ($i=0; $i < $timereg_table->data_num_rows; $i++)
				{
					$sql_obj		= New sql_query;
					$sql_obj->string	= "SELECT time_booked, billable FROM timereg WHERE groupid='". $timereg_table->data[$i]["id"] ."'";
					$sql_obj->execute();
					
					if ($sql_obj->num_rows())
					{
						$sql_obj->fetch_array();

						foreach ($sql_obj->data as $data)
						{
							if ($data["billable"] == 0)
							{
								$timereg_table->data[$i]["time_not_billed"] += $data["time_booked"];
							}
							else
							{
								$timereg_table->data[$i]["time_billed"] += $data["time_booked"];
							}
						}
					}
				}
				
			
				// add view/edit link
				$structure = NULL;
				$structure["projectid"]["value"]	= "$projectid";
				$structure["groupid"]["column"]		= "id";
				$timereg_table->add_link("view/edit", "projects/timebilled-edit.php", $structure);

				$timereg_table->render_table();
			}


			print "<p><b><a href=\"index.php?page=projects/timebilled-edit.php&projectid=$projectid\">Add new time group.</a></b></p>";



		} // end if project exists
		
	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
