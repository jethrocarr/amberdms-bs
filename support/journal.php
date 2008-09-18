<?php
/*
	support_tickets/journal.php
	
	access: support_tickets_view (read-only)
		support_tickets_write (write access)

	The support ticket system uses the standard journal system in order
	to record all the events going on.
*/

if (user_permissions_get('support_view'))
{
	$id = $_GET["id"];
	
	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "Support Ticket Details";
	$_SESSION["nav"]["query"][]	= "page=support/view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Support Ticket Journal";
	$_SESSION["nav"]["query"][]	= "page=support/journal.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=support/journal.php&id=$id";
	
	$_SESSION["nav"]["title"][]	= "Delete Support Ticket";
	$_SESSION["nav"]["query"][]	= "page=support/delete.php&id=$id";


	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		print "<h3>SUPPORT TICKET JOURNAL</h3><br>";
		print "<p>Use this journal to file all notes, attachments or other information relating to this support ticket.</p>";


		// make sure the support ticket exists
		$sql = New sql_query;
		$sql->string = "SELECT id FROM `support_tickets` WHERE id='$id'";
		$sql->execute();
		
		if (!$sql->num_rows())
		{
			print "<p><b>Error: The requested support_ticket does not exist. <a href=\"index.php?page=support_tickets/support_tickets.php\">Try looking for your support_ticket on the support_ticket list page.</a></b></p>";
		}
		else
		{
			/*
				Define the journal structure
			*/

			// basic
			$journal		= New journal_display;
			$journal->journalname	= "support_tickets";

			// define SQL structure
			$journal->sql_obj->prepare_sql_addwhere("customid='$id'");		// we only want journal entries for this ticket!

			// process SQL			
			$journal->generate_sql();
			$journal->load_data();

			// display			
			$journal->render_journal();
			
		

		}

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
