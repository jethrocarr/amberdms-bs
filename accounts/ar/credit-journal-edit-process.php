<?php
/*
	accounts/ar/journal-edit-process.php

	access: accounts_ar_write

	Allows the user to post an entry to the journal or edit an existing journal entry.
*/

// includes
require("../../include/config.php");
require("../../include/amberphplib/main.php");


if (user_permissions_get('accounts_ar_write'))
{
	/////////////////////////
	
	// start the journal processing

	$journal = New journal_process;
	$journal->prepare_set_journalname("account_ar_credit");

	// import form data
	$journal->process_form_input();

		
	//// ERROR CHECKING ///////////////////////


	// make sure the invoice ID submitted really exists
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM account_ar_credit WHERE id='". $journal->structure["customid"] ."'";
	$sql_obj->execute();
	
	if (!$sql_obj->num_rows())
	{
		$_SESSION["error"]["message"][]	= "Unable to find requested credit note to modify journal for.";
	}


	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"]["journal_edit"] = "failed";
		header("Location: ../../index.php?page=accounts/ar/credit-journal.php&id=". $journal->structure["customid"] ."&journalid=". $journal->structure["id"] ."&action=". $journal->structure["action"] ."");
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
		header("Location: ../../index.php?page=accounts/ar/credit-journal.php&id=". $journal->structure["customid"] ."");
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
