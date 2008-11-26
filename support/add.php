<?php
/*
	support_tickets/add.php
	
	access: support_tickets_write

	Allows new support tickets to be added to the database.
*/

if (user_permissions_get('support_write'))
{
	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		print "<h3>ADD SUPPORT TICKET</h3><br>";
		print "<p>This page allows you to add a new support ticket to the database.</p>";


		/*
			Define form structure
		*/
		$form = New form_input;
		$form->formname = "support_ticket_add";
		$form->language = $_SESSION["user"]["lang"];

		$form->action = "support/edit-process.php";
		$form->method = "post";
		

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "title";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "date_start";
		$structure["type"]		= "date";
		$structure["defaultvalue"]	= date("Y-m-d");
		$structure["options"]["req"]	= "yes";
		$form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "date_end";
		$structure["type"]		= "date";
		$form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "details";
		$structure["type"]		= "textarea";
		$form->add_input($structure);

		// status + priority
		$structure = form_helper_prepare_dropdownfromdb("status", "SELECT id, value as label FROM support_tickets_status");
		$structure["options"]["req"]	= "yes";
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
		$form->subforms["support_ticket_details"]	= array("title", "priority", "details");
		$form->subforms["support_ticket_status"]	= array("status", "date_start", "date_end");
		$form->subforms["submit"]			= array("submit");

		
		// fetch the form data
		$form->load_data_error();

		// display the form
		$form->render_form();

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
