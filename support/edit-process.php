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
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM `support_tickets` WHERE id='$id' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "process", "The ticket you have attempted to edit - $id - does not exist in this system.");
		}
	}
	else
	{
		$mode = "add";
	}


		
	//// ERROR CHECKING ///////////////////////


	// make sure we don't choose a ticket that has already been taken
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM `support_tickets` WHERE title='". $data["title"] ."'";

	if ($id)
		$sql_obj->string .= " AND id!='$id'";

	$sql_obj->execute();

	if ($sql_obj->num_rows())
	{
		log_write("error", "process", "This title is already used by another support ticket - please choose a unique title.");
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
		// start transaction
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();


		if ($mode == "add")
		{
			// create a new entry in the DB
			$sql_obj->string	= "INSERT INTO `support_tickets` (title) VALUES ('".$data["title"]."')";
			$sql_obj->execute();
	
			$id = $sql_obj->fetch_insert_id();
		}

		if ($id)
		{
			// update ticket details
			$sql_obj->string	= "UPDATE `support_tickets` SET "
							."title='". $data["title"] ."', "
							."priority='". $data["priority"] ."', "
							."details='". $data["details"] ."', "
							."status='". $data["status"] ."', "
							."date_start='". $data["date_start"] ."', "
							."date_end='". $data["date_end"] ."' "
							."WHERE id='$id' LIMIT 1";
			$sql_obj->execute();
		}


		// update journal
		if ($mode == "add")
		{
			journal_quickadd_event("support_tickets", $id, "Support ticket created");
		}
		else
		{
			journal_quickadd_event("support_tickets", $id, "Support ticket details updated");
		}


		// commit
		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "process", "An error occured whilst updating support ticket. No changes have been made.");
		}
		else
		{
			$sql_obj->trans_commit();

			if ($mode == "add")
			{
				log_write("notification", "process", "Support ticket successfully created.");
			}
			else
			{
				log_write("notification", "process", "Support ticket successfully updated.");
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
