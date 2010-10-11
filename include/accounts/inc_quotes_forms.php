<?php
/*
	include/accounts/inc_quotes_details.php

	Provides forms and processing code for adjusting the basic details of an quote. This
	code is simular to the inc_quotes_details.php page, but due to the number of variations
	it was decided to split it into a seporate function.
*/


require("include/accounts/inc_quotes.php");
require("include/accounts/inc_invoices.php");
require("include/accounts/inc_invoices_items.php");
require("include/accounts/inc_charts.php");



/*
	class: quote_form_details

	Provides a form for creating or adjusting a quote.
*/
class quote_form_details
{
	var $quoteid;		// ID of the quote to edit (if any)
	var $processpage;	// Page to submit the form to

	var $mode;
	
	var $obj_form;


	function execute()
	{
		log_debug("quote_form_details", "Executing execute()");

		if ($this->quoteid)
		{
			$this->mode = "edit";
		}
		else
		{
			$this->mode = "add";
		}

		
		/*
			Start Form
		*/
		$this->obj_form = New form_input;
		$this->obj_form->formname	= "quote_". $this->mode;
		$this->obj_form->language	= $_SESSION["user"]["lang"];

		$this->obj_form->action		= $this->processpage;
		$this->obj_form->method		= "POST";
		


		/*
			Define form structure
		*/
		
		// basic details
		$sql_struct_obj	= New sql_query;
		$sql_struct_obj->prepare_sql_settable("customers");
		$sql_struct_obj->prepare_sql_addfield("id", "customers.id");
		$sql_struct_obj->prepare_sql_addfield("label", "customers.code_customer");
		$sql_struct_obj->prepare_sql_addfield("label1", "customers.name_customer");
		$sql_struct_obj->prepare_sql_addorderby("code_customer");
		$sql_struct_obj->prepare_sql_addwhere("id = 'CURRENTID' OR date_end = '0000-00-00'");
		
		$structure = form_helper_prepare_dropdownfromobj("customerid", $sql_struct_obj);
//		$structure = form_helper_prepare_dropdownfromdb("customerid", "SELECT id, code_customer as label, name_customer as label1 FROM customers ORDER BY name_customer");
		$structure["options"]["req"]	= "yes";
		$structure["options"]["width"]	= "600";
		$this->obj_form->add_input($structure);
		
		$sql_struct_obj	= New sql_query;
		$sql_struct_obj->prepare_sql_settable("staff");
		$sql_struct_obj->prepare_sql_addfield("id", "staff.id");
		$sql_struct_obj->prepare_sql_addfield("label", "staff.staff_code");
		$sql_struct_obj->prepare_sql_addfield("label1", "staff.name_staff");
		$sql_struct_obj->prepare_sql_addorderby("staff_code");
		$sql_struct_obj->prepare_sql_addwhere("id = 'CURRENTID' OR date_end = '0000-00-00'");
		
		$structure = form_helper_prepare_dropdownfromobj("employeeid", $sql_struct_obj);	
//		$structure = form_helper_prepare_dropdownfromdb("employeeid", "SELECT id, staff_code as label, name_staff as label1 FROM staff ORDER BY name_staff");
		$structure["options"]["req"]		= "yes";
		$structure["options"]["autoselect"]	= "yes";
		$structure["options"]["width"]		= "600";
		$structure["defaultvalue"]		= $_SESSION["user"]["default_employeeid"];
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "code_quote";
		$structure["type"]		= "input";

		if ($this->mode == "edit")
			$structure["options"]["req"] = "yes";

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
		$structure["fieldname"] 	= "date_validtill";
		$structure["type"]		= "date";
		$structure["defaultvalue"]	= quotes_calc_duedate(date("Y-m-d"));
		$this->obj_form->add_input($structure);



		// ID
		$structure = NULL;
		$structure["fieldname"]		= "id_quote";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->quoteid;
		$this->obj_form->add_input($structure);	


		// submit
		$structure = NULL;
		$structure["fieldname"]		= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Save Changes";
		$this->obj_form->add_input($structure);


		// load data
		$this->obj_form->sql_query = "SELECT customerid, employeeid, code_quote, notes, date_trans, date_validtill FROM account_quotes WHERE id='". $this->quoteid ."'";
		$this->obj_form->load_data();


		// define subforms
		$this->obj_form->subforms["quote_details"]	= array("customerid", "employeeid", "code_quote", "date_trans", "date_validtill");
		$this->obj_form->subforms["quote_other"]	= array("notes");

		$this->obj_form->subforms["hidden"]		= array("id_quote");
		$this->obj_form->subforms["submit"]		= array("submit");
	}


	function render_html()
	{
		log_debug("quote_form_details", "Executing render_html()");
		
		$this->obj_form->render_form();
	}

} // end of quote_form_details



/*
	class: quote_form_convert

	Provides a form for converting the quote into an invoice - includes some input fields needed
	to get further information before conversion can be performed.
*/
class quote_form_convert
{
	var $quoteid;		// ID of the quote
	var $processpage;	// Page to submit the form to

	var $obj_form;


	function execute()
	{
		log_debug("quote_form_delete", "Executing execute()");


		/*
			Start Form
		*/
		$this->obj_form = New form_input;
		$this->obj_form->formname	= "_quote_convert";
		$this->obj_form->language	= $_SESSION["user"]["lang"];

		$this->obj_form->action		= $this->processpage;
		$this->obj_form->method		= "POST";
		


		/*
			Define form structure
		*/
		
		// basic details
		$structure = NULL;
		$structure["fieldname"] 	= "code_quote";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "code_invoice";
		$structure["type"]		= "input";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "code_ordernumber";
		$structure["type"]		= "input";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "code_ponumber";
		$structure["type"]		= "input";
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
		$structure = charts_form_prepare_acccountdropdown("dest_account", "ar_summary_account");
		$structure["options"]["req"]		= "yes";
		$structure["options"]["autoselect"]	= "yes";
		$this->obj_form->add_input($structure);


		
		// ID
		$structure = NULL;
		$structure["fieldname"]		= "id_quote";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->quoteid;
		$this->obj_form->add_input($structure);	


		// submit
		$structure = NULL;
		$structure["fieldname"]		= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Convert to Invoice";
		$this->obj_form->add_input($structure);


		// load data
		$this->obj_form->sql_query = "SELECT date_create, code_quote FROM account_quotes WHERE id='". $this->quoteid ."'";
		$this->obj_form->load_data();


		// define subforms
		$this->obj_form->subforms["quote_convert_details"]	= array("code_quote", "code_invoice", "code_ordernumber", "code_ponumber", "date_trans", "date_due");
		$this->obj_form->subforms["quote_convert_financials"]	= array("dest_account");
		$this->obj_form->subforms["hidden"]			= array("id_quote");
		$this->obj_form->subforms["submit"]			= array("submit");
	}

	function render_html()
	{
		log_debug("quote_form_convert", "Executing render_html()");
		
		$this->obj_form->render_form();
	}

} // end of class quote_form_convert







/*
	class: quote_form_delete

	Provides a form for deleting quote.
*/
class quote_form_delete
{
	var $quoteid;		// ID of the quote
	var $processpage;	// Page to submit the form to

	var $obj_form;


	function execute()
	{
		log_debug("quote_form_delete", "Executing execute()");


		/*
			Start Form
		*/
		$this->obj_form = New form_input;
		$this->obj_form->formname		= "quote_delete";
		$this->obj_form->language		= $_SESSION["user"]["lang"];

		$this->obj_form->action			= $this->processpage;
		$this->obj_form->method			= "POST";
		


		/*
			Define form structure
		*/
		
		// basic details
		$structure = NULL;
		$structure["fieldname"] 	= "code_quote";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "delete_confirm";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "Yes, I wish to delete this quote and realise that once deleted the data can not be recovered.";
		$this->obj_form->add_input($structure);



		// hidden date field
		$structure = NULL;
		$structure["fieldname"] 	= "date_create";
		$structure["type"]		= "hidden";
		$this->obj_form->add_input($structure);


		// ID
		$structure = NULL;
		$structure["fieldname"]		= "id_quote";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->quoteid;
		$this->obj_form->add_input($structure);	


		// submit
		$structure = NULL;
		$structure["fieldname"]		= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Delete Quote";
		$this->obj_form->add_input($structure);


		// load data
		$this->obj_form->sql_query = "SELECT date_create, code_quote FROM account_quotes WHERE id='". $this->quoteid ."'";
		$this->obj_form->load_data();


		// define subforms
		$this->obj_form->subforms["quote_delete"]		= array("code_quote");
		$this->obj_form->subforms["hidden"]			= array("id_quote", "date_create");
		$this->obj_form->subforms["submit"]			= array("delete_confirm", "submit");
	}


	function render_html()
	{
		log_debug("quote_form_delete", "Executing render_html()");
	
		// display render form
		$this->obj_form->render_form();
	}

} // end of quote_form_delete



?>
