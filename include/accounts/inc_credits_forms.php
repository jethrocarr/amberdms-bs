<?php
/*
	include/accounts/inc_credits_credits.php

	Provides various UI pages/forms used by credit handling.
*/

require("include/accounts/inc_invoices.php");
require("include/accounts/inc_charts.php");



/*
	class: credit_form_details
	
	Generate forms for handling credit details - used for adding or adjusting
	existing credits.
*/
class credit_form_details
{
	var $type;		// Either "ar_credit" or "ap_credit"
	var $credit_id;		// ID of the credit to edit (if any)
	var $customer_id;	// customer ID (if any)
	var $vendor_id;		// vendor ID (if any)
	var $processpage;	// Page to submit the form to

	var $locked;

	var $mode;
	
	var $obj_form;


	function execute()
	{
		log_debug("credit_form_details", "Executing execute()");

		if ($this->credit_id)
		{
			$this->mode = "edit";
		}
		else
		{
			$this->mode = "add";
		}

		
		/*
			Make sure credit does exist and fetch locked status
		*/
		if ($this->mode == "edit")
		{
			$sql_credit_obj		= New sql_query;
			$sql_credit_obj->string	= "SELECT id, locked FROM account_". $this->type ." WHERE id='". $this->credit_id ."' LIMIT 1";
			$sql_credit_obj->execute();
			
			if (!$sql_credit_obj->num_rows())
			{
				print "<p><b>Error: The requested credit note does not exist. <a href=\"index.php?page=accounts/". $this->type ."/". $this->type .".php\">Try looking on the credit note list page.</a></b></p>";
				return 0;
			}
			else
			{
				$sql_credit_obj->fetch_array();
				
				$this->locked = $sql_credit_obj->data[0]["locked"];
			}
		}


		/*
			Start Form
		*/
		$this->obj_form = New form_input;
		$this->obj_form->formname		= $this->type ."_credit_". $this->mode;
		$this->obj_form->language		= $_SESSION["user"]["lang"];

		$this->obj_form->action			= $this->processpage;
		$this->obj_form->method			= "POST";
		


		/*
			Define form structure
		*/
		
		// basic details
		if ($this->type == "ap_credit")
		{
			// load vendor dropdown
			$sql_struct_obj	= New sql_query;
			$sql_struct_obj->prepare_sql_settable("vendors");
			$sql_struct_obj->prepare_sql_addfield("id", "vendors.id");
			$sql_struct_obj->prepare_sql_addfield("label", "vendors.code_vendor");
			$sql_struct_obj->prepare_sql_addfield("label1", "vendors.name_vendor");
			$sql_struct_obj->prepare_sql_addorderby("code_vendor");
			$sql_struct_obj->prepare_sql_addwhere("id = 'CURRENTID' OR date_end = '0000-00-00'");
			
			$structure = form_helper_prepare_dropdownfromobj("vendorid", $sql_struct_obj);
			$structure["options"]["req"]		= "yes";
			$structure["options"]["width"]		= "600";
			$structure["options"]["search_filter"]	= "enabled";
			$structure["defaultvalue"]		= $this->vendor_id;
			$this->obj_form->add_input($structure);


			// load AP invoice list
		}
		else
		{
			// load customer dropdown
			$sql_struct_obj	= New sql_query;
			$sql_struct_obj->prepare_sql_settable("customers");
			$sql_struct_obj->prepare_sql_addfield("id", "customers.id");
			$sql_struct_obj->prepare_sql_addfield("label", "customers.code_customer");
			$sql_struct_obj->prepare_sql_addfield("label1", "customers.name_customer");
			$sql_struct_obj->prepare_sql_addorderby("code_customer");
			$sql_struct_obj->prepare_sql_addwhere("id = 'CURRENTID' OR date_end = '0000-00-00'");
			
			$structure = form_helper_prepare_dropdownfromobj("customerid", $sql_struct_obj);
			$structure["options"]["req"]		= "yes";
			$structure["options"]["width"]		= "600";
			$structure["options"]["search_filter"]	= "enabled";
			$structure["defaultvalue"]		= $this->customer_id;
			$this->obj_form->add_input($structure);


			// load AR invoice list
			$structure = form_helper_prepare_dropdownfromdb("invoiceid", "SELECT id, code_invoice as label FROM account_ar WHERE amount_total > 0 ORDER BY code_invoice");

			if (@count($structure["values"]) == 0)
			{
				$structure["defaultvalue"] = "There are no invoices that can be credited!";
			}

			$structure["options"]["search_filter"] = "enabled";
			$structure["options"]["width"]		= "350";
			
			$this->obj_form->add_input($structure);


		}

		$sql_struct_obj	= New sql_query;
		$sql_struct_obj->prepare_sql_settable("staff");
		$sql_struct_obj->prepare_sql_addfield("id", "staff.id");
		$sql_struct_obj->prepare_sql_addfield("label", "staff.staff_code");
		$sql_struct_obj->prepare_sql_addfield("label1", "staff.name_staff");
		$sql_struct_obj->prepare_sql_addorderby("staff_code");
		$sql_struct_obj->prepare_sql_addwhere("id = 'CURRENTID' OR date_end = '0000-00-00'");
		
		$structure = form_helper_prepare_dropdownfromobj("employeeid", $sql_struct_obj);
		$structure["options"]["req"]		= "yes";
		$structure["options"]["autoselect"]	= "yes";
		$structure["options"]["width"]		= "600";
		$structure["options"]["search_filter"]	= "enabled";
		$structure["defaultvalue"]		= @$_SESSION["user"]["default_employeeid"];
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "code_credit";
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



		// destination account
		if ($this->type == "ap_credit")
		{
			$structure = charts_form_prepare_acccountdropdown("dest_account", "ap_summary_account");
		}
		else
		{
			$structure = charts_form_prepare_acccountdropdown("dest_account", "ar_summary_account");
		}
			
		$structure["options"]["req"]		= "yes";
		$structure["options"]["autoselect"]	= "yes";
		$structure["options"]["search_filter"]	= "enabled";
		$structure["options"]["width"]		= "600";
		$this->obj_form->add_input($structure);


		

		// ID
		$structure = NULL;
		$structure["fieldname"]		= "id_credit";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->credit_id;
		$this->obj_form->add_input($structure);	


		// submit
		$structure = NULL;
		$structure["fieldname"]		= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Save Changes";
		$this->obj_form->add_input($structure);


		// load data
		if ($this->type == "ap_credit")
		{
			$this->obj_form->sql_query = "SELECT vendorid, invoiceid, employeeid, code_credit, code_ordernumber, code_ponumber, notes, date_trans, dest_account FROM account_". $this->type ." WHERE id='". $this->credit_id ."'";
		}
		else
		{
			$this->obj_form->sql_query = "SELECT customerid, invoiceid, employeeid, code_credit, code_ordernumber, code_ponumber, notes, date_trans, dest_account FROM account_". $this->type ." WHERE id='". $this->credit_id ."'";
		}
		
		$this->obj_form->load_data();


		/*
			Fetch any provided values from $_GET if adding a new credit and no error data provided
		*/
		if ($this->mode == "add" && error_check())
		{
			$this->obj_form->structure["customerid"]["defaultvalue"]	= @security_script_input('/^[0-9]*$/', $_GET["customerid"]);
			$this->obj_form->structure["invoiceid"]["defaultvalue"]		= @security_script_input('/^[0-9]*$/', $_GET["invoiceid"]);
			$this->obj_form->structure["vendorid"]["defaultvalue"]		= @security_script_input('/^[0-9]*$/', $_GET["vendorid"]);
		}


		// define subforms
		if ($this->type == "ap_credit")
		{
			$this->obj_form->subforms[$this->type ."_details"]	= array("vendorid", "invoiceid", "employeeid", "code_credit", "code_ordernumber", "code_ponumber", "date_trans");
		}
		else
		{
			$this->obj_form->subforms[$this->type ."_details"]	= array("customerid", "invoiceid", "employeeid", "code_credit", "code_ordernumber", "code_ponumber", "date_trans");
		}
		
		$this->obj_form->subforms[$this->type ."_financials"]		= array("dest_account");
		$this->obj_form->subforms[$this->type ."_other"	]		= array("notes");

		$this->obj_form->subforms["hidden"]				= array("id_credit");
	
	
		if ($this->locked)
		{
			$this->obj_form->subforms["submit"]			= array();
		}
		else
		{
			$this->obj_form->subforms["submit"]			= array("submit");
		}
		
		return 1;
	}


	function render_html()
	{
		log_debug("credit_form_details", "Executing render_html()");

		// display form	
		$this->obj_form->render_form();

		if ($this->locked)
		{
			format_msgbox("locked", "<p>This credit has been locked and can no longer be edited.</p>");
		}
	}

} // end of credit_form_details




/*
	class: credit_form_export

	Allows you to export the credit in different formats and provides functions to allow you to email the credit directly to the customer.
*/
class credit_form_export
{
	var $type;		// Only "ar" currently supported
	var $credit_id;		// ID of the credit
	var $processpage;	// Page to submit the form to


	var $obj_form_email;
	var $obj_form_download;
	var $obj_credit;
	var $obj_pdf;



	function execute()
	{
		log_debug("credit_form_export", "Executing execute()");

		/*
			Fetch basic credit details
		*/
		$obj_sql_credit			= New sql_query;
		$obj_sql_credit->string		= "SELECT code_credit, customerid FROM account_". $this->type ." WHERE id='". $this->credit_id ."' LIMIT 1";
		$obj_sql_credit->execute();
		$obj_sql_credit->fetch_array();



		/*
			Generate Email

			This function call provides us with all the email fields we can use to complete the form with.
		*/
			
		$obj_credit		= New credit;
		$obj_credit->type	= $this->type;
		$obj_credit->id		= $this->credit_id;

		$email = $obj_credit->generate_email();
		

		/*
			Define email form
		*/
		$this->obj_form_email = New form_input;
		$this->obj_form_email->formname = "credit_export_email";
		$this->obj_form_email->language = $_SESSION["user"]["lang"];

		$this->obj_form_email->action = $this->processpage;
		$this->obj_form_email->method = "post";
		

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "sender";
		$structure["type"]		= "radio";
		$structure["defaultvalue"]	= $email["sender"];
		$structure["values"]		= array("system", "user");
		
		$structure["translations"]["system"]	= sql_get_singlevalue("SELECT value FROM config WHERE name='COMPANY_NAME'") ." &lt;". sql_get_singlevalue("SELECT value FROM config WHERE name='COMPANY_CONTACT_EMAIL'") ."&gt;";
		$structure["translations"]["user"]	= user_information("realname") . " &lt;". user_information("contact_email") ."&gt;";
		
		$this->obj_form_email->add_input($structure);


		
		
		$structure = NULL;
		$structure["fieldname"] 	= "subject";
		$structure["type"]		= "input";
		$structure["defaultvalue"]	= $email["subject"];
		$structure["options"]["width"]	= "600";
		$this->obj_form_email->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "email_to";
		$structure["type"]		= "input";
		$structure["defaultvalue"]	= $email["to"];
		$structure["options"]["width"]	= "600";
		$this->obj_form_email->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "email_cc";
		$structure["type"]		= "input";
		$structure["defaultvalue"]	= $email["cc"];
		$structure["options"]["width"]	= "600";
		$this->obj_form_email->add_input($structure);
			
		$structure = NULL;
		$structure["fieldname"] 	= "email_bcc";
		$structure["type"]		= "input";
		$structure["defaultvalue"]	= $email["bcc"];
		$structure["options"]["width"]	= "600";
		$this->obj_form_email->add_input($structure);
	
		$structure = NULL;
		$structure["fieldname"] 	= "email_message";
		$structure["type"]		= "textarea";
		$structure["defaultvalue"] 	= $email["message"];

		$structure["options"]["width"]	= "600";
		$structure["options"]["height"]	= "100";
		$this->obj_form_email->add_input($structure);
		
		// hidden
		$structure = NULL;
		$structure["fieldname"] 	= "formname";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->obj_form_email->formname;
		$this->obj_form_email->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "id_credit";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->credit_id;
		$this->obj_form_email->add_input($structure);



		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Send via Email";
		$this->obj_form_email->add_input($structure);
		
		// load any data returned due to errors
		$this->obj_form_email->load_data_error();



		/*
			Define download form
		*/
		$this->obj_form_download = New form_input;
		$this->obj_form_download->formname = "credit_export_download";
		$this->obj_form_download->language = $_SESSION["user"]["lang"];

		$this->obj_form_download->action = $this->processpage;
		$this->obj_form_download->method = "post";
		

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "credit_mark_as_sent";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "Check this to show that the credit has been sent to the customer when you download the PDF";
		$this->obj_form_download->add_input($structure);


		// hidden
		$structure = NULL;
		$structure["fieldname"] 	= "formname";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->obj_form_download->formname;
		$this->obj_form_download->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "id_credit";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->credit_id;
		$this->obj_form_download->add_input($structure);


		

		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Download as PDF";
		$this->obj_form_download->add_input($structure);
		

		// load any data returned due to errors
		$this->obj_form_download->load_data_error();

		return 1;
	}

	
	function render_html()
	{
		log_debug("credit_form_export", "Executing render_html()");


		// download form
		print "<table width=\"100%\" class=\"table_highlight\"><tr><td>";
		print "<table cellpadding=\"5\" width=\"100%\"><tr>";

/*
	TODO: good place to add an icon

		print "<td valign=\"top\">";
			print "pdf_icon_here";
		print "</td>";
*/

		print "<td width=\"100%\">";
			print "<h3>Download PDF:</h3>";
			print "<form method=\"". $this->obj_form_download->method ."\" action=\"". $this->obj_form_download->action ."\">";
			print "<br><br>";
			$this->obj_form_download->render_field("credit_mark_as_sent");
			print "<br>";
			$this->obj_form_download->render_field("formname");
			$this->obj_form_download->render_field("id_credit");
			$this->obj_form_download->render_field("submit");
			print "</form>";
		print "</td>";	
		
		print "</tr></table>";
		print "</td></tr></table>";
		print "<br><br>";


		// email form
		print "<table width=\"100%\" class=\"table_highlight\"><tr><td>";
		print "<table cellpadding=\"5\" width=\"100%\"><tr>";

/*
	TODO: good place to add an icon

		print "<td valign=\"top\">";
			print "email_icon_here";
		print "</td>";
*/

		print "<td width=\"100%\">";
			print "<h3>Email PDF:</h3>";

			// check if we are permitted to send emails
			if (sql_get_singlevalue("SELECT value FROM config WHERE name='EMAIL_ENABLE'") == "enabled")
			{
				print "<form method=\"". $this->obj_form_email->method ."\" action=\"". $this->obj_form_email->action ."\">";
				print "<table width=\"100%\">";
				$this->obj_form_email->render_row("sender");
				$this->obj_form_email->render_row("subject");
				$this->obj_form_email->render_row("email_to");
				$this->obj_form_email->render_row("email_cc");
				$this->obj_form_email->render_row("email_bcc");
				$this->obj_form_email->render_row("email_message");
				print "</table>";
		
				$this->obj_form_email->render_field("formname");
				$this->obj_form_email->render_field("id_credit");
				$this->obj_form_email->render_field("submit");
			
				print "</form>";
			}
			else
			{
				format_msgbox("locked", "<p>The ability to email PDFs has been disabled by the administrator.</p>");
			}
		print "</td>";	
		
		print "</tr></table>";
		print "</td></tr></table>";
	}


} // end of credit_form_export







/*
	class: credit_form_delete

	Provides a form to allow deletion of an unwanted credit, provided that the credit is not locked.
	
*/
class credit_form_delete
{
	var $type;		// Either "ar" or "ap"
	var $credit_id;		// ID of the credit to delete
	var $processpage;	// Page to submit the form to

	var $locked;
	var $amount_credit;

	var $mode;
	
	var $obj_form;


	function execute()
	{
		log_debug("credit_form_delete", "Executing execute()");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT locked FROM account_". $this->type ." WHERE id='". $this->credit_id ."' LIMIT 1";
		$sql_obj->execute();
		$sql_obj->fetch_array();
		
		$this->locked		= $sql_obj->data[0]["locked"];


		/*
			Start Form
		*/
		$this->obj_form = New form_input;
		$this->obj_form->formname		= $this->type ."_credit_delete";
		$this->obj_form->language		= $_SESSION["user"]["lang"];

		$this->obj_form->action		= $this->processpage;
		$this->obj_form->method		= "POST";
		


		/*
			Define form structure
		*/
		
		// basic details
		$structure = NULL;
		$structure["fieldname"] 	= "code_credit";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "delete_confirm";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "Yes, I wish to delete this credit note and realise that once deleted the data can not be recovered.";
		$this->obj_form->add_input($structure);



		// hidden fields
		$structure = NULL;
		$structure["fieldname"] 	= "date_create";
		$structure["type"]		= "hidden";
		$this->obj_form->add_input($structure);
		


		// ID
		$structure = NULL;
		$structure["fieldname"]		= "id_credit";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->credit_id;
		$this->obj_form->add_input($structure);	


		// submit
		$structure = NULL;
		$structure["fieldname"]		= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "submit_credit_delete";
		$this->obj_form->add_input($structure);


		// load data
		$this->obj_form->sql_query = "SELECT date_create, code_credit, locked FROM account_". $this->type ." WHERE id='". $this->credit_id ."'";
		$this->obj_form->load_data();


		$this->obj_form->subforms[$this->type ."_delete"]		= array("code_credit");

		$this->obj_form->subforms["hidden"]				= array("id_credit", "date_create");


		if ($this->locked)
		{
			$this->obj_form->subforms["submit"]			= array("");
		}
		else
		{
			$this->obj_form->subforms["submit"]			= array("delete_confirm", "submit");
		}

	}


	function render_html()
	{
		log_debug("credit_form_delete", "Executing render_html()");
		
		// display form
		$this->obj_form->render_form();

		// display any reasons to prevent deletion
		if ($this->locked)
		{
			format_msgbox("locked", "<p>This credit has been locked and can no longer be removed.</p>");
		}
	}
}


/*
	class: credit_form_lock

	Provides a form to lock to the credit note, to ensure payments are not reversed by people deleting credit notes
	when they shouldn't.
	
*/
class credit_form_lock
{
	var $type;		// Either "ar" or "ap"
	var $credit_id;		// ID of the credit to delete
	var $processpage;	// Page to submit the form to
	var $locked;

	var $mode;
	
	var $obj_form;


	function execute()
	{
		log_debug("credit_form_lock", "Executing execute()");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT locked FROM account_". $this->type ." WHERE id='". $this->credit_id ."' LIMIT 1";
		$sql_obj->execute();
		$sql_obj->fetch_array();
		
		$this->locked		= $sql_obj->data[0]["locked"];


		/*
			Define form structure
		*/

		$this->obj_form = New form_input;
		$this->obj_form->formname		= $this->type ."_credit_lock";
		$this->obj_form->language		= $_SESSION["user"]["lang"];

		$this->obj_form->action			= $this->processpage;
		$this->obj_form->method			= "POST";
		
		
		// basic details
		$structure = NULL;
		$structure["fieldname"]		= "lock_credit";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "Yes, I wish to lock this credit note and realise once done, will be unable to unlock again.";
		$this->obj_form->add_input($structure);


		// hidden fields
		$structure = NULL;
		$structure["fieldname"]		= "id_credit";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->credit_id;
		$this->obj_form->add_input($structure);	


		// submit
		$structure = NULL;
		$structure["fieldname"]		= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "submit_credit_lock";
		$this->obj_form->add_input($structure);
	}


	function render_html()
	{
		log_debug("credit_form_lock", "Executing render_html()");
		
		if ($this->locked)
		{
			format_msgbox("important", "<p>This credit note is locked, no further changes can be made to it.</p>");
		}
		else
		{
			print "<table width=\"100%\" class=\"table_highlight_open\">";
			print "<tr>";
				print "<td>";
				print "<p><b>This credit note is currently unlocked - by locking the credit note, you can ensure it won't be deleted and the refund/credit won't be removed from the customer's account.</b></p>";
				print "<form method=\"". $this->obj_form->method ."\" action=\"". $this->obj_form->action ."\">";
			
				$this->obj_form->render_field("lock_credit");
					
				print "<br>";
				$this->obj_form->render_field("id_credit");
				$this->obj_form->render_field("submit");

				
				print "</form>";
				print "</td>";
			print "</tr>";
			print "</table>";
		}

		print "<br>";
	}

} // end of credit_form_lock





		



?>
