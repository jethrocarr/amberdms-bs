<?php
/*
	accounts/charts/add.php
	
	access: account_charts_write

	Form to add a new account to the database.

*/


class page_output
{
	var $obj_form;


	function check_permissions()
	{
		return user_permissions_get("accounts_charts_write");
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
		$this->obj_form->formname = "chart_add";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "accounts/charts/edit-process.php";
		$this->obj_form->method = "post";
		

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "code_chart";
		$structure["type"]		= "input";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "description";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);
		
		$structure = form_helper_prepare_radiofromdb("chart_type", "SELECT id, value as label FROM account_chart_type");
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);


		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Create Chart";
		$this->obj_form->add_input($structure);
		

		// define subforms
		$this->obj_form->subforms["chart_details"]	= array("code_chart", "description", "chart_type");
		$this->obj_form->subforms["submit"]		= array("submit");
		
		// load any data returned due to errors
		$this->obj_form->load_data_error();
	}


	function render_html()
	{
		// Title + Summary
		print "<h3>ADD NEW ACCOUNT</h3><br>";
		print "<p>This page allows you to add a new account to the chart of accounts.</p>";

		// display the form
		$this->obj_form->render_form();
	}
}

?>
