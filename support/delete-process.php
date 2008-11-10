<?php
/*
	support/delete-process.php

	access: support_tickets_write

	Deletes an unwanted support ticket.
*/

// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");


if (user_permissions_get('support_write'))
{
	/////////////////////////

	$id				= security_form_input_predefined("int", "id_support_ticket", 1, "");

	// these exist to make error handling work right
	$data["title"]			= security_form_input_predefined("any", "title", 0, "");

	// confirm deletion
	$data["delete_confirm"]		= security_form_input_predefined("any", "delete_confirm", 1, "You must confirm the deletion");

	
	// make sure the support ticket actually exists
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM `support_tickets` WHERE id='$id'";
	$sql_obj->execute();
	
	if (!$sql_obj->num_rows())
	{
		$_SESSION["error"]["message"][] = "The support ticket you have attempted to edit - $id - does not exist in this system.";
	}


		
	//// ERROR CHECKING ///////////////////////
			


	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"]["support_ticket_delete"] = "failed";
		header("Location: ../index.php?page=support/delete.php&id=$id");
		exit(0);
		
	}
	else
	{

		/*
			Delete Support Ticket
		*/
			
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM support_tickets WHERE id='$id'";
			
		if (!$sql_obj->execute())
		{
			$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst trying to delete the support ticket";
		}
		else
		{		
			$_SESSION["notification"]["message"][] = "Support Ticket has been successfully deleted.";
		}



		/*
			Delete Journal
		*/
		journal_delete_entire("support_tickets", $id);



		// return to support ticket list
		header("Location: ../index.php?page=support/support.php");
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
