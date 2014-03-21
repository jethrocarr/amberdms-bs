<?php
/*
	admin/auditlock.php
	
	access: admin users only

	Allows administrators to lock all fully paid invoices, GL transactions, journal entries older
	than a supplied date - this is useful for locking off an old financial tax year once all the data has been
	processed.
*/

class page_output
{
	var $obj_form;


	function check_permissions()
	{
		return user_permissions_get("admin");
	}

	function check_requirements()
	{
		// nothing to do
		return 1;
	}



	function execute()
	{
		/*
			Define form structure
		*/
		
		$this->obj_form = New form_input;
		$this->obj_form->formname = "auditlock";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "admin/auditlock-process.php";
		$this->obj_form->method = "post";


		// general
		$structure = NULL;
		$structure["fieldname"] 	= "date_lock";
		$structure["type"]		= "date";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "lock_journals";
		$structure["type"]		= "checkbox";
		$structure["options"]["req"]	= "yes";
		$structure["options"]["label"]	= "Select to lock all journal postings before the supplied date.";
		$structure["defaultvalue"]	= "on";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "lock_timesheets";
		$structure["type"]		= "checkbox";
		$structure["options"]["req"]	= "yes";
		$structure["options"]["label"]	= "Select to lock all hours booked to the timesheet before the supplied date.";
		$structure["defaultvalue"]	= "on";
		$this->obj_form->add_input($structure);



		$structure = NULL;
		$structure["fieldname"] 	= "lock_invoices_open";
		$structure["type"]		= "checkbox";
		$structure["options"]["req"]	= "yes";
		$structure["options"]["label"]	= "Select this to lock ALL invoices before the supplied date, including unpaid invoices.";
		$this->obj_form->add_input($structure);



		// submit section
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Save Changes";
		$this->obj_form->add_input($structure);
		
		
		// define subforms
		$this->obj_form->subforms["auditlock"]		= array("date_lock", "lock_invoices_open", "lock_journals", "lock_timesheets");
		$this->obj_form->subforms["submit"]		= array("submit");

		// fetch any returned error data
		$this->obj_form->load_data_error();
	}



	function render_html()
	{
		// Title + Summary
		print "<h3>AUDIT LOCKING</h3><br>";
		print "<p>This page allows the administrator to lock all financial records earlier than a specified date. The purpose of this feature is to allow all records to be locked once all the accounts have been balanced, which typically occurs at the end of the financial year. Note that if you would like to do automated locking, you can configure various lock options on the program configuration page.</p>";

		print "<p>By default, the audit lock process will only lock GL transactions and fully paid invoices. However, you have additional locking options to close unpaid invoices, journal entries and booked time.</p>";
	
		// display the form
		$this->obj_form->render_form();
	}

	
}

?>
