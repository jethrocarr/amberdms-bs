<?php
/*
	accounts/taxes/add.php
	
	access: account_taxes_write

	Form to add a new tax to the database.
*/


// custom includes
require("include/accounts/inc_charts.php");


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

		// tax account selection
		$structure = charts_form_prepare_acccountdropdown("chartid", "tax_summary_account");
		$structure["options"]["req"]	= "yes";

		if (!$structure["values"])
		{
			$structure["type"]		= "text";
			$structure["defaultvalue"]	= "<b>You need to add some tax accounts for this tax to belong to, before you can use this tax</b>";
		}
		$form->add_input($structure);


	
		
		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Create Tax";
		$form->add_input($structure);
		

		// define subforms
		$form->subforms["general"]	= array("name_tax", "chartid", "taxrate", "description");
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
