<?php
/*
	include/accounts/inc_invoices_forms.php

	Provides various forms for use by the invoicing pages.
*/

require("include/accounts/inc_invoices.php");
require("include/accounts/inc_charts.php");



/*
	class: invoice_form_details

	Generates forms for processing service details - used to adjust existing invoices
	or to add new ones.
*/
class invoice_form_details
{
	var $type;		// Either "ar" or "ap"
	var $invoiceid;		// ID of the invoice to edit (if any)
	var $processpage;	// Page to submit the form to

	var $mode;
	
	var $obj_form;


	function execute()
	{
		log_debug("invoice_form_details", "Executing execute()");

		if ($this->invoiceid)
		{
			$this->mode = "edit";
		}
		else
		{
			$this->mode = "add";
		}

		
		/*
			Make sure invoice does exist!
		*/
		if ($this->mode == "edit")
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM account_". $this->type ." WHERE id='". $this->invoiceid ."'";
			$sql_obj->execute();
			
			if (!$sql_obj->num_rows())
			{
				print "<p><b>Error: The requested invoice does not exist. <a href=\"index.php?page=accounts/". $this->type ."/". $this->type .".php\">Try looking on the invoice/invoice list page.</a></b></p>";
				return 0;
			}
		}


		/*
			Start Form
		*/
		$this->obj_form = New form_input;
		$this->obj_form->formname		= $this->type ."_invoice_". $this->mode;
		$this->obj_form->language		= $_SESSION["user"]["lang"];

		$this->obj_form->action			= $this->processpage;
		$this->obj_form->method			= "POST";
		


		/*
			Define form structure
		*/
		
		// basic details
		if ($this->type == "ap")
		{
			$structure = form_helper_prepare_dropdownfromdb("vendorid", "SELECT id, name_vendor as label FROM vendors");
			$structure["options"]["req"]	= "yes";
			$this->obj_form->add_input($structure);
		}
		else
		{
			$structure = form_helper_prepare_dropdownfromdb("customerid", "SELECT id, name_customer as label FROM customers");
			$structure["options"]["req"]	= "yes";
			$this->obj_form->add_input($structure);
		}
			
		$structure = form_helper_prepare_dropdownfromdb("employeeid", "SELECT id, name_staff as label FROM staff");
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "code_invoice";
		$structure["type"]		= "input";

		if ($this->mode == "edit")
			$structure["options"]["req"] = "yes";

		$this->obj_form->add_input($structure);


		$structure = NULL;
		$structure["fieldname"] 	= "code_ordernumber";
		$structure["type"]		= "input";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "code_ponumber";
		$structure["type"]		= "input";
		$this->obj_form->add_input($structure);
			
		$structure = NULL;
		$structure["fieldname"] 	= "notes";
		$structure["type"]		= "textarea";
		$structure["options"]["height"]	= "100";
		$structure["options"]["width"]	= 500;
		$this->obj_form->add_input($structure);



		// dates
		$structure = NULL;
		$structure["fieldname"] 	= "date_trans";
		$structure["type"]		= "date";
		$structure["defaultvalue"]	= date("Y-m-d");
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "date_due";
		$structure["type"]		= "date";
		$structure["defaultvalue"]	= invoice_calc_duedate(date("Y-m-d"));
		$this->obj_form->add_input($structure);


		// destination account
		if ($this->type == "ap")
		{
			$structure = charts_form_prepare_acccountdropdown("dest_account", "ap_summary_account");
		}
		else
		{
			$structure = charts_form_prepare_acccountdropdown("dest_account", "ar_summary_account");
		}
			
		if ($structure["values"])
		{
			if (count(array_keys($structure["values"])) == 1)
			{
				// if there is only 1 tax option avaliable, select it as the default
				$structure["options"]["noselectoption"] = "yes";
			}
		}
		
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);


		

		// ID
		$structure = NULL;
		$structure["fieldname"]		= "id_invoice";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->invoiceid;
		$this->obj_form->add_input($structure);	


		// submit
		$structure = NULL;
		$structure["fieldname"]		= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Save Changes";
		$this->obj_form->add_input($structure);


		// load data
		if ($this->type == "ap")
		{
			$this->obj_form->sql_query = "SELECT vendorid, employeeid, code_invoice, code_ordernumber, code_ponumber, notes, date_trans, date_due, dest_account FROM account_". $this->type ." WHERE id='". $this->invoiceid ."'";
		}
		else
		{
			$this->obj_form->sql_query = "SELECT customerid, employeeid, code_invoice, code_ordernumber, code_ponumber, notes, date_trans, date_due, dest_account FROM account_". $this->type ." WHERE id='". $this->invoiceid ."'";
		}
		
		$this->obj_form->load_data();


		// define subforms
		if ($this->type == "ap")
		{
			$this->obj_form->subforms[$this->type ."_invoice_details"]	= array("vendorid", "employeeid", "code_invoice", "code_ordernumber", "code_ponumber", "date_trans", "date_due");
		}
		else
		{
			$this->obj_form->subforms[$this->type ."_invoice_details"]	= array("customerid", "employeeid", "code_invoice", "code_ordernumber", "code_ponumber", "date_trans", "date_due");
		}
		
		$this->obj_form->subforms[$this->type ."_invoice_financials"]		= array("dest_account");
		$this->obj_form->subforms[$this->type ."_invoice_other"]		= array("notes");

		$this->obj_form->subforms["hidden"]					= array("id_invoice");
		$this->obj_form->subforms["submit"]					= array("submit");

		return 1;
	}


	function render_html()
	{
		log_debug("invoice_form_details", "Executing render_html()");

		// display form	
		$this->obj_form->render_form();
	}

} // end of invoice_form_details



/*
	class: invoice_form_delete

	Provides a form to allow deletion of an unwanted invoice, provided that the invoice is not locked.
	
*/
class invoice_form_delete
{
	var $type;		// Either "ar" or "ap"
	var $invoiceid;		// ID of the invoice to delete
	var $processpage;	// Page to submit the form to

	var $mode;
	
	var $obj_form;


	function execute()
	{
		log_debug("invoice_form_delete", "Executing execute()");

		/*
			Start Form
		*/
		$this->obj_form = New form_input;
		$this->obj_form->formname		= $this->type ."_invoice_delete";
		$this->obj_form->language		= $_SESSION["user"]["lang"];

		$this->obj_form->action		= $this->processpage;
		$this->obj_form->method		= "POST";
		


		/*
			Define form structure
		*/
		
		// basic details
		$structure = NULL;
		$structure["fieldname"] 	= "code_invoice";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "delete_confirm";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "Yes, I wish to delete this invoice and realise that once deleted the data can not be recovered.";
		$this->obj_form->add_input($structure);



		// hidden date field
		$structure = NULL;
		$structure["fieldname"] 	= "date_create";
		$structure["type"]		= "hidden";
		$this->obj_form->add_input($structure);


		// ID
		$structure = NULL;
		$structure["fieldname"]		= "id_invoice";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->invoiceid;
		$this->obj_form->add_input($structure);	


		// submit
		$structure = NULL;
		$structure["fieldname"]		= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Delete Invoice";
		$this->obj_form->add_input($structure);


		// load data
		$this->obj_form->sql_query = "SELECT date_create, code_invoice FROM account_". $this->type ." WHERE id='". $this->invoiceid ."'";
		$this->obj_form->load_data();


		$this->obj_form->subforms[$this->type ."_invoice_delete"]	= array("code_invoice", "delete_confirm");

		$this->obj_form->subforms["hidden"]			= array("id_invoice", "date_create");
		$this->obj_form->subforms["submit"]			= array("submit");

	}


	function render_html()
	{
		log_debug("invoice_form_delete", "Executing render_html()");
		
		/*
			Verify that the invoice can be deleted, then render delete form.
		*/
		
		$expirydate = sql_get_singlevalue("SELECT value FROM config WHERE name='ACCOUNTS_INVOICE_LOCK'");


		if (time_date_to_timestamp($this->obj_form->structure["date_create"]["defaultvalue"]) < mktime() - time_date_to_timestamp($expirydate))
		{
			print "<p><b>Sorry, this invoice has now locked and can not be deleted.</b></p>";
		}
		else
		{
			$this->obj_form->render_form();
		}
	}
}






?>
