<?php
/*
	accounts/gl/add.php
	
	access: account_gl_write

	Form to add a new transaction to the general ledger. This feature is typically used for doing transfer between accounts or making payments
	of taxes.
*/


class page_output
{
	var $obj_form;


	function check_permissions()
	{
		return user_permissions_get("accounts_gl_write");
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
		$this->obj_form->formname = "transaction_add";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "accounts/gl/edit-process.php";
		$this->obj_form->method = "post";
		

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "code_gl";
		$structure["type"]		= "input";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "date_trans";
		$structure["type"]		= "date";
		$structure["defaultvalue"]	= date("Y-m-d");
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);
		
		$structure = form_helper_prepare_dropdownfromdb("employeeid", "SELECT id, staff_code as label, name_staff as label1 FROM staff ORDER BY name_staff");
		$structure["options"]["req"]	= "yes";
		$structure["options"]["width"]	= "600";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "description";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$structure["options"]["width"]	= "600";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "notes";
		$structure["type"]		= "textarea";
		$structure["options"]["width"]	= "600";
		$structure["options"]["height"]	= "50";
		$this->obj_form->add_input($structure);


		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Create Transaction";
		$this->obj_form->add_input($structure);
		

		// define subforms
		$this->obj_form->subforms["general_ledger_transaction_details"]	= array("code_gl", "date_trans", "employeeid", "description", "notes");
		$this->obj_form->subforms["submit"]				= array("submit");
		
		// load any data returned due to errors
		$this->obj_form->load_data_error();
	}

	function render_html()
	{
		// title + summary
		print "<h3>ADD NEW TRANSACTION</h3><br>";
		print "<p>This page allows you to add a new transaction to the general ledger - this feature is typically used for performing transfers between accounts or making payments of taxes.</p>";

		// display the form
		$this->obj_form->render_form();
	}
}

?>
