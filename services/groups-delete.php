<?php
/*
	services/groups-delete.php

	access:	services_write

	Allows an unwanted and unused service group to be deleted.
*/


require("include/services/inc_services_groups.php");

class page_output
{
	var $obj_service_group;
	var $obj_menu_nav;
	var $obj_form;


	function page_output()
	{
		// init
		$this->obj_service_group	= New service_groups;

		// fetch variables
		$this->obj_service_group->id	= @security_script_input('/^[0-9]*$/', $_GET["id"]);


		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Service Group Details", "page=services/groups-view.php&id=". $this->obj_service_group->id ."");
		$this->obj_menu_nav->add_item("Delete Service Group", "page=services/groups-delete.php&id=". $this->obj_service_group->id ."", TRUE);
	}



	function check_permissions()
	{
		return user_permissions_get("services_write");
	}



	function check_requirements()
	{
		// verify that service group exists
		if (!$this->obj_service_group->verify_id())
		{
			log_write("error", "page_output", "The requested service group (". $this->id .") does not exist - possibly the service group has already been deleted?");
			return 0;
		}

		unset($sql_obj);


		// check if the service group can be deleted
		$this->locked = $this->obj_service_group->check_delete_lock();

		return 1;
	}


	function execute()
	{
		/*
			Define form structure
		*/
		$this->obj_form			= New form_input;
		$this->obj_form->formname	= "service_group_delete";
		$this->obj_form->language	= $_SESSION["user"]["lang"];

		$this->obj_form->action		= "services/groups-delete-process.php";
		$this->obj_form->method		= "post";
		

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "group_name";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "group_description";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);


		// hidden
		$structure = NULL;
		$structure["fieldname"] 	= "id_service_group";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->obj_service_group->id;
		$this->obj_form->add_input($structure);
		
		
		// confirm delete
		$structure = NULL;
		$structure["fieldname"] 	= "delete_confirm";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "Yes, I wish to delete this service group and realise that once deleted the data can not be recovered.";
		$this->obj_form->add_input($structure);



		// define submit field
		$structure = NULL;
		$structure["fieldname"]		= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "delete";
				
		$this->obj_form->add_input($structure);


		
		// define subforms
		$this->obj_form->subforms["service_group_delete"]	= array("group_name", "group_description");
		$this->obj_form->subforms["hidden"]			= array("id_service_group");

		if ($this->locked)
		{
			$this->obj_form->subforms["submit"]	= array();
		}
		else
		{
			$this->obj_form->subforms["submit"]	= array("delete_confirm", "submit");
		}
		
		// fetch the form data
		$this->obj_form->sql_query = "SELECT group_name, group_description FROM `service_groups` WHERE id='". $this->obj_service_group->id ."' LIMIT 1";
		$this->obj_form->load_data();
		
	}
	


	function render_html()
	{

		// title/summary
		print "<h3>DELETE SERVICE GROUP</h3><br>";
		print "<p>This page allows you to delete any unwanted, empty, service groups.</p>";

		// display the form
		$this->obj_form->render_form();
		
		if ($this->locked)
		{
			format_msgbox("locked", "<p>This service group can not be deleted as services currently belong to it.</p>");
		}
	}


} // end page_output class


?>
