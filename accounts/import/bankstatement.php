<?php
/*
	bankstatement.php
	
	access: "accounts_import_statement" group members

	Allows uploading of a bank statement to assign types for each entry.
*/

class page_output
{
	var $obj_form;

	function check_permissions()
	{
		return user_permissions_get('accounts_import_statement');
	}

	function check_requirements()
	{
		// nothing todo
		return 1;
	}


	/*
		Define form structure
	*/
	function execute()
	{
		$this->obj_form 		= New form_input;
		$this->obj_form->formname 	= "bankstatementimport";
		$this->obj_form->language 	= $_SESSION["user"]["lang"];
		$this->obj_form->action 	= "accounts/import/bankstatement-process.php";
		$this->obj_form->method 	= "post";
		
		$structure 		= NULL;
		$structure["fieldname"]	= "BANK_STATEMENT";
		$structure["type"]	= "file";
		$this->obj_form->add_input($structure);
		
		$structure 			= NULL;
		$structure["fieldname"]		= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Import";
		$this->obj_form->add_input($structure);
		
		$this->obj_form->subforms["upload_bank_statement"]	= array("BANK_STATEMENT");
		$this->obj_form->subforms["import"]			= array("submit");
	} 



	/*
		Output: HTML format
	*/
	function render_html()
	{
		    // Title + Summary
		print "<h3>BANK STATEMENT IMPORT</h3><br>";
		print "<p>Select the CSV file you wish to import.</p>";
	
		// display the form
		$this->obj_form->render_form();
	}	

} // end class page_output


?>
