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
	$terms = sql_get_singlevalue("SELECT value FROM config WHERE name='ACCOUNTS_TERMS_DAYS' LIMIT 1");

	// break up the date, and reconfigure
	$date_array	= explode("-", $date);
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

        if($type=="project")
        {
            print "<br>";
            return;
        }
        
	// fetch invoice information
	$sql_obj = New sql_query;
	$sql_obj->prepare_sql_settable("account_$type");

	if ($type == "ar")
	{
		$sql_obj->prepare_sql_addfield("date_sent");
		$sql_obj->prepare_sql_addfield("sentmethod");
                $sql_obj->prepare_sql_addfield("cancelled");
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
            
            if(isset($sql_obj->data[0]["cancelled"]) && $sql_obj->data[0]["cancelled"]=='1')
            {
			print "<table width=\"100%\" class=\"table_highlight_important\">";
			print "<tr>";
				print "<td>";
				print "<b>Invoice ". $sql_obj->data[0]["code_invoice"] ." is cancelled.</b>";
				print "<p>This invoice cannot be modified.</p>";
				print "</td>";
			print "</tr>";
			print "</table>";
            }
            else
            {

		// check for presence of invoice items
		$sql_item_obj		= New sql_query;
		$sql_item_obj->string	= "SELECT id FROM account_items WHERE invoicetype='$type' AND invoiceid='$id' LIMIT 1";
		$sql_item_obj->execute();

		if (!$sql_item_obj->num_rows())
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
				print "<table width=\"100%\" class=\"table_highlight_open\">";
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
						print "<td>". format_money($sql_obj->data[0]["amount_total"]) ."</td>";
					print "</tr>";
					
					print "<tr>";
						print "<td>Total Paid:</td>";
						print "<td>". format_money($sql_obj->data[0]["amount_paid"]) ."</td>";
					print "</tr>";


					$amount_due = $sql_obj->data[0]["amount_total"] - $sql_obj->data[0]["amount_paid"];

					print "<tr>";
						print "<td>Amount Due:</td>";
						print "<td>". format_money($amount_due) ."</td>";
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
		
	var $invoice_fields;		// array for storage of all invoice fields with associated data
	
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

		// check lock status
		if ($return = $this->check_lock())
		{
			return $return;
		}

		// check for any credits against this invoice.
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_". $this->type ."_credit WHERE invoiceid='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			// unable to delete, credit notes against this invoice
			log_write("error", "process", "You are unable to delete this invoice as there are credit(s) assigned against it - first remove the credits");

			return 1;
		}

		return 0;

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
		load_data_export

		Loads the invoice data for exporting for PDF or email or other purposes.

		Return Codes
		0	failure
		1	success
	*/
	function load_data_export()
	{
		log_debug("invoice", "Executing load_data_export()");


		/*
			Customer Data
		*/
		

		// fetch customer data
		$sql_customer_obj		= New sql_query;
		$sql_customer_obj->string	= "SELECT code_customer, name_customer, tax_number, address1_street, address1_city, address1_state, address1_country, address1_zipcode FROM customers WHERE id='". $this->data["customerid"] ."' LIMIT 1";
		$sql_customer_obj->execute(); 
		$sql_customer_obj->fetch_array(); 

		$obj_sql_contact		= New sql_query;
		$obj_sql_contact->string	= "SELECT id, contact FROM customer_contacts WHERE customer_id = '". $this->data["customerid"] ."' AND role = 'accounts'";
		$obj_sql_contact->execute();
		$obj_sql_contact->fetch_array();
		


		// customer fields
		$this->invoice_fields["code_customer"] = $sql_customer_obj->data[0]["code_customer"]; 
		$this->invoice_fields["customer_name"] = $sql_customer_obj->data[0]["name_customer"]; 

		$this->invoice_fields["customer_contact"]	= $obj_sql_contact->data[0]["contact"];
		$this->invoice_fields["customer_contact_email"]	= sql_get_singlevalue("SELECT detail AS value FROM customer_contact_records WHERE contact_id = '" .$obj_sql_contact->data[0]["id"]. "' AND type = 'email' LIMIT 1");
		$this->invoice_fields["customer_contact_phone"]	= sql_get_singlevalue("SELECT detail AS value FROM customer_contact_records WHERE contact_id = '" .$obj_sql_contact->data[0]["id"]. "' AND type = 'phone' LIMIT 1");
		$this->invoice_fields["customer_contact_fax"]	= sql_get_singlevalue("SELECT detail AS value FROM customer_contact_records WHERE contact_id = '" .$obj_sql_contact->data[0]["id"]. "' AND type = 'fax' LIMIT 1");

		$this->invoice_fields["customer_address1_street"] = $sql_customer_obj->data[0]["address1_street"]; 
		$this->invoice_fields["customer_address1_city"] = $sql_customer_obj->data[0]["address1_city"]; 
		$this->invoice_fields["customer_address1_state"] = $sql_customer_obj->data[0]["address1_state"]; 
		$this->invoice_fields["customer_address1_country"] = $sql_customer_obj->data[0]["address1_country"]; 

		if ($sql_customer_obj->data[0]["address1_zipcode"] == 0)
		{
			$sql_customer_obj->data[0]["address1_zipcode"] = "";
		}
		
		$this->invoice_fields["customer_tax_number"] = $sql_customer_obj->data[0]["tax_number"]; 
		$this->invoice_fields["customer_address1_zipcode"] = $sql_customer_obj->data[0]["address1_zipcode"]; 



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
		$this->invoice_fields["company_name"] = $data_company["company_name"]; 
		
		$this->invoice_fields["company_contact_email"] = $data_company["company_contact_email"]; 
		$this->invoice_fields["company_contact_phone"] = $data_company["company_contact_phone"]; 
		$this->invoice_fields["company_contact_fax"] = $data_company["company_contact_fax"]; 
		
		$this->invoice_fields["company_address1_street"] = $data_company["company_address1_street"]; 
		$this->invoice_fields["company_address1_city"] = $data_company["company_address1_city"]; 
		$this->invoice_fields["company_address1_state"] = $data_company["company_address1_state"]; 
		$this->invoice_fields["company_address1_country"] = $data_company["company_address1_country"]; 
		$this->invoice_fields["company_address1_zipcode"] = $data_company["company_address1_zipcode"]; 
		
		$this->invoice_fields["company_address2_street"] = $data_company["company_address2_street"]; 
		$this->invoice_fields["company_address2_city"] = $data_company["company_address2_city"]; 
		$this->invoice_fields["company_address2_state"] = $data_company["company_address2_state"]; 
		$this->invoice_fields["company_address2_country"] = $data_company["company_address2_country"]; 
		$this->invoice_fields["company_address2_zipcode"] = $data_company["company_address2_zipcode"]; 

		$this->invoice_fields["company_reg_number"] = $data_company["company_reg_number"]; 
		$this->invoice_fields["company_tax_number"] = $data_company["company_tax_number"];
		
		if ($this->type == "ar")
		{
			$this->invoice_fields["company_payment_details"] = $data_company["company_payment_details"]; 
		}

		if ($this->type == "quotes")
		{
			$this->invoice_fields["notes"] = $this->data["notes"];
			
			if($this->data["terms_of_business"]=="terms_consumer")
			{
				$this->invoice_fields["terms_of_business"] = $data_company["company_b2c_terms"];
			}
			else if($this->data["terms_of_business"]=="terms_business")
			{
				$this->invoice_fields["terms_of_business"] = $data_company["company_b2b_terms"];
			}
		}

		/*
			Invoice Data (exc items/taxes)
		*/
		if ($this->type == "ar")
		{
			$this->invoice_fields["code_invoice"] = $this->data["code_invoice"]; 
			$this->invoice_fields["code_ordernumber"] = $this->data["code_ordernumber"]; 
			$this->invoice_fields["code_ponumber"] = $this->data["code_ponumber"]; 
			$this->invoice_fields["date_due"] = time_format_humandate($this->data["date_due"]);
                        if($this->data["cancelled"]=='1')
                        {
                            $this->invoice_fields["invoice_cancelled"]=$this->data["cancelled"];
                        }
		}
		else
		{
			$this->invoice_fields["code_quote"] = $this->data["code_quote"]; 
			$this->invoice_fields["date_validtill"] = time_format_humandate($this->data["date_validtill"]);  
		}
		
		if(!isset($this->data["amount_paid"]))
		{
			$this->data["amount_paid"] = 0;
		}
		
		$this->invoice_fields["date_trans"] = time_format_humandate($this->data["date_trans"]);  
		$this->invoice_fields["amount"] = format_money($this->data["amount"]);  
		$this->invoice_fields["amount_total"] = format_money($this->data["amount_total"] - $this->data["amount_paid"]);  
		$this->invoice_fields["amount_currency"] = sql_get_singlevalue("SELECT value FROM config WHERE name='CURRENCY_DEFAULT_NAME'") ; 

		if ($this->data["amount_paid"] > 0)
		{
			$this->invoice_fields["amount_paid"] = format_money($this->data["amount_paid"]);    
		}


		return 1;
	}

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

		if (empty($this->data["code_invoice"]))
		{
			$this->prepare_code_invoice();
		}

		if (empty($this->data["date_trans"]))
		{
			$this->data["date_trans"] = date("Y-m-d");
		}
		
		if (empty($this->data["date_due"]))
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
		prepare_date_shift

		Generates an invoice date that follows the ACCOUNTS_SERVICES_DATESHIFT options - this will change the date back to the
		last date of the previous month if the date is within the range configured.

		The use for this feature is to date invoices for the end of the month, rather than the first of the subsequent month, to
		address the issue of some companies refusing to pay until the following month of the invoice date.

		Returns
		0	failure
		1	success
	*/
	function prepare_date_shift()
	{
		log_write("debug", "invoice", "Executing prepare_date_shift()");


		if (!$this->data["date_trans"])
		{
			$this->data["date_trans"] = date("Y-m-d");
		}


		// fetch the day of the month, check against configuration options
		if (time_calculate_daynum($this->data["date_trans"]) < $GLOBALS["config"]["ACCOUNTS_SERVICES_DATESHIFT"])
		{
			// calculate previous month date
			$newdate = sql_get_singlevalue("SELECT DATE_SUB('". $this->data["date_trans"] ."', INTERVAL 1 MONTH ) as value");

			// calulate last date of that month
			$newdate = time_calculate_monthdate_last($newdate);

			log_write("debug", "invoice", "Performing date shift from ". $this->data["date_trans"] ."  to $newdate");

			$this->data["date_trans"] = $newdate;
		}
		else
		{
			log_write("debug", "invoice", "No date shift required, using current date");
		}

		return 1;

	} // end of prepare_date_shift



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


		// make sure a dest account is seelcted
		if (!$this->data["dest_account"])
		{
			if ($this->data["dest_account_orig"])
			{
				// no dest account supplied, but one originally set - use
				$this->data["dest_account"] = $this->data["dest_account_orig"];
			}
			else
			{
				/*
					When invoices are generated automatically, such as part of the accounts invoicing or service invoicing
					work, a destination AR or AP account needs to be selected.

					Typically we handle this in the UI, by default selecting the first account in the list and then if more exist,
					allowing the user to select the desired.

					For automated invoices, we just select the first match - this will meet the needs of most users who
					will only ever have one AR and one AP account, 


					TODO: A better solution might be to select the first one by default, unless a configuration option has been
					set detailing which AR account to use.
				*/
					

				// fetch the ID of the summary type label
				$menuid = sql_get_singlevalue("SELECT id as value FROM account_chart_menu WHERE value='". $this->type ."_summary_account' LIMIT 1");

				// fetch the top AR/AP summary account
				$sql_query	= "SELECT "
						."account_charts.id as value "
						."FROM account_charts "
						."LEFT JOIN account_charts_menus ON account_charts_menus.chartid = account_charts.id "
						."WHERE account_charts_menus.menuid='$menuid' "
						."LIMIT 1";
								
				$this->data["dest_account"]	= sql_get_singlevalue($sql_query);

				if (!$this->data["dest_account"])
				{
					log_write("error", "services_invoicegen", "No AR/AP summary account could be found, it is not possible to create an invoice without one.");

					return 0;
				}
			}
		}


		/*
			Start SQL Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();



		/*
			Update the invoice details
		*/
			
		if ($this->type == "ap")
		{
			@$sql_obj->string = "UPDATE `account_". $this->type ."` SET "
						."vendorid='". $this->data["vendorid"] ."', "
						."employeeid='". $this->data["employeeid"] ."', "
						."notes='". $this->data["notes"] ."', "
						."code_invoice='". $this->data["code_invoice"] ."', "
						."code_ordernumber='". $this->data["code_ordernumber"] ."', "
						."code_ponumber='". $this->data["code_ponumber"] ."', "
						."date_trans='". $this->data["date_trans"] ."', "
						."date_due='". $this->data["date_due"] ."', "
						."dest_account='". $this->data["dest_account"] ."' "
						."WHERE id='". $this->id ."' LIMIT 1";
		}
		else
		{
			@$sql_obj->string = "UPDATE `account_". $this->type ."` SET "
						."customerid='". $this->data["customerid"] ."', "
						."employeeid='". $this->data["employeeid"] ."', "
						."notes='". $this->data["notes"] ."', "
						."code_invoice='". $this->data["code_invoice"] ."', "
						."code_ordernumber='". $this->data["code_ordernumber"] ."', "
						."code_ponumber='". $this->data["code_ponumber"] ."', "
						."date_trans='". $this->data["date_trans"] ."', "
						."date_due='". $this->data["date_due"] ."', "
						."dest_account='". $this->data["dest_account"] ."' "
						."WHERE id='". $this->id ."' LIMIT 1";
		}
		
		if (!$sql_obj->execute())
		{
			$sql_obj->trans_rollback();

			log_write("error", "invoice", "Unable to update database with new invoice information. No changes have been made.");

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


		/*
			Commit
		*/
		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "invoice", "An error occured whilst attempting to update invoice. No changes have been made.");

			return 0;
		}
		else
		{
			$sql_obj->trans_commit();

			return 1;
		}
		
		
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



		/*
			Start SQL Transaction
		*/

		$sql_obj = New sql_query;
		$sql_obj->trans_begin();



		/*
			Delete Invoice
		*/

                if($this->type=="ar")
                {
                        if(sql_get_singlevalue("SELECT cancelled as value FROM account_ar WHERE id='".$this->id."'")=='0')
                        {
                                $rollback=1;
                        }
                        else
                        {
                                $rollback=0;
                        }
                }
                else
                {
                    $rollback=1;
                }
                
                if($GLOBALS["config"]["ACCOUNTS_CANCEL_DELETE"]=="1" && $this->type=="ar")
                {
                        $sql_obj->string	= "UPDATE account_". $this->type ." SET cancelled = 1 WHERE id='". $this->id ."' LIMIT 1";
                        $sql_obj->execute();
                }
                else
                {
                        $sql_obj->string	= "DELETE FROM account_". $this->type ." WHERE id='". $this->id ."' LIMIT 1";
                        $sql_obj->execute();
                }

		/*
			Delete Invoice Items
		
			We do this by using the invoice_items::action_delete() function, since there are number of complex
			steps when deleting certain invoice items (such as time items)
		*/

		$sql_items_obj		= New sql_query;
		$sql_items_obj->string	= "SELECT id FROM account_items WHERE invoicetype='". $this->type ."' AND invoiceid='". $this->id ."'";
		$sql_items_obj->execute();

		if ($sql_items_obj->num_rows())
		{
			$sql_items_obj->fetch_array();

			foreach ($sql_items_obj->data as $data_sql)
			{
				// delete each invoice one-at-a-time.
				$obj_invoice_item			= New invoice_items;

				$obj_invoice_item->type_invoice		= $this->type;
				$obj_invoice_item->id_invoice		= $this->id;
                                $obj_invoice_item->rollback             = $rollback;
                                $obj_invoice_item->deletecancel         = $GLOBALS["config"]["ACCOUNTS_CANCEL_DELETE"];
				$obj_invoice_item->id_item		= $data_sql["id"];
				$obj_invoice_item->action_delete();

				unset($obj_invoice_item);
			}
		}



		/*
			Delete Journal
		*/
                if($GLOBALS["config"]["ACCOUNTS_CANCEL_DELETE"]=="1" && $this->type=="ar")
                {
                    journal_quickadd_event("account_".$this->type."", $this->id, "Invoice cancelled");
                }
                else
                {
                    journal_delete_entire("account_". $this->type ."", $this->id);
                }


		/*
			Delete transactions from ledger
			
			(Most transactions are deleted by the item deletion code, but tax, pay and AR/AP
			 ledger transactions need to be removed manually)
		*/

		$sql_obj->string	= "DELETE FROM account_trans WHERE (type='". $this->type ."' || type='". $this->type ."_tax' || type='". $this->type ."_pay') AND customid='". $this->id ."'";
		$sql_obj->execute();



		/*
			Commit
		*/

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "invoice", "An error occured whilst deleting the invoice. No changes have been made.");

			return 0;
		}
		else
		{
			$sql_obj->trans_commit();
			
			return 1;
		}
		
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
		log_debug("invoice", "Executing generate_pdf()");
	

		// load data if required
		if (!is_array($this->invoice_fields))
		{
			$this->load_data();	
			$this->load_data_export();
		}

		
		
		// start the PDF object
		//
		// note: the & allows decontructors to operate 
		//       Unfortunatly this trick is now deprecated with PHP 5.3.x and creates unsilencable errors ~JC 20100110
		//

		// get template filename based on currently selected options
		$template_data = sql_get_singlerow("SELECT `template_type`, `template_file` FROM templates WHERE template_type IN('". $this->type ."_invoice_tex', '". $this->type ."_invoice_htmltopdf') AND active='1' LIMIT 1");
		//exit("<pre>".print_r($template_data, true)."</pre>");
		switch($template_data['template_type']) 
		{
			case $this->type .'_invoice_htmltopdf':
				$this->obj_pdf = New template_engine_htmltopdf;
				$template_file = $template_data['template_file']."/index.html";
				
				if (is_dir("../../{$template_data['template_file']}"))
				{
					$this->obj_pdf->set_template_directory("../../{$template_data['template_file']}");
				}
				else
				{
					$this->obj_pdf->set_template_directory("../{$template_data['template_file']}");
				}			
			break;
			
			case $this->type .'_invoice_tex':
			default:
				$this->obj_pdf = New template_engine_latex;
				$template_file = $template_data['template_file'].".tex";
			break;
		
		}
		
		
		if (!$template_file)
		{
			// fall back to old version
			//
			// TODO: we can remove this fallback code once the new templating system is fully implemented, this is to
			// just make everything work whilst stuff like quote templates are being added.
			//
			$template_file = "templates/latex/". $this->type ."_invoice";
		}

		// load template
		if (file_exists("../../$template_file"))
		{
			$this->obj_pdf->prepare_load_template("../../$template_file"); 
		}
		elseif (file_exists("../$template_file"))
		{
			$this->obj_pdf->prepare_load_template("../$template_file");
		}
		else
		{
			// if we can't find the template file, then something is rather wrong.
			log_write("error", "invoice", "Unable to find template file $template_file, currently running in directory ". getcwd() .", fatal error.");
			return 0;
		}

		
		/*
			Company Data
		*/
		
		// company logo
		$this->obj_pdf->prepare_add_file("company_logo", "png", "COMPANY_LOGO", 0);
		
		
	

		/*
			Previous Activity

			Some invoice PDFs include a "previous activity" statement function displaying past account activity - we only display unpaid
			invoices.
		*/
		$structure_pastactivity		= array();
		$structure_pastactivity[0]	= array(); // reserved for past balance

		$amount_outstanding		= sql_get_singlevalue("SELECT SUM(amount_total - amount_paid) as value FROM account_ar WHERE customerid='". $this->data["customerid"] ."' AND id!='". $this->id ."' AND date_trans <= '". $this->data["date_trans"] ."'");
		$amount_outstanding_past	= $amount_outstanding;
		
		$sql_past_obj			= New sql_query;
		$sql_past_obj->string		= "SELECT id, code_invoice, date_trans, amount_total, amount_paid, date_trans FROM account_ar WHERE customerid='". $this->data["customerid"] ."' AND id!='". $this->id ."' AND date_trans <= '". $this->data["date_trans"] ."' ORDER BY date_trans DESC LIMIT 2";
		$sql_past_obj->execute();

		if ($sql_past_obj->num_rows())
		{
			$sql_past_obj->fetch_array();

			foreach ($sql_past_obj->data as $data_row)
			{
				// invoice
				$itemdata			= array();

				$itemdata["item_date_raw"]	= time_date_to_timestamp($data_row["date_trans"]) .".". $data_row["id"] ."00";	// used to sort items
				$itemdata["item_date"]		= time_format_humandate($data_row["date_trans"]);
				$itemdata["item_details"]	= "Invoice ". $data_row["code_invoice"] ."";
				$itemdata["item_amount"]	= format_money($data_row["amount_total"]);

				$structure_pastactivity[]	= $itemdata;


				// payments (if any)
				if ($data_row["amount_paid"] > 0)
				{
					$sql_pay_obj		= New sql_query;
					$sql_pay_obj->string	= "SELECT id, amount FROM account_items WHERE invoiceid='". $data_row["id"] ."' AND type='payment'";
					$sql_pay_obj->execute();
					$sql_pay_obj->fetch_array();

					foreach ($sql_pay_obj->data as $data_pay)
					{
						// update balance
						$amount_outstanding_past = $amount_outstanding_past + $data_pay["amount"];

						// source & date
						$pay_date	= sql_get_singlevalue("SELECT option_value as value FROM account_items_options WHERE itemid='". $data_pay["id"] ."' AND option_name='DATE_TRANS' LIMIT 1");
						$pay_credit	= sql_get_singlevalue("SELECT option_value as value FROM account_items_options WHERE itemid='". $data_pay["id"] ."' AND option_name='CREDIT' LIMIT 1");

						// add payment item
						$itemdata			= array();
						$itemdata["item_date_raw"]	= time_date_to_timestamp($pay_date) .".". $data_row["id"] ."01"; // used to sort items
						$itemdata["item_date"]		= time_format_humandate($pay_date);
						
						if ($pay_credit)
						{
							$itemdata["item_details"]	= "Credit applied to invoice ". $data_row["code_invoice"] ."";
						}
						else
						{
							$itemdata["item_details"]	= "Payment against invoice ". $data_row["code_invoice"] ."";
						}

						$itemdata["item_amount"]	= "-". format_money($data_pay["amount"]);

						$structure_pastactivity[]	= $itemdata;
					}

					unset($sql_pay_obj);
				}

				$amount_outstanding_past	= $amount_outstanding_past - $data_row["amount_total"];
			}

			// sort by date, to correct payment & invoice ordering

			if (!function_exists("cmp_date"))
			{
				function cmp_date($a, $b)
				{
					if ($a["item_date_raw"] == $b["item_date_raw"]) {
						return 0;
					}
				
					return ($a["item_date_raw"] < $b["item_date_raw"]) ? -1 : 1;
				}
			}

			usort($structure_pastactivity, "cmp_date");

			// add previous balance item
			$structure_pastactivity[0]["item_date"]		= "Previous Balance";
			$structure_pastactivity[0]["item_details"]	= "";
			$structure_pastactivity[0]["item_amount"]	= format_money($amount_outstanding_past);
			
		}
		else
		{
			$itemdata = array();

			$itemdata["item_date"]		= time_format_humandate(date("Y-m-d"));;
			$itemdata["item_details"]	= "No Past Activity";
			$itemdata["item_amount"]	= "";

			$structure_pastactivity[0]	= $itemdata;

			$amount_outstanding		= "0.00";
		}


		$this->obj_pdf->prepare_add_array("previous_items", $structure_pastactivity);
		$this->obj_pdf->prepare_add_field("amount_outstanding", format_money($amount_outstanding));

		unset($structure_pastactivity);
		unset($sql_past_obj);



		/*
			Add general invoice details from load_data_export	
		*/
		$this->invoice_fields["amount_total"]		= format_money($this->data["amount_total"] - $this->data["amount_paid"]);  
		$this->invoice_fields["amount_total_final"]	= format_money(($this->data["amount_total"] - $this->data["amount_paid"]) + $amount_outstanding);

		foreach($this->invoice_fields as $invoice_field_key => $invoice_field_value) {
			$this->obj_pdf->prepare_add_field($invoice_field_key, $invoice_field_value);
		}
	

		
		/*
			Invoice Items
			(excluding tax items - these need to be processed in a different way)
		*/

		// fetch invoice items
		$sql_items_obj			= New sql_query;
		$sql_items_obj->string		= "SELECT "
							."id, type, chartid, customid, quantity, units, amount, price, description "
							."FROM account_items "
							."WHERE invoiceid='". $this->id ."' "
							."AND invoicetype='". $this->type ."' "
							."AND type!='tax' "
							."AND type!='payment' "
							."ORDER BY type, customid, chartid, description";
		
		
		$sql_items_obj->execute();
		$sql_items_obj->fetch_array();


		$structure_invoiceitems		= array();
		$structure_group_summary	= array();

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


					/*
						Fetch discount (if any)
					*/

					$itemdata["discount"] = sql_get_singlevalue("SELECT option_value as value FROM account_items_options WHERE itemid='". $itemdata["id"] ."' AND option_name='DISCOUNT'");


					/*
						Calculate Amount

						(Amount field already has discount removed, but we can't use this for export, since we want the line item to be the full
						 amount, with an additional line item for the discount)
					*/

					$itemdata["amount"] = $itemdata["price"] * $itemdata["quantity"];

					$sql_obj		= New sql_query;
					$sql_obj->string	= "SELECT product_groups.group_name "
										." FROM products "
										." LEFT JOIN product_groups "
										." ON product_groups.id = products.id_product_group "
										." WHERE products.id = '". $itemdata["customid"] ."' "
										." LIMIT 1";

					$sql_obj->execute();
					$sql_obj->fetch_array();
					
					if($sql_obj->data[0]["group_name"] != null) {
						$structure["group"]	= $sql_obj->data[0]["group_name"];
					} else {
						$structure["group"]	= lang_trans("group_products");
					}
				break;


				case "time":
					/*
						Fetch time group ID
					*/

					$groupid = sql_get_singlevalue("SELECT option_value as value FROM account_items_options WHERE itemid='". $itemdata["id"] ."' AND option_name='TIMEGROUPID'");

					$structure["info"] = sql_get_singlevalue("SELECT CONCAT_WS(' -- ', projects.code_project, time_groups.name_group) as value FROM time_groups LEFT JOIN projects ON projects.id = time_groups.projectid WHERE time_groups.id='$groupid' LIMIT 1");


					/*
						Fetch discount (if any)
					*/

					$itemdata["discount"] = sql_get_singlevalue("SELECT option_value as value FROM account_items_options WHERE itemid='". $itemdata["id"] ."' AND option_name='DISCOUNT'");


					/*
						Calculate Amount

						(Amount field already has discount removed, but we can't use this for export, since we want the line item to be the full
						 amount, with an additional line item for the discount)
					*/

					$itemdata["amount"]	= $itemdata["price"] * $itemdata["quantity"];


					$structure["group"]	= lang_trans("group_time");

				break;


				case "service":
				case "service_usage":

					/*
						Fetch Service Name
					*/

					$sql_obj		= New sql_query;
					$sql_obj->string	= "SELECT name_service FROM services WHERE id='". $itemdata["customid"] ."' LIMIT 1";
					$sql_obj->execute();

					$sql_obj->fetch_array();
					
					$structure["info"]	= $sql_obj->data[0]["name_service"];

					unset($sql_obj);



					/*
						Fetch discount (if any)
					*/

					$itemdata["discount"] = sql_get_singlevalue("SELECT option_value as value FROM account_items_options WHERE itemid='". $itemdata["id"] ."' AND option_name='DISCOUNT'");



					/*
						Calculate Amount

						(Amount field already has discount removed, but we can't use this for export, since we want the line item to be the full
						 amount, with an additional line item for the discount)
					*/

					$itemdata["amount"] 	= $itemdata["price"] * $itemdata["quantity"];


					/*
						Fetch CDR group if any
					*/

					$itemdata["CDR_BILLGROUP"] = sql_get_singlevalue("SELECT option_value as value FROM account_items_options WHERE itemid='". $itemdata["id"] ."' AND option_name='CDR_BILLGROUP'");


					/*
						Set the service group

						This is used for layout and titling purposes on the invoice - there are several options, we need to fetch the service group depending
						on whether the service item is a plan or usage item and specific service types might have other group conditions, eg CDR_BILLGROUP.
					*/
					$sql_obj			= New sql_query;

					if ($itemdata["type"] == "service_usage")
					{
						if ($itemdata["CDR_BILLGROUP"])
						{
							$sql_obj->string	= "SELECT CONCAT_WS(' ', billgroup_name, 'Call Charges') as group_name FROM cdr_rate_billgroups WHERE id='". $itemdata["CDR_BILLGROUP"] ."' LIMIT 1";
						}
						else
						{
							$sql_obj->string	= "SELECT service_groups.group_name FROM services LEFT JOIN service_groups ON service_groups.id = services.id_service_group_usage WHERE services.id = '". $itemdata["customid"] ."' LIMIT 1";
						}
					}
					else
					{
						$sql_obj->string	= "SELECT service_groups.group_name FROM services LEFT JOIN service_groups ON service_groups.id = services.id_service_group WHERE services.id = '". $itemdata["customid"] ."' LIMIT 1";
					}

					$sql_obj->execute();
					$sql_obj->fetch_array();
					
					$structure["group"]	= $sql_obj->data[0]["group_name"];

					unset($sql_obj);
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

					$itemdata["price"]	= NULL;

					$structure["group"]	= lang_trans("group_other");
					$structure["group_num"]	= "1";

					unset($sql_obj);
				break;
			}

			// define group summary values
			if (!isset($structure_group_summary[ $structure["group"] ]))
			{
				$structure_group_summary[ $structure["group"] ] = 0;
			}
			
			$structure_group_summary[ $structure["group"] ] += $itemdata["amount"];


			// finalise item
			$structure["description"]	= trim($itemdata["description"]);
			$structure["units"]		= $itemdata["units"];

			if ($itemdata["price"])
			{
				$structure["price"]	= format_money($itemdata["price"]);
			}
			else
			{
				$structure["price"]	= "";
			}

			$structure["amount"]		= format_money($itemdata["amount"]);

			$structure_invoiceitems[] = $structure;


			// if a discount exists, then we add an additional item row for the discount
			if (!empty($itemdata["discount"]))
			{
				$structure["description"]	= "Discount of ".  $itemdata["discount"] ."%";
				$structure["quantity"]		= "";
				$structure["units"]		= "";
				$structure["price"]		= "";

				// work out the discount amount to remove
				$discount_calc	= $itemdata["discount"] / 100;
				$discount_calc	= $itemdata["amount"] * $discount_calc;

				$structure["amount"]		= "-". format_money($discount_calc);

				// track for summary report
				if (!isset($structure_group_summary[ "group_discount" ]))
				{
					$structure_group_summary[ "group_discount" ] = $discount_calc;
				}
				else
				{
					$structure_group_summary[ "group_discount" ] += $discount_calc;
				}

				// add extra line item
				$structure_invoiceitems[] = $structure;
			}
		}
		
		foreach($structure_invoiceitems as $invoice_item)
		{
			$invoice_items_by_group[$invoice_item['group']][] = $invoice_item;
		}

		ksort($invoice_items_by_group);

		if(count($invoice_items_by_group) > 1)
		{
			$structure_invoiceitems = array();
			foreach($invoice_items_by_group as $invoice_item_set)
			{
				$structure_invoiceitems = array_merge($structure_invoiceitems, $invoice_item_set);
			}
		
		}
		
		//exit("<pre>".print_r($structure_invoiceitems,true)."</pre>");
		
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
				$structure["amount"]		= format_money($taxdata["amount"]);
				$structure_taxitems[] = $structure;
			}
		}
		else
		{
			$structure_taxitems=array();
		}
	
		$this->obj_pdf->prepare_add_array("taxes", $structure_taxitems);



		/*
			Payment Items
		*/

		// fetch payment items
		$sql_payment_obj			= New sql_query;
		$sql_payment_obj->string		= "SELECT id, amount, description FROM account_items WHERE invoiceid='". $this->id ."' AND invoicetype='". $this->type ."' AND type='payment'";
		$sql_payment_obj->execute();
		
		
		
		$structure_payments = array();

		if ($sql_payment_obj->num_rows())
		{
			$sql_payment_obj->fetch_array();

			foreach ($sql_payment_obj->data as $itemdata)
			{
				$structure = array();
			
				if (sql_get_singlevalue("SELECT option_value as value FROM account_items_options WHERE itemid='". $itemdata["id"] ."' AND option_name='CREDIT' LIMIT 1"))
				{
					$structure["label"]	= "Credit Applied";
				}
				else
				{
					$structure["label"]	= "Payment";
				}

				$structure["amount"]		= format_money($itemdata["amount"]);
				$structure["date"]		= time_format_humandate( sql_get_singlevalue("SELECT option_value as value FROM account_items_options WHERE option_name='DATE_TRANS' AND itemid='". $itemdata["id"] ."' LIMIT 1") );

				$structure_payments[] = $structure;
			}
		}
	
		$this->obj_pdf->prepare_add_array("invoice_payments", $structure_payments);


		
		/*
			Group Summaries

			Some invoice templates have a summary header page with totals for each group type. Users can
			then read through for full item listings and details.
		*/
		
		$stucture_group_summary_final = array();


		// add all non-discount groups
		foreach (array_keys($structure_group_summary) as $group_name)
		{
			if ($group_name != 'group_discount')
			{
				$structure = array();
	
				$structure["group_name"]		= $group_name;
				$structure["group_amount"]		= format_money($structure_group_summary[ $group_name ]);
	
				$structure_group_summary_final[]	= $structure;
			}
		}


		// add discount group last
		if (isset($structure_group_summary["group_discount"]) && ($structure_group_summary["group_discount"] > 0))
		{
			$structure = array();

			$structure["group_name"]		= lang_trans('group_discount');
			$structure["group_amount"]		= "-". format_money($structure_group_summary['group_discount']);

			$structure_group_summary_final[]	= $structure;
		}

		$this->obj_pdf->prepare_add_array("summary_items", $structure_group_summary_final);


		// depending on the billing options we may adjust template display, ensure default is set first

		$this->obj_pdf->prepare_add_field('billing_default', 1);	
		$billing_option		= sql_get_singlevalue("SELECT billing_method AS value FROM customers WHERE id='". $this->data["customerid"] ."'");

		log_write("debug", "inc_invoice", "Billing option for customer is: " . $billing_option);

		switch($billing_option) {
			case 'direct debit':
				$this->obj_pdf->prepare_add_field('billing_direct_debit', 1);
				$this->obj_pdf->prepare_add_field('billing_default', '');
			break;
			case 'manual':
			default:
				$this->obj_pdf->prepare_add_field('billing_default', 1);
			break;
		}

		/*
			Output PDF
		*/

		// perform string escaping for latex
		$this->obj_pdf->prepare_escape_fields(array("terms_of_business"));
		
		// fillter template data
		$this->obj_pdf->fillter_template_data();
		
		// fill template
		$this->obj_pdf->prepare_filltemplate();


		/*
			Debugging Functions

			Debugging invoice generation can be tricky, especially when making large number of
			development changes. These functions need to be uncommented in code, future releases
			should make them a checkable option.
		*/

		// display invoice in browser, suitable for HTML-based invoices only
		//foreach ($this->obj_pdf->processed as $line)
		//{
		//	$line = str_replace('(tmp_filename)', "../../". $template_data['template_file'] ."/", $line);
		//	print $line;
		//}

		// output raw HTML
		//print "<pre>";
		//print_r($this->obj_pdf->processed);
		//print "</pre>";

		//die("Terminated Generation");
		

		// generate PDF output
		$this->obj_pdf->generate_pdf();

	} // end of generate_pdf



	/*
		generate_email

		Generates all the fields needed for an invoice email - this function fetches the text template, fills
		in all the details and returns a structured array.

		This function is used by the UI and the backend.

		Returns
		0		Failure
		array		Email Data
	*/

	function generate_email()
	{
		log_write("debug", "inc_invoice", "Executing generate_email()");


		// load data if required
		if (!is_array($this->invoice_fields))
		{
			$this->load_data();	
			$this->load_data_export();
		}


		/*
			restructure the invoice data into a form we can handle
		*/

		$invoice_data_parts['keys']	= array_keys($this->invoice_fields);
		$invoice_data_parts['values']	= array_values($this->invoice_fields);
		
		foreach($invoice_data_parts['keys'] as $index => $key)
		{
			$invoice_data_parts['keys'][$index] = "(".$key.")";
		} 	
		foreach($invoice_data_parts['values'] as $index => $value)
		{
			$invoice_data_parts['values'][$index] = trim($value);
		}



		/*
			Assemble the message data
		*/

		$email = array();


		// default to system rather than user
		$email["sender"]	= "system";
	
		// email to the accounts user
		if (empty($this->invoice_fields["customer_contact_email"]))
		{
			$email["to"]	= "";
		}
		else
		{
			$email["to"]	= $this->invoice_fields["customer_contact"] ." <". $this->invoice_fields["customer_contact_email"] .">";
		}


		// default cc
		$email["cc"]		= "";

		// default bcc
		if ($GLOBALS["config"]["ACCOUNTS_EMAIL_AUTOBCC"])
		{
			$email["bcc"]	= "Accounts <". $GLOBALS["config"]["ACCOUNTS_EMAIL_ADDRESS"] .">";
		}
		else
		{
			$email["bcc"]	= "";
		}


		// type specific
		if ($this->type == "ar")
		{
			$email["subject"]	= "Invoice ". $this->invoice_fields["code_invoice"];
			$email["message"]	= sql_get_singlevalue("SELECT value FROM config WHERE name IN('TEMPLATE_INVOICE_EMAIL') LIMIT 1");
		}
		else
		{
			$email["subject"]	= "Quote ". $this->invoice_fields["code_quote"];
			$email["message"]	= sql_get_singlevalue("SELECT value FROM config WHERE name IN('TEMPLATE_QUOTE_EMAIL') LIMIT 1");
		}
		

		// replace fields in the template
		$email["message"]		= str_replace($invoice_data_parts['keys'], $invoice_data_parts['values'], $email["message"]);


		return $email;

	} // end of generate_email


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
		if (!@include_once('Mail.php'))
		{
			log_write("error", "invoice", "Unable to find Mail module required for sending email");
			return 0;
		}
		
		if (!@include_once('Mail/mime.php'))
		{
			log_write("error", "invoice", "Unable to find Mail::Mime module required for sending email");
			return 0;
		}


		// track attachment files to tidy up
		$file_attachments = array();

		/*
			Prepare Email Mime Data & Headers
		*/

		// fetch sender address
		//
		// users have the choice of sending as the company or as their own staff email address & name.
		//
		if ($email_sender == "user")
		{
			// send as the user
			$email_sender = "\"". user_information("realname") . "\" <". user_information("contact_email") .">";
		}
		else
		{
			// send as the system
			$email_sender = "\"". sql_get_singlevalue("SELECT value FROM config WHERE name='COMPANY_NAME'") ."\" <". sql_get_singlevalue("SELECT value FROM config WHERE name='COMPANY_CONTACT_EMAIL'") .">";
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
		



		/*
			Generate a PDF of the invoice and save to tmp file
		*/

		log_debug("invoice", "Generating invoice PDF for emailing");

		// generate PDF
		$this->generate_pdf();

		if (error_check())
		{
			return 0;
		}
		
		// save to a temporary file
		if ($this->type == "ar")
		{
			$tmp_file_invoice = file_generate_name($GLOBALS["config"]["PATH_TMPDIR"] ."/invoice_". $this->data["code_invoice"] ."", "pdf");
		}
		else
		{
			$tmp_file_invoice = file_generate_name($GLOBALS["config"]["PATH_TMPDIR"] ."/quote_". $this->data["code_quote"] ."", "pdf");
			//$email_template	= sql_get_singlevalue("SELECT value FROM config WHERE name IN('TEMPLATE_QUOTE_EMAIL') LIMIT 1");
		}
		
		if (!$fhandle = fopen($tmp_file_invoice, "w"))
		{
			log_write("error", "invoice", "A fatal error occured whilst writing invoice PDF to file $tmp_file_invoice, unable to send email");
			return 0;
		}
		
		fwrite($fhandle, $this->obj_pdf->output);
		fclose($fhandle);

		// attach
		$mail_mime->addAttachment($tmp_file_invoice, 'application/pdf');
		$file_attachments[] = $tmp_file_invoice;


		/*
			Fetch Extra Attachments

			Certain billing processes may add file attachments to the journal that should be sent along with the invoice
			when an email is generated.

			Here we grab those file attachments and send each one.
		*/
	
		$obj_sql_journal		= New sql_query;
		$obj_sql_journal->string	= "SELECT id FROM journal WHERE journalname='account_ar' AND customid='". $this->id ."' AND title LIKE 'SERVICE:%'";
		$obj_sql_journal->execute();

		if ($obj_sql_journal->num_rows())
		{
			$obj_sql_journal->fetch_array();

			foreach ($obj_sql_journal->data as $data_journal)
			{
				// there are journaled attachments to send
				//
				// we don't care about any of the journal data, we just need to pull the file attachment from
				// storage, write to disk and then attach to the email
				//


				// fetch file object
				$file_obj			= New file_storage;
				$file_obj->data["type"]		= "journal";
				$file_obj->data["customid"]	= $data_journal["id"];

				if (!$file_obj->load_data_bytype())
				{
					log_write("error", "inc_invoices", "Unable to load file from journal to attach to invoice email - possible file storage issue?");
					return 0;
				}
			
				$file_extension 	= format_file_extension($file_obj->data["file_name"]);
				$file_name		= format_file_noextension($file_obj->data["file_name"]);
				$file_ctype		= format_file_contenttype($file_extension);


				// we have to write the file to disk before attaching it
				$tmp_file_attach	= file_generate_name($GLOBALS["config"]["PATH_TMPDIR"] ."/". $file_name, $file_extension);

				if (!$file_obj->filedata_write($tmp_file_attach))
				{
					log_write("error", "inc_invoices", "Unable to write file attachments from journal to tmp space");
					return 0;
				}

				// add to the invoice
				$mail_mime->addAttachment($tmp_file_attach, $file_ctype);
				$file_attachments[] = $tmp_file_attach;

				// cleanup - tmp file will be removed ;ater
				unset($file_obj);

			} // end of for each journal item

		} // end if sendable journal items

		unset($obj_sql_journal);


		


		/*
			Email the invoice
		*/
		

		log_write("debug", "invoice", "Sending generated email....");

		$mail_body	= $mail_mime->get();
	 	$mail_headers	= $mail_mime->headers($mail_headers);

		$mail		= & Mail::factory('mail', "-f ". $GLOBALS["config"]["COMPANY_CONTACT_EMAIL"]);
		$status 	= $mail->send($email_to, $mail_headers, $mail_body);

		if (PEAR::isError($status))
		{
			log_write("error", "inc_invoice", "An error occured whilst attempting to send the email: ". $status->getMessage() ."");
		}
		else
		{
			log_write("debug", "inc_invoice", "Successfully sent email invoice");


			/*
				Start SQL Transaction to post email to journal
			*/

			$sql_obj = New sql_query;
			$sql_obj->trans_begin();


			/*
				Mark the invoice as having been sent
			*/
			$sql_obj		= New sql_query;
			$sql_obj->string	= "UPDATE account_". $this->type ." SET date_sent='". date("Y-m-d") ."', sentmethod='email' WHERE id='". $this->id ."'";
			$sql_obj->execute();


			/*
				Add the email information to the journal, including attaching a copy
				of the generated PDF
			*/

			log_write("debug", "inc_invoice", "Uploading PDF and email details to journal...");


			// create journal entry
			$journal = New journal_process;
			
			$journal->prepare_set_journalname("account_". $this->type);
			$journal->prepare_set_customid($this->id);
			$journal->prepare_set_type("file");
			
			$journal->prepare_set_title("EMAIL: $email_subject");

			$data["content"] = NULL;
			$data["content"] .= "To:\t". $email_to ."\n";
			$data["content"] .= "Cc:\t". $email_cc ."\n";
			$data["content"] .= "Bcc:\t". $email_bcc ."\n";
			$data["content"] .= "From:\t". $email_sender ."\n";
			$data["content"] .= "\n";
			$data["content"] .= $email_message;
			$data["content"] .= "\n";
				
				
			$journal->prepare_set_content($data["content"]);

			$journal->action_update();		// create journal entry
			$journal->action_lock();		// lock it to prevent any changes to historical record of delivered email


			// upload PDF file as an attachement
			$file_obj			= New file_storage;
			$file_obj->data["type"]		= "journal";
			$file_obj->data["customid"]	= $journal->structure["id"];

			if (!$file_obj->action_update_file($tmp_file_invoice))
			{
				log_write("error", "inc_invoice", "Unable to upload emailed PDF to journal entry");
			}


			/*
				Commit
			*/
			if (error_check())
			{
				$sql_obj->trans_rollback();
			}
			else
			{
				$sql_obj->trans_commit();
			}

		} // end if successful send


		// cleanup - remove the temporary files
		log_debug("inc_invoice", "Performing cleanup, removing temporary files used for emails");
		
		foreach ($file_attachments as $filename)
		{
			log_debug("inc_invoice", "Removing tmp file $filename");
			unlink($filename);
		}


		// return
		if (error_check())
		{
			return 0;
		}
		else
		{
			return 1;
		}
	
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

        var $rollback;          // Set to true if items need to be rolled back
        
        var $deletecancel=0;      // Set to true if items are for an invoice being cancelled.
                                // Items will be deleted otherwise.

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
			Verify that the invoice exists (or the project)
		*/
            
		$sql_obj		= New sql_query;
                if($this->type_invoice=="project")
                {
                    $sql_obj->string	= "SELECT id FROM projects WHERE id='". $this->id_invoice ."' LIMIT 1";
                }
                else
                {
                    $sql_obj->string	= "SELECT id FROM account_". $this->type_invoice ." WHERE id='". $this->id_invoice ."' LIMIT 1";
                }
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
				$this->data["discount"]		= $data["discount"];

				// calculate the total amount
				$this->data["amount"]		= $data["price"] * $data["quantity"];

				// apply any discounts
				if ($this->data["discount"])
				{
					// convert percentage to float
					$discount_calc = 1 - ($this->data["discount"] / 100);

					// apply discount
					$this->data["amount"]	= $this->data["amount"] * $discount_calc;
				}

				// get the chart for the product - this will be the account_sales
				// for ar/quotes, or account_purchase for AP invoices

				$sql_obj = New sql_query;

				if ($this->type_invoice == "ap")
				{
					$sql_obj->string = "SELECT account_purchase as account FROM products WHERE id='". $this->data["customid"] ."' LIMIT 1";
				}
				else
				{
					$sql_obj->string = "SELECT account_sales as account FROM products WHERE id='". $this->data["customid"] ."' LIMIT 1";
				}

				$sql_obj->execute();

				if ($sql_obj->num_rows())
				{
					$sql_obj->fetch_array();
	
					$this->data["chartid"] = $sql_obj->data[0]["account"];
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
				$this->data["discount"]		= $data["discount"];

				

				// fetch the number of billable hours for the supplied timegroupid
				$sql_obj		= New sql_query;
				$sql_obj->string	= "SELECT SUM(time_booked) as time_billable FROM timereg WHERE groupid='". $this->data["timegroupid"] ."' AND billable='1'";
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


				// apply any discounts
				if ($this->data["discount"])
				{
					// convert percentage to float
					$discount_calc = 1 - ($this->data["discount"] / 100);

					// apply discount
					$this->data["amount"]	= $this->data["amount"] * $discount_calc;
				}


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


			case "service":
			case "service_usage":
				/*
					SERVICE ITEMS
				*/
			
				// a service item can only be added to an AR transactions
				if ($this->type_invoice != "ar")
				{
					log_write("error", "inc_invoices", "You can only add service invoice items to AR invoices.");
					return 0;
				}


				// save information
				$this->data["price"]			= $data["price"];
				$this->data["quantity"]			= $data["quantity"];
				$this->data["units"]			= $data["units"];
				$this->data["customid"]			= $data["customid"];
				$this->data["description"]		= $data["description"];
				$this->data["discount"]			= $data["discount"];

				// extra ID
				$this->data["id_service_customer"]	= $period_usage_data["id_service_customer"];
				$this->data["id_period"]		= $period_usage_data["id"];
				
				// service specific
				$this->data["cdr_billgroup"]	= $data["cdr_billgroup"];

				// calculate the total amount
				$this->data["amount"]		= $data["price"] * $data["quantity"];

				// apply any discounts
				if ($this->data["discount"])
				{
					// convert percentage to float
					$discount_calc = 1 - ($this->data["discount"] / 100);

					// apply discount
					$this->data["amount"]	= $this->data["amount"] * $discount_calc;
				}

				// get the chart for the service - this will be the account_sales
				// for ar/quotes, or account_purchase for AP invoices

				$sql_obj = New sql_query;
				$sql_obj->string = "SELECT chartid as account FROM services WHERE id='". $this->data["customid"] ."' LIMIT 1";
				$sql_obj->execute();

				if ($sql_obj->num_rows())
				{
					$sql_obj->fetch_array();
	
					$this->data["chartid"] = $sql_obj->data[0]["account"];
				}
				else
				{
					log_write("error", "inc_invoice", "Unknown service, unable to add item.");
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
				
				// check credit amount
				if ($this->data["chartid"] == "credit")
				{
					if ($this->type_invoice == "ar")
					{
						$id_customer	= sql_get_singlevalue("SELECT customerid as value FROM account_ar WHERE id='". $this->id_invoice ."' LIMIT 1");
						$credit_balance	= sql_get_singlevalue("SELECT SUM(amount_total) as value FROM customers_credits WHERE id_customer='". $id_customer ."' AND id_custom!='". $this->id_item ."'");
					}
					else
					{
						$id_vendor	= sql_get_singlevalue("SELECT vendorid as value FROM account_ap WHERE id='". $this->id_invoice ."' LIMIT 1");
						$credit_balance	= sql_get_singlevalue("SELECT SUM(amount_total) as value FROM vendor_credits WHERE id_vendor='". $id_vendor ."' AND id_custom!='". $this->id_item ."'");
					}

					if ($this->data["amount"] > $credit_balance)
					{
						log_write("error", "inc_invoice_items", "Unable to accept credit payment of ". format_money($this->data["amount"]) .", credit balance is currently ". format_money($credit_balance) ."");
					}
				}
			break;


			case "credit":
				/*
					CREDIT ITEMS
				*/

				// very simple, just copy the data across
				foreach (array_keys($data) as $i)
				{
					$this->data[ $i ] = $data[ $i ];
				}

			break;
		

			default:
				log_write("error", "inc_invoice", "Unknown item type provided.");
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


		/*
			Start SQL Transaction
		*/

		$sql_obj = New sql_query;
		$sql_obj->trans_begin();



		/*
			Create new invoice item
		*/
		$sql_obj->string	= "INSERT INTO `account_items` (invoiceid, invoicetype) VALUES ('". $this->id_invoice ."', '". $this->type_invoice ."')";
		$sql_obj->execute();

		$this->id_item		= $sql_obj->fetch_insert_id();


		/*
			Update Journal
		*/
		journal_quickadd_event("account_". $this->type_invoice ."", $this->id_invoice, "Item successfully created");



		/*
			Commit
		*/
		if (error_check() || $this->id_item == 0)
		{
			$sql_obj->trans_rollback();

			log_write("error", "invoice_items", "An error occured preventing the creation of the invoice item");

			return 0;
		}
		else
		{
			$sql_obj->trans_commit();

			log_write("notification", "invoice_items", "Successfully created new invoice item");

			return $this->id_item;
		}

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
	
		/*
			Start SQL Transaction
		*/

		$sql_obj = New sql_query;
		$sql_obj->trans_begin();

                

		// create a new item if required
		if (!$this->id_item)
		{
			if (!$this->action_create())
			{
				$sql_obj->trans_rollback();
				return 0;
			}
		}


		/*
			Fetch required values
		*/

		if ($this->type_item == "time")
		{
			// fetch the current timegroup id - we need this to check if the timegroup ID has changed.
			$this->data["timegroupid_old"] = sql_get_singlevalue("SELECT option_value as value FROM account_items_options WHERE itemid='". $this->id_item ."' AND option_name='TIMEGROUPID'");
		}

		if ($this->type_item == "payment" && $this->data["chartid"] == "credit")
		{
			// fetch the actual chartid of the AR/AP account for credit handling
			$this->data["chartid"]	= sql_get_singlevalue("SELECT dest_account as value FROM account_". $this->type_invoice ." WHERE id='". $this->id_invoice ."' LIMIT 1");
			$this->data["credit"]	= "CREDIT";
		}

                if ($this->type_item == "product" && ($this->type_invoice=="ar" || $this->type_invoice=="ap" || $this->type_invoice=="project"))
                {
                    $quantdiff= $this->data["quantity"]-sql_get_singlevalue("SELECT quantity as value FROM account_items WHERE id='".$this->id_item."'");
                }
	
		/*
			Update Item
		*/

		// Setting defaults when they haven't been set
		if(!isset($this->data["price"]))
			$this->data["price"]=0;
		if(!isset($this->data["discount"]))
			$this->data["discount"]=0;
		if(!isset($this->data["quantity"]))
			$this->data["quantity"]=0;
		if(!isset($this->data["units"]))
			$this->data["units"]="";
		if(!isset($this->data["customid"]))
			$this->data["customid"]="";

		$sql_obj->string = "UPDATE `account_items` SET "
					."type='". $this->type_item ."', "
					."amount='". $this->data["amount"] ."', "
					."price='". $this->data["price"] ."', "
					."chartid='". $this->data["chartid"] ."', "
					."customid='". $this->data["customid"] ."', "
					."quantity='". $this->data["quantity"] ."', "
					."units='". $this->data["units"] ."', "
					."description='". $this->data["description"] ."' "
					."WHERE id='". $this->id_item ."' LIMIT 1";
						
		$sql_obj->execute();
	
                /* Update product quantities
                 * 
                 */
                if($this->type_item=="product"  && ($this->type_invoice=="ar" || $this->type_invoice=="ap" || $this->type_invoice=="project"))
                {
                    $quantnew = sql_get_singlevalue("SELECT quantity_instock as value FROM products WHERE id='".$this->data["customid"]."'");
                    
                    if($this->type_invoice=="ar" || $this->type_invoice=="project")
                        $quantdiff=-$quantdiff;
                    
                    $quantnew+=$quantdiff;
                    if($quantnew<0)
                        $quantnew=0;
                    
                    if($quantdiff!=0)
                    {
                        $sql_obj->string = "UPDATE products SET quantity_instock='".$quantnew."' WHERE id='".$this->data["customid"]."' LIMIT 1";
                        $sql_obj->execute();

                        $invproj=$this->type_invoice=="project"?"project":"invoice";
                        journal_quickadd_event("products",$this->data["customid"],"Product added to $invproj. (Stock Adj. ".sprintf("%+d",$quantdiff).")");
                    }
                }
                
		/*
			Update Item Options
		*/

		// remove all existing options
		$sql_obj->string	= "DELETE FROM account_items_options WHERE itemid='". $this->id_item ."'";
		$sql_obj->execute();


		// create options for standard transactions
		if ($this->type_item == "standard" || $this->type_item == "credit")
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
			$sql_obj->string	= "INSERT INTO account_items_options (itemid, option_name, option_value) VALUES ('". $this->id_item ."', 'SOURCE', '". $this->data["source"] ."')";
			$sql_obj->execute();

			// date_trans
			$sql_obj->string	= "INSERT INTO account_items_options (itemid, option_name, option_value) VALUES ('". $this->id_item ."', 'DATE_TRANS', '". $this->data["date_trans"] ."')";
			$sql_obj->execute();

			// credit
			if (!empty($this->data["credit"]))
			{
				// flag this payment as a credit, so that we handle it correctly
				$sql_obj->string	= "INSERT INTO account_items_options (itemid, option_name, option_value) VALUES ('". $this->id_item ."', 'CREDIT', 'CREDIT')";
				$sql_obj->execute();
			}
		}


		// create options for time items
		if ($this->type_item == "time")
		{
		
			// check if the time group has been changed - if so, we need to unlock the old timegroup, otherwise
			// the system will end up with a locked time group that can never be used.
			if ($this->data["timegroupid_old"] != $this->data["timegroupid"])
			{
				// fetch the current lock status of the time group
				// if it's set to 1, we want to keep that, otherwise if 2, set to 0
				$locked = sql_get_singlevalue("SELECT locked as value FROM time_groups WHERE id='". $this->data["timegroupid_old"] ."' LIMIT 1");

				if ($locked == 2)
				{
					$locked = 0;
				}

				$sql_obj->string	= "UPDATE time_groups SET invoiceid='0', invoiceitemid='0', locked='$locked' WHERE id='". $this->data["timegroupid_old"] ."' LIMIT 1";
				$sql_obj->execute();
			}


			// create options entry for the timegroupid
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
			$sql_obj->string	= "UPDATE time_groups SET invoiceid='". $this->id_invoice ."', invoiceitemid='". $this->id_item ."', locked='". $locked ."' WHERE id='". $this->data["timegroupid"] ."'";
			$sql_obj->execute();
		}


		// set discount for time or product items if supplied
		if ($this->data["discount"])
		{
			$sql_obj->string	= "INSERT INTO account_items_options (itemid, option_name, option_value) VALUES ('". $this->id_item ."', 'DISCOUNT', '". $this->data["discount"] ."')";
			$sql_obj->execute();
		}


		if (!empty($this->data["cdr_billgroup"]))
		{
			$sql_obj->string	= "INSERT INTO account_items_options (itemid, option_name, option_value) VALUES ('". $this->id_item ."', 'CDR_BILLGROUP', '". $this->data["cdr_billgroup"] ."')";
			$sql_obj->execute();
		}


		// starting with phone services, we are recording service-customer assignment IDs
		if (!empty($this->data["id_service_customer"]))
		{
			$sql_obj->string	= "INSERT INTO account_items_options (itemid, option_name, option_value) VALUES ('". $this->id_item ."', 'ID_SERVICE_CUSTOMER', '". $this->data["id_service_customer"] ."')";
			$sql_obj->execute();
		}

		if (!empty($this->data["id_period"]))
		{
			$sql_obj->string	= "INSERT INTO account_items_options (itemid, option_name, option_value) VALUES ('". $this->id_item ."', 'ID_PERIOD', '". $this->data["id_period"] ."')";
			$sql_obj->execute();
		}




		/*
			Update Journal
		*/

		journal_quickadd_event("account_". $this->type_invoice ."", $this->id_invoice, "Invoice Item Updated");



		/*
			Commit
		*/

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "invoice_items", "An error occured whilst updating invoice item - No changes have been made");
			
			return 0;
		}
		else
		{
			$sql_obj->trans_commit();

			$_SESSION["notification"]["message"] = array();
			log_write("notification", "invoice_items", "Successfully updated invoice item");

			return $this->id_item;	
		}
		
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
			Start SQL Transaction
		*/

		$sql_obj = New sql_query;
		$sql_obj->trans_begin();


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
				$sql_obj->string	= "DELETE FROM account_items WHERE id='". $data_item["id"] ."' LIMIT 1";
				$sql_obj->execute();

				// delete the tax items options
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
				switch ($data["type"])
				{
					case "product":
					case "time":
						/*
							HANDLE TAXES FOR PRODUCT-BASED ITEMS
						*/


						// fetch the taxes for the selected product
						$sql_product_tax_obj		= New sql_query;
						$sql_product_tax_obj->string	= "SELECT taxid FROM `products_taxes` WHERE productid='". $data["customid"] ."'";
						$sql_product_tax_obj->execute();

						if ($sql_product_tax_obj->num_rows())
						{
							$sql_product_tax_obj->fetch_array();

							foreach ($sql_product_tax_obj->data as $data_product_tax)
							{
								// automatic
								// note: no need to multiple by quantity, since the item amount is already price * quantity
								if (!isset($tax_structure[ $data_product_tax["taxid"] ]["auto"]))
								{
									$tax_structure[ $data_product_tax["taxid"] ]["auto"]	= $data["amount"];
								}
								else
								{
									$tax_structure[ $data_product_tax["taxid"] ]["auto"]	+= $data["amount"];
								}
							}
						}
					break;

	
					case "service":
					case "service_usage":
						/*
							HANDLE TAXES FOR SERVICE ITEMS
						*/


						// fetch the taxes for the selected service
						$sql_service_tax_obj		= New sql_query;
						$sql_service_tax_obj->string	= "SELECT taxid FROM `services_taxes` WHERE serviceid='". $data["customid"] ."'";
						$sql_service_tax_obj->execute();

						if ($sql_service_tax_obj->num_rows())
						{
							$sql_service_tax_obj->fetch_array();

							foreach ($sql_service_tax_obj->data as $data_service_tax)
							{
								// automatic
								// note: no need to multiple by quantity, since the item amount is already price * quantity
								if (!isset($tax_structure[ $data_service_tax["taxid"] ]["auto"]))
								{
									$tax_structure[ $data_service_tax["taxid"] ]["auto"]	= $data["amount"];
								}
								else
								{
									$tax_structure[ $data_service_tax["taxid"] ]["auto"]	+= $data["amount"];
								}
							}
						}
					break;

					case "standard":
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
							foreach ($sql_item_tax_obj->data as $data_item_tax)
							{
								// automatic
								// note: no need to multiple by quantity, since the item amount is already price * quantity
								if (!isset($tax_structure[ $data_item_tax["taxid"] ]["auto"]))
								{
									$tax_structure[ $data_item_tax["taxid"] ]["auto"]	= $data["amount"];
								}
								else
								{
									$tax_structure[ $data_item_tax["taxid"] ]["auto"]	+= $data["amount"];
								}
							}
						}
					break;

					case "credit":
						/*
							HANDLE TAXES FOR CREDIT ITEMS

							All taxes for credit items are automatically generated, so we need to get the list of taxes
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
								if (!isset($tax_structure[ $data_item_tax["taxid"] ]["auto"]))
								{
									$tax_structure[ $data_item_tax["taxid"] ]["auto"]	= $data["amount"];
								}
								else
								{
									$tax_structure[ $data_item_tax["taxid"] ]["auto"]	+= $data["amount"];
								}
							}
						}
					break;

				}

			} // end of loop through items

		} // end if items exist


		/*
			Check what taxes that the customer/vendor has enabled - we can only
			create tax items if they are enabled.
		*/
		$enabled_taxes = NULL;

		if ($this->type_invoice == "ap" || $this->type_invoice == "ap_credit")
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
		elseif($this->type_invoice!="project")
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
			Run through all the tax structure and generate tax items (if any)
		*/
		if ($tax_structure)
		{
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
					if (!empty($tax_structure[ $taxid ]["manual"]))
					{
						$amount += $tax_structure[ $taxid ]["manual"];
					}

					// any items requiring automatic tax generation?
					if (!empty($tax_structure[ $taxid ]["auto"]))
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

		} // end if tax structure



		/*
			Commit
		*/
		$sql_obj = New sql_query;

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "invoice_items", "An error occured whilst attempting to update invoice taxes. No changes have been made.");
			
			return 0;
		}
		else
		{
			$sql_obj->trans_commit();

			return 1;
		}

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

                // Ignore if project
                if($this->type_invoice=="project")
                {
                    return 1;
                }

		// default values
		$amount		= "0";
		$amount_tax	= "0";
		$amount_total	= "0";


		/*
			Total up all the items, and all the tax
		*/

		$amount		= 0;
		$amount_tax	= 0;
		$amount_paid	= 0;

		// fetch item amounts from DB
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT amount, type FROM `account_items` WHERE invoicetype='". $this->type_invoice ."' AND invoiceid='". $this->id_invoice ."'";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$sql_obj->fetch_array();

			foreach ($sql_obj->data as $data_sql)
			{
				// total up the different item types
				if ($data_sql["type"] != "tax" && $data_sql["type"] != "payment")
				{
					$amount += $data_sql["amount"];
				}

				if ($data_sql["type"] == "tax")
				{
					$amount_tax += $data_sql["amount"];
				}

				if ($data_sql["type"] == "payment")
				{
					$amount_paid += $data_sql["amount"];
				}
			}
		}

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

		if ($this->type_invoice == "quotes" || $this->type_invoice == "ar_credit" || $this->type_invoice == "ar_credit")
		{
			$sql_obj->string = "UPDATE `account_". $this->type_invoice ."` SET "
						."amount='". $amount ."', "
						."amount_tax='". $amount_tax ."', "
						."amount_total='". $amount_total ."' "
						."WHERE id='". $this->id_invoice ."' LIMIT 1";
		}
		else
		{
			$sql_obj->string = "UPDATE `account_". $this->type_invoice ."` SET "
					."amount='". $amount ."', "
					."amount_tax='". $amount_tax ."', "
					."amount_total='". $amount_total ."', "
					."amount_paid='". $amount_paid ."' "
					."WHERE id='". $this->id_invoice ."' LIMIT 1";
		}
		

		if (!$sql_obj->execute())
		{
			log_debug("invoice_items", "A fatal SQL error occured whilst attempting to update invoice totals");
			return 0;
		}


		/*
			Update the credit (if any)
		*/

		if ($this->type_invoice == "ar_credit" || $this->type_invoice == "ap_credit")
		{
			$credit		= New credit;
			$credit->id	= $this->id_invoice;
			$credit->type	= $this->type_invoice;

			$credit->load_data();

			$credit->action_update_balance();
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

	
		/*
			Start SQL Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();


		// fetch key information from invoice
		$sql_inv_obj		= New sql_query;
                if($this->type_invoice=="project")
                {
                    $sql_inv_obj->string    = "SELECT id, dest_account, CURDATE() AS date_trans FROM projects WHERE id='". $this->id_invoice ."' LIMIT 1";    
                }
                else
                {
                    $sql_inv_obj->string    = "SELECT id, dest_account, date_trans FROM account_". $this->type_invoice ." WHERE id='". $this->id_invoice ."' LIMIT 1";
                }
		$sql_inv_obj->execute();
		$sql_inv_obj->fetch_array();


		// remove all the old ledger entries belonging to this invoice
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
				switch ($this->type_invoice)
				{
					case "ar_credit":
						ledger_trans_add("debit", $trans_type, $this->id_invoice, $sql_inv_obj->data[0]["date_trans"], $item_data["chartid"], $item_data["amount"], "", "");
					break;
					
					case "ap_credit":
						ledger_trans_add("credit", $trans_type, $this->id_invoice, $sql_inv_obj->data[0]["date_trans"], $item_data["chartid"], $item_data["amount"], "", "");
					break;

					case "ap":
						ledger_trans_add("debit", $trans_type, $this->id_invoice, $sql_inv_obj->data[0]["date_trans"], $item_data["chartid"], $item_data["amount"], "", "");
					break;

					case "ar":
                                        case "project":
					default:
						ledger_trans_add("credit", $trans_type, $this->id_invoice, $sql_inv_obj->data[0]["date_trans"], $item_data["chartid"], $item_data["amount"], "", "");
					break;
				}

				// add up the total for the AR entry.
				$amount += $item_data["amount"];
			}

			switch ($this->type_invoice)
			{
				case "ap":
					// create credit from AP account
					ledger_trans_add("credit", $this->type_invoice, $this->id_invoice, $sql_inv_obj->data[0]["date_trans"], $sql_inv_obj->data[0]["dest_account"], $amount, "", "");
				break;

				case "ap_credit":
					// create debit to AP account
					ledger_trans_add("debit", $this->type_invoice, $this->id_invoice, $sql_inv_obj->data[0]["date_trans"], $sql_inv_obj->data[0]["dest_account"], $amount, "", "");
				break;

				case "ar_credit":
					// create credit from AR account
					ledger_trans_add("credit", $this->type_invoice, $this->id_invoice, $sql_inv_obj->data[0]["date_trans"], $sql_inv_obj->data[0]["dest_account"], $amount, "", "");
				break;

				case "ar":
                                case "project":
				default:
					// create debit to AR account
					ledger_trans_add("debit", $this->type_invoice, $this->id_invoice, $sql_inv_obj->data[0]["date_trans"], $sql_inv_obj->data[0]["dest_account"], $amount, "", "");
				break;
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

					if ($option_data["option_name"] == "CREDIT")
						$data["credit"] = $option_data["option_value"];
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

					// if a credit, we need to subtract from vendor credit pool
					if (!empty($data["credit"]))
					{
						$id_vendor = sql_get_singlevalue("SELECT vendorid as value FROM account_ar WHERE id='". $this->id_invoice ."' LIMIT 1");
						$id_employee = sql_get_singlevalue("SELECT employeeid as value FROM account_ar WHERE id='". $this->id_invoice ."' LIMIT 1");

						$sql_obj->string = "DELETE FROM vendors_credits WHERE id_vendor='". $id_vendor ."' AND type='payment' AND id_custom='". $data["id"]."' LIMIT 1";
						$sql_obj->execute();

						$sql_obj->string = "INSERT INTO vendors_credits (date_trans, type, amount_total, id_custom, id_employee, id_vendor, description) VALUES ('". $data["date_trans"] ."', 'payment', '-". $data["amount"] ."', '". $data["id"] ."', '". $id_employee ."', '". $id_vendor ."', '". $data["description"] ."')";
						$sql_obj->execute();
					}

				}
				else
				{
					// we need to debit the destination account for the payment to go into and credit the AR account
					ledger_trans_add("debit", $this->type_invoice ."_pay", $this->id_invoice, $data["date_trans"], $data["chartid"], $data["amount"], $data["source"], $data["description"]);
					ledger_trans_add("credit", $this->type_invoice ."_pay", $this->id_invoice, $data["date_trans"], $sql_inv_obj->data[0]["dest_account"], $data["amount"], $data["source"], $data["description"]);
					
					// if a credit, we need to subtract from customer credit pool
					if (!empty($data["credit"]))
					{
						$id_customer = sql_get_singlevalue("SELECT customerid as value FROM account_ar WHERE id='". $this->id_invoice ."' LIMIT 1");
						$id_employee = sql_get_singlevalue("SELECT employeeid as value FROM account_ar WHERE id='". $this->id_invoice ."' LIMIT 1");

						$sql_obj->string = "DELETE FROM customers_credits WHERE id_customer='". $id_customer ."' AND type='payment' AND id_custom='". $data["id"] ."' LIMIT 1";
						$sql_obj->execute();

						$sql_obj->string = "INSERT INTO customers_credits (date_trans, type, amount_total, id_custom, id_employee, id_customer, description) VALUES ('". $data["date_trans"] ."', 'payment', '-". $data["amount"] ."', '". $data["id"] ."', '". $id_employee ."', '". $id_customer ."', '". $data["description"] ."')";
						$sql_obj->execute();
					}
				}
			}
		}


		/*
			Commit
		*/
		$sql_obj = New sql_query;

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "invoice_items", "An error occured whilst attempting to update ledger for invoice. No changes have been made.");
			
			return 0;
		}
		else
		{
			$sql_obj->trans_commit();

			return 1;
		}

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
			Start SQL Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();


                /*
                 *      Replace stock items if necessary
                 */
                if ($this->rollback==1 && $this->type_item == "product" && ($this->type_invoice=="ar" || $this->type_invoice=="ap" || $this->type_invoice=="project"))
                {
                    $quant=sql_get_singlevalue("SELECT quantity as value FROM account_items WHERE id='".$this->id_item."'");
                    $prodid=sql_get_singlevalue("SELECT customid as value FROM account_items WHERE id='".$this->id_item."'");
                    $quantnew = sql_get_singlevalue("SELECT quantity_instock as value FROM products WHERE id='".$prodid."'");

                    if($this->type_invoice=="ap")
                        $quant=-$quant;
                           
                    $sql_obj->string = "UPDATE products SET quantity_instock='".($quantnew+$quant)."' WHERE id='".$prodid."' LIMIT 1";
                    $sql_obj->execute();
                    
                    $invproj=$this->type_invoice=="project"?"project":"invoice";
                    journal_quickadd_event("products",$prodid,"Product removed from $invproj. (Stock Adj.".sprintf("%+d",$quant).")");
                }
                
		/*
			Unlock time_groups if required
		*/
		if ($this->rollback==1 && $this->type_item == "time")
		{
			$groupid = sql_get_singlevalue("SELECT option_value as value FROM account_items_options WHERE itemid='". $this->id_item ."' AND option_name='TIMEGROUPID'");
		
			// fetch the current lock status of the time group
			// if it's set to 1, we want to keep that, otherwise if 2, set to 0
			$locked = sql_get_singlevalue("SELECT locked as value FROM time_groups WHERE id='$groupid'");

			if ($locked == 2)
			{
				$locked = 0;
			}

			$sql_obj->string	= "UPDATE time_groups SET invoiceid='0', invoiceitemid='0', locked='$locked' WHERE id='$groupid'";
			$sql_obj->execute();
		}


		/*
			Delete credit payments if required
		*/

		if ($this->rollback==1 && $this->type_item == "payment")
		{
			$credit = sql_get_singlevalue("SELECT option_value as value FROM account_items_options WHERE itemid='". $this->id_item ."' AND option_name='CREDIT'");

			if (!empty($credit))
			{
				if ($this->type_invoice == "ap")
				{
					$id_vendor = sql_get_singlevalue("SELECT vendorid as value FROM account_ar WHERE id='". $this->id_invoice ."' LIMIT 1");

					$sql_obj->string = "DELETE FROM vendors_credits WHERE id_vendor='". $id_vendor ."' AND type='payment' AND id_custom='". $this->id_item ."' LIMIT 1";
					$sql_obj->execute();
				}
				else
				{
					$id_customer = sql_get_singlevalue("SELECT customerid as value FROM account_ar WHERE id='". $this->id_invoice ."' LIMIT 1");

					$sql_obj->string = "DELETE FROM customers_credits WHERE id_customer='". $id_customer ."' AND type='payment' AND id_custom='". $this->id_item ."' LIMIT 1";
					$sql_obj->execute();
				}
			}

		}

                // Delete any project expenses transactions
                $sql_obj->string = "DELETE FROM account_trans WHERE type='proj_ar' AND customid='".$this->id_item."'";
                $sql_obj->execute();
	
                // Delete links to projects
                $sql_obj->string	= "DELETE FROM account_items_options WHERE option_name='INVOICED_EXPENSE' AND option_value='". $this->id_item ."'";
                $sql_obj->execute();
                
		/*
			Delete the invoice item options
		*/
                if(($this->deletecancel=="0" && $this->type_invoice=="ar") || $this->type_invoice!="ar")
                {
                        $sql_obj->string	= "DELETE FROM account_items_options WHERE itemid='". $this->id_item ."'";
                        $sql_obj->execute();
	
                                                
		/*
			Delete the invoice item
		*/

                        $sql_obj->string	= "DELETE FROM account_items WHERE id='". $this->id_item ."' AND invoicetype='". $this->type_invoice ."' LIMIT 1";
                        $sql_obj->execute();
                        
                        journal_quickadd_event("account_". $this->type_invoice ."", $this->id_invoice, "Item successfully deleted.");
                }
                else
                {
                    journal_quickadd_event("account_". $this->type_invoice ."", $this->id_invoice, "Item successfully cancelled.");
                }

		/*
			Commit
		*/

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "invoice_items", "An error occured whilst attempting to delete invoice item. No changes have been made.");
			
			return 0;
		}
		else
		{
			$sql_obj->trans_commit();

			log_write("notification", "invoice_items", "Invoice item removed successfully");

			return 1;
		}


	}

	
	
} // END OF INVOICE_ITEMS CLASS



/*
	CLASS INVOICE_AUTOPAY

	Functions for checking a customer/vendor for automatic payment or credit sources and
	making payments against invoices when generated by automated processes.
*/
class invoice_autopay
{
	var $id_invoice;	// id of the invoice
	var $id_org;		// id of the customer/vendor
	var $type_invoice;	// type of invoice

	var $obj_invoice;	// invoice object

	var $capable;		// flag capability


	/*
		check_autopay_capable

		Checks whether the invoice can have an automatic payment made - this includes
		checking whether or not the customer/vendor has any credit.

		Returns
		0	Not Autopay Capable
		1	Is Autopay Capable
	*/

	function check_autopay_capable()
	{
		log_write("debug", "invoice_autopay", "Executing check_autopay_capable()");


		// fetch customer/vendor information
		if ($this->type_invoice == "ap")
		{
			$this->id_org	= sql_get_singlevalue("SELECT vendorid as value FROM account_ar WHERE id='". $this->id_invoice ."' LIMIT 1");
		}
		else
		{
			$this->id_org	= sql_get_singlevalue("SELECT customerid as value FROM account_ar WHERE id='". $this->id_invoice ."' LIMIT 1");
		}


		// check credit pool
		if ($this->type_invoice == "ap")
		{
			$credit = sql_get_singlevalue("SELECT SUM(amount_total) as value FROM vendors_credits WHERE id_vendor='". $this->id_org ."' LIMIT 1");
		}
		else
		{
			$credit = sql_get_singlevalue("SELECT SUM(amount_total) as value FROM customers_credits WHERE id_customer='". $this->id_org ."' LIMIT 1");
		}

		if ($credit > 0)
		{
			log_write("debug", "invoice_autopay", "There is credit ($credit) available for autopayments");

			$this->capable = 1;
			return 1;
		}


		// no autopayment sources
		return 0;

	} // end of check_autopay_capable



	/*
		autopay

		Makes any appropiate automatic payments for the selected invoice.

		Returns
		-1	Unexpected Error
		0	Not Autopay Capable
		1	Autopayment Made
	*/

	function autopay()
	{
		log_write("debug", "invoice_autopay", "Executing autopay()");


		// check capabilities
		if (!$this->capable)
		{
			if (!$this->check_autopay_capable())
			{
				return 0;
			}
		}

		/*
			Start SQL Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();



		/*
			Load Invoice Data
		*/

		$this->obj_invoice		= New invoice;
		$this->obj_invoice->id		= $this->id_invoice;
		$this->obj_invoice->type	= $this->type_invoice;

		if (!$this->obj_invoice->load_data())
		{
			log_write("error", "invoice_autopay", "Unable to load invoice data for checking for autopayments");
			return -1;
		}



		/*
			Make AutoPayments
		*/

		// handle credit payments
		$this->autopay_credit();

		// future: credit card, direct debit?



		/*
			Commit
		*/

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "invoice_autopay", "An error occured whilst making an invoice autopayment");

			return 0;
		}
		else
		{
			$sql_obj->trans_commit();

			return 1;
		}

	} // end of autopay



	/*
		autopay_credit

		Makes an autopayment from a customer/vendor's credit - is called by autopay() directly,
		this function should not be called directly itself.


		Returns
		0	No payment made
		1	Credit Payment Made
	*/

	function autopay_credit()
	{
		log_write("debug", "invoice_autopay", "Executing autopay_credit()");


		/*
			Fetch Current Credit Pool Amount

			NOTE: we do a full query here, to prevent caching from giving us incorrect information.
		*/

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT SUM(amount_total) as value FROM customers_credits WHERE id_customer='". $this->id_org ."' LIMIT 1";
		$sql_obj->execute();
		$sql_obj->fetch_array();


		$amount_credit = $sql_obj->data[0]["value"];

		unset($sql_obj);

		
		if ($amount_credit > 0)
		{
			$this->obj_invoice->data["amount_due"] = $this->obj_invoice->data["amount_total"] - $this->obj_invoice->data["amount_paid"];

			if ($amount_credit > $this->obj_invoice->data["amount_due"])
			{
				$amount_credit = $this->obj_invoice->data["amount_due"];
			}
		}
		else
		{
			log_write("debug", "invoice_autopay", "No credit available");

			return 0;
		}


		/*
			Define Item Details
		*/

		$item = New invoice_items;

		$item->id_invoice		= $this->id_invoice;
		$item->type_invoice		= $this->type_invoice;
		$item->type_item		= "payment";

		$data = array();

		$data["date_trans"]	= date("Y-m-d");
		$data["amount"]		= $amount_credit;
		$data["source"]		= "CREDIT";
		$data["description"]	= "Automated Credit Payment";
		$data["chartid"]	= "credit";

		if (!$item->prepare_data($data))
		{
			log_write("error", "invoice_autopay", "An error was encountered whilst processing credit payment data.");
			return 0;
		}

		$item->action_create();
		$item->action_update();
		$item->action_update_total();
		$item->action_update_ledger();

		if (!$item->id_item)
		{
			log_write("error","invoice_autopay", "An error occured whilst creating the credit autopay item");
		}

		unset($item);


		/*
			Make Credit Payment Item
		*/

		log_write("debug", "invoice_autopay", "Autopayment against invoice ". $this->id_invoice ." made from credit pool.");
		return 1;

	} // end of autopay_credit



} // end class invoice_autopay



?>
