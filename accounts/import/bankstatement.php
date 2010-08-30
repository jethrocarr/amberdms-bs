<?php
/*
	bankstatement.php
	
	access: "accounts_import_statement" group members

	Allows uploading of a bank statement to assign types for each entry.
*/

require("include/accounts/inc_charts.php");

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
		
		
		$structure = charts_form_prepare_acccountdropdown("dest_account", "ap_summary_account");
			
		$structure["options"]["req"]		= "yes";
		$structure["options"]["autoselect"]	= "yes";
		$structure["options"]["search_filter"]	= "enabled";
		$structure["options"]["width"]		= "600";
		$this->obj_form->add_input($structure);
		
		
		$structure = form_helper_prepare_dropdownfromdb("employeeid", "SELECT id, staff_code as label, name_staff as label1 FROM staff ORDER BY name_staff");
		$structure["options"]["req"]		= "yes";
		$structure["options"]["width"]		= "600";
		$structure["options"]["search_filter"]	= "enabled";
		$structure["defaultvalue"]		= @$_SESSION["user"]["default_employeeid"];
		$this->obj_form->add_input($structure);
		
		
		$structure 			= NULL;
		$structure["fieldname"]		= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Import";
		$this->obj_form->add_input($structure);
		
		$this->obj_form->subforms["upload_bank_statement"]	= array("BANK_STATEMENT", "dest_account", "employeeid" );
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
