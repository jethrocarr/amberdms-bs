<?php
/*
	projects/timebilled-edit-process.php

	access: projects_timegroup

	Allows the creation of new time groups, or adjustments to existing ones.
*/

// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");


if (user_permissions_get('projects_timegroup'))
{
	// select the IDs that the user does have access to, unless if they
	// have full access
	if (!user_permissions_get("timekeeping_all_view"))
	{
		if (!$access_staff_ids = user_permissions_staff_getarray("timereg_view"))
		{
			log_write("error", "process", "Unable to create time group, as you have no access permissions to any staff.");
		}
	}

	/////////////////////////

	$projectid			= @security_form_input_predefined("int", "projectid", 1, "");
	$groupid			= @security_form_input_predefined("int", "groupid", 0, "");
	
	$data["name_group"]		= @security_form_input_predefined("any", "name_group", 1, "");
	$data["customerid"]		= @security_form_input_predefined("int", "customerid", 1, "");
	$data["code_invoice"]		= @security_form_input_predefined("any", "code_invoice", 0, "");
	$data["description"]		= @security_form_input_predefined("any", "description", 0, "");




	//// VERIFY PROJECT/TIME GROUP IDS /////////////
	

	// check that the specified project actually exists
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM `projects` WHERE id='$projectid' LIMIT 1";
	$sql_obj->execute();

	if (!$sql_obj->num_rows())
	{
		log_write("error", "process", "The project you have attempted to edit - $projectid - does not exist in this system.");
	}
	else
	{
		if ($groupid)
		{
			$mode = "edit";
			
			// are we editing an existing group? make sure it exists and belongs to this project
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT projectid FROM time_groups WHERE id='$groupid' LIMIT 1";
			$sql_obj->execute();

			if (!$sql_obj->num_rows())
			{
				log_write("error", "process", "The time group you have attempted to edit - $groupid - does not exist in this system.");
			}
			else
			{
				$sql_obj->fetch_array();

				if ($sql_obj->data[0]["projectid"] != $projectid)
				{
					log_write("error", "process", "The requested time group does not match the provided project ID. Potential application bug?");
				}

				if ($sql_obj->data[0]["locked"])
				{
					log_write("error", "process", "This time group can not be adjusted since it has now been locked.");
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
	$sql_entries_obj->prepare_sql_addfield("locked", "");
	$sql_entries_obj->prepare_sql_addfield("groupid", "");
	$sql_entries_obj->prepare_sql_addfield("billable", "");
	$sql_entries_obj->prepare_sql_addfield("time_booked", "");
	
	if ($groupid)
	{
		$sql_entries_obj->prepare_sql_addwhere("(groupid='$groupid' OR !groupid)");
	}
	else
	{
		$sql_entries_obj->prepare_sql_addwhere("!groupid");
	}

	// if user has limited employee access, only process time records for those employees
	if ($access_staff_ids)
	{
		$sql_entries_obj->prepare_sql_addwhere("employeeid IN (". format_arraytocommastring($access_staff_ids) .")");
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

			// save to session array
			$_SESSION["error"]["time_". $entries_data["id"] ."_bill"]	= $_POST["time_". $entries_data["id"] ."_bill"];
			$_SESSION["error"]["time_". $entries_data["id"] ."_nobill"]	= $_POST["time_". $entries_data["id"] ."_nobill"];
			
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
		header("Location: ../index.php?page=projects/timebilled-edit.php&id=$projectid&groupid=$groupid");
		exit(0);
	}
	else
	{
		/*
			Start Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();


		/*
			Add a new group (if required)
		*/
		if ($mode == "add")
		{
			$sql_obj->string	= "INSERT INTO `time_groups` (projectid) VALUES ('$projectid')";
			$sql_obj->execute();

			$groupid = $sql_obj->fetch_insert_id();
		}


		if ($groupid)
		{
			/*
				Update details
			*/
			
			$sql_obj->string = "UPDATE time_groups SET "
						."name_group='". $data["name_group"] ."', "
						."customerid='". $data["customerid"] ."', "
						."description='". $data["description"] ."' "
						."WHERE id='$groupid'";
			
			$sql_obj->execute();


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

							if ($entries_data["locked"] == "1")
							{
								$locked = "1";
							}
							else
							{
								$locked = "2";
							}
							
							$sql_obj->string	= "UPDATE timereg SET billable='". $data["time_entries"][ $entries_data["id"] ]["billable"] ."', locked='$locked' WHERE id='". $entries_data["id"] ."' LIMIT 1";
							$sql_obj->execute();
						}
					}
					else
					{
						// the user has removed this entry from the group
						if ($entries_data["locked"] == "1")
						{
							// keep the entry locked
							$locked = "1";
						}
						else
						{
							// time entry was locked by this group, we can unlock it
							$locked = "0";
						}
						
						$sql_obj->string	= "UPDATE timereg SET billable='0', groupid='0', locked='$locked' WHERE id='". $entries_data["id"] ."' LIMIT 1";
						$sql_obj->execute();
					}
				}
				else
				{
					if ($data["time_entries"][ $entries_data["id"] ])
					{
						// the entry has been added to the group.

						if ($entries_data["locked"] == "1")
						{
							$locked = "1";
						}
						else
						{
							$locked = "2";
						}
							
						
						$sql_obj->string	= "UPDATE timereg SET groupid='$groupid', billable='". $data["time_entries"][ $entries_data["id"] ]["billable"] ."', locked='$locked' WHERE id='". $entries_data["id"] ."'";
						$sql_obj->execute();
					}
				}
			}


			/*
				Commit
			*/
			if (error_check())
			{
				$sql_obj->trans_rollback();

				log_write("error", "process", "An error occured whilst attempting to update the time group. No changes have been made.");
		
				$_SESSION["error"]["form"]["timebilled_view"] = "failed";
				header("Location: ../index.php?page=projects/timebilled-edit.php&id=$projectid&groupid=$groupid");
				exit(0);
			}
			else
			{
				$sql_obj->trans_commit();


				if ($mode == "add")
				{
					log_write("notification", "process", "Time billing group successfully created.");
				}
				else
				{
					log_write("notification", "process", "Time billing group successfully updated.");
				}


				header("Location: ../index.php?page=projects/timebilled.php&id=$projectid");
				exit(0);
			}
		}

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
