<?php
/*
	accounts/taxes/add.php
	
	access: account_taxes_write

	Form to add a new tax to the database.
*/

if (user_permissions_get('accounts_taxes_write'))
{
	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		print "<h3>ADD NEW TAX</h3><br>";
		print "<p>This page allows you to add a tax to the system.</p>";

		/*
			Define form structure
		*/
		$form = New form_input;
		$form->formname = "tax_add";
		$form->language = $_SESSION["user"]["lang"];

		$form->action = "accounts/taxes/edit-process.php";
		$form->method = "post";
		

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "name_tax";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "taxrate";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$form->add_input($structure);
	
		$structure = NULL;
		$structure["fieldname"] 	= "description";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$form->add_input($structure);
	
		
		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Create Tax";
		$form->add_input($structure);
		

		// define subforms
		$form->subforms["general"]	= array("name_tax", "taxrate", "description");
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
