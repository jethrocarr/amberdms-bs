<?php
/*
	support_tickets/add.php
	
	access: support_tickets_write

	Allows new support tickets to be added to the database.
*/


class page_output
{
	var $obj_form;	// page form


	function check_permissions()
	{
		return user_permissions_get("support_write");
	}

	function check_requirements()
	{
		// nothing todo
		return 1;
	}


	function execute()
	{
		/*
			Define form structure
		*/
		$this->obj_form = New form_input;
		$this->obj_form->formname = "support_ticket_add";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "support/edit-process.php";
		$this->obj_form->method = "post";
		

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "title";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "date_start";
		$structure["type"]		= "date";
		$structure["defaultvalue"]	= date("Y-m-d");
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "date_end";
		$structure["type"]		= "date";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "details";
		$structure["type"]		= "textarea";
		$this->obj_form->add_input($structure);

		// status + priority
		$structure = form_helper_prepare_dropdownfromdb("status", "SELECT id, value as label FROM support_tickets_status");
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = form_helper_prepare_dropdownfromdb("priority", "SELECT id, value as label FROM support_tickets_priority");
		$this->obj_form->add_input($structure);


		// customer/product/project/service ID


		// submit section
		if (user_permissions_get("support_write"))
		{
			$structure = NULL;
			$structure["fieldname"] 	= "submit";
			$structure["type"]		= "submit";
			$structure["defaultvalue"]	= "Save Changes";
			$this->obj_form->add_input($structure);
		
		}
		else
		{
			$structure = NULL;
			$structure["fieldname"] 	= "submit";
			$structure["type"]		= "message";
			$structure["defaultvalue"]	= "<p><i>Sorry, you don't have permissions to make changes to support_ticket records.</i></p>";
			$this->obj_form->add_input($structure);
		}
		
		
		// define subforms
		$this->obj_form->subforms["support_ticket_details"]	= array("title", "priority", "details");
		$this->obj_form->subforms["support_ticket_status"]	= array("status", "date_start", "date_end");
		$this->obj_form->subforms["submit"]			= array("submit");

		
		// fetch the form data
		$this->obj_form->load_data_error();


	}

	
	function render_html()
	{
		// Title + Summary
		print "<h3>ADD SUPPORT TICKET</h3><br>";
		print "<p>This page allows you to add a new support ticket to the database.</p>";

		// display the form
		$this->obj_form->render_form();
	}

}

?>
