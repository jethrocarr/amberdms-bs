<?php
/*
	accounts/ap/journal-edit-process.php

	access: accounts_ap_write

	Allows the user to post an entry to the journal or edit an existing journal entry.
*/

// includes
require("../../include/config.php");
require("../../include/amberphplib/main.php");


if (user_permissions_get('accounts_ap_write'))
{
	/////////////////////////
	
	// start the journal processing

	$journal = New journal_process;
	$journal->prepare_set_journalname("account_ap");

	// import form data
	$journal->process_form_input();

		
	//// ERROR CHECKING ///////////////////////


	// make sure the invoice ID submitted really exists
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM account_ap WHERE id='". $journal->structure["customid"] ."'";
	$sql_obj->execute();
	
	if (!$sql_obj->num_rows())
	{
		$_SESSION["error"]["message"][]	= "Unable to find requested invoice/transaction to modify journal for.";
	}


	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"]["journal_edit"] = "failed";
		header("Location: ../../index.php?page=accounts/ap/journal.php&id=". $journal->structure["customid"] ."&journalid=". $journal->structure["id"] ."&action=". $journal->structure["action"] ."");
		exit(0);
	}
	else
	{
		// what action should we take?
		if ($journal->structure["id"])
		{
			// update or delete?
			if ($journal->structure["action"] == "delete")
			{
				// DELETE
				
				if ($journal->action_delete())
				{
					$_SESSION["notification"]["message"][] = "Journal entry successfully removed.";
				}
				else
				{
					$_SESSION["error"]["message"][] = "An error occured whilst deleting the journal entry.";
				}
			}
			else
			{
				// UPDATE
			
				if ($journal->action_update())
				{
					$_SESSION["notification"]["message"][] = "Journal entry updated successfully.";
				}
				else
				{
					$_SESSION["error"]["message"][] = "An error occured whilst updating the journal.";
				}
			}
			
		}
		else
		{
			// CREATE
			
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
		header("Location: ../../index.php?page=accounts/ap/journal.php&id=". $journal->structure["customid"] ."");
		exit(0);
	}

	/////////////////////////
	
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../../index.php?page=message.php");
	exit(0);
}


?>
