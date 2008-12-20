<?php
/*
	projects/timebilled-delete.php
	
	access: projects_write

	Allows the deletion of a deletion page.
*/


class page_output
{
	var $id;
	var $groupid;
	
	var $obj_menu_nav;
	var $obj_form;

	var $obj_sql_entries;

	var $locked;


	function page_output()
	{
		// fetch variables
		$this->id	= security_script_input('/^[0-9]*$/', $_GET["id"]);
		$this->groupid	= security_script_input('/^[0-9]*$/', $_GET["groupid"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Project Details", "page=projects/view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Project Phases", "page=projects/phases.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Timebooked", "page=projects/timebooked.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Timebilled/Grouped", "page=projects/timebilled.php&id=". $this->id ."", TRUE);
		$this->obj_menu_nav->add_item("Project Journal", "page=projects/journal.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Delete Project", "page=projects/delete.php&id=". $this->id ."");
	}


	function check_permissions()
	{
		return user_permissions_get("projects_write");
	}



	function check_requirements()
	{
		// verify that project exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM projects WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested project (". $this->id .") does not exist - possibly the project has been deleted.");
			return 0;
		}

		unset($sql_obj);
		
		// verify that the time group exists and belongs to this project
		if ($this->groupid)
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT projectid, locked FROM time_groups WHERE id='". $this->groupid ."' LIMIT 1";
			$sql_obj->execute();

			if (!$sql_obj->num_rows())
			{
				log_write("error", "page_output", "The requested time group (". $this->groupid .") does not exist - possibly the time group has been deleted.");
				return 0;
			}
			else
			{
				$sql_obj->fetch_array();

				$this->locked = $sql_obj->data[0]["locked"];

				if ($sql_obj->data[0]["projectid"] != $this->id)
				{
					log_write("error", "page_output", "The requested time group (". $this->groupid .") does not belong to the selected project (". $this->id .")");
					return 0;
				}
			}
			
			unset($sql_obj);
		}

		return 1;
	}


	function execute()
	{
		/*
			Define form structure
		*/
		$this->obj_form = New form_input;
		$this->obj_form->formname = "timebilled_delete";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "projects/timebilled-delete-process.php";
		$this->obj_form->method = "post";
	
	
		// general
		$structure = NULL;
		$structure["fieldname"] 	= "name_group";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "name_customer";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "description";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "code_invoice";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "delete_confirm";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "Yes, I wish to delete this time group and realise that once deleted the data can not be recovered.";
		$this->obj_form->add_input($structure);

	


		// hidden values
		$structure = NULL;
		$structure["fieldname"]		= "projectid";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->id;
		$this->obj_form->add_input($structure);
		
		$structure = null;
		$structure["fieldname"]		= "groupid";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->groupid;
		$this->obj_form->add_input($structure);
	
		
		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "delete";
		$this->obj_form->add_input($structure);

		

		// fetch the form data if editing
		$this->obj_form->sql_query = "SELECT name_group, description, account_ar.code_invoice, customers.name_customer FROM time_groups LEFT JOIN customers ON customers.id = time_groups.customerid LEFT JOIN account_ar ON account_ar.id = time_groups.invoiceid WHERE time_groups.id='". $this->groupid ."' LIMIT 1";
		$this->obj_form->load_data();


		// display the subforms
		$this->obj_form->subforms["timebilled_details"]	= array("name_group", "name_customer", "code_invoice", "description");
		$this->obj_form->subforms["hidden"]		= array("projectid", "groupid");
		
		if ($this->locked)
		{
			$this->obj_form->subforms["submit"]	= array();
		}
		else
		{
			$this->obj_form->subforms["submit"]	= array("delete_confirm", "submit");
		}
	}


	function render_html()
	{
		// Title + Summary
		print "<h3>DELETE TIME GROUP</h3><br>";
		print "<p>This page allows you to delete a time group. Once deleted, this action is irreverable.</p>";

		// display the form
		$this->obj_form->render_form();

		if ($this->locked)
		{
			format_msgbox("locked", "<p>This timegroup is now locked - if you wish to delete it, you will first need to remove it from the invoice that it belongs too.</p>");
		}
	}
}

?>
