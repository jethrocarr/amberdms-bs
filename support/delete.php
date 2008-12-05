<?php
/*
	support/delete.php
	
	access:	support_write

	Allows an unwanted support ticket to be deleted.
*/


class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_form;


	function page_output()
	{
		// fetch variables
		$this->id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Support Ticket Details", "page=support/view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Support Ticket Journal", "page=support/journal.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Delete Support Ticket", "page=support/delete.php&id=". $this->id ."", TRUE);
	}



	function check_permissions()
	{
		return user_permissions_get("support_write");
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
		$this->obj_form->formname = "support_tickets_delete";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "support/delete-process.php";
		$this->obj_form->method = "post";
		

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "title";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);


		// hidden
		$structure = NULL;
		$structure["fieldname"] 	= "id_support_ticket";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->id;
		$this->obj_form->add_input($structure);
		
		
		// confirm delete
		$structure = NULL;
		$structure["fieldname"] 	= "delete_confirm";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "Yes, I wish to delete this support ticket and realise that once deleted the data can not be recovered.";
		$this->obj_form->add_input($structure);



		// define submit field
		$structure = NULL;
		$structure["fieldname"]		= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "delete";
		$this->obj_form->add_input($structure);


		
		// define subforms
		$this->obj_form->subforms["support_delete"]	= array("title");
		$this->obj_form->subforms["hidden"]		= array("id_support_ticket");
		$this->obj_form->subforms["submit"]		= array("delete_confirm", "submit");

		
		// fetch the form data
		$this->obj_form->sql_query = "SELECT title FROM `support_tickets` WHERE id='". $this->id ."' LIMIT 1";
		$this->obj_form->load_data();

	}


	function render_html()
	{
		// Title + Summary
		print "<h3>DELETE SUPPORT TICKET</h3><br>";
		print "<p>This page allows you to delete an unwanted support ticket.</p>";

		// display the form
		$this->obj_form->render_form();
	}
}

?>
