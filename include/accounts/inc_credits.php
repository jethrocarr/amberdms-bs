<?php
/*
	include/accounts/inc_credits.php

	Provides various support functions for working with credits for both AR and AP.
*/



/*
	FUNCTIONS
*/




/*
	credit_render_summarybox($type, $id)

	Displays a status box showing the credit note status and whether the credit note has been paid
	back to the customer as a refund, or whether it's been pooled into customer's credit pool.

	Values
	id	id of the credit
	type	type - ar_credit or ap_credit

	Return Codes
	0	failure
	1	sucess
*/
function credit_render_summarybox($type, $id)
{
	log_debug("inc_credits", "credit_render_summarybox($type, $id)");

	// fetch credit information
	$sql_obj = New sql_query;
	$sql_obj->prepare_sql_settable("account_$type");

	if ($type == "ar_credit")
	{
		$sql_obj->prepare_sql_addfield("date_sent");
		$sql_obj->prepare_sql_addfield("sentmethod");
             	$sql_obj->prepare_sql_addfield("customerid");
	}
        else
        {
             	$sql_obj->prepare_sql_addfield("vendorid");            
        }
	
	$sql_obj->prepare_sql_addfield("code_credit");
	$sql_obj->prepare_sql_addfield("amount_total");
	$sql_obj->prepare_sql_addfield("locked");
	$sql_obj->prepare_sql_addfield("invoiceid");

	$sql_obj->prepare_sql_addwhere("id='$id'");
	$sql_obj->prepare_sql_setlimit("1");

	$sql_obj->generate_sql();
	$sql_obj->execute();

	if ($sql_obj->num_rows())
	{
		$sql_obj->fetch_array();

		// check for presence of credit items
		$sql_item_obj		= New sql_query;
		$sql_item_obj->string	= "SELECT id FROM account_items WHERE invoicetype='$type' AND invoiceid='$id' LIMIT 1";
		$sql_item_obj->execute();

		if (!$sql_item_obj->num_rows())
		{
			print "<table width=\"100%\" class=\"table_highlight_important\">";
			print "<tr>";
				print "<td>";
				print "<b>Credit Note ". $sql_obj->data[0]["code_credit"] ." has no items on it</b>";
				print "<p>This credit note is currently empty, add some items to it using the Credit Items page.</p>";
				print "</td>";
			print "</tr>";
			print "</table>";
		}
		else
		{
			if ($sql_obj->data[0]["locked"])
			{
				print "<table width=\"100%\" class=\"table_highlight_open\">";
				print "<tr>";
					print "<td>";
					print "<b>Credit Note ". $sql_obj->data[0]["code_credit"] ." is locked.</b>";
					print "<p>This credit note has been locked and no further action is required.</p>";
			}
			else
			{
				print "<table width=\"100%\" class=\"table_highlight_important\">";
				print "<tr>";
					print "<td>";
					print "<b>Credit Note ". $sql_obj->data[0]["code_credit"] ." is open/unlocked.</b>";
			}




			print "<table cellpadding=\"4\">";


			// fetch code_invoice
			if ($type == "ar_credit")
			{
				$code_invoice	= sql_get_singlevalue("SELECT code_invoice as value FROM account_ar WHERE id='". $sql_obj->data[0]["invoiceid"] ."' LIMIT 1");
				
				$invoice_url	= "index.php?page=accounts/ar/invoice-view.php&id=". $sql_obj->data[0]["invoiceid"] ."";
			}
			else
			{
				$code_invoice	= sql_get_singlevalue("SELECT code_invoice as value FROM account_ap WHERE id='". $sql_obj->data[0]["invoiceid"] ."' LIMIT 1");

				$invoice_url	= "index.php?page=accounts/ap/invoice-view.php&id=". $sql_obj->data[0]["invoiceid"] ."";
			}
			
			print "<tr>";
				print "<td>Associated Invoice:</td>";
				print "<td>Applies to invoice <a href=\"". $invoice_url ."\">". $code_invoice ."</a></td>";
			print "</tr>";


			// fetch customer/vendor details
			if ($type == "ar_credit")
			{
				$customer_name	= sql_get_singlevalue("SELECT CONCAT_WS('--', code_customer, name_customer) as value FROM customers WHERE id='". $sql_obj->data[0]["customerid"] ."' LIMIT 1");

				print "<tr>";
					print "<td>Credited Customer</td>";
					print "<td><a href=\"index.php?page=customers/credit.php&id_customer=". $sql_obj->data[0]["customerid"] ."\">". $customer_name ."</a></td>";
				print "</tr>";
			}
			else
			{
				$vendor_name	= sql_get_singlevalue("SELECT CONCAT_WS('--', code_vendor, name_vendor) as value FROM vendors WHERE id='". $sql_obj->data[0]["vendorid"] ."' LIMIT 1");

				print "<tr>";
					print "<td>Credit from Vendor</td>";
					print "<td><a href=\"index.php?page=vendors/credit.php&id_vendor=". $sql_obj->data[0]["vendorid"] ."\">". $vendor_name ."</a></td>";
				print "</tr>";

			}


			print "<tr>";
				print "<td>Total Credit:</td>";
				print "<td>". format_money($sql_obj->data[0]["amount_total"]) ."</td>";
			print "</tr>";


			if ($type == "ar_credit")
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

		print "<br>";
	}
}



/*
	credit_render_invoiceselect

	Provides an interface for selecting an invoice to make credit entries against - this is in the form of
	a simple radio form, so that a user can select which item to credit and then be taken to the add item page
	which will auto-completed based on the selection.

	Values
	id		id of the credit
	type		type - ar_credit or ap_credit
	processpage	Credit item add page to pass information to.

	Return Codes
	0	failure
	1	sucess
*/
function credit_render_invoiceselect($type, $id, $processpage)
{
	log_debug("inc_credits", "credit_render_summarybox($type, $id)");

	// fetch credit information
	$sql_obj = New sql_query;
	$sql_obj->prepare_sql_settable("account_$type");

	if ($type == "ar_credit")
	{
		$sql_obj->prepare_sql_addfield("date_sent");
		$sql_obj->prepare_sql_addfield("sentmethod");
	}
	
	$sql_obj->prepare_sql_addfield("code_credit");
	$sql_obj->prepare_sql_addfield("amount_total");
	$sql_obj->prepare_sql_addfield("invoiceid");
	$sql_obj->prepare_sql_addfield("locked");

	$sql_obj->prepare_sql_addwhere("id='$id'");
	$sql_obj->prepare_sql_setlimit("1");

	$sql_obj->generate_sql();
	$sql_obj->execute();

	if ($sql_obj->num_rows())
	{
		$sql_obj->fetch_array();

		if ($sql_obj->data[0]["locked"])
		{
			// credit note is locked, nothing todo
			return 1;
		}


		/*
			Select Invoice Items
		*/

		$invoice_type = "unknown";

		if ($type == "ar_credit")
		{
			$invoice_type = "ar";
		}
		elseif ($type == "ap_credit")
		{
			$invoice_type = "ap";
		}

		$sql_invoice_obj		= New sql_query;
		$sql_invoice_obj->string	= "SELECT id as itemid, type, customid, chartid, quantity, units, price, amount, description FROM account_items WHERE invoiceid='". $sql_obj->data[0]["invoiceid"] ."' AND invoicetype='". $invoice_type ."' AND type!='payment' AND type!='tax'";
		$sql_invoice_obj->execute();

		if ($sql_invoice_obj->num_rows())
		{
			$sql_invoice_obj->fetch_array();
		}


		/*
			Create Form
		*/

		$obj_invoice_form			= New form_input;

		$obj_invoice_form->formname		= $type ."_invoiceselect";
		$obj_invoice_form->language		= $_SESSION["user"]["lang"];

		$obj_invoice_form->action		= "index.php";
		$obj_invoice_form->method		= "GET";
		

		// ID
		$structure = NULL;
		$structure["fieldname"]		= "id";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $id;
		$obj_invoice_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]		= "page";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $processpage;
		$obj_invoice_form->add_input($structure);	



		// submit
		$structure = NULL;
		$structure["fieldname"]		= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "submit_add_credit_item";
		$obj_invoice_form->add_input($structure);



		/*
			Generate Items Radio Array
		*/

		if ($sql_invoice_obj->num_rows())
		{
			$structure = NULL;
			$structure["fieldname"]		= "invoice_item";
			$structure["type"]		= "radio";

			foreach ($sql_invoice_obj->data as $data_invoice)
			{
				$description = $data_invoice["description"];

				switch ($data_invoice["type"])
				{
					case "standard":
						$description = sql_get_singlevalue("SELECT CONCAT_WS('--', code_chart, description) as value FROM account_charts WHERE id='". $data_invoice["chartid"] ."' LIMIT 1");
					break;

					case "product":
						$description = sql_get_singlevalue("SELECT CONCAT_WS('--', code_product, name_product) as value FROM products WHERE id='". $data_invoice["customid"] ."' LIMIT 1");
					break;

					case "service":
					case "service_usage":
						$description = sql_get_singlevalue("SELECT name_service as value FROM services WHERE id='". $data_invoice["customid"] ."' LIMIT 1");
					break;

					default:
						$description = "unknown item";
					break;
				}

				$description .= " <i>". $data_invoice["description"] ."</i>";
				$description .= " [". format_money($data_invoice["amount"]) ." exc tax]";


				$structure["values"][]					= $data_invoice["itemid"];
				$structure["translations"][ $data_invoice["itemid"] ]	= $description;
			}

			$obj_invoice_form->add_input($structure);
		}



		/*
			Render Form
		*/

		if ($sql_invoice_obj->num_rows())
		{
			print "<table width=\"100%\" class=\"table_highlight_info\">";
			print "<tr>";
				print "<td>";
				print "<p><b>Select an item to be credited from the selected invoice - note that amounts can be varied once selected:</b></p>";
				print "<form method=\"". $obj_invoice_form->method ."\" action=\"". $obj_invoice_form->action ."\">";
			
				$obj_invoice_form->render_field("invoice_item");
					
				print "<br>";
				$obj_invoice_form->render_field("id");
				$obj_invoice_form->render_field("page");
				$obj_invoice_form->render_field("submit");

				
				print "</form>";
				print "</td>";
			print "</tr>";
			print "</table>";
		}
		else
		{
			/*
				No invoice items!
			*/

			format_msgbox("important", "<p>Unable to add any items to this credit note - the selected invoice has no items on it.</p>");
		}

		print "<br>";
	}

} // end of credit_render_invoiceselect()




/*
	CLASSES
*/




/*
	CLASS: CREDIT

	Provides functions for creating, editing and deleting credits
*/
class credit
{
	var $type;		// type of invoice - ar/ap
	var $id;		// ID of credit
	
	var $data;		// array for storage of all credit datsa
		
	var $credit_fields;	// array for storage of all credit fields with associated data
	

	var $obj_pdf;		// generated PDF object


	/*
		verify_credit

		Checks that the provided ID & type point to a valid credit

		Results
		0	Failure to find the credit
		1	Success - credit exists
	*/

	function verify_credit()
	{
		log_debug("inc_credit", "Executing verify_credit()");

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

	} // end of verify_credit


	/*
		check_lock

		Returns whether the credit is locked or not.

		Results
		0	Unlocked
		1	Locked
		2	Failure (fail safe by reporting lock)
	*/

	function check_lock()
	{
		log_debug("inc_credit", "Executing check_lock()");

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

		Checks if the credit note is able to be deleted or not and returns the lock status.

		Results
		0	Unlocked
		1	Locked
		2	Failure (fail safe by reporting lock)
	*/

	function check_delete_lock()
	{
		log_debug("inc_credit", "Executing check_delete_lock()");

		return $this->check_lock();

	}  // end of check_delete_lock




	/*
		load_data

		Loads the credit data from the MySQL database.

		Return Codes
		0	failure
		1	success
	*/
	function load_data()
	{
		log_debug("inc_credit", "Executing load_data()");
		
		// fetch credit information from DB.
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT * FROM account_". $this->type ." WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_debug("inc_credit", "No such credit note ". $this->id ." in account_". $this->type ."");
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

		Loads the credit note data for exporting for PDF or email or other purposes.

		Return Codes
		0	failure
		1	success
	*/
	function load_data_export()
	{
		log_debug("inc_credit", "Executing load_data_export()");


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
		$this->credit_fields["code_customer"] = $sql_customer_obj->data[0]["code_customer"]; 
		$this->credit_fields["customer_name"] = $sql_customer_obj->data[0]["name_customer"]; 

		$this->credit_fields["customer_contact"]	= $obj_sql_contact->data[0]["contact"];
		$this->credit_fields["customer_contact_email"]	= sql_get_singlevalue("SELECT detail AS value FROM customer_contact_records WHERE contact_id = '" .$obj_sql_contact->data[0]["id"]. "' AND type = 'email' LIMIT 1");
		$this->credit_fields["customer_contact_phone"]	= sql_get_singlevalue("SELECT detail AS value FROM customer_contact_records WHERE contact_id = '" .$obj_sql_contact->data[0]["id"]. "' AND type = 'phone' LIMIT 1");
		$this->credit_fields["customer_contact_fax"]	= sql_get_singlevalue("SELECT detail AS value FROM customer_contact_records WHERE contact_id = '" .$obj_sql_contact->data[0]["id"]. "' AND type = 'fax' LIMIT 1");

		$this->credit_fields["customer_address1_street"] = $sql_customer_obj->data[0]["address1_street"]; 
		$this->credit_fields["customer_address1_city"] = $sql_customer_obj->data[0]["address1_city"]; 
		$this->credit_fields["customer_address1_state"] = $sql_customer_obj->data[0]["address1_state"]; 
		$this->credit_fields["customer_address1_country"] = $sql_customer_obj->data[0]["address1_country"]; 

		if ($sql_customer_obj->data[0]["address1_zipcode"] == 0)
		{
			$sql_customer_obj->data[0]["address1_zipcode"] = "";
		}
		
		$this->credit_fields["customer_tax_number"] = $sql_customer_obj->data[0]["tax_number"]; 
		$this->credit_fields["customer_address1_zipcode"] = $sql_customer_obj->data[0]["address1_zipcode"]; 



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
		$this->credit_fields["company_name"] = $data_company["company_name"]; 
		
		$this->credit_fields["company_contact_email"] = $data_company["company_contact_email"]; 
		$this->credit_fields["company_contact_phone"] = $data_company["company_contact_phone"]; 
		$this->credit_fields["company_contact_fax"] = $data_company["company_contact_fax"]; 
		
		$this->credit_fields["company_address1_street"] = $data_company["company_address1_street"]; 
		$this->credit_fields["company_address1_city"] = $data_company["company_address1_city"]; 
		$this->credit_fields["company_address1_state"] = $data_company["company_address1_state"]; 
		$this->credit_fields["company_address1_country"] = $data_company["company_address1_country"]; 
		$this->credit_fields["company_address1_zipcode"] = $data_company["company_address1_zipcode"]; 
		
		if ($this->type == "ar")
		{
			$this->credit_fields["company_payment_details"] = $data_company["company_payment_details"]; 
		}

		/*
			Credit Data (exc items/taxes)
		*/
		
		$this->credit_fields["code_credit"] = $this->data["code_credit"]; 
		$this->credit_fields["code_invoice"] = sql_get_singlevalue("SELECT code_invoice as value FROM account_ar WHERE id='". $this->data["invoiceid"] ."' LIMIT 1");
		$this->credit_fields["code_ordernumber"] = $this->data["code_ordernumber"]; 
		$this->credit_fields["code_ponumber"] = $this->data["code_ponumber"]; 
		
		$this->credit_fields["date_trans"] = time_format_humandate($this->data["date_trans"]);  
		$this->credit_fields["amount"] = format_money($this->data["amount"]);  
		$this->credit_fields["amount_total"] = format_money($this->data["amount_total"]);
		$this->credit_fields["amount_currency"] = sql_get_singlevalue("SELECT value FROM config WHERE name='CURRENCY_DEFAULT_NAME'") ; 
		


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
		log_debug("inc_credit", "Executing prepare_set_defaults");

		if (empty($this->data["code_credit"]))
		{
			$this->prepare_code_credit();
		}

		if (empty($this->data["date_trans"]))
		{
			$this->data["date_trans"] = date("Y-m-d");
		}
		
		return 1;
	}


	/*
		prepare_code_credit

		Generate a code_credit value for the invoice - either using one supplied to the function, or otherwise
		by checking the DB and fetching a suitable code from there.

		If a user requests to use a code_credit that has already been allocated, the function will return failure.

		Values
		code_credit		(optional) Request a code_credit value to use

		Results
		0			failure
		1			success
	*/
	function prepare_code_credit($code_credit = NULL)
	{
		log_debug("inc_credit", "Executing prepare_code_credit($code_credit)");


		if ($code_credit)
		{
			// user has provided a code_credit
			// we need to verify that it is not already in use by any other invoice.
			
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM account_". $this->type ." WHERE code_credit='". $code_credit ."'";
		
			if ($this->data["id"])
				$sql_obj->string .= " AND id!='". $this->data["id"] ."'";
	
			// for AP invoices, the ID only need to be unique for the particular vendor we are working with, since
			// it's almost guaranteed that different vendors will use the same numbering scheme for their invoices
			if ($this->type == "ap")
				$sql_obj->string .= " AND vendorid='". $data["vendorid"] ."'";
			
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				log_debug("inc_credit", "Warning: The requested invoice code is already in use by another invoice");
				return 0;
			}

			unset($sql_obj);

			// save code_credit
			$this->data["code_credit"] = $code_credit;
		}
		else
		{
			// generate an invoice ID using the database
			$this->data["code_credit"] = config_generate_uniqueid("ACCOUNTS_CREDIT_NUM", "SELECT id FROM account_". $this->type ." WHERE code_credit='VALUE'");
		}
		
		return 1;
		
	} // end of prepare_code_credit





	/*
		action_create

		Create a new invoice.

		Results
		0	failure
		1	success
	*/
	function action_create()
	{
		log_debug("inc_credit", "Executing action_create()");
	
	
		// set any default field if they have been left blank
		$this->prepare_set_defaults();
		
		// create new invoice entry
		$sql_obj		= New sql_query;
		$sql_obj->string	= "INSERT INTO account_". $this->type ." (code_credit, date_create) VALUES ('".$this->data["code_credit"]."', '". date("Y-m-d") ."')";
		if (!$sql_obj->execute())
		{
			log_debug("inc_credit", "Failure whilst creating credit note entry.");
			return 0;
		}

		$this->id = $sql_obj->fetch_insert_id();

		unset($sql_obj);


		// call the update function to process the invoice now that we have an ID for the DB row
		if (!$this->action_update())
		{
			return 0;
		}



		log_debug("inc_credit", "Successfully created new credit note ". $this->id ."");

		return 1;
		
	} // end of action_create



	/*
		action_update

		Updates an existing credit note

		Results
		0	failure
		1	success
	*/
	function action_update()
	{
		log_debug("inc_credit", "Executing action_update()");

		// we must have an ID provided
		if (!$this->id)
		{
			log_debug("inc_credit", "No invoice ID supplied to action_update function");
			return 0;
		}

		// set any default field if they have been left blank
		$this->prepare_set_defaults();


		// fetch the original dest_account value - we will use it after we update the invoice details
		$this->data["dest_account_orig"]	= sql_get_singlevalue("SELECT dest_account as value FROM account_". $this->type ." WHERE id='". $this->id ."' LIMIT 1");

		if (isset($this->data["customerid"]))
		{
			$this->data["customerid_orig"]	= sql_get_singlevalue("SELECT customerid as value FROM account_". $this->type ." WHERE id='". $this->id ."' LIMIT 1");
		}
		else
		{
			$this->data["vendorid_orig"]	= sql_get_singlevalue("SELECT vendorid as value FROM account_". $this->type ." WHERE id='". $this->id ."' LIMIT 1");
		}


		/*
			Start SQL Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();



		/*
			Update the invoice details
		*/
			
		if ($this->type == "ap_credit")
		{
			@$sql_obj->string = "UPDATE `account_". $this->type ."` SET "
						."vendorid='". $this->data["vendorid"] ."', "
						."invoiceid='". $this->data["invoiceid"] ."', "
						."employeeid='". $this->data["employeeid"] ."', "
						."notes='". $this->data["notes"] ."', "
						."code_credit='". $this->data["code_credit"] ."', "
						."code_ordernumber='". $this->data["code_ordernumber"] ."', "
						."code_ponumber='". $this->data["code_ponumber"] ."', "
						."date_trans='". $this->data["date_trans"] ."', "
						."dest_account='". $this->data["dest_account"] ."' "
						."WHERE id='". $this->id ."' LIMIT 1";
		}
		else
		{
			@$sql_obj->string = "UPDATE `account_". $this->type ."` SET "
						."customerid='". $this->data["customerid"] ."', "
						."invoiceid='". $this->data["invoiceid"] ."', "
						."employeeid='". $this->data["employeeid"] ."', "
						."notes='". $this->data["notes"] ."', "
						."code_credit='". $this->data["code_credit"] ."', "
						."code_ordernumber='". $this->data["code_ordernumber"] ."', "
						."code_ponumber='". $this->data["code_ponumber"] ."', "
						."date_trans='". $this->data["date_trans"] ."', "
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

			To make it easy, we call the credit_items class and execute the action_update_ledger function to
			re-create all ledger entries.
		*/


		if ($this->data["dest_account_orig"] != $this->data["dest_account"])
		{
			log_debug("invoice", "dest_account has changed, calling action_update_ledger to update all the ledger transactions");
			
			// re-create all the ledger entries
			$credit_items			= New invoice_items;
			
			$credit_items->type_invoice	= $this->type;
			$credit_items->id_invoice	= $this->id;

			$credit_items->action_update_ledger();

			unset($credit_items);
		}



		/*
			Check for changes to the customer/vendor ID

			This is very important, if the customer/vendor is changed, we will need to remove the credit from them, and add to the correct customer/vendor.
		*/
		
		if (isset($this->data["customerid"]))
		{
			if ($this->data["customerid_orig"] != $this->data["customerid"])
			{
				$sql_obj->string = "UPDATE customers_credits SET id_customer='". $this->data["customerid"] ."' WHERE type='creditnote' AND id_custom='". $this->id ."' LIMIT 1";
				$sql_obj->execute();
			}
		}
		else
		{
			if ($this->data["vendorid_orig"] != $this->data["vendorid"])
			{
				$sql_obj->string = "UPDATE vendors_credits SET id_vendor='". $this->data["vendorid"] ."' WHERE type='creditnote' AND id_custom='". $this->id ."' LIMIT 1";
				$sql_obj->execute();
			}
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
		action_update_balance

		Applies the refunded amount against the customer's account as a credit which can then be used for paying invoices
		or refunded as a cash payment.

		Returns
		0	Failure
		#	ID of customer/vendor refund item
	*/
	function action_update_balance()
	{
		log_debug("inc_credit", "Executing action_update_balance()");


		/*
			Load Credit Note Details
		*/

		// we must have an ID provided
		if (!$this->id)
		{
			log_debug("inc_credit", "No invoice ID supplied to action_update_balance function");
			return 0;
		}
		if (!$this->data)
		{
			log_debug("inc_credit", "No credit data supplied to action_update_balance function");
			return 0;
		}




		/*
			Start SQL Transaction
		*/

		$sql_obj = New sql_query;
		$sql_obj->trans_begin();




		/*
			Delete and update
		*/

		if ($this->type == "ap_credit")
		{
			$sql_obj->string = "DELETE FROM vendors_credits WHERE id_vendor='". $this->data["vendorid"] ."' AND type='creditnote' AND id_custom='". $this->id ."' LIMIT 1";
			$sql_obj->execute();

			$sql_obj->string = "INSERT INTO vendors_credits (date_trans, type, amount_total, id_custom, id_employee, id_vendor, description) VALUES ('". $this->data["date_trans"] ."', 'creditnote', '". $this->data["amount_total"] ."', '". $this->id ."', '". $this->data["employeeid"] ."', '". $this->data["vendorid"] ."', '". $this->data["notes"] ."')";
			$sql_obj->execute();
		}
		else
		{
			$sql_obj->string = "DELETE FROM customers_credits WHERE id_customer='". $this->data["customerid"] ."' AND type='creditnote' AND id_custom='". $this->id ."' LIMIT 1";
			$sql_obj->execute();

			$sql_obj->string = "INSERT INTO customers_credits (date_trans, type, amount_total, id_custom, id_employee, id_customer, description) VALUES ('". $this->data["date_trans"] ."', 'creditnote', '". $this->data["amount_total"] ."', '". $this->id ."', '". $this->data["employeeid"] ."', '". $this->data["customerid"] ."', '". $this->data["notes"] ."')";
			$sql_obj->execute();
		}


		/*
			Commit
		*/
		
		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "inc_credit", "An error occured whilst attempting to update credit note. No changes have been made.");

			return 0;
		}
		else
		{
			$sql_obj->trans_commit();

			return 1;
		}
	

	} // end of action_update_balance


	/*
		action_lock

		Locks a credit note.

		Results
		0	failure
		1	success
	*/
	function action_lock()
	{
		log_debug("inc_credit", "Executing action_lock()");

		// we must have an ID provided
		if (!$this->id)
		{
			log_debug("inc_credit", "No credit note ID supplied to action_update function");
			return 0;
		}

		
		// update the lock status
		$sql_obj		= New sql_query;
		$sql_obj->string	= "UPDATE `account_". $this->type ."` SET locked='1' WHERE id='". $this->id ."' LIMIT 1";
		
		if (!$sql_obj->execute())
		{
			log_write("error", "inc_credit", "Unable to lock the credit note. No changes have been made.");
			return 0;
		}
	

		return 1;

	} // end of action_lock



	/*
		action_delete

		Deletes an existing credit note (assuming that it's not locked) and associated entry in
		customer/vendor credit pool.

		Results
		0	failure
		1	success
	*/
	function action_delete()
	{
		log_debug("inc_credits", "Executing action_delete()");

		// we must have an ID provided
		if (!$this->id)
		{
			log_debug("inc_credits", "No credit note ID to action_delete function");
			return 0;
		}


		/*
			Start SQL Transaction
		*/

		$sql_obj = New sql_query;
		$sql_obj->trans_begin();



		/*
			Delete Credit Note
		*/

		$sql_obj->string	= "DELETE FROM account_". $this->type ." WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();
		


		/*
			Delete Credit Items (aka Invoice Items)
		
			We do this by using the invoice_items::action_delete() function, since there are number of complex
			steps when deleting certain invoice items.
		*/

		$sql_items_obj		= New sql_query;
		$sql_items_obj->string	= "SELECT id FROM account_items WHERE invoicetype='". $this->type ."' AND invoiceid='". $this->id ."'";
		$sql_items_obj->execute();

		if ($sql_items_obj->num_rows())
		{
			$sql_items_obj->fetch_array();

			foreach ($sql_items_obj->data as $data_sql)
			{
				// delete each item one-at-a-time.
				$obj_credit_item			= New invoice_items;

				$obj_credit_item->type_invoice		= $this->type;
				$obj_credit_item->id_invoice		= $this->id;
				$obj_credit_item->id_item		= $data_sql["id"];
				$obj_credit_item->action_delete();

				unset($obj_credit_item);
			}
		}


		/*
			Delete Journal
		*/

		journal_delete_entire("account_". $this->type ."", $this->id);



		/*
			Delete transactions from ledger
			
			(Most transactions are deleted by the item deletion code, but tax, pay and AR/AP
			 ledger transactions need to be removed manually)
		*/

		$sql_obj->string	= "DELETE FROM account_trans WHERE (type='". $this->type ."' || type='". $this->type ."_tax') AND customid='". $this->id ."'";
		$sql_obj->execute();



		/*
			Delete customer/vendor credit allocations
		*/

		if ($this->type == "ap_credit")
		{
			$sql_obj->string = "DELETE FROM vendors_credits WHERE id_vendor='". $this->data["vendorid"] ."' AND type='creditnote' AND id_custom='". $this->id ."' LIMIT 1";
			$sql_obj->execute();
		}
		else
		{
			$sql_obj->string = "DELETE FROM customers_credits WHERE id_customer='". $this->data["customerid"] ."' AND type='creditnote' AND id_custom='". $this->id ."' LIMIT 1";
			$sql_obj->execute();
		}




		/*
			Commit
		*/

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "invoice", "An error occured whilst deleting the credit note. No changes have been made.");

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

		Generates a PDF of the credit note and saves it into memory at $this->obj_pdf->output.

		Results
		0	failure
		1	success
	*/
	function generate_pdf()
	{
		log_debug("inc_credits", "Executing generate_pdf()");
	

		// load data if required
		if (!is_array($this->credit_fields))
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
		$template_data = sql_get_singlerow("SELECT `template_type`, `template_file` FROM templates WHERE template_type IN('". $this->type ."_tex', '". $this->type ."_htmltopdf') AND active='1' LIMIT 1");
		//exit("<pre>".print_r($template_data, true)."</pre>");
		switch($template_data['template_type']) 
		{
			case $this->type .'_htmltopdf':
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
			
			case $this->type .'_tex':
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
			$template_file = "templates/latex/". $this->type ."";
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
			log_write("error", "inc_credits", "Unable to find template file $template_file, currently running in directory ". getcwd() .", fatal error.");
			return 0;
		}

		
		/*
			Company Data
		*/
		
		// company logo
		$this->obj_pdf->prepare_add_file("company_logo", "png", "COMPANY_LOGO", 0);
		
		
		
		
		// convert the credit_fields array into 
		foreach($this->credit_fields as $credit_field_key => $credit_field_value) {
			$this->obj_pdf->prepare_add_field($credit_field_key, $credit_field_value);
		}
		
		

		/*
			Fetch credit items (all credit items other than tax, are type == 'credit')
		*/

		// fetch invoice items
		$sql_items_obj			= New sql_query;
		$sql_items_obj->string		= "SELECT "
							."id, type, chartid, customid, quantity, units, amount, price, description "
							."FROM account_items "
							."WHERE invoiceid='". $this->id ."' "
							."AND invoicetype='". $this->type ."' "
							."AND type='credit' "
							."ORDER BY customid, chartid, description";
		$sql_items_obj->execute();
		$sql_items_obj->fetch_array();

		$structure_credititems		= array();

		foreach ($sql_items_obj->data as $itemdata)
		{
			$structure = array();
			
			$structure["info"]	= "CREDIT";
			$structure["quantity"]	= " ";
			$structure["group"]	= lang_trans("group_other");
			$structure["price"]	= "";
			$structure["description"]	= trim($itemdata["description"]);
			$structure["units"]		= $itemdata["units"];
			$structure["amount"]		= format_money($itemdata["amount"], 1);

			$structure_credititems[] = $structure;
		}
		
		//exit("<pre>".print_r($structure_credititems,true)."</pre>");
		$this->obj_pdf->prepare_add_array("credit_items", $structure_credititems);

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
	
		$this->obj_pdf->prepare_add_array("taxes", $structure_taxitems);


		/*
			Output PDF
		*/

		// perform string escaping for latex
		$this->obj_pdf->prepare_escape_fields();
		
		// fillter template data
		$this->obj_pdf->fillter_template_data();
		
		// fill template
		$this->obj_pdf->prepare_filltemplate();

		// Useful for debugging - shows the processed template lines BEFORE it is fed to the render engine
		//print "<pre>";
		//print_r($this->obj_pdf->processed);
		//print "</pre>";
		
		// generate PDF output
		$this->obj_pdf->generate_pdf();

	} // end of generate_pdf



	/*
		generate_email

		Generates all the fields needed for a credit email - this function fetches the text template, fills
		in all the details and returns a structured array.

		This function is used by the UI and the backend.

		Returns
		0		Failure
		array		Email Data
	*/

	function generate_email()
	{
		log_write("debug", "inc_credits", "Executing generate_email()");


		// load data if required
		if (!is_array($this->credit_fields))
		{
			$this->load_data();	
			$this->load_data_export();
		}


		/*
			restructure the invoice data into a form we can handle
		*/

		$credit_data_parts['keys']	= array_keys($this->credit_fields);
		$credit_data_parts['values']	= array_values($this->credit_fields);
		
		foreach($credit_data_parts['keys'] as $index => $key)
		{
			$credit_data_parts['keys'][$index] = "(".$key.")";
		} 	
		foreach($credit_data_parts['values'] as $index => $value)
		{
			$credit_data_parts['values'][$index] = trim($value);
		}



		/*
			Assemble the message data
		*/

		$email = array();


		// default to system rather than user
		$email["sender"]	= "system";
	
		// email to the accounts user
		$email["to"]		= $this->credit_fields["customer_contact"] ." <". $this->credit_fields["customer_contact_email"] .">";

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
		$email["subject"]	= "Credit Note ". $this->credit_fields["code_credit"];
		$email["message"]	= sql_get_singlevalue("SELECT value FROM config WHERE name IN('TEMPLATE_CREDIT_EMAIL') LIMIT 1");

		// replace fields in the template
		$email["message"]	= str_replace($credit_data_parts['keys'], $credit_data_parts['values'], $email["message"]);


		return $email;

	} // end of generate_email


	/*
		email_credit

		Sends a PDF version of the credit note via email and then records a copy
		of the email in the credit journal.

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
	function email_credit($email_sender, $email_to, $email_cc, $email_bcc, $email_subject, $email_message)
	{
		log_debug("inc_credits", "Executing email_credit([options])");


		// external dependency of Mail_Mime
		if (!@include_once('Mail.php'))
		{
			log_write("error", "inc_credits", "Unable to find Mail module required for sending email");
			return 0;
		}
		
		if (!@include_once('Mail/mime.php'))
		{
			log_write("error", "inc_credits", "Unable to find Mail::Mime module required for sending email");
			return 0;
		}


		/*
			Generate a PDF of the credit note and save to tmp file
		*/

		log_debug("inc_credits", "Generating credit note PDF for emailing");

		// generate PDF
		$this->generate_pdf();
		if (error_check())
		{
			return 0;
		}
		
		// save to a temporary file
		$tmp_filename = file_generate_name("/tmp/credit_". $this->data["code_credit"] ."", "pdf");

		if (!$fhandle = fopen($tmp_filename, "w"))
		{
			die("fatal error occured whilst writing to file $tmp_filename");
		}
			
		fwrite($fhandle, $this->obj_pdf->output);
		fclose($fhandle);



		/*
			Email the credit note
		*/
		
		log_debug("inc_credits", "Sending email");

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
		$mail_mime->addAttachment($tmp_filename, 'application/pdf');

		$mail_body	= $mail_mime->get();
	 	$mail_headers	= $mail_mime->headers($mail_headers);

		$mail		= & Mail::factory('mail', "-f ". $GLOBALS["config"]["COMPANY_CONTACT_EMAIL"]);
		$status 	= $mail->send($email_to, $mail_headers, $mail_body);

		if (PEAR::isError($status))
		{
			log_write("error", "inc_credits", "An error occured whilst attempting to send the email: ". $status->getMessage() ."");
		}
		else
		{
			log_write("debug", "inc_credits", "Successfully sent email invoice");


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

			log_write("debug", "inc_credits", "Uploading PDF and email details to journal...");


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

			if (!$file_obj->action_update_file($tmp_filename))
			{
				log_write("error", "inc_credits", "Unable to upload emailed PDF to journal entry");
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
		log_debug("inc_credits", "Performing cleanup - removing temporary file $tmp_filename");
		unlink($tmp_filename);


		// return
		if (error_check())
		{
			return 0;
		}
		else
		{
			return 1;
		}
	
	} // end 



} // END OF CREDIT CLASS



/*
	CLASS: CREDIT_REFUND

	Provides functions for creating, updating and deleting credit refunds made to customers or by vendors.
*/
class credit_refund
{
	var $id;		// ID of the refund
	var $type;		// "customer" or "vendor"
	var $data;		// array of returned data


	/*
		verify_id

		Checks that the provided ID & type point to a valid credit refund.

		Results
		0	Failure to find the refund
		1	Success - refund exists.
	*/

	function verify_id()
	{
		log_debug("inc_credit_refund", "Executing verify_id()");

		if ($this->id)
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM ". $this->type ."s_credits WHERE id='". $this->id ."' LIMIT 1";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				return 1;
			}
		}

		return 0;

	} // end of verify_id



	/*
		load_data

		Loads the credit data from the MySQL database.

		Return Codes
		0	failure
		1	success
	*/
	function load_data()
	{
		log_debug("inc_credit_refund", "Executing load_data()");
		
		// fetch credit information from DB.
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT * FROM ". $this->type ."s_credits WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_debug("inc_credit", "No such credit note refund ". $this->id ." in ". $this->type ."_credits");
			return 0;
		}
		else
		{
			$sql_obj->fetch_array();

			// save all the data into class variables
			$this->data = $sql_obj->data[0];

			// make amount positive
			$this->data["amount_total"]	= $this->data["amount_total"] * -1;

			unset($sql_obj);
		}

		return 1;
		
	} // end of load_data



	/*
		action_create

		Creates a new credit refund item.

		Results
		0	failure
		#	success - returns item ID
	*/
	function action_create()
	{
		log_debug("inc_credit_refund", "Executing action_create()");

		// create new credit table entry
		$sql_obj		= New sql_query;
		$sql_obj->string	= "INSERT INTO ". $this->type ."s_credits (date_trans, type, id_". $this->type .") VALUES ('".$this->data["date_trans"]."', 'refund', '". $this->data["id_customer"] ."')";

		if (!$sql_obj->execute())
		{
			log_debug("inc_credit", "Failure whilst creating credit refund entry.");
			return 0;
		}

		$this->id = $sql_obj->fetch_insert_id();

		unset($sql_obj);


		// call the update function to process the refund fully and generate ledger fields.
		if (!$this->action_update())
		{
			return 0;
		}


		log_debug("inc_credit_refund", "Successfully created new credit refund ". $this->id ."");

		return $this->id;
		
	} // end of action_create



	/*
		action_update

		Updates an existing credit refund, including ledger records.

		Results
		0	failure
		1	success
	*/
	function action_update()
	{
		log_debug("inc_credit_refund", "Executing action_update()");

		// we must have an ID provided
		if (!$this->id)
		{
			log_debug("inc_credit_refund", "No credit refund ID supplied to action_update function");
			return 0;
		}


		/*
			Start SQL Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();



		/*
			Update the Refund Details
		*/
			
		$sql_obj->string = "UPDATE `". $this->type ."s_credits` SET "
					."date_trans='". $this->data["date_trans"] ."', "
					."type='refund', "
					."amount_total='-". $this->data["amount_total"] ."', "
					."id_custom='0', "
					."id_employee='". $this->data["id_employee"] ."', "
					."id_". $this->type ."='". $this->data["id_". $this->type] ."', "
					."description='". $this->data["description"] ."' "
					."WHERE id='". $this->id ."' LIMIT 1";

		if (!$sql_obj->execute())
		{
			$sql_obj->trans_rollback();

			log_write("error", "inc_credit_refund", "Unable to update database with credit refund entry. No changes have been made.");

			return 0;
		}



		/*
			Update Ledger Records
		*/

		if ($this->type == "customer")
		{
			// delete existing records
			$sql_obj->string	= "DELETE FROM account_trans WHERE (type='ar_refund') AND customid='". $this->id ."'";
			$sql_obj->execute();

			// add new ledger records
			ledger_trans_add("credit", "ar_refund", $this->id, $this->data["date_trans"], $this->data["account_asset"], $this->data["amount_total"], 'CREDIT REFUND', $this->data["description"]);
			ledger_trans_add("debit", "ar_refund", $this->id, $this->data["date_trans"], $this->data["account_dest"], $this->data["amount_total"], 'CREDIT REFUND', $this->data["description"]);
		}
		else
		{
			// delete existing records
			$sql_obj->string	= "DELETE FROM account_trans WHERE (type='ap_refund') AND customid='". $this->id ."'";
			$sql_obj->execute();
			
			// add new ledger records
			ledger_trans_add("debit", "ap_refund", $this->id, $this->data["date_trans"], $this->data["account_asset"], $this->data["amount_total"], 'CREDIT REFUND', $this->data["description"]);
			ledger_trans_add("credit", "ap_refund", $this->id, $this->data["date_trans"], $this->data["account_dest"], $this->data["amount_total"], 'CREDIT REFUND', $this->data["description"]);
		}




		/*
			Commit
		*/
		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "inc_credit_refund", "An error occured whilst attempting to update credit refund. No changes have been made.");

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

		Deletes the selected credit refund, including removing the associated ledger items.

		Results
		0	failure
		1	success
	*/
	function action_delete()
	{
		log_debug("inc_credits_refund", "Executing action_delete()");

		// we must have an ID provided
		if (!$this->id)
		{
			log_debug("inc_credits_refund", "No credit refund ID to action_delete function");
			return 0;
		}


		/*
			Start SQL Transaction
		*/

		$sql_obj = New sql_query;
		$sql_obj->trans_begin();



		/*
			Delete Credit Refund
		*/

		$sql_obj->string	= "DELETE FROM ". $this->type ."s_credits WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();
		


		/*
			Delete Ledger Items
		*/

		if ($this->type == "customer")
		{
			$sql_obj->string	= "DELETE FROM account_trans WHERE (type='ar_refund') AND customid='". $this->id ."'";
			$sql_obj->execute();
		}
		else
		{
			$sql_obj->string	= "DELETE FROM account_trans WHERE (type='ap_refund') AND customid='". $this->id ."'";
			$sql_obj->execute();
		}


		/*
			Commit
		*/

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "invoice", "An error occured whilst deleting the credit refund. No changes have been made.");

			return 0;
		}
		else
		{
			$sql_obj->trans_commit();
			
			return 1;
		}
		
	} // end of action_delete

} // END OF CREDIT_REFUND CLASS

	
?>
