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
			print "<table width=\"100%\" class=\"invoice_summarybox_open\">";
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
				print "<table width=\"100%\" class=\"invoice_summarybox_closed\">";
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
				print "<table width=\"100%\" class=\"invoice_summarybox_open\">";
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

	This class provides all functions for managing invoices and invoice items, including
	adding/removing items, re-calculating taxes, creating new invoices and more.
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
		if ($this->action_update())
		{
			log_debug("invoice", "Successfully created new invoice ". $this->id ."");
			return 1;
		}
		
		return 0;
		
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


		// update the invoice details
		$sql_obj = New sql_query;
			
		if ($type == "ap")
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
			log_debug("invoice", "An error occured whilst attempting to update the invoice");
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
			log_debug("invoice", "Error: Problem occured whilst deleting invoice from acccount_". $this->type ."");
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
					log_debug("invoice", "Error: Problem occured whilst deleting invoice item option records");
				}
			}
		}


		// delete all the invoice items
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM account_items WHERE invoicetype='". $this->type ."' AND invoiceid='". $this->id ."'";
		
		if (!$sql_obj->execute())
		{
			$error = 1;
			log_debug("invoice", "Error: Problem occured whilst deleting invoice item");
		}
		
		// delete invoice journal entries
		journal_delete_entire("account_". $this->type ."", $this->id);


		// delete invoice transactions
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM account_trans WHERE (type='". $this->type ."' || type='". $this->type ."_tax' || type='". $this->type ."_pay') AND customid='". $this->id ."'";
		
		if (!$sql_obj->execute())
		{
			$error = 1;
			log_debug("invoice", "Error: Problem occured whilst deleting invoice transactions");
		}


		if ($error)
		{
			return 0;
		}
		
		return 1;
		
	} // end of action_delete


} // END OF INVOICE CLASS



?>
