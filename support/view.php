<?php
/*
	support_tickets/view.php
	
	access: support_tickets_view (read-only)
		support_tickets_write (write access)

	Displays all the details for the support_ticket and if the user has correct
	permissions allows the support_ticket to be updated.
*/

class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_form;

	function page_output()
	{
		// fetch variables
		$this->id = @security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Support Ticket Details", "page=support/view.php&id=". $this->id ."", TRUE);
		$this->obj_menu_nav->add_item("Support Ticket Journal", "page=support/journal.php&id=". $this->id ."");

		if (user_permissions_get("support_write"))
		{
			$this->obj_menu_nav->add_item("Delete Support Ticket", "page=support/delete.php&id=". $this->id ."");
		}
	}



	function check_permissions()
	{
		return user_permissions_get("support_view");
	}



	function check_requirements()
	{
		// verify that support ticket exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM support_tickets WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested support ticket (". $this->id .") does not exist - possibly the ticket has been deleted.");
			return 0;
		}

		unset($sql_obj);


		return 1;
	}


	function execute()
	{

		/*
			Define form structure
		*/
		$this->obj_form = New form_input;
		$this->obj_form->formname = "support_ticket_view";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "support/edit-process.php";
		$this->obj_form->method = "post";
		

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "id_support_ticket";
		$structure["type"]		= "text";
		$structure["defaultvalue"]	= $this->id;
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "title";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "date_start";
		$structure["type"]		= "date";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "date_end";
		$structure["type"]		= "date";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "details";
		$structure["type"]		= "textarea";
		$structure["options"]["width"]	= "600";
		$structure["options"]["height"]	= "100";
		$this->obj_form->add_input($structure);

		// status + priority
		$structure = form_helper_prepare_dropdownfromdb("status", "SELECT id, value as label FROM support_tickets_status");
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = form_helper_prepare_dropdownfromdb("priority", "SELECT id, value as label FROM support_tickets_priority");
		$this->obj_form->add_input($structure);


		// customer/product/project/service ID


		// submit section
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Save Changes";
		$this->obj_form->add_input($structure);
		
		
		// define subforms
		$this->obj_form->subforms["support_ticket_details"]	= array("id_support_ticket", "title", "priority", "details");
		$this->obj_form->subforms["support_ticket_status"]	= array("status", "date_start", "date_end");
		
		if (user_permissions_get("support_write"))
		{
			$this->obj_form->subforms["submit"]		= array("submit");
		}
		else
		{
			$this->obj_form->subforms["submit"]		= array();
		}

		
		// fetch the form data
		$this->obj_form->sql_query = "SELECT * FROM `support_tickets` WHERE id='". $this->id ."' LIMIT 1";
		$this->obj_form->load_data();

	}


	function render_html()
	{
		// Title + Summary
		print "<h3>SUPPORT TICKET DETAILS</h3><br>";
		print "<p>This page allows you to view and set the general details for this support ticket. For full content of the support ticket including attached files and emails, see the journal.</p>";


		// display the form
		$this->obj_form->render_form();

		if (!user_permissions_get("support_write"))
		{
			format_msgbox("locked", "<p>Sorry, you do not have permissions to make changes to the support ticket details.</p>");
		}
	}
}

?>
