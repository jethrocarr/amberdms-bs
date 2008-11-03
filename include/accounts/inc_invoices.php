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

	This function will generate a unique invoice ID, by taking the current value from
	ACCOUNTS_$type_INVOICENUM and then making sure it has not already been used.

	Once a unique invoiceid has been determined, the system will update the ACCOUNTS_$type_INVOICENUM
	value so that no other invoice will take it.

	Call this function just prior to inserting a new invoice into the database.

	Values
	type	Either "ar" or "ap".

	Returns
	#	invoice ID in a string.
*/
function invoice_generate_invoiceid($type)
{
	log_debug("inc_invoices", "Executing invoice_generate_invoiceid($type)");
	
	$type_uc = strtoupper($type);
	
	$invoiceid	= 0;
	$invoicenum	= sql_get_singlevalue("SELECT value FROM config WHERE name='ACCOUNTS_". $type_uc ."._INVOICENUM'");

	if (!$invoicenum)
		die("Unable to fetch ACCOUNTS_". $type_uc ."_INVOICENUM value from config database");

	while ($invoiceid == 0)
	{
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_$type WHERE code_invoice='$invoicenum'";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			// invoicenumber already taken, increment and rety
			$invoicenum++;
		}
		else
		{
			// found an avaliable invoice number
			$invoiceid = $invoicenum;


			// update the DB
			$invoicenum++;
			$sql_obj		= New sql_query;
			$sql_obj->string	= "UPDATE config SET value='$invoicenum' WHERE name='ACCOUNTS_". $type_uc ."_INVOICENUM'";
			$sql_obj->execute();
		}
	}

	return $invoiceid;
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



?>
