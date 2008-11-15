<?php
/*
	accounts/gl/add.php
	
	access: account_gl_write

	Form to add a new transaction to the general ledger. This feature is typically used for doing transfer between accounts or making payments
	of taxes.
*/

if (user_permissions_get('accounts_gl_write'))
{
	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		print "<h3>ADD NEW TRANSACTION</h3><br>";
		print "<p>This page allows you to add a new transaction to the general ledger - this feature is typically used for performing transfers between accounts or making payments of taxes.</p>";


		/*
			Define form structure
		*/
		$form = New form_input;
		$form->formname = "transaction_add";
		$form->language = $_SESSION["user"]["lang"];

		$form->action = "accounts/gl/edit-process.php";
		$form->method = "post";
		

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "code_gl";
		$structure["type"]		= "input";
		$form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "date_trans";
		$structure["type"]		= "date";
		$structure["defaultvalue"]	= date("Y-m-d");
		$structure["options"]["req"]	= "yes";
		$form->add_input($structure);
		
		$structure = form_helper_prepare_dropdownfromdb("employeeid", "SELECT id, name_staff as label FROM staff");
		$structure["options"]["req"]	= "yes";
		$form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "description";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "notes";
		$structure["type"]		= "textarea";
		$form->add_input($structure);


		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Create Transaction";
		$form->add_input($structure);
		

		// define subforms
		$form->subforms["general"]	= array("code_gl", "date_trans", "employeeid", "description", "notes");
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
