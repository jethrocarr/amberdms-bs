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
	log_debug("inc_invoices", "invoice_render_summarybox($id, $type)");

	// fetch invoice information
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT code_invoice, amount_total, amount_paid FROM account_$type WHERE id='$id' LIMIT 1";
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
				print "<p>This invoice needs to have some items added to it using the links in the nav menu above.</p>";
				print "</td>";
			print "</tr>";
			print "</table>";
		}
		else
		{
			if ($sql_obj->data[0]["amount_paid"] == $sql_obj->data[0]["amount_total"])
			{
				print "<table width=\"100%\" class=\"table_highlight_info\">";
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
						print "<td>$". $sql_obj->data[0]["amount_total"] ."</td>";
					print "</tr>";
					
					print "<tr>";
						print "<td>Total Paid:</td>";
						print "<td>$". $sql_obj->data[0]["amount_paid"] ."</td>";
					print "</tr>";


					$amount_due = $sql_obj->data[0]["amount_total"] - $sql_obj->data[0]["amount_paid"];
					$amount_due = sprintf("%0.2f", $amount_due);

					print "<tr>";
						print "<td>Amount Due:</td>";
						print "<td>$". $amount_due."</td>";
					print "</tr>";
					
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
		$sql_obj->string	= "SELECT * FROM account_". $this->type ." WHERE id='". $this->id ."'";
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
			$this->data = $sql_obj->data;

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


		// if the customer/vendor has a default tax configured, then we need to add a tax item to the invoice.
		if ($this->type == "ap")
		{
			$defaulttax = sql_get_singlevalue("SELECT tax_default as value FROM vendors WHERE id='". $this->data["vendorid"] ."'");
		}
		else
		{
			$defaulttax = sql_get_singlevalue("SELECT tax_default as value FROM customers WHERE id='". $this->data["customerid"] ."'");
		}

		if ($defaulttax)
		{
			// add a tax item to this invoice
			$item_tax			= New invoice_items;
			$item_tax->id_invoice		= $this->id;
			$item_tax->type_invoice		= $this->type;
			$item_tax->type_item		= "tax";

			$itemdata			= array();
			$itemdata["customid"]		= $defaulttax;

			$item_tax->prepare_data($itemdata);

			if (!$item_tax->action_create())
			{
				return 0;
			}

			// note: normally we would generate ledger and total amounts here, but
			// because there are no other items at this stage, tax amount and all
			// totals will be equal to zero.
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

		// update the invoice details
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
		else
		{
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

		// delete all the item options
		$sql_item_obj		= New sql_query;
		$sql_item_obj->string	= "SELECT id FROM account_items WHERE invoicetype='". $this->type ."' AND invoiceid='". $this->id ."'";
		$sql_item_obj->execute();
		

		if ($sql_item_obj->num_rows())
		{
			$sql_item_obj->fetch_array();

			foreach ($sql_item_obj->data as $data)
			{
				$sql_obj		= New sql_query;
				$sql_obj->string	= "DELETE FROM account_items_options WHERE itemid='". $data["id"] ."'";
				
				if (!$sql_obj->execute())
				{
					$error = 1;
					log_write("error", "invoice", "Problem occured whilst deleting invoice item option records from DB");
				}
			}
		}


		// delete all the invoice items
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM account_items WHERE invoicetype='". $this->type ."' AND invoiceid='". $this->id ."'";
		
		if (!$sql_obj->execute())
		{
			$error = 1;
			log_write("error", "invoice", "Problem occured whilst deleting invoice item from DB");
		}
		
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

				$this->data["amount"]		= $data["amount"];
				$this->data["chartid"]		= $data["chartid"];
				$this->data["description"]	= $data["description"];
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




			case "tax":
				/*
					TAX ITEMS

					We need to either use the manual amounts provided, or calculate the tax amount.
				*/

				// fetch key information from form
				$this->data["customid"]		= $data["customid"];
				$this->data["manual_option"]	= $data["manual_option"];


				// fetch information about the tax - we need to know the account and taxrate
				$sql_tax_obj		= New sql_query;
				$sql_tax_obj->string	= "SELECT chartid, taxrate FROM account_taxes WHERE id='". $this->data["customid"] ."' LIMIT 1";
				$sql_tax_obj->execute();

				if (!$sql_tax_obj->num_rows())
				{
					$_SESSION["error"]["message"][] = "Unknown tax requested!";
					return 0;
				}
				else
				{
					$sql_tax_obj->fetch_array();
					
					$this->data["chartid"] = $sql_tax_obj->data[0]["chartid"];
					$this->data["taxrate"] = $sql_tax_obj->data[0]["taxrate"];
				}



				// calculate tax, either:
				//	1. manual amount provided
				//	2. automatic based on the percentage provided
				if ($this->data["manual_option"])
				{
					// 1. MANUAL AMOUNT
					
					// save manual value
					$this->data["amount"]	= $data["manual_amount"];

					// label it for the ledgers
					$this->data["description"] = "Manual tax calculation";
				}
				else
				{
					// 2. AUTOMATIC CALC
					
					// fetch total of billable items
					$amount	= sql_get_singlevalue("SELECT sum(amount) as value FROM `account_items` WHERE invoicetype='". $this->type_invoice ."' AND invoiceid='". $this->id_invoice ."' AND type!='tax'");

					// calculate taxable amount
					$this->data["amount"]	= $amount * ($this->data["taxrate"] / 100);
					$this->data["amount"]	= sprintf("%0.2f", $this->data["amount"]);

					
					// label it for the ledgers
					$this->data["description"] = "Automatic tax calculation at rate of ". $this->data["taxrate"] ."%";
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
		1		success
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
				log_debug("invoice_items", "Successfully created new invoice item ". $this->id_item ."");
				return 1;
			}
		}

		return 0;
	} // end of action_create




	/*
		action_update

		Updates the invoice item information.


		Returns
		0		failure
		1		success
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


		// flag tax item as manual if required
		if ($this->type_item == "tax" && $this->data["manual_option"] == "on")
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "INSERT INTO account_items_options (itemid, option_name, option_value) VALUES ('". $this->id_item ."', 'TAX_CALC_MODE', 'manual')";
			$sql_obj->execute();
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


		// options for time items
		if ($this->type_item == "time")
		{
			// create options entry for the timegroupid
			$sql_obj		= New sql_query;
			$sql_obj->string	= "INSERT INTO account_items_options (itemid, option_name, option_value) VALUES ('". $this->id_item ."', 'TIMEGROUPID', '". $this->data["timegroupid"] ."')";
			$sql_obj->execute();

			// update the time_group with the status, invoiceid and itemid
			$sql_obj		= New sql_query;
			$sql_obj->string	= "UPDATE time_groups SET invoiceid='". $this->id_invoice ."', invoiceitemid='". $this->id_item ."', locked='1' WHERE id='". $this->data["timegroupid"] ."'";
			$sql_obj->execute();
		}

	
		return 1;	
		
	} // end of action_update
	



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
		action_update_tax
		
		This function regenerates the taxes for any auto-matically calculated tax items on this invoice.

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


		// fetch taxable amount
		$amount		= sql_get_singlevalue("SELECT sum(amount) as value FROM `account_items` WHERE invoicetype='". $this->type_invoice ."' AND invoiceid='". $this->id_invoice ."' AND type!='tax' AND type!='payment'");


		/*
			Run though all the tax items on this invoice
		*/
		$sql_items_obj		= New sql_query;
		$sql_items_obj->string	= "SELECT id, customid, amount FROM account_items WHERE invoicetype='". $this->type_invoice ."' AND invoiceid='". $this->id_invoice ."' AND type='tax'";
		$sql_items_obj->execute();

		if ($sql_items_obj->num_rows())
		{
			$sql_items_obj->fetch_array();

			foreach ($sql_items_obj->data as $data)
			{
				// determine if we need to calculate tax for this item
				$mode = sql_get_singlevalue("SELECT option_value AS value FROM account_items_options WHERE itemid='". $data["id"] ."' AND option_name='TAX_CALC_MODE' LIMIT 1");

				if (!$mode || $mode != "manual")
				{
					/*
						This item is an automatically calculated tax item.
						
						Fetch taxrate information, calculate new tax amount and update the item
					*/
				
					// fetch required information
					$sql_tax_obj		= New sql_query;
					$sql_tax_obj->string	= "SELECT taxrate, chartid FROM account_taxes WHERE id='". $data["customid"] ."' LIMIT 1";
					$sql_tax_obj->execute();

					if ($sql_tax_obj->num_rows())
					{
						$sql_tax_obj->fetch_array();
					}

				
					// calculate taxable amount
					$amount = $amount * ($sql_tax_obj->data[0]["taxrate"] / 100);
					$amount = sprintf("%0.2f", $amount);

					// update the item with the new amount
					$sql_obj		= New sql_query;
					$sql_obj->string	= "UPDATE account_items SET amount='$amount' WHERE id='". $data["id"] ."'";
					$sql_obj->execute();


					// note - the invoice_items_update ledger function should now be called to update the ledger

				}
			}
		}
		else
		{
			log_debug("invoice_items", "No tax items belonging to this invoice to process.");
		}

		return 1;

	} // end of action_update_tax




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
		

		/*
			Unlock time_groups if required
		*/
		if ($this->type_item == "time")
		{
			$groupid = sql_get_singlevalue("SELECT option_value as value FROM account_items_options WHERE itemid='". $this->id_item ."' AND option_name='TIMEGROUPID'");
		
			$sql_obj		= New sql_query;
			$sql_obj->string	= "UPDATE time_groups SET invoiceid='0', invoiceitemid='0', locked='0' WHERE id='$groupid'";
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


		return 1;
	}

	
	
} // END OF INVOICE_ITEMS CLASS




?>
