<?php
/*
	customers/journal-edit-process.php

	access: customers_write

	Allows the user to post an entry to the journal or edit an existing journal entry.
*/

// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");


if (user_permissions_get('customers_write'))
{
	/////////////////////////
	
	// start the journal processing

	$journal = New journal_process;
	$journal->prepare_set_journalname("customers");

	// import form data
	$journal->process_form_input();

		
	//// ERROR CHECKING ///////////////////////


	// TODO: update this section to use newer customer framework?
	// make sure the customers ID submitted really exists
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM customers WHERE id='". $journal->structure["customid"] ."' LIMIT 1";
	$sql_obj->execute();
	
	if (!$sql_obj->num_rows())
	{
		log_write("error", "process", "Unable to find requested customer record to modify journal for.");
	}


	// verify journal entry status
	if ($journal->check_lock())
	{
		log_write("error", "process", "Unable to edit the selected journal entry, it has been locked.");
	}

	
	// return to previous page upon error
	if (error_check())
	{	
		$_SESSION["error"]["form"]["journal_edit"] = "failed";
		header("Location: ../index.php?page=customers/journal.php&id=". $journal->structure["customid"] ."&journalid=". $journal->structure["id"] ."&action=". $journal->structure["action"] ."");
		exit(0);
	}
	else
	{
		if ($journal->structure["action"] == "delete")
		{
			$journal->action_delete();
		}
		else
		{
			// update or create
			$journal->action_update();
		}
			
	
		// display updated details
		header("Location: ../index.php?page=customers/journal.php&id=". $journal->structure["customid"] ."");
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
