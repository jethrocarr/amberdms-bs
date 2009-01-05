#!/usr/bin/php
<?php
/*
	include/cron/auditlocking.php

	This script is called daily and performs locking for:
	* invoices (any invoice fully paid more than ACCOUNTS_INVOICE_LOCK days ago)
	* GL transactions (any transation made more than ACCOUNTS_GL_LOCK days ago)
	* journals (any journal posted more than JOURNAL_LOCK days ago)
	* time entries (any more than TIMESHEET_LOCK days ago)

	Locking is irreversable but helps prevent unwanted changes to completed invoices and prevents adjustments
	being made to old journal postings.

	Locking is optional - if the lock day variables are set to 0, then nothing will be locked.

	
*/

// includes
require("../config.php");
require("../amberphplib/main.php");


print "Started Audit Locking Cron Job\n";


/*
	Lock paid invoices 

	Note: If ACCOUNTS_INVOICE_LOCK is set to 0, then locking is disabled.
*/

print "Checking for invoices to lock...\n";

auditlocking_invoices("ar");
auditlocking_invoices("ap");



/*
	Lock GL transactions
*/

print "Checking for GL transactions...\n";


// fetch number of days to perform locking for
$lockdays = sql_get_singlevalue("SELECT value FROM config WHERE name='ACCOUNTS_GL_LOCK'");

if ($lockdays)
{
	// calculate locking date
	$locking_date = date("Y-m-d", mktime() - (86400 * $lockdays));

	// fetch all GL transactions older than $locking_date
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id, code_gl FROM account_gl WHERE locked='0' AND date_trans < '$locking_date'";
	$sql_obj->execute();

	if ($sql_obj->num_rows())
	{
		$sql_obj->fetch_array();

		foreach ($sql_obj->data as $data_trans)
		{
			// lock the transaction
			print "Locked GL transaction ". $data_trans["code_gl"] ."\n";

			$sql_obj		= New sql_query;
			$sql_obj->string	= "UPDATE account_gl SET locked='1' WHERE id='". $data_trans["id"] ."' LIMIT 1";
			$sql_obj->execute();
		}
		
	} // end of loop through transactions
	
} // end of lockdays




/*
	Lock journal posts
*/

print "Locking journal posts\n";

// fetch number of days to perform locking for
$lockdays = sql_get_singlevalue("SELECT value FROM config WHERE name='JOURNAL_LOCK'");

if ($lockdays)
{
	// calculate locking timestamp
	$locking_time = mktime() - (86400 * $lockdays);


	// lock any journal entries older than $locking_time
	$sql_obj		= New sql_query;
	$sql_obj->string	= "UPDATE journal SET locked='1' WHERE locked='0' AND timestamp < '$locking_time'";
	$sql_obj->execute();
	
} // end of lockdays


/*
	Lock Time Entries
*/

print "Locking timesheets/time entries\n";

// fetch number of days to perform locking for
$lockdays = sql_get_singlevalue("SELECT value FROM config WHERE name='TIMESHEET_LOCK'");

if ($lockdays)
{
	// calculate locking date
	$locking_date = date("Y-m-d", mktime() - (86400 * $lockdays));

	// lock any time entries older than $locking_date
	$sql_obj		= New sql_query;
	$sql_obj->string	= "UPDATE timereg SET locked='1' WHERE date < '$locking_date'";
	$sql_obj->execute();
	
} // end of lockdays



// complete
print "Audit Locking Complete\n";
exit(0);



////// FUNCTIONS ///////




/*
	auditlocking_invoices

	Parameters
	type		Invoice type - "ar" or "ap"

	Results
	0		failure
	1		success
*/
function auditlocking_invoices($type)
{
	log_debug("auditlocking", "Executing auditlocking_invoices($type)");

	// fetch number of days to perform locking for
	$lockdays = sql_get_singlevalue("SELECT value FROM config WHERE name='ACCOUNTS_INVOICE_LOCK'");

	if ($lockdays)
	{
		// fetch all fully paid, unlocked invoices
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id, code_invoice FROM account_$type WHERE amount_total=amount_paid AND locked='0'";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$sql_obj->fetch_array();

			foreach ($sql_obj->data as $data_invoice)
			{
				// hold the highest payment timestamp here - we then run through all the payment items to find the one with the most recent date.
				$timestamp = 0;
				
				// fetch all the payment items for this invoice
				$sql_item_obj		= New sql_query;
				$sql_item_obj->string	= "SELECT id FROM account_items WHERE invoiceid='". $data_invoice ."' AND invoicetype='$type' AND type='payment'";
				$sql_item_obj->execute();
				
				if ($sql_item_obj->num_rows())
				{
					$sql_item_obj->fetch_array();

					foreach ($sql_item_obj->data as $data_item)
					{
						// fetch only the latest payment date
						$sql_date_obj		= New sql_query;
						$sql_date_obj->string	= "SELECT option_value FROM account_items_options WHERE option_name='DATE_TRANS' AND itemid='". $data_item["id"] ."' ORDER BY option_value DESC LIMIT 1";
						$sql_date_obj->execute();
						$sql_date_obj->fetch_array();

						// convert date to timestamp
						$timestamp_tmp = time_date_to_timestamp($data_date["option_value"]);
					
						if ($timestamp_tmp > $timestamp)
						{
							$timestamp = $timestamp_tmp;
						}
					}
				
				}

				// if the date is older than (today - ACCOUNTS_INVOICE_LOCK), then lock the invoice
				if ((mktime() - (86400 * $lockdays)) > $timestamp)
				{
					// lock invoice
					print "Locked $type invoice ". $data_invoice["code_invoice"] ."\n";
					$sql_obj		= New sql_query;
					$sql_obj->string	= "UPDATE account_$type SET locked='1' WHERE id='". $data_invoice["id"] ."' LIMIT 1";
					$sql_obj->execute();
				}
				
			}
			
		} // end of loop through invoices
		
	} // end of lockdays

} // end of auditlocking_invoices




?>
