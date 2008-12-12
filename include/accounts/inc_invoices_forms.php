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
	class: invoice_form_export

	Allows you to export the invoice in different formats and provides functions to allow you to email the invoice directly to the customer.
*/
class invoice_form_export
{
	var $type;		// Either "ar" or "quotes"
	var $invoiceid;		// ID of the invoice
	var $processpage;	// Page to submit the form to


	var $obj_form_email;
	var $obj_form_download;
	var $obj_pdf;



	function execute()
	{
		log_debug("invoice_form_export", "Executing execute()");

		/*
			Fetch basic invoice details
		*/
		$obj_sql_invoice		= New sql_query;

		if ($this->type == "ar")
		{
			$obj_sql_invoice->string	= "SELECT code_invoice, customerid FROM account_". $this->type ." WHERE id='". $this->invoiceid ."' LIMIT 1";
		}
		else
		{
			$obj_sql_invoice->string	= "SELECT code_quote, customerid FROM account_". $this->type ." WHERE id='". $this->invoiceid ."' LIMIT 1";
		}
		
		$obj_sql_invoice->execute();
		$obj_sql_invoice->fetch_array();


		/*
			Fetch basic customer information
		*/
		$obj_sql_customer		= New sql_query;
		$obj_sql_customer->string	= "SELECT contact_email FROM customers WHERE id='". $obj_sql_invoice->data[0]["customerid"] ."' LIMIT 1";
		$obj_sql_customer->execute();
		$obj_sql_customer->fetch_array();


	


		/*
			Define email form
		*/
		$this->obj_form_email = New form_input;
		$this->obj_form_email->formname = "invoice_export_email";
		$this->obj_form_email->language = $_SESSION["user"]["lang"];

		$this->obj_form_email->action = $this->processpage;
		$this->obj_form_email->method = "post";
		

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "subject";
		$structure["type"]		= "input";

		if ($this->type == "ar")
		{
			$structure["defaultvalue"]	= "Invoice ". $obj_sql_invoice->data[0]["code_invoice"];
		}
		else
		{
			$structure["defaultvalue"]	= "Quote ". $obj_sql_invoice->data[0]["code_quote"];
		}
		
		$this->obj_form_email->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "email_to";
		$structure["type"]		= "input";
		$structure["defaultvalue"]	= $obj_sql_customer->data[0]["contact_email"];
		$this->obj_form_email->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "email_cc";
		$structure["type"]		= "input";
		$structure["defaultvalue"]	= "";
		$this->obj_form_email->add_input($structure);
			
		$structure = NULL;
		$structure["fieldname"] 	= "email_bcc";
		$structure["type"]		= "input";
		$structure["defaultvalue"]	= "";
		$this->obj_form_email->add_input($structure);
	
		$structure = NULL;
		$structure["fieldname"] 	= "message";
		$structure["type"]		= "textarea";
		$structure["defaultvalue"]	= "";
		$this->obj_form_email->add_input($structure);
		
		// hidden
		$structure = NULL;
		$structure["fieldname"] 	= "formname";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->obj_form_email->formname;
		$this->obj_form_email->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "id_invoice";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->invoiceid;
		$this->obj_form_email->add_input($structure);



		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Email Invoice";
		$this->obj_form_email->add_input($structure);
		
		// load any data returned due to errors
		$this->obj_form_email->load_data_error();



		/*
			Define download form
		*/
		$this->obj_form_download = New form_input;
		$this->obj_form_download->formname = "invoice_export_download";
		$this->obj_form_download->language = $_SESSION["user"]["lang"];

		$this->obj_form_download->action = $this->processpage;
		$this->obj_form_download->method = "post";
		

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "invoice_mark_as_sent";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "Check this to show that the invoice has been sent to the customer when you download the PDF";
		$this->obj_form_download->add_input($structure);


		// hidden
		$structure = NULL;
		$structure["fieldname"] 	= "formname";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->obj_form_download->formname;
		$this->obj_form_download->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "id_invoice";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->invoiceid;
		$this->obj_form_download->add_input($structure);


		

		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Download Invoice";
		$this->obj_form_download->add_input($structure);
		

		// load any data returned due to errors
		$this->obj_form_download->load_data_error();

		return 1;
	}

	
	function render_html()
	{
		log_debug("invoice_form_export", "Executing render_html()");


		// download form
		print "<table width=\"100%\" class=\"table_highlight\"><tr><td>";
		print "<table cellpadding=\"5\"><tr>";
		
		print "<td valign=\"top\">";
			print "pdf_icon_here";
		print "</td>";

		print "<td width=\"100%\">";
			print "<h3>Download PDF:</h3>";
			print "<form method=\"". $this->obj_form_download->method ."\" action=\"". $this->obj_form_download->action ."\">";
			print "<br><br>";
			$this->obj_form_download->render_field("invoice_mark_as_sent");
			print "<br>";
			$this->obj_form_download->render_field("formname");
			$this->obj_form_download->render_field("id_invoice");
			$this->obj_form_download->render_field("submit");
			print "</form>";
		print "</td>";	
		
		print "</tr></table>";
		print "</td></tr></table>";
		print "<br><br>";


		// email form
		print "<table width=\"100%\" class=\"table_highlight\"><tr><td>";
		print "<table cellpadding=\"5\"><tr>";
		
		print "<td valign=\"top\">";
			print "email_icon_here";
		print "</td>";

		print "<td width=\"100%\">";
			print "<h3>Email PDF:</h3>";
			print "<form method=\"". $this->obj_form_email->method ."\" action=\"". $this->obj_form_email->action ."\">";
			print "<table width=\"100%\">";
			$this->obj_form_email->render_row("subject");
			$this->obj_form_email->render_row("email_to");
			$this->obj_form_email->render_row("email_cc");
			$this->obj_form_email->render_row("email_bcc");
			$this->obj_form_email->render_row("message");
			print "</table>";
		
			$this->obj_form_email->render_field("formname");
			$this->obj_form_email->render_field("id_invoice");
			$this->obj_form_email->render_field("submit");
			
			print "</form>";
		print "</td>";	
		
		print "</tr></table>";
		print "</td></tr></table>";
	}


} // end of invoice_form_export







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
