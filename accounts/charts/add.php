<?php
/*
	accounts/charts/add.php
	
	access: account_charts_write

	Form to add a new account to the database.

*/

if (user_permissions_get('accounts_charts_write'))
{
	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		print "<h3>ADD NEW ACCOUNT</h3><br>";
		print "<p>This page allows you to add a new account to the chart of accounts.</p>";

		/*
			Define form structure
		*/
		$form = New form_input;
		$form->formname = "chart_add";
		$form->language = $_SESSION["user"]["lang"];

		$form->action = "accounts/charts/edit-process.php";
		$form->method = "post";
		

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "code_chart";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "description";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$form->add_input($structure);
		
		$structure = form_helper_prepare_radiofromdb("chart_type", "SELECT id, value as label FROM account_chart_type");
		$structure["options"]["req"]	= "yes";
		$form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "chart_category";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$form->add_input($structure);

		
		

		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Create Customer";
		$form->add_input($structure);
		

		// define subforms
		$form->subforms["general"]	= array("code_chart", "description", "chart_type", "chart_category");
		$form->subforms["submit"]	= array("submit");
		
		// load any data returned due to errors
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
