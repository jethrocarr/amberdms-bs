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


	// make sure the customers ticket ID submitted really exists
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM customers WHERE id='". $journal->structure["id"] ."'";
	$sql_obj->execute();
	
	if ($sql_obj->num_rows())
	{
		$_SESSION["error"]["message"][]			= "The customer you have requested does not exist.";
	}


	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"]["journal_edit"] = "failed";
		header("Location: ../index.php?page=customers/journal.php&id=". $journal->structure["customid"] ."&journalid=". $journal->structure["id"] ."");
		exit(0);
	}
	else
	{
		// create or update the journal entry
		if ($journal->structure["id"])
		{
			if ($journal->action_update())
			{
				$_SESSION["notification"]["message"][] = "Journal entry updated successfully.";
			}
			else
			{
				$_SESSION["error"]["message"][] = "An error occured whilst updating the journal.";
			}
		}
		else
		{
			if ($journal->action_create())
			{
				$_SESSION["notification"]["message"][] = "Journal entry created successfully.";
			}
			else
			{
				$_SESSION["error"]["message"][] = "An error occured whilst creating the new journal entry.";
			}
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
