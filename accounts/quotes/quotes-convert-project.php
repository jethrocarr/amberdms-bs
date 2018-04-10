<?php
/*
	projects/add.php
	
	access: projects_write

	Form to add a new project to the database.

*/

class page_output
{
	var $obj_form;	// page form
        var $obj_menu_nav;

        function __construct()
	{
		// fetch quote ID
		$this->id = @security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Quote Details", "page=accounts/quotes/quotes-view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Quote Items", "page=accounts/quotes/quotes-items.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Quote Journal", "page=accounts/quotes/journal.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Export Quote", "page=accounts/quotes/quotes-export.php&id=". $this->id ."");
                $this->obj_menu_nav->add_item("Create Project", "page=accounts/quotes/quotes-convert-project.php&id=". $this->id ."",TRUE);
		$this->obj_menu_nav->add_item("Convert to Invoice", "page=accounts/quotes/quotes-convert.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Delete Quote", "page=accounts/quotes/quotes-delete.php&id=". $this->id ."");
	}
        
	function check_permissions()
	{
		return user_permissions_get("projects_write");
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
		$this->obj_form->formname = "project_add";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "projects/edit-process.php";
		$this->obj_form->method = "post";
	
	
		// general
		$structure = NULL;
		$structure["fieldname"] 	= "name_project";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "code_project";
		$structure["type"]		= "input";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]		= "project_quote";
		$structure["type"]		= "input";
                $structure["defaultvalue"]      = sql_get_singlevalue("SELECT code_quote as value FROM account_quotes WHERE id=".$this->id);
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "date_start";
		$structure["type"]		= "date";
		$structure["defaultvalue"]	= date("Y-m-d");
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] = "date_end";
		$structure["type"]	= "date";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "internal_only";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "This is an internal project - do not alert to unbilled hours";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "details";
		$structure["type"]		= "textarea";
		$this->obj_form->add_input($structure);


		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Create Project";
		$this->obj_form->add_input($structure);
		

		// define subforms
		$this->obj_form->subforms["project_view"]	= array("code_project", "name_project", "project_quote", "date_start", "date_end", "internal_only", "details");
		$this->obj_form->subforms["submit"]		= array("submit");
		
		// load any data returned due to errors
		$this->obj_form->load_data_error();

	}

	function render_html()
	{
		// Title + Summary
		print "<h3>ADD NEW PROJECT</h3><br>";
		print "<p>This page allows you to add a new project.</p>";


		// display the form
		$this->obj_form->render_form();
	}

}

?>
