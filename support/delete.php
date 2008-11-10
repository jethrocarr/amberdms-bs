<?php
/*
	support/delete.php
	
	access:	support_write

	Allows an unwanted support ticket to be deleted.
*/

if (user_permissions_get('support_write'))
{
	$id = $_GET["id"];
	
	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "Support Ticket Details";
	$_SESSION["nav"]["query"][]	= "page=support/view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Support Ticket Journal";
	$_SESSION["nav"]["query"][]	= "page=support/journal.php&id=$id";
	
	$_SESSION["nav"]["title"][]	= "Delete Support Ticket";
	$_SESSION["nav"]["query"][]	= "page=support/delete.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=support/delete.php&id=$id";



	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		print "<h3>DELETE SUPPORT TICKET</h3><br>";
		print "<p>This page allows you to delete an unwanted support ticket.</p>";

		$mysql_string	= "SELECT id FROM `support_tickets` WHERE id='$id'";
		$mysql_result	= mysql_query($mysql_string);
		$mysql_num_rows	= mysql_num_rows($mysql_result);

		if (!$mysql_num_rows)
		{
			print "<p><b>Error: The requested support ticket does not exist. <a href=\"index.php?page=support/support.php\">Try looking for your support ticket on the support tickets page.</a></b></p>";
		}
		else
		{
			/*
				Define form structure
			*/
			$form = New form_input;
			$form->formname = "support_tickets_delete";
			$form->language = $_SESSION["user"]["lang"];

			$form->action = "support/delete-process.php";
			$form->method = "post";
			

			// general
			$structure = NULL;
			$structure["fieldname"] 	= "title";
			$structure["type"]		= "text";
			$form->add_input($structure);


			// hidden
			$structure = NULL;
			$structure["fieldname"] 	= "id_support_ticket";
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= "$id";
			$form->add_input($structure);
			
			
			// confirm delete
			$structure = NULL;
			$structure["fieldname"] 	= "delete_confirm";
			$structure["type"]		= "checkbox";
			$structure["options"]["label"]	= "Yes, I wish to delete this support ticket and realise that once deleted the data can not be recovered.";
			$form->add_input($structure);


	
			// define submit field
			$structure = NULL;
			$structure["fieldname"]		= "submit";
			$structure["type"]		= "submit";
			$structure["defaultvalue"]	= "delete";
			$form->add_input($structure);


			
			// define subforms
			$form->subforms["support_delete"]	= array("title");
			$form->subforms["hidden"]		= array("id_support_ticket");
			$form->subforms["submit"]		= array("delete_confirm", "submit");

			
			// fetch the form data
			$form->sql_query = "SELECT title FROM `support_tickets` WHERE id='$id' LIMIT 1";		
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
