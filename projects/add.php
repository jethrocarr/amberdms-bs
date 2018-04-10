<?php
/*
	projects/add.php
	
	access: projects_write

	Form to add a new project to the database.

*/
require("include/accounts/inc_charts.php");

class page_output
{
	var $obj_form;	// page form


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

                // load customer dropdown	
                $sql_struct_obj	= New sql_query;
                $sql_struct_obj->prepare_sql_settable("customers");
                $sql_struct_obj->prepare_sql_addfield("id", "customers.id");
                $sql_struct_obj->prepare_sql_addfield("label", "customers.code_customer");
                $sql_struct_obj->prepare_sql_addfield("label1", "customers.name_customer");
                $sql_struct_obj->prepare_sql_addorderby("code_customer");
                $sql_struct_obj->prepare_sql_addwhere("id = 'CURRENTID' OR date_end = '0000-00-00'");

                $structure = form_helper_prepare_dropdownfromobj("customerid", $sql_struct_obj);
                $structure["options"]["req"]		= "yes";
                $structure["options"]["width"]		= "600";
                $structure["options"]["search_filter"]	= "enabled";
                $structure["defaultvalue"]		= "";
                $this->obj_form->add_input($structure);

                // Accounts dropbox
                $structure = charts_form_prepare_acccountdropdown("dest_account", "ar_summary_account");
                $structure["options"]["req"]		= "yes";
		$structure["options"]["autoselect"]	= "yes";
		$structure["options"]["search_filter"]	= "enabled";
		$structure["options"]["width"]		= "600";
                $structure["options"]["label"]          = " This is the account for any project expenses.";
		$this->obj_form->add_input($structure);
                
		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Create Project";
		$this->obj_form->add_input($structure);
		

		// define subforms
		$this->obj_form->subforms["project_view"]	= array("code_project", "name_project","customerid", "date_start", "date_end", "internal_only", "details");
		$this->obj_form->subforms["project_financials"] = array("project_quote","dest_account");
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
