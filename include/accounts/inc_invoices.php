<?php
/*
	include/accounts/inc_invoices.php

	Contains various help, wrapper and useful functions for working with invoices in the database.
*/



/*
	FUNCTIONS
*/



/*
	invoice_calc_duedate($date)

	This function takes the supplied date in YYYY-MM-DD format, and
	adds the number of days for the default payment term in the DB
	and returns a new due date value - this is suitable for the default
	due date on invoices

	Returns the data in YYYY-MM-DD format.
*/
function invoice_calc_duedate($date)
{
	log_debug("inc_invoices", "Executing invoice_calc_duedate($date)");
	
	// get the terms
	$terms = sql_get_singlevalue("SELECT value FROM config WHERE name='ACCOUNTS_TERMS_DAYS'");

	// break up the date, and reconfigure
	$date_array	= split("-", $date);
	$timestamp	= mktime(0, 0, 0, $date_array[1], ($date_array[2] + $terms), $date_array[0]);

	// generate the date
	return date("Y-m-d", $timestamp);
}


/*
	invoice_generate_invoiceid($type)

	Wrapper function for config_generate_uniqueid to generate a unique, unused ID
	for an invoice.

	Call this function just prior to inserting a new invoice into the database.

	Values
	type	Suitable options: "ar" or "ap"

	Returns
	#	invoice ID in a string.
*/
function invoice_generate_invoiceid($type)
{
	log_debug("inc_invoices", "Executing invoice_generate_invoiceid($type)");
	
	$type_uc = strtoupper($type);

	// use amberphplib function to perform most of the work
	return config_generate_uniqueid("ACCOUNTS_". $type_uc ."_INVOICENUM", "SELECT id FROM account_$type WHERE code_invoice='VALUE'");
}



/*
	invoice_render_summarybox($type, $id)

	Displays a summary box showing the status of the invoice (paid or unpaid) and information
	on the total of the invoice and total amount of payments.

	Values
	id	id of the invoice
	type	type - ar or ap

	Return Codes
	0	failure
	1	sucess
*/

function invoice_render_summarybox($type, $id)
{
	log_debug("inc_invoices", "invoice_render_summarybox($type, $id)");

	// fetch invoice information
	$sql_obj = New sql_query;
	$sql_obj->prepare_sql_settable("account_$type");

	if ($type == "ar")
	{
		$sql_obj->prepare_sql_addfield("date_sent");
		$sql_obj->prepare_sql_addfield("sentmethod");
	}
	
	$sql_obj->prepare_sql_addfield("code_invoice");
	$sql_obj->prepare_sql_addfield("amount_total");
	$sql_obj->prepare_sql_addfield("amount_paid");

	$sql_obj->prepare_sql_addwhere("id='$id'");
	$sql_obj->prepare_sql_setlimit("1");

	$sql_obj->generate_sql();
	$sql_obj->execute();

	if ($sql_obj->num_rows())
	{
		$sql_obj->fetch_array();

		if ($sql_obj->data[0]["amount_total"] == 0)
		{
			print "<table width=\"100%\" class=\"table_highlight_important\">";
			print "<tr>";
				print "<td>";
				print "<b>Invoice ". $sql_obj->data[0]["code_invoice"] ." has no items on it</b>";
				print "<p>This invoice is currently empty, add some items to it using the Invoice Items page.</p>";
				print "</td>";
			print "</tr>";
			print "</table>";
		}
		else
		{
			if ($sql_obj->data[0]["amount_paid"] == $sql_obj->data[0]["amount_total"])
			{
				print "<table width=\"100%\" class=\"table_highlight_green\">";
				print "<tr>";
					print "<td>";
					print "<b>Invoice ". $sql_obj->data[0]["code_invoice"] ." is closed (fully paid).</b>";
					print "<p>This invoice has been fully paid and no further action is required.</p>";
					print "</td>";
				print "</tr>";
				print "</table>";
			}
			else
			{
				print "<table width=\"100%\" class=\"table_highlight_important\">";
				print "<tr>";
					print "<td>";
					print "<b>Invoice ". $sql_obj->data[0]["code_invoice"] ." is open (unpaid).</b>";

					print "<table cellpadding=\"4\">";
					
					print "<tr>";
						print "<td>Total Due:</td>";
						print "<td>". sql_get_singlevalue("SELECT value FROM config WHERE name='CURRENCY_DEFAULT_SYMBOL'") ."". $sql_obj->data[0]["amount_total"] ."</td>";
					print "</tr>";
					
					print "<tr>";
						print "<td>Total Paid:</td>";
						print "<td>". sql_get_singlevalue("SELECT value FROM config WHERE name='CURRENCY_DEFAULT_SYMBOL'") ."". $sql_obj->data[0]["amount_paid"] ."</td>";
					print "</tr>";


					$amount_due = $sql_obj->data[0]["amount_total"] - $sql_obj->data[0]["amount_paid"];
					$amount_due = sprintf("%0.2f", $amount_due);

					print "<tr>";
						print "<td>Amount Due:</td>";
						print "<td>". sql_get_singlevalue("SELECT value FROM config WHERE name='CURRENCY_DEFAULT_SYMBOL'") ."". $amount_due."</td>";
					print "</tr>";

				
					if ($type == "ar")
					{
						print "<tr>";
							print "<td>Date Sent:</td>";
	
							if ($sql_obj->data[0]["sentmethod"] == "")
							{
								print "<td><i>Has not been sent to customer</i></td>";
							}
							else
							{
								print "<td>". $sql_obj->data[0]["date_sent"] ." (". $sql_obj->data[0]["sentmethod"] .")</td>";
							}
						print "</tr>";
					}
									
					print "</tr></table>";
					
					print "</td>";
				print "</tr>";
				print "</table>";
				
			}
		}

		print "<br>";
	}
}




/*
	CLASSES
*/




/*
	CLASS: INVOICE

	Provides functions for creating, editing and deleting invoices.
*/
class invoice
{
	var $type;		// type of invoice - AR/QUOTE/AP
	var $id;		// ID of invoice
	
	var $data;		// array for storage of all invoice data

	var $obj_pdf;		// generated PDF object


	/*
		verify_invoice

		Checks that the provided ID & type point to a valid invoice

		Results
		0	Failure to find the invoice
		1	Success - invoice exists
	*/

	function verify_invoice()
	{
		log_debug("inc_invoice", "Executing verify_invoice()");

		if ($this->id)
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM account_". $this->type ." WHERE id='". $this->id ."' LIMIT 1";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				return 1;
			}
		}

		return 0;

	} // end of verify_invoice


	/*
		check_lock

		Returns whether the invoice is locked or not.

		Results
		0	Unlocked
		1	Locked
		2	Failure (fail safe by reporting lock)
	*/

	function check_lock()
	{
		log_debug("inc_gl", "Executing check_lock()");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT locked FROM `account_". $this->type ."` WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$sql_obj->fetch_array();

			return $sql_obj->data[0]["locked"];
		}

		// failure
		return 2;

	}  // end of check_lock


	/*
		check_delete_lock

		Checks if the invoice is able to be deleted or not and returns the lock status.

		Results
		0	Unlocked
		1	Locked
		2	Failure (fail safe by reporting lock)
	*/

	function check_delete_lock()
	{
		log_debug("inc_gl", "Executing check_delete_lock()");

		return $this->check_lock();

	}  // end of check_delete_lock




	/*
		load_data

		Loads the invoice data from the MySQL database.

		Return Codes
		0	failure
		1	success
	*/
	function load_data()
	{
		log_debug("invoice", "Executing load_data()");
		
		// fetch invoice information from DB.
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT * FROM account_". $this->type ." WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_debug("invoice", "No such invoice ". $this->id ." in account_". $this->type ."");
			return 0;
		}
		else
		{
			$sql_obj->fetch_array();

			// save all the data into class variables
			$this->data = $sql_obj->data[0];

			unset($sql_obj);
		}

		return 1;
		
	} // end of load_data


	/*
		prepare_set_defaults

		Set the default values on the invoice unless values have already been provided.

		Results
		0			failure
		1			success
	*/
	function prepare_set_defaults()
	{
		log_debug("invoice", "Executing prepare_set_defaults");

		if (!$this->data["code_invoice"])
		{
			$this->prepare_code_invoice();
		}

		if (!$this->data["date_trans"])
		{
			$this->data["date_trans"] = date("Y-m-d");
		}
		
		if (!$this->data["date_due"])
		{
			$this->data["date_due"] = invoice_calc_duedate($this->data["date_trans"]);
		}


		return 1;
	}


	/*
		prepare_code_invoice

		Generate a code_invoice value for the invoice - either using one supplied to the function, or otherwise
		by checking the DB and fetching a suitable code from there.

		If a user requests to use a code_invoice that has already been allocated, the function will return failure.

		Values
		code_invoice		(optional) Request a code_invoice value to use

		Results
		0			failure
		1			success
	*/
	function prepare_code_invoice($code_invoice = NULL)
	{
		log_debug("invoice", "Executing prepare_code_invoice($code_invoice)");


		if ($code_invoice)
		{
			// user has provided a code_invoice
			// we need to verify that it is not already in use by any other invoice.
			
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM account_". $this->type ." WHERE code_invoice='". $code_invoice ."'";
		
			if ($this->data["id"])
				$sql_obj->string .= " AND id!='". $this->data["id"] ."'";
	
			// for AP invoices, the ID only need to be unique for the particular vendor we are working with, since
			// it's almost guaranteed that different vendors will use the same numbering scheme for their invoices
			if ($this->type == "ap")
				$sql_obj->string .= " AND vendorid='". $data["vendorid"] ."'";
			
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				log_debug("invoice", "Warning: The requested invoice code is already in use by another invoice");
				return 0;
			}

			unset($sql_obj);

			// save code_invoice
			$this->data["code_invoice"] = $code_invoice;
		}
		else
		{
			// generate an invoice ID using the database
			$type_uc = strtoupper($this->type);
			$this->data["code_invoice"] = config_generate_uniqueid("ACCOUNTS_". $type_uc ."_INVOICENUM", "SELECT id FROM account_". $this->type ." WHERE code_invoice='VALUE'");
		}
		
		return 1;
		
	} // end of prepare_code_invoice





	/*
		action_create

		Create a new invoice.

		Results
		0	failure
		1	success
	*/
	function action_create()
	{
		log_debug("invoice", "Executing action_create()");
	
	
		// set any default field if they have been left blank
		$this->prepare_set_defaults();
		
		// create new invoice entry
		$sql_obj		= New sql_query;
		$sql_obj->string	= "INSERT INTO account_". $this->type ." (code_invoice, date_create) VALUES ('".$this->data["code_invoice"]."', '". date("Y-m-d") ."')";
		if (!$sql_obj->execute())
		{
			log_debug("invoice", "Failure whilst creating initial invoice entry.");
			return 0;
		}

		$this->id = $sql_obj->fetch_insert_id();

		unset($sql_obj);


		// call the update function to process the invoice now that we have an ID for the DB row
		if (!$this->action_update())
		{
			return 0;
		}



		log_debug("invoice", "Successfully created new invoice ". $this->id ."");
		return 1;
		
	} // end of action_create



	/*
		action_update

		Updates an existing invoice.

		Results
		0	failure
		1	success
	*/
	function action_update()
	{
		log_debug("invoice", "Executing action_update()");

		// we must have an ID provided
		if (!$this->id)
		{
			log_debug("invoice", "No invoice ID supplied to action_update function");
			return 0;
		}

		// set any default field if they have been left blank
		$this->prepare_set_defaults();


		// fetch the original dest_account value - we will use it after we update the invoice details
		$this->data["dest_account_orig"] = sql_get_singlevalue("SELECT dest_account as value FROM account_". $this->type ." WHERE id='". $this->id ."' LIMIT 1");




		/*
			Update the invoice details
		*/
		$sql_obj = New sql_query;
			
		if ($this->type == "ap")
		{
			$sql_obj->string = "UPDATE `account_". $this->type ."` SET "
						."vendorid='". $this->data["vendorid"] ."', "
						."employeeid='". $this->data["employeeid"] ."', "
						."notes='". $this->data["notes"] ."', "
						."code_invoice='". $this->data["code_invoice"] ."', "
						."code_ordernumber='". $this->data["code_ordernumber"] ."', "
						."code_ponumber='". $this->data["code_ponumber"] ."', "
						."date_trans='". $this->data["date_trans"] ."', "
						."date_due='". $this->data["date_due"] ."', "
						."dest_account='". $this->data["dest_account"] ."' "
						."WHERE id='". $this->id ."'";
		}
		else
		{
			$sql_obj->string = "UPDATE `account_". $this->type ."` SET "
						."customerid='". $this->data["customerid"] ."', "
						."employeeid='". $this->data["employeeid"] ."', "
						."notes='". $this->data["notes"] ."', "
						."code_invoice='". $this->data["code_invoice"] ."', "
						."code_ordernumber='". $this->data["code_ordernumber"] ."', "
						."code_ponumber='". $this->data["code_ponumber"] ."', "
						."date_trans='". $this->data["date_trans"] ."', "
						."date_due='". $this->data["date_due"] ."', "
						."dest_account='". $this->data["dest_account"] ."' "
						."WHERE id='". $this->id ."'";
		}
		
		if (!$sql_obj->execute())
		{
			log_write("error", "invoice", "Unable to update database with new invoice information");
			return 0;
		}



		/*
			Check for changes to the destination account

			This is very important - if the user changes the destination account, we need to update all the entries
			in the account_trans table for this invoice.

			To make it easy, we call the invoice_items class and execute the action_update_ledger function to
			re-create all ledger entries.
		*/


		if ($this->data["dest_account_orig"] != $this->data["dest_account"])
		{
			log_debug("invoice", "dest_account has changed, calling action_update_ledger to update all the ledger transactions");
			
			// re-create all the ledger entries
			$invoice_items			= New invoice_items;
			
			$invoice_items->type_invoice	= $this->type;
			$invoice_items->id_invoice	= $this->id;

			$invoice_items->action_update_ledger();

			unset($invoice_items);
		}



	
		return 1;
		
		
	} // end of action_update




	/*
		action_delete

		Deletes an existing invoice.

		Results
		0	failure
		1	success
	*/
	function action_delete()
	{
		log_debug("invoice", "Executing action_delete()");

		// we must have an ID provided
		if (!$this->id)
		{
			log_debug("invoice", "No invoice ID supplied to action_delete function");
			return 0;
		}


		// track errors
		$error = 0;

	
		// delete invoice itself
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM account_". $this->type ." WHERE id='". $this->id ."'";
		
		if (!$sql_obj->execute())
		{
			$error = 1;
			log_write("error", "invoice", "Problem occured whilst deleting invoice from acccount_". $this->type ." in DB");
		}

		// delete all the invoice items.
		//
		// we do this by using the invoice_items::action_delete() function, since there are number of complex
		// steps when deleting certain invoice items (such as time items)

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_items WHERE invoicetype='". $this->type ."' AND invoiceid='". $this->id ."'";
				
		if (!$sql_obj->execute())
		{
			$error = 1;
			log_write("error", "invoice", "Problem occured whilst deleting invoice items from DB");
		}
		else
		{
			$sql_obj->fetch_array();

			foreach ($sql_obj->data as $data)
			{
				// delete each invoice one-at-a-time.
				$obj_invoice_item			= New invoice_items;

				$obj_invoice_item->type_invoice		= $this->type;
				$obj_invoice_item->id_invoice		= $this->id;
				$obj_invoice_item->id_item		= $data["id"];
				$obj_invoice_item->action_delete();

				unset($obj_invoice_item);
			}
		}

		unset($sql_obj);



		// delete invoice journal entries
		journal_delete_entire("account_". $this->type ."", $this->id);


		// delete invoice transactions
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM account_trans WHERE (type='". $this->type ."' || type='". $this->type ."_tax' || type='". $this->type ."_pay') AND customid='". $this->id ."'";
		
		if (!$sql_obj->execute())
		{
			$error = 1;
			log_write("error", "invoice", "Problem occured whilst deleting invoice transactions");
		}


		if ($error)
		{
			return 0;
		}
		
		return 1;
		
	} // end of action_delete


	/*
		generate_pdf

		Generates a PDF of the invoice and saves it into memory at $this->obj_pdf->output.

		Results
		0	failure
		1	success
	*/
	function generate_pdf()
	{
		log_debug("invoice", "Executing prepare_generate_pdf()");

		// start the PDF object
		// note: the & allows decontructors to operate
		$this->obj_pdf =& New template_engine_latex;

		// load template
		if (file_exists("../../templates/latex/". $this->type ."_invoice.tex"))
		{
			$this->obj_pdf->prepare_load_template("../../templates/latex/". $this->type ."_invoice.tex");
		}
		else
		{
			$this->obj_pdf->prepare_load_template("../templates/latex/". $this->type ."_invoice.tex");
		}

		

		/*
			Customer Data
		*/
		

		// fetch customer data
		$sql_customer_obj		= New sql_query;
		$sql_customer_obj->string	= "SELECT name_contact, name_customer, address1_street, address1_city, address1_state, address1_country, address1_zipcode FROM customers WHERE id='". $this->data["customerid"] ."' LIMIT 1";
		$sql_customer_obj->execute();
		$sql_customer_obj->fetch_array();


		// customer fields
		$this->obj_pdf->prepare_add_field("customer\_name", $sql_customer_obj->data[0]["name_customer"]);
		$this->obj_pdf->prepare_add_field("customer\_contact", $sql_customer_obj->data[0]["name_contact"]);
		$this->obj_pdf->prepare_add_field("customer\_address1\_street", $sql_customer_obj->data[0]["address1_street"]);
		$this->obj_pdf->prepare_add_field("customer\_address1\_city", $sql_customer_obj->data[0]["address1_city"]);
		$this->obj_pdf->prepare_add_field("customer\_address1\_state", $sql_customer_obj->data[0]["address1_state"]);
		$this->obj_pdf->prepare_add_field("customer\_address1\_country", $sql_customer_obj->data[0]["address1_country"]);

		if ($sql_customer_obj->data[0]["address1_zipcode"] == 0)
		{
			$sql_customer_obj->data[0]["address1_zipcode"] = "";
		}
		
		$this->obj_pdf->prepare_add_field("customer\_address1\_zipcode", $sql_customer_obj->data[0]["address1_zipcode"]);



		/*
			Company Data
		*/
		
		// company logo
		$this->obj_pdf->prepare_add_file("company_logo", "png", "COMPANY_LOGO", 0);
		
		// fetch company data
		$sql_company_obj		= New sql_query;
		$sql_company_obj->string	= "SELECT name, value FROM config WHERE name LIKE '%COMPANY%'";
		$sql_company_obj->execute();
		$sql_company_obj->fetch_array();

		foreach ($sql_company_obj->data as $data_db)
		{
			$data_company[ strtolower($data_db["name"]) ] = $data_db["value"];
		}

		// company fields
		$this->obj_pdf->prepare_add_field("company\_name", $data_company["company_name"]);
		
		$this->obj_pdf->prepare_add_field("company\_contact\_email", $data_company["company_contact_email"]);
		$this->obj_pdf->prepare_add_field("company\_contact\_phone", $data_company["company_contact_phone"]);
		$this->obj_pdf->prepare_add_field("company\_contact\_fax", $data_company["company_contact_fax"]);
		
		$this->obj_pdf->prepare_add_field("company\_address1\_street", $data_company["company_address1_street"]);
		$this->obj_pdf->prepare_add_field("company\_address1\_city", $data_company["company_address1_city"]);
		$this->obj_pdf->prepare_add_field("company\_address1\_state", $data_company["company_address1_state"]);
		$this->obj_pdf->prepare_add_field("company\_address1\_country", $data_company["company_address1_country"]);
		$this->obj_pdf->prepare_add_field("company\_address1\_zipcode", $data_company["company_address1_zipcode"]);
		
		if ($this->type == "ar")
		{
			$this->obj_pdf->prepare_add_field("company\_payment\_details", $data_company["company_payment_details"]);
		}

		

		/*
			Invoice Data (exc items/taxes)
		*/
		if ($this->type == "ar")
		{
			$this->obj_pdf->prepare_add_field("code\_invoice", $this->data["code_invoice"]);
			$this->obj_pdf->prepare_add_field("code\_ordernumber", $this->data["code_ordernumber"]);
			$this->obj_pdf->prepare_add_field("date\_due", time_format_humandate($this->data["date_due"]));
		}
		else
		{
			$this->obj_pdf->prepare_add_field("code\_quote", $this->data["code_quote"]);
			$this->obj_pdf->prepare_add_field("date\_validto", time_format_humandate($this->data["date_validto"]));
		}
		
		$this->obj_pdf->prepare_add_field("date\_trans", time_format_humandate($this->data["date_trans"]));
		$this->obj_pdf->prepare_add_field("amount", $this->data["amount"]);
		$this->obj_pdf->prepare_add_field("amount\_total", $this->data["amount_total"]);



		/*
			Invoice Items
			(excluding tax items - these need to be processed in a different way)
		*/

		// fetch invoice items
		$sql_items_obj			= New sql_query;
		$sql_items_obj->string		= "SELECT id, type, chartid, customid, quantity, units, amount, price, description FROM account_items WHERE invoiceid='". $this->id ."' AND invoicetype='". $this->type ."' AND type!='tax' AND type!='payment'";
		$sql_items_obj->execute();
		$sql_items_obj->fetch_array();


		$structure_invoiceitems = array();
		foreach ($sql_items_obj->data as $itemdata)
		{
			$structure = array();
			
			$structure["quantity"]		= $itemdata["quantity"];

			switch ($itemdata["type"])
			{
				case "product":
					/*
						Fetch product code
					*/
					$sql_obj		= New sql_query;
					$sql_obj->string	= "SELECT code_product FROM products WHERE id='". $itemdata["customid"] ."' LIMIT 1";
					$sql_obj->execute();

					$sql_obj->fetch_array();
					
					$structure["info"] = $sql_obj->data[0]["code_product"];
					
					unset($sql_obj);
				break;


				case "time":
					/*
						Fetch time group ID
					*/

					$groupid = sql_get_singlevalue("SELECT option_value as value FROM account_items_options WHERE itemid='". $itemdata["id"] ."' AND option_name='TIMEGROUPID'");

					$structure["info"] = sql_get_singlevalue("SELECT CONCAT_WS(' -- ', projects.code_project, time_groups.name_group) as value FROM time_groups LEFT JOIN projects ON projects.id = time_groups.projectid WHERE time_groups.id='$groupid' LIMIT 1");
				break;


				case "standard":
					/*
						Fetch account name and blank a few fields
					*/

					$sql_obj		= New sql_query;
					$sql_obj->string	= "SELECT CONCAT_WS(' -- ',code_chart,description) as name_account FROM account_charts WHERE id='". $itemdata["chartid"] ."' LIMIT 1";
					$sql_obj->execute();

					$sql_obj->fetch_array();
					
					$structure["info"]	= $sql_obj->data[0]["name_account"];
					$structure["quantity"]	= " ";

					unset($sql_obj);
				break;
			}


			$structure["description"]	= $itemdata["description"];
			$structure["units"]		= $itemdata["units"];
			$structure["price"]		= $itemdata["price"];
			$structure["amount"]		= $itemdata["amount"];

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
							."account_taxes.name_tax, "
							."account_taxes.taxnumber "
							."FROM "
							."account_items "
							."LEFT JOIN account_taxes ON account_taxes.id = account_items.customid "
							."WHERE "
							."invoiceid='". $this->id ."' "
							."AND invoicetype='". $this->type ."' "
							."AND type='tax'";

		$sql_tax_obj->execute();

		if ($sql_tax_obj->num_rows())
		{
			$sql_tax_obj->fetch_array();

			$structure_taxitems = array();
			foreach ($sql_tax_obj->data as $taxdata)
			{
				$structure = array();
			
				$structure["name_tax"]		= $taxdata["name_tax"];
				$structure["taxnumber"]		= $taxdata["taxnumber"];
				$structure["amount"]		= $taxdata["amount"];

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

		// Useful for debugging - shows the processed latex data before it is turned into a PDF.
		//print "<pre>";
		//print_r($this->obj_pdf->processed);
		//print "</pre>";
		
		// generate PDF output
		$this->obj_pdf->generate_pdf();

	} // end of generate_pdf



	/*
		email_invoice

		Sends a PDF version of the invoice via email and then records a copy
		of the email in the invoice journal.

		Fields
		email_sender	Either "system" or "user" to select the from address for the email.
		email_to	Destination address(es)
		email_cc	Destination address(es)
		email_bcc	Destination address(es)
		email_subject	Email Subject
		email_message	Text message of the email.

		Returns
		0	failure
		1	success
	*/
	function email_invoice($email_sender, $email_to, $email_cc, $email_bcc, $email_subject, $email_message)
	{
		log_debug("invoice", "Executing email_invoice([options])");


		// external dependency of Mail_Mime
		@include('Mail.php');
		if (!@include('Mail/mime.php'))
		{
			log_write("error", "invoice", "Unable to find Mail::Mime module required for sending email");
			return 0;
		}


	
		/*
			Generate a PDF of the invoice and save to tmp file
		*/

		log_debug("invoice", "Generating invoice PDF for emailing");

		// generate PDF
		$this->generate_pdf();

		// save to a temporary file
		if ($this->type == "ar")
		{
			$tmp_filename = file_generate_name("/tmp/invoice_". $this->data["code_invoice"] ."", "pdf");
		}
		else
		{
			$tmp_filename = file_generate_name("/tmp/quote_". $this->data["code_quote"] ."", "pdf");
		}
			

		if (!$fhandle = fopen($tmp_filename, "w"))
		{
			die("fatal error occured whilst writing to file $tmp_filename");
		}
			
		fwrite($fhandle, $this->obj_pdf->output);
		fclose($fhandle);



		/*
			Email the invoice
		*/
		
		log_debug("invoice", "Sending email");
		// fetch sender address
		//
		// users have the choice of sending as the company or as their own staff email address & name.
		//
		if ($email_sender == "user")
		{
			// send as the user
			$email_sender = user_information("realname") . " <". user_information("contact_email") .">";
		}
		else
		{
			// send as the system
			$email_sender = sql_get_singlevalue("SELECT value FROM config WHERE name='COMPANY_NAME'") ." <". sql_get_singlevalue("SELECT value FROM config WHERE name='COMPANY_CONTACT_EMAIL'") .">";
		}
			

		// prepare headers
		$mail_headers = array(
				'From'   	=> $email_sender,
				'Subject'	=> $email_subject,
				'Cc'		=> $email_cc,
				'Bcc'		=> $email_bcc
		);

		$mail_mime = new Mail_mime("\n");
			
		$mail_mime->setTXTBody($email_message);
		$mail_mime->addAttachment($tmp_filename, 'application/pdf');

		$mail_body	= $mail_mime->get();
	 	$mail_headers	= $mail_mime->headers($mail_headers);

		$mail		= & Mail::factory('mail');
		$mail->send($email_to, $mail_headers, $mail_body);


		/*
			Mark the invoice as having been sent
		*/
		$sql_obj		= New sql_query;
		$sql_obj->string	= "UPDATE account_". $this->type ." SET date_sent='". date("Y-m-d") ."', sentmethod='email' WHERE id='". $this->id ."'";
		$sql_obj->execute();


		/*
			Add the email information to the journal
		*/

		$journal = New journal_process;
		
		$journal->prepare_set_journalname("account_". $this->type);
		$journal->prepare_set_customid($this->id);
		$journal->prepare_set_type("text");
		
		$journal->prepare_set_title("EMAIL: $email_subject");

		$data["content"] = NULL;
		$data["content"] .= "To: ". $email_to ."\n";
		$data["content"] .= "Cc: ". $email_cc ."\n";
		$data["content"] .= "Bcc: ". $email_bcc ."\n";
		$data["content"] .= "From: ". $email_sender ."\n";
		$data["content"] .= "\n";
		$data["content"] .= $email_message;
		$data["content"] .= "\n\n";
		$data["content"] .= "[attachment scrubbed]";
			
			
		$journal->prepare_set_content($data["content"]);

		$journal->action_create();


		// cleanup - remove the temporary files
		log_debug("inc_invoices_process", "Performing cleanup - removing temporary file $tmp_filename");
		unlink($tmp_filename);

		return 1;
	
	} // end of email_invoice

} // END OF INVOICE CLASS


	





/*
	CLASS: INVOICE_ITEMS

	Provides functions for working with invoice items.
*/
class invoice_items
{
	var $id_invoice;	// id of the invoice
	var $id_item;		// id of the item

	var $type_invoice;	// type of invoice
	var $type_item;		// type of invoice

	var $data;		// data of the item



	/*
		verify_invoice

		Determine if the invoice ID supplied does actually exist.

		Returns
		0		failure
		1		success
	*/
	function verify_invoice()
	{
		/*
			Verify that the invoice exists
		*/
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_". $this->type_invoice ." WHERE id='". $this->id_invoice ."' LIMIT 1";
		$sql_obj->execute();
	
		if (!$sql_obj->num_rows())
		{
			return 0;
		}

		return 1;
		
	} // end of verify_invoice
		


	/*
		verify_item

		Checks that the supplied item ID actually exists and belongs to this invoice.

		Results
		0		failure
		1		success
	*/
	function verify_item()
	{
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id, type FROM account_items WHERE id='". $this->id_item ."' AND invoicetype='". $this->type_invoice ."' AND invoiceid='". $this->id_invoice ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			return 0;
		}
		else
		{
			// if we don't know the item type currently, we can use this SQL query to fetch it for us now
			if (!$this->type_item)
			{
				$sql_obj->fetch_array();

				$this->type_item = $sql_obj->data[0]["type"];
			}
		}

		return 1;
	} // end verify_item


	/*
		load_data

		Loads the item data from the MySQL database.

		Return Codes
		0	failure
		1	success
	*/
	function load_data()
	{
		log_debug("invoice", "Executing load_data()");
		
		// fetch invoice item information from DB.
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT * FROM account_items WHERE id='". $this->id_item ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_debug("invoice", "No such item". $this->id_item ."");
			return 0;
		}
		else
		{
			$sql_obj->fetch_array();

			// save all the data into class variables
			$this->data = $sql_obj->data[0];

			unset($sql_obj);


			// if this is a standard item, load the tax options as well
			if ($this->type_item == "standard")
			{
				// load all the taxes
				$sql_tax_obj		= New sql_query;
				$sql_tax_obj->string	= "SELECT id FROM account_taxes";
				$sql_tax_obj->execute();

				if ($sql_tax_obj->num_rows())
				{
					// run through all the taxes
					$sql_tax_obj->fetch_array();

					foreach ($sql_tax_obj->data as $data_tax)
					{
						// see if this tax is currently inuse by the item
						$sql_taxenabled_obj		= New sql_query;
						$sql_taxenabled_obj->string	= "SELECT id FROM account_items_options WHERE itemid='". $this->id_item ."' AND option_name='TAX_CHECKED' AND option_value='". $data_tax["id"] ."'";
						$sql_taxenabled_obj->execute();

						if ($sql_taxenabled_obj->num_rows())
						{
							$this->data["tax_". $data_tax["id"] ] = "on";
						}
						else
						{
							$this->data["tax_". $data_tax["id"] ] = "off";
						}

						unset($sql_taxenabled_obj);
					}

				} // end of loop through taxes

			}

		}

		return 1;
		
	} // end 



	/*
		check_lock

		Returns whether the invoice that this item belongs to is locked or not.

		Results
		0	Unlocked
		1	Locked
		2	Failure (fail safe by reporting lock)
	*/

	function check_lock()
	{
		log_debug("inc_gl", "Executing check_lock()");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT locked FROM `account_". $this->type_invoice ."` WHERE id='". $this->id_invoice ."' LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$sql_obj->fetch_array();

			return $sql_obj->data[0]["locked"];
		}

		// failure
		return 2;

	}  // end of check_lock




	/*
		prepare_data

		Take the supplied array of data fields, then processes all the data and saves into
		the $this->data array.

		Values
		$data		array of data fields to process

		Results
		0		failure
		1		success
	*/
	function prepare_data($data)
	{
		log_debug("invoice_items", "Executing prepare_data(array)");
		

		// we must have supplied item data
		if (!$this->type_item)
		{
			log_write("error", "invoice_items", "No item_type value supplied, unable to process data.");
			return 0;
		}

		// process for the item type.
		switch($this->type_item)
		{
			case "standard":
				/*
					STANDARD ITEMS
				*/

				// very simple, just copy the data across
				foreach (array_keys($data) as $i)
				{
					$this->data[ $i ] = $data[ $i ];
				}

//				$this->data["amount"]		= $data["amount"];
//				$this->data["chartid"]		= $data["chartid"];
//				$this->data["description"]	= $data["description"];



			break;
			

			case "product":
				/*
					PRODUCT ITEMS
				*/
		
				// save information
				$this->data["price"]		= $data["price"];
				$this->data["quantity"]		= $data["quantity"];
				$this->data["units"]		= $data["units"];
				$this->data["customid"]		= $data["customid"];
				$this->data["description"]	= $data["description"];

				// calculate the total amount
				$this->data["amount"]		= $data["price"] * $data["quantity"];

				// get the chart for the product
				$sql_obj		= New sql_query;
				$sql_obj->string	= "SELECT account_sales FROM products WHERE id='". $this->data["customid"] ."' LIMIT 1";
				$sql_obj->execute();

				if ($sql_obj->num_rows())
				{
					$sql_obj->fetch_array();
	
					$this->data["chartid"] = $sql_obj->data[0]["account_sales"];
				}
				else
				{
					if (!$_SESSION["error"]["productid-error"])
					{
						$_SESSION["error"]["message"][] = "The requested product does not exist!";
						$_SESSION["error"]["productid-error"] = 1;
						return 0;
					}
				}
			break;


			case "time":
				/*
					TIME ITEMS

					We need to get the number of billable hours, then calculate
					the total charge for the item.

					The supplied price is the cost per hour, and the supplied productid
					provides the information for where the time should be billed to.
				*/
			
				// a time item can only be added to an AR transactions
				if ($this->type_invoice != "ar")
				{
					$_SESSION["error"]["message"][] = "You can only add time invoice items to AR invoices.";
					return 0;
				}

		
				// save information
				$this->data["price"]		= $data["price"];
				$this->data["customid"]		= $data["customid"];
				$this->data["timegroupid"]	= $data["timegroupid"];
				$this->data["description"]	= $data["description"];
				$this->data["units"]		= $data["units"];

				// fetch the number of billable hours for the supplied timegroupid
				$sql_obj		= New sql_query;
				$sql_obj->string	= "SELECT SUM(time_booked) as time_billable FROM timereg WHERE groupid='". $this->data["timegroupid"] ."'";
				$sql_obj->execute();

				if ($sql_obj->num_rows())
				{
					// work out the number of hours and excess minutes
					$sql_obj->fetch_array();
		
					$minutes	= $sql_obj->data[0]["time_billable"] / 60;
					$hours		= sprintf("%d",$minutes / 60);
					
					$excess_minutes = sprintf("%02d", $minutes - ($hours * 60));
					
					// convert minutes to base-10 numbering systems
					// eg: 15mins becomes 0.25
					$excess_minutes = $excess_minutes / 60;
					
					// set the quantity
					$this->data["quantity"] = $hours + $excess_minutes;
				}
				else
				{
					$_SESSION["error"]["message"][] = "Invalid time group supplied!";
					return 0;
				}
				
				
				// calculate the total amount
				$this->data["amount"] = $this->data["price"] * $this->data["quantity"];

				// get the chart for the product
				$sql_obj		= New sql_query;
				$sql_obj->string	= "SELECT account_sales FROM products WHERE id='". $this->data["customid"] ."' LIMIT 1";
				$sql_obj->execute();

				if ($sql_obj->num_rows())
				{
					$sql_obj->fetch_array();

					$this->data["chartid"] = $sql_obj->data[0]["account_sales"];
				}
				else
				{
					if (!$_SESSION["error"]["productid-error"])
					{
						$_SESSION["error"]["message"][]		= "The requested product does not exist!";
						$_SESSION["error"]["productid-error"]	= 1;
						return 0;
					}
				}
			break;


			case "payment":
				/*
					PAYMENT ITEM
				*/

				// fetch information from form
				$this->data["date_trans"]		= $data["date_trans"];
				$this->data["amount"]			= $data["amount"];
				$this->data["chartid"]			= $data["chartid"];
				$this->data["source"]			= $data["source"];
				$this->data["description"]		= $data["description"];
				
			break;


			default:
				$_SESSION["error"]["message"][] = "Unknown item type provided.";
				return 0;
			break;
		}


		// complete
		return 1;
		
	} // end of prepare_data



	/*
		action_create
	
		Create a new invoice item

		Returns
		0		failure
		#		success - return the item ID
	*/
	function action_create()
	{
		log_debug("invoice_items", "Executing action_create");
		
		$sql_obj		= New sql_query;
		$sql_obj->string	= "INSERT INTO `account_items` (invoiceid, invoicetype) VALUES ('". $this->id_invoice ."', '". $this->type_invoice ."')";
		
		if (!$sql_obj->execute())
		{
			return 0;
		}
		else
		{
			$this->id_item = $sql_obj->fetch_insert_id();

			if ($this->action_update())
			{
				// notify success
				log_write("notification", "invoice_items", "Successfully created new invoice item");
				journal_quickadd_event("account_". $this->type_invoice ."", $this->id_invoice, "Item successfully created");


				return $this->id_item;
			}
		}

		return 0;
	} // end of action_create




	/*
		action_update

		Updates the invoice item information.


		Note: This does not update the invoice details, taxes or ledger, the following functions
		need to be called afterwards:
		* action_update_tax
		* action_update_total
		* action_update_ledger

		Returns
		0		failure
		#		success - return item ID
	*/
	function action_update()
	{
		log_debug("invoice_items", "Executing action_update()");
		
		if (!$this->id_item)
		{
			log_write("error", "invoice_items", "No item ID was supplied before running action_update");
			return 0;
		}


		/*
			Update Item
		*/
		$sql_obj = New sql_query;
			
		$sql_obj->string = "UPDATE `account_items` SET "
					."type='". $this->type_item ."', "
					."amount='". $this->data["amount"] ."', "
					."price='". $this->data["price"] ."', "
					."chartid='". $this->data["chartid"] ."', "
					."customid='". $this->data["customid"] ."', "
					."quantity='". $this->data["quantity"] ."', "
					."units='". $this->data["units"] ."', "
					."description='". $this->data["description"] ."' "
					."WHERE id='". $this->id_item ."'";
						
		if (!$sql_obj->execute())
		{
			log_write("error", "invoice_items", "A fatal problem ocurred whilst attempting to update the DB.");
			return 0;
		}



		/*
			Update Item Options
		*/

		// remove all existing options
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM account_items_options WHERE itemid='". $this->id_item ."'";
		$sql_obj->execute();


		// create options for standard transactions
		if ($this->type_item == "standard")
		{
			// fetch list of tax IDs
			$sql_tax_obj		= New sql_query;
			$sql_tax_obj->string	= "SELECT id FROM account_taxes";
			$sql_tax_obj->execute();

			if ($sql_tax_obj->num_rows())
			{
				$sql_tax_obj->fetch_array();

				foreach ($sql_tax_obj->data as $data_tax)
				{
					if ($this->data["tax_". $data_tax["id"] ] == "on")
					{
						$sql_obj		= New sql_query;
						$sql_obj->string	= "INSERT INTO account_items_options (itemid, option_name, option_value) VALUES ('". $this->id_item ."', 'TAX_CHECKED', '". $data_tax["id"] ."')";
						$sql_obj->execute();
					}
				}

			} // end of loop through taxes

		}

		// create options for payments
		if ($this->type_item == "payment")
		{
			// source
			$sql_obj		= New sql_query;
			$sql_obj->string	= "INSERT INTO account_items_options (itemid, option_name, option_value) VALUES ('". $this->id_item ."', 'SOURCE', '". $this->data["source"] ."')";
			$sql_obj->execute();

			// date_trans
			$sql_obj		= New sql_query;
			$sql_obj->string	= "INSERT INTO account_items_options (itemid, option_name, option_value) VALUES ('". $this->id_item ."', 'DATE_TRANS', '". $this->data["date_trans"] ."')";
			$sql_obj->execute();
		}


		// create options for time items
		if ($this->type_item == "time")
		{
			// create options entry for the timegroupid
			$sql_obj		= New sql_query;
			$sql_obj->string	= "INSERT INTO account_items_options (itemid, option_name, option_value) VALUES ('". $this->id_item ."', 'TIMEGROUPID', '". $this->data["timegroupid"] ."')";
			$sql_obj->execute();

			// fetch the current lock status of the time group
			// if it's set to 1, we want to keep that. otherwise, we want to set it to 2
			$locked = sql_get_singlevalue("SELECT locked as value FROM time_groups WHERE id='". $this->data["timegroupid"] ."'");

			if ($locked == 0)
			{
				$locked = 2;
			}

			// update the time_group with the status, invoiceid and itemid
			$sql_obj		= New sql_query;
			$sql_obj->string	= "UPDATE time_groups SET invoiceid='". $this->id_invoice ."', invoiceitemid='". $this->id_item ."', locked='". $locked ."' WHERE id='". $this->data["timegroupid"] ."'";
			$sql_obj->execute();
		}


		// success
		log_write("notification", "invoice_items", "Successfully updated invoice item");
		journal_quickadd_event("account_". $this->type_invoice ."", $this->id_invoice, "Item successfully updated");
	
		return $this->id_item;	
		
	} // end of action_update
	


	/*
		action_update_tax
	
		This function runs through the invoice items and re-generates all the taxes
		on the invoice.
	
		Note that it does NOT update the tax totals on the invoice itself or the ledger, so you MUST run
		the following functions afterwards:
		* action_update_totals
		* action_update_ledger

		Return Codes
		0		failure
		1		success
	*/
	function action_update_tax()
	{
		log_debug("invoice_items", "Executing action_update_tax()");


		/*
			Delete all taxes currently on the selected invoice.
		*/

		log_write("debug", "invoice_items", "Removing existing tax items...");


		$sql_items_obj		= New sql_query;
		$sql_items_obj->string	= "SELECT id FROM account_items WHERE invoicetype='". $this->type_invoice ."' AND invoiceid='". $this->id_invoice ."' AND type='tax'";
		$sql_items_obj->execute();

		if ($sql_items_obj->num_rows())
		{
			$sql_items_obj->fetch_array();

			foreach ($sql_items_obj->data as $data_item)
			{
				// delete the tax items
				$sql_obj		= New sql_query;
				$sql_obj->string	= "DELETE FROM account_items WHERE id='". $data_item["id"] ."'";
				$sql_obj->execute();

				// delete the tax items options
				$sql_obj		= New sql_query;
				$sql_obj->string	= "DELETE FROM account_items_options WHERE itemid='". $data_item["id"] ."'";
				$sql_obj->execute();
			}
		}





		/*
			Run though all the non-tax & non-payment items and get their tax details. This creates
			an associative array of tax information which we can then use to create aggregated tax
			items.
		*/

		$tax_structure = NULL;

		log_write("debug", "invoice_items", "Totalling different taxes and item values...");

		$sql_items_obj		= New sql_query;
		$sql_items_obj->string	= "SELECT id, type, customid, amount, quantity FROM account_items WHERE invoicetype='". $this->type_invoice ."' AND invoiceid='". $this->id_invoice ."' AND type!='tax' AND type!='payment'";
		$sql_items_obj->execute();

		if ($sql_items_obj->num_rows())
		{
			$sql_items_obj->fetch_array();

			foreach ($sql_items_obj->data as $data)
			{
				if ($data["type"] == "time" || $data["type"] == "product")
				{
					/*
						HANDLE TAXES FOR PRODUCT-BASED ITEMS
					*/


					// fetch the taxes for the selected product
					$sql_product_tax_obj		= New sql_query;
					$sql_product_tax_obj->string	= "SELECT taxid, manual_option, manual_amount FROM `products_taxes` WHERE productid='". $data["customid"] ."'";
					$sql_product_tax_obj->execute();

					if ($sql_product_tax_obj->num_rows())
					{
						$sql_product_tax_obj->fetch_array();

						foreach ($sql_product_tax_obj->data as $data_product_tax)
						{
							// add item amount, 
							if ($data_product_tax["manual_option"])
							{
								// manual amount
								// note: we multiple manual amount by the quantity of units to ensure valid tax amount
								$tax_structure[ $data_product_tax["taxid"] ]["manual"]	+= $data_product_tax["manual_amount"] * $data["quantity"];
							}
							else
							{
								// automatic
								// note: no need to multiple by quantity, since the item amount is already price * quantity
								$tax_structure[ $data_product_tax["taxid"] ]["auto"]	+= $data["amount"];
							}
						}
					}
				} // end of if item == time || item == product
				elseif ($data["type"] == "standard")
				{
					/*
						HANDLE TAXES FOR STANDARD ITEMS

						All taxes for standard items are automatically generated, so we need to get the list of taxes
						selected for the item from the account_items_options table and then add them to the structure
					*/

					// fetch the taxes for the selected item
					$sql_item_tax_obj		= New sql_query;
					$sql_item_tax_obj->string	= "SELECT option_value as taxid FROM account_items_options WHERE itemid='". $data["id"] ."'";
					$sql_item_tax_obj->execute();

					if ($sql_item_tax_obj->num_rows())
					{
						$sql_item_tax_obj->fetch_array();

						foreach ($sql_item_tax_obj->data as $data_item_tax)
						{
							// automatic
							// note: no need to multiple by quantity, since the item amount is already price * quantity
							$tax_structure[ $data_item_tax["taxid"] ]["auto"] += $data["amount"];
						}
					}

				}



			} // end of loop through items

		} // end if items exist


		/*
			Check what taxes that the customer/vendor has enabled - we can only
			create tax items if they are enabled.
		*/
		$enabled_taxes = NULL;

		if ($this->type_invoice == "ap")
		{
			$vendorid		= sql_get_singlevalue("SELECT vendorid as value FROM account_ap WHERE id='". $this->id_invoice ."'");

			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT taxid FROM vendors_taxes WHERE vendorid='$vendorid'";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				$sql_obj->fetch_array();

				foreach ($sql_obj->data as $data)
				{
					$enabled_taxes[] = $data["taxid"];
				}
			}
		}
		else
		{
			$customerid		= sql_get_singlevalue("SELECT customerid as value FROM account_". $this->type_invoice ." WHERE id='". $this->id_invoice ."'");

			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT taxid FROM customers_taxes WHERE customerid='$customerid'";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				$sql_obj->fetch_array();

				foreach ($sql_obj->data as $data)
				{
					$enabled_taxes[] = $data["taxid"];
				}
			}

		}


		/*
			Run through all the tax structure and generate tax items
		*/

		foreach (array_keys($tax_structure) as $taxid)
		{
			// only process taxes which are enabled for the customer/vendor
			if (in_array($taxid, $enabled_taxes))
			{
				// fetch required information about the tax
				$sql_tax_obj		= New sql_query;
				$sql_tax_obj->string	= "SELECT taxrate, chartid FROM account_taxes WHERE id='". $taxid ."' LIMIT 1";
				$sql_tax_obj->execute();
				$sql_tax_obj->fetch_array();


				/*
					Work out total amount of tax
				*/

				$amount = 0;

				// add any manual (aka fixed amount) taxes
				$amount += $tax_structure[ $taxid ]["manual"];

				// any items requiring automatic tax generation?
				if ($tax_structure[ $taxid ]["auto"])
				{
					// calculate taxable amount
					$amount += $tax_structure[ $taxid ]["auto"] * ($sql_tax_obj->data[0]["taxrate"] / 100);
				}


				/*
					Create new tax item
				*/

				$sql_obj		= New sql_query;
				$sql_obj->string	= "INSERT INTO `account_items` "
							."(invoiceid, "
							."invoicetype, "
							."type, "
							."amount, "
							."chartid, "
							."customid, "
							."description"
							.") VALUES ("
							."'". $this->id_invoice ."', "
							."'". $this->type_invoice ."', "
							."'tax', "
							."'". $amount ."', "
							."'". $sql_tax_obj->data[0]["chartid"] ."', "
							."'". $taxid ."', "
							."'')";

				$sql_obj->execute();

			} // end if tax enabled
				
		} // end of loop through tax structure

		return 1;

	} // end of action_update_tax




	/*
		action_update_total

		This function totals up all the items on the invoice and updates the totals on the invoice itself. This
		function should be run once all items have been added/updated on the invoice.
	
		Returns
		0		failure
		1		success
	*/
	function action_update_total()
	{
		log_debug("invoice_items", "Executing action_update_total()");


		// default values
		$amount		= "0";
		$amount_tax	= "0";
		$amount_total	= "0";


		/*
			Total up all the items, and all the tax
		*/

		// calculate totals from the DB
		$amount		= sql_get_singlevalue("SELECT sum(amount) as value FROM `account_items` WHERE invoicetype='". $this->type_invoice ."' AND invoiceid='". $this->id_invoice ."' AND type!='tax' AND type!='payment'");
		$amount_tax	= sql_get_singlevalue("SELECT sum(amount) as value FROM `account_items` WHERE invoicetype='". $this->type_invoice ."' AND invoiceid='". $this->id_invoice ."' AND type='tax'");
		$amount_paid	= sql_get_singlevalue("SELECT sum(amount) as value FROM `account_items` WHERE invoicetype='". $this->type_invoice ."' AND invoiceid='". $this->id_invoice ."' AND type='payment'");

		// final totals
		$amount_total	= $amount + $amount_tax;

		$amount		= sprintf("%0.2f", $amount);
		$amount_tax	= sprintf("%0.2f", $amount_tax);
		$amount_total	= sprintf("%0.2f", $amount_total);
		$amount_paid	= sprintf("%0.2f", $amount_paid);



		/*
			Update the invoice
		*/
		
		$sql_obj = New sql_query;

		if ($this->type_invoice == "quotes")
		{
			$sql_obj->string = "UPDATE `account_". $this->type_invoice ."` SET "
						."amount='". $amount ."', "
						."amount_tax='". $amount_tax ."', "
						."amount_total='". $amount_total ."' "
						."WHERE id='". $this->id_invoice ."'";
		}
		else
		{
			$sql_obj->string = "UPDATE `account_". $this->type_invoice ."` SET "
					."amount='". $amount ."', "
					."amount_tax='". $amount_tax ."', "
					."amount_total='". $amount_total ."', "
					."amount_paid='". $amount_paid ."' "
					."WHERE id='". $this->id_invoice ."'";
		}
		

		if (!$sql_obj->execute())
		{
			log_debug("invoice_items", "A fatal SQL error occured whilst attempting to update invoice totals");
			return 0;
		}


		return 1;

	} // end of action_update_total



	/*
		action_update_ledger
		
		This function updates the ledger based on the data in the account_items table. This function needs to be
		run after making any changes to any item on the invoice, including payments.

		Return Codes
		0		failure
		1		success
	*/
	function action_update_ledger()
	{
		log_debug("invoice_items", "Executing action_update_ledger()");


		// fetch key information from invoice
		$sql_inv_obj		= New sql_query;
		$sql_inv_obj->string	= "SELECT id, dest_account, date_trans FROM account_". $this->type_invoice ." WHERE id='". $this->id_invoice ."' LIMIT 1";
		$sql_inv_obj->execute();
		$sql_inv_obj->fetch_array();


		// remove all the old ledger entries belonging to this invoice
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM `account_trans` WHERE customid='". $this->id_invoice ."' AND (type='". $this->type_invoice ."' || type='". $this->type_invoice ."_pay' || type='". $this->type_invoice ."_tax')";
		$sql_obj->execute();


		/*
			PROCESS NON-PAYMENT ITEMS

			For all normal items, we want to aggregate the totals per chart then add ledger entries
			per-invoice, not per-item.

			Then we create the following in the ledger:

				AR INVOICES
				* A single debit from the AR account
				* A single credit to each different account for the items.

				AP INVOICES
				* A single credit to the AP account
				* A single debit to each different account for the items

			Payment items need to be handled differently - see code further down.
		*/
		
		// add up the total for the AR entry
		$amount = 0;

		// Fetch totals per chart from the items table.
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT chartid, type, SUM(amount) as amount FROM `account_items` WHERE invoicetype='". $this->type_invoice ."' AND invoiceid='". $this->id_invoice ."' AND type!='payment' GROUP BY chartid";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$sql_obj->fetch_array();

			foreach ($sql_obj->data as $item_data)
			{
				// set trans type
				if ($item_data["type"] == "tax")
				{
					$trans_type = $this->type_invoice ."_tax";
				}
				else
				{
					$trans_type = $this->type_invoice;
				}
			
				// create ledger entry for this account
				if ($this->type_invoice == "ap")
				{
					ledger_trans_add("debit", $trans_type, $this->id_invoice, $sql_inv_obj->data[0]["date_trans"], $item_data["chartid"], $item_data["amount"], "", "");
				}
				else
				{
					ledger_trans_add("credit", $trans_type, $this->id_invoice, $sql_inv_obj->data[0]["date_trans"], $item_data["chartid"], $item_data["amount"], "", "");
				}

				// add up the total for the AR entry.
				$amount += $item_data["amount"];
			}

			if ($this->type_invoice == "ap")
			{
				// create credit from AP account
				ledger_trans_add("credit", $this->type_invoice, $this->id_invoice, $sql_inv_obj->data[0]["date_trans"], $sql_inv_obj->data[0]["dest_account"], $amount, "", "");
			}
			else
			{
				// create debit to AR account
				ledger_trans_add("debit", $this->type_invoice, $this->id_invoice, $sql_inv_obj->data[0]["date_trans"], $sql_inv_obj->data[0]["dest_account"], $amount, "", "");
			}
		}




		/*
			PROCESS PAYMENT ITEMS

			Payment entries are different to other items, in that we need to add stand alone
			entries for each payment item, since payments can be made on different dates, so therefore
			can not be aggregated.
		*/

		// run though each payment item
		$sql_item_obj		= New sql_query;
		$sql_item_obj->string	= "SELECT id, chartid, amount, description FROM `account_items` WHERE invoicetype='". $this->type_invoice ."' AND invoiceid='". $this->id_invoice ."' AND type='payment'";
		$sql_item_obj->execute();

		if ($sql_item_obj->num_rows())
		{
			$sql_item_obj->fetch_array();

			foreach ($sql_item_obj->data as $data)
			{
				// fetch information from options
				$sql_option_obj		= New sql_query;
				$sql_option_obj->string	= "SELECT option_name, option_value FROM account_items_options WHERE itemid='". $data["id"] ."'";
				$sql_option_obj->execute();

				$sql_option_obj->fetch_array();

				foreach ($sql_option_obj->data as $option_data)
				{
					if ($option_data["option_name"] == "SOURCE")
						$data["source"] = $option_data["option_value"];

					if ($option_data["option_name"] == "DATE_TRANS")
						$data["date_trans"] = $option_data["option_value"];
				}


				// addslashes to memo & source fields - since we have pulled data from the DB, that data could
				// contains quotation marks or other unacceptable input, so we must process it
				$data["description"]	= addslashes($data["description"]);
				$data["source"]		= addslashes($data["source"]);
				

				if ($this->type_invoice == "ap")
				{
					// we need to credit the destination account for the payment to come from and debit the AP account
					ledger_trans_add("credit", $this->type_invoice ."_pay", $this->id_invoice, $data["date_trans"], $data["chartid"], $data["amount"], $data["source"], $data["description"]);
					ledger_trans_add("debit", $this->type_invoice ."_pay", $this->id_invoice, $data["date_trans"], $sql_inv_obj->data[0]["dest_account"], $data["amount"], $data["source"], $data["description"]);
				}
				else
				{
					// we need to debit the destination account for the payment to go into and credit the AR account
					ledger_trans_add("debit", $this->type_invoice ."_pay", $this->id_invoice, $data["date_trans"], $data["chartid"], $data["amount"], $data["source"], $data["description"]);
					ledger_trans_add("credit", $this->type_invoice ."_pay", $this->id_invoice, $data["date_trans"], $sql_inv_obj->data[0]["dest_account"], $data["amount"], $data["source"], $data["description"]);
				}
			}
		}


		return 1;

	} // end of action_update_ledger





	/*
		action_delete

		Delete an unwanted invoice item.

		Afterwards, the following functions should be run in this order.
		* action_update_tax
		* action_update_totals
		* action_update_ledger

		Returns
		0		failure
		1		success
	*/
	function action_delete()
	{
		log_debug("invoice_items", "Executing action_delete()");
	
		// we may need to fetch the item type, since often this is not passed
		// to the delete function
		if (!$this->type_item)
		{
			$this->type_item = sql_get_singlevalue("SELECT type as value FROM account_items WHERE id='". $this->id_item ."' LIMIT 1");
		}


		/*
			Unlock time_groups if required
		*/
		if ($this->type_item == "time")
		{
			$groupid = sql_get_singlevalue("SELECT option_value as value FROM account_items_options WHERE itemid='". $this->id_item ."' AND option_name='TIMEGROUPID'");
		
			// fetch the current lock status of the time group
			// if it's set to 1, we want to keep that, otherwise if 2, set to 0
			$locked = sql_get_singlevalue("SELECT locked as value FROM time_groups WHERE id='$groupid'");

			if ($locked == 2)
			{
				$locked = 0;
			}

			$sql_obj		= New sql_query;
			$sql_obj->string	= "UPDATE time_groups SET invoiceid='0', invoiceitemid='0', locked='$locked' WHERE id='$groupid'";
			$sql_obj->execute();
		}
	
	
		/*
			Delete the invoice item options
		*/

		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM account_items_options WHERE itemid='". $this->id_item ."'";
		$sql_obj->execute();

	
		/*
			Delete the invoice item
		*/

		// delete item
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM account_items WHERE id='". $this->id_item ."' AND invoicetype='". $this->type_invoice ."'";
		
		if (!$sql_obj->execute())
		{
			log_write("error", "invoice_items", "Unable to delete invoice item ". $this->id_item ." from DB");
			return 0;
		}



		/*
			Update Journal
		*/

		journal_quickadd_event("account_". $this->type_invoice ."", $this->id_invoice, "Item successfully deleted");

		return 1;
	}

	
	
} // END OF INVOICE_ITEMS CLASS




?>
