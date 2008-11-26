<?php
/*
	support/edit-process.php

	access: support_tickets_write

	Allows the general details of a support ticket to be adjusted.
*/

// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");


if (user_permissions_get('support_write'))
{
	/////////////////////////

	$id				= security_form_input_predefined("int", "id_support_ticket", 0, "");
	
	$data["title"]			= security_form_input_predefined("any", "title", 1, "You must provide the support ticket with a title");
	$data["priority"]		= security_form_input_predefined("any", "priority", 1, "You must select a priority for the support ticket");
	$data["details"]		= security_form_input_predefined("any", "details", 0, "");
	$data["status"]			= security_form_input_predefined("any", "status", 1, "You must set the status.");
	$data["date_start"]		= security_form_input_predefined("date", "date_start", 1, "");
	$data["date_end"]		= security_form_input_predefined("date", "date_end", 0, "");
	

	// are we editing an existing ticket or a new one?
	if ($id)
	{
		$mode = "edit";

		// make sure the ticket exists
		$mysql_string		= "SELECT id FROM `support_tickets` WHERE id='$id'";
		$mysql_result		= mysql_query($mysql_string);
		$mysql_num_rows		= mysql_num_rows($mysql_result);

		if (!$mysql_num_rows)
		{
			$_SESSION["error"]["message"][] = "The ticket you have attempted to edit - $id - does not exist in this system.";
		}
	}
	else
	{
		$mode = "add";
	}


		
	//// ERROR CHECKING ///////////////////////


	// make sure we don't choose a ticket that has already been taken
	$mysql_string	= "SELECT id FROM `support_tickets` WHERE title='". $data["title"] ."'";
	if ($id)
		$mysql_string .= " AND id!='$id'";
	$mysql_result	= mysql_query($mysql_string);
	$mysql_num_rows	= mysql_num_rows($mysql_result);

	if ($mysql_num_rows)
	{
		$_SESSION["error"]["message"][] = "This title is already used by another support ticket - please choose a unique title.";
		$_SESSION["error"]["title-error"] = 1;
	}


	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		if ($mode == "edit")
		{
			$_SESSION["error"]["form"]["support_ticket_view"] = "failed";
			header("Location: ../index.php?page=support/view.php&id=$id");
			exit(0);
		}
		else
		{
			$_SESSION["error"]["form"]["support_ticket_add"] = "failed";
			header("Location: ../index.php?page=support/add.php");
			exit(0);
		}
	}
	else
	{
		if ($mode == "add")
		{
			// create a new entry in the DB
			$mysql_string = "INSERT INTO `support_tickets` (title) VALUES ('".$data["title"]."')";
			if (!mysql_query($mysql_string))
			{
				$_SESSION["error"]["message"][] = "A fatal SQL error occured: ". $mysql_error();
			}

			$id = mysql_insert_id();
		}

		if ($id)
		{
			// update ticket details
			$mysql_string = "UPDATE `support_tickets` SET "
						."title='". $data["title"] ."', "
						."priority='". $data["priority"] ."', "
						."details='". $data["details"] ."', "
						."status='". $data["status"] ."', "
						."date_start='". $data["date_start"] ."', "
						."date_end='". $data["date_end"] ."' "
						."WHERE id='$id'";
			
			if (!mysql_query($mysql_string))
			{
				$_SESSION["error"]["message"][] = "A fatal SQL error occured: ". mysql_error();
			}
			else
			{
				if ($mode == "add")
				{
					// message + journal entry
					$_SESSION["notification"]["message"][] = "Support ticket successfully created.";
					journal_quickadd_event("support_tickets", $id, "Support ticket created");
				}
				else
				{
					// message + journal entry
					$_SESSION["notification"]["message"][] = "Support ticket successfully updated.";
					journal_quickadd_event("support_tickets", $id, "Support ticket details updated");
				}
				
			}
		}


		// display updated details
		header("Location: ../index.php?page=support/view.php&id=$id");
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
