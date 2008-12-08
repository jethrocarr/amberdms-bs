<?php
/*
	accounts/taxes/add.php
	
	access: account_taxes_write

	Form to add a new tax to the database.
*/


// custom includes
require("include/accounts/inc_charts.php");


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
		$this->obj_form->formname = "tax_add";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "accounts/taxes/edit-process.php";
		$this->obj_form->method = "post";
		

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "name_tax";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "taxrate";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);
	
		$structure = NULL;
		$structure["fieldname"] 	= "taxnumber";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "description";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);

		// tax account selection
		$structure = charts_form_prepare_acccountdropdown("chartid", "tax_summary_account");
		$structure["options"]["req"]	= "yes";

		if (!$structure["values"])
		{
			$structure["type"]		= "text";
			$structure["defaultvalue"]	= "<b>You need to add some tax accounts for this tax to belong to, before you can use this tax</b>";
		}
		$this->obj_form->add_input($structure);


	
		
		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Create Tax";
		$this->obj_form->add_input($structure);
		

		// define subforms
		$this->obj_form->subforms["general"]	= array("name_tax", "chartid", "taxrate", "taxnumber", "description");
		$this->obj_form->subforms["submit"]	= array("submit");
		
		// load any data returned due to errors
		$this->obj_form->load_data_error();

	}


	function render_html()
	{
		// Title + Summary
		print "<h3>ADD NEW TAX</h3><br>";
		print "<p>This page allows you to add a tax to the system.</p>";

		// display the form
		$this->obj_form->render_form();
	}
	
}

?>
