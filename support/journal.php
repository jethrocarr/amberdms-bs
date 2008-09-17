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
	$_SESSION["nav"]["current"]	= "page=support/view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Support Ticket Journal";
	$_SESSION["nav"]["query"][]	= "page=support/journal.php&id=$id";
	
	$_SESSION["nav"]["title"][]	= "Delete Support Ticket";
	$_SESSION["nav"]["query"][]	= "page=support/delete.php&id=$id";


	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		print "<h3>SUPPORT TICKET DETAILS</h3><br>";
		print "<p>This page allows you to view and set the general details for this support ticket. For full content of the support ticket including attached files and emails, see the journal.</p>";


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
				Define form structure
			*/
			$form = New form_input;
			$form->formname = "support_ticket_view";
			$form->language = $_SESSION["user"]["lang"];

			$form->action = "support_tickets/edit-process.php";
			$form->method = "post";
			

			// general
			$structure = NULL;
			$structure["fieldname"] 	= "id_support";
			$structure["type"]		= "text";
			$structure["defaultvalue"]	= "$id";
			$form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"] 	= "title";
			$structure["type"]		= "input";
			$structure["options"]["req"]	= "yes";
			$form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"] 	= "date_start";
			$structure["type"]		= "date";
			$form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"] 	= "date_end";
			$structure["type"]		= "date";
			$form->add_input($structure);

			// status + priority
			$structure = form_helper_prepare_dropdownfromdb("status", "SELECT id, value as label FROM support_tickets_status");
			$form->add_input($structure);
    
			$structure = form_helper_prepare_dropdownfromdb("priority", "SELECT id, value as label FROM support_tickets_priority");
			$form->add_input($structure);


			// customer/product/project/service ID


			// submit section
			if (user_permissions_get("support_write"))
			{
				$structure = NULL;
				$structure["fieldname"] 	= "submit";
				$structure["type"]		= "submit";
				$structure["defaultvalue"]	= "Save Changes";
				$form->add_input($structure);
			
			}
			else
			{
				$structure = NULL;
				$structure["fieldname"] 	= "submit";
				$structure["type"]		= "message";
				$structure["defaultvalue"]	= "<p><i>Sorry, you don't have permissions to make changes to support_ticket records.</i></p>";
				$form->add_input($structure);
			}
			
			
			// define subforms
			$form->subforms["support_ticket_details"]	= array("id_support_ticket", "title", "priority");
			$form->subforms["support_ticket_status"]	= array("status", "date_start", "date_end");
			$form->subforms["submit"]			= array("submit");

			
			// fetch the form data
			$form->sql_query = "SELECT * FROM `support_tickets` WHERE id='$id' LIMIT 1";
			$form->load_data();

			// display the form
			$form->render_form();

		}

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
