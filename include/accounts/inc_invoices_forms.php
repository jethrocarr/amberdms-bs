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
	var $type;		// Either "ar" or "ap"
	var $invoiceid;		// ID of the invoice
	var $page_export;	// Page to submit the form to

	var $obj_pdf;



	function execute()
	{
		log_debug("invoice_form_export", "Executing execute()");


		// nothing todo
		return 1;
	}


	function render_html()
	{
		log_debug("invoice_form_export", "Executing render_html()");

		// display export as PDF link
		print "<p><a href=\"index-export.php?mode=pdf&page=". $this->page_export ."&id=". $this->invoiceid ."\">Export PDF</a></p>";
	}


	function render_pdf()
	{
		log_debug("invoice_form_export", "Executing render_pdf()");
		
		// start the PDF object
		$this->obj_pdf = New template_engine_latex;

		// load template
		$this->obj_pdf->prepare_load_template("templates/latex/". $this->type ."_invoice.tex");


		/*
			Fetch data + define fields
		*/


		// fetch invoice data
		$sql_invoice_obj		= New sql_query;
		$sql_invoice_obj->string	= "SELECT code_invoice, code_ordernumber, customerid, date_due, date_trans, amount_total, amount_tax, amount FROM account_". $this->type ." WHERE id='". $this->invoiceid ."' LIMIT 1";
		$sql_invoice_obj->execute();
		$sql_invoice_obj->fetch_array();



		// fetch customer data
		$sql_customer_obj		= New sql_query;
		$sql_customer_obj->string	= "SELECT name_contact, name_customer, address1_street, address1_city, address1_state, address1_country, address1_zipcode FROM customers WHERE id='". $sql_invoice_obj->data[0]["customerid"] ."' LIMIT 1";
		$sql_customer_obj->execute();
		$sql_customer_obj->fetch_array();

		// customer fields
		$this->obj_pdf->prepare_add_field("name\_customer", $sql_customer_obj->data[0]["name_customer"]);


		/*
			Invoice Data (exc items/taxes)
		*/
		$this->obj_pdf->prepare_add_field("code\_invoice", $sql_invoice_obj->data[0]["code_invoice"]);
		$this->obj_pdf->prepare_add_field("code\_ordernumber", $sql_invoice_obj->data[0]["code_ordernumber"]);
		$this->obj_pdf->prepare_add_field("date\_trans", $sql_invoice_obj->data[0]["date_trans"]);
		$this->obj_pdf->prepare_add_field("date\_due", $sql_invoice_obj->data[0]["date_due"]);
		$this->obj_pdf->prepare_add_field("amount", $sql_invoice_obj->data[0]["amount"]);
		$this->obj_pdf->prepare_add_field("amount\_total", $sql_invoice_obj->data[0]["amount_total"]);



		/*
			Invoice Items
			(excluding tax items - these need to be processed in a different way)
		*/

		// fetch invoice items
		$sql_items_obj			= New sql_query;
		$sql_items_obj->string		= "SELECT id, type, chartid, customid, quantity, units, amount, price, description FROM account_items WHERE invoiceid='". $this->invoiceid ."' AND invoicetype='". $this->type ."' AND type!='tax' AND type!='payment'";
		$sql_items_obj->execute();
		$sql_items_obj->fetch_array();


		$structure_invoiceitems = array();
		foreach ($sql_items_obj->data as $data)
		{
			$structure = array();
			
			$structure["quantity"]		= $data["quantity"];

			switch ($data["type"])
			{
				case "product":
					/*
						Fetch product name
					*/
					$sql_obj		= New sql_query;
					$sql_obj->string	= "SELECT name_product FROM products WHERE id='". $data["customid"] ."' LIMIT 1";
					$sql_obj->execute();

					$sql_obj->fetch_array();
					
					$structure["info"] = $sql_obj->data[0]["name_product"];
					
					unset($sql_obj);
				break;


				case "time":
					/*
						Fetch time group ID
					*/

					$groupid = sql_get_singlevalue("SELECT option_value as value FROM account_items_options WHERE itemid='". $data["id"] ."' AND option_name='TIMEGROUPID'");

					$structure["info"] = sql_get_singlevalue("SELECT CONCAT_WS(' -- ', projects.code_project, time_groups.name_group) as value FROM time_groups LEFT JOIN projects ON projects.id = time_groups.projectid WHERE time_groups.id='$groupid' LIMIT 1");
				break;


				case "standard":
					/*
						Fetch account name and blank a few fields
					*/

					$sql_obj		= New sql_query;
					$sql_obj->string	= "SELECT CONCAT_WS(' -- ',code_chart,description) as name_account FROM account_charts WHERE id='". $data["chartid"] ."' LIMIT 1";
					$sql_obj->execute();

					$sql_obj->fetch_array();
					
					$structure["info"]	= $sql_obj->data[0]["name_account"];
					$structure["quantity"]	= " ";

					unset($sql_obj);
				break;
			}


			$structure["description"]	= $data["description"];
			$structure["units"]		= $data["units"];
			$structure["price"]		= $data["price"];
			$structure["amount"]		= $data["amount"];

			$structure_invoiceitems[] = $structure;
		}
	
		$this->obj_pdf->prepare_add_array("invoice_items", $structure_invoiceitems);

		unset($sql_items_obj);



		/*
			Tax Items
		*/

		// fetch tax items
		$sql_tax_obj			= New sql_query;
		$sql_tax_obj->string		= "SELECT "
							."account_items.amount, "
							."account_items.description, "
							."account_taxes.name_tax, "
							."account_taxes.taxnumber "
							."FROM "
							."account_items "
							."LEFT JOIN account_taxes ON account_taxes.id = account_items.customid "
							."WHERE "
							."invoiceid='". $this->invoiceid ."' "
							."AND invoicetype='". $this->type ."' "
							."AND type='tax'";
		$sql_tax_obj->execute();

		if ($sql_tax_obj->num_rows())
		{
			$sql_tax_obj->fetch_array();

			$structure_taxitems = array();
			foreach ($sql_tax_obj->data as $data)
			{
				$structure = array();
			
				$structure["name_tax"]		= $data["name_tax"];
				$structure["description"]	= $data["description"];
				$structure["taxnumber"]		= $data["taxnumber"];
				$structure["amount"]		= $data["amount"];

				$structure_taxitems[] = $structure;
			}
		}
	
		$this->obj_pdf->prepare_add_array("taxes", $structure_taxitems);





		/*
			Output PDF
		*/

		// perform string escaping for latex
		$this->obj_pdf->prepare_escape_fields();
		
		// fill template
		$this->obj_pdf->prepare_filltemplate();

		// generate PDF output
		$this->obj_pdf->generate_pdf();

		// output PDF file
		print $this->obj_pdf->output;
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
