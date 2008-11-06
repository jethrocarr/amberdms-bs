<?php
/*
	projects/timebilled-edit-process.php

	access: projects_write

	Allows the creation of new time groups, or adjustments to existing ones.
*/

// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");


if (user_permissions_get('projects_write'))
{
	/////////////////////////

	$projectid			= security_form_input_predefined("int", "projectid", 1, "");
	$groupid			= security_form_input_predefined("int", "groupid", 0, "");
	
	$data["name_group"]		= security_form_input_predefined("any", "name_group", 1, "");
	$data["customerid"]		= security_form_input_predefined("int", "customerid", 1, "");
	$data["description"]		= security_form_input_predefined("any", "description", 0, "");




	//// VERIFY PROJECT/TIME GROUP IDS /////////////
	

	// check that the specified project actually exists
	$mysql_string	= "SELECT id FROM `projects` WHERE id='$projectid'";
	$mysql_result	= mysql_query($mysql_string);
	$mysql_num_rows	= mysql_num_rows($mysql_result);

	if (!$mysql_num_rows)
	{
		$_SESSION["error"]["message"][] = "The project you have attempted to edit - $projectid - does not exist in this system.";
	}
	else
	{
		if ($groupid)
		{
			$mode = "edit";
			
			// are we editing an existing group? make sure it exists and belongs to this project
			$mysql_string	= "SELECT projectid FROM time_groups WHERE id='$groupid'";
			$mysql_result	= mysql_query($mysql_string);
			$mysql_num_rows	= mysql_num_rows($mysql_result);

			if (!$mysql_num_rows)
			{
				$_SESSION["error"]["message"][] = "The time group you have attempted to edit - $groupid - does not exist in this system.";
			}
			else
			{
				$mysql_data = mysql_fetch_array($mysql_result);

				if ($mysql_data["projectid"] != $projectid)
				{
					$_SESSION["error"]["message"][] = "The requested time group does not match the provided project ID. Potential application bug?";
				}
				
			}
		}
		else
		{
			$mode = "add";
		}
	}


	/*
		Fetch all the information for the time entries.
	*/

	$sql_entries_obj = New sql_query;
	
	$sql_entries_obj->prepare_sql_settable("timereg");
	
	$sql_entries_obj->prepare_sql_addfield("id", "");
	$sql_entries_obj->prepare_sql_addfield("groupid", "");
	$sql_entries_obj->prepare_sql_addfield("billable", "");
	$sql_entries_obj->prepare_sql_addfield("time_booked", "");
	
	if ($groupid)
	{
		$sql_entries_obj->prepare_sql_addwhere("groupid='$groupid' OR !groupid");
	}
	else
	{
		$sql_entries_obj->prepare_sql_addwhere("!groupid");
	}

	$sql_entries_obj->generate_sql();
	$sql_entries_obj->execute();

	if ($sql_entries_obj->num_rows())
	{
		$sql_entries_obj->fetch_array();

		foreach ($sql_entries_obj->data as $entries_data)
		{
			// only get the data for selected time entries
			if ($_POST["time_". $entries_data["id"] ."_bill"] == "on")
			{
				$data["time_entries"][ $entries_data["id"] ]["billable"] = 1;
			}
			elseif ($_POST["time_". $entries_data["id"] ."_nobill"] == "on")
			{
				$data["time_entries"][ $entries_data["id"] ]["billable"] = 0;
			}
		}
	}


		
	//// ERROR CHECKING ///////////////////////


	// make sure we don't choose a time group name that has already been used somewhere in the same project
	// (we don't mind if other projects have the same name)
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM time_groups WHERE name_group='". $data["name_group"] ."' AND projectid='$projectid'";
	
	if ($groupid)
		$sql_obj->string .= " AND id!='$groupid'";


	$sql_obj->execute();

	if ($sql_obj->num_rows())
	{
		$_SESSION["error"]["message"][] = "This group name is already used in this project - please choose a unique name.";
		$_SESSION["error"]["name_project-error"] = 1;
	}


	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"]["timebilled_view"] = "failed";
		header("Location: ../index.php?page=projects/timebilled-edit.php&projectid=$projectid&groupid=$groupid");
		exit(0);
	}
	else
	{
		/*
			Add a new group if required
		*/
		if ($mode == "add")
		{
			$mysql_string = "INSERT INTO `time_groups` (projectid) VALUES ('$projectid')";
			if (!mysql_query($mysql_string))
			{
				$_SESSION["error"]["message"][] = "A fatal SQL error occured: ". $mysql_error();
			}

			$groupid = mysql_insert_id();
		}


		if ($groupid)
		{
			/*
				Update details
			*/
			
			$sql_obj = New sql_query;
			$sql_obj->string = "UPDATE time_groups SET "
						."name_group='". $data["name_group"] ."', "
						."customerid='". $data["customerid"] ."', "
						."description='". $data["description"] ."' "
						."WHERE id='$groupid'";
			
			if (!$sql_obj->execute())
			{
				$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst trying to update details";
			}
			else
			{

				/*
					Update time entries

					Here we run though all the entries returned from the database, and
					then compare them to the entries that the user has selected.

					This will allow us to work out if there have been any changes - eg: changed
					from billable to non-billable, or removed from the group.
				*/
				
				foreach ($sql_entries_obj->data as $entries_data)
				{
					if ($entries_data["groupid"])
					{
						// time entry already part of this group

						if ($data["time_entries"][ $entries_data["id"] ])
						{
							if ($entries_data["billable"] != $data["time_entries"][ $entries_data["id"] ]["billable"])
							{
								// the billable status of this entry has changed.
								$sql_obj		= New sql_query;
								$sql_obj->string	= "UPDATE timereg SET billable='". $data["time_entries"][ $entries_data["id"] ]["billable"] ."' WHERE id='". $entries_data["id"] ."'";
								$sql_obj->execute();
							}
						}
						else
						{
							// the user has removed this entry from the group
							$sql_obj		= New sql_query;
							$sql_obj->string	= "UPDATE timereg SET billable='0', groupid='0' WHERE id='". $entries_data["id"] ."'";
							$sql_obj->execute();
						}
					}
					else
					{
						if ($data["time_entries"][ $entries_data["id"] ])
						{
							// the entry has been added to the group.
							$sql_obj		= New sql_query;
							$sql_obj->string	= "UPDATE timereg SET groupid='$groupid', billable='". $data["time_entries"][ $entries_data["id"] ]["billable"] ."' WHERE id='". $entries_data["id"] ."'";
							$sql_obj->execute();
						}
					}
				}
				


			
				if ($mode == "add")
				{
					$_SESSION["notification"]["message"][] = "Time billing group successfully created.";
				}
				else
				{
					$_SESSION["notification"]["message"][] = "Time billing group successfully updated.";
				}
				
			}
		}

		// display updated details
		header("Location: ../index.php?page=projects/timebilled.php&id=$projectid");
		exit(0);
	}

	/////////////////////////
	
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
