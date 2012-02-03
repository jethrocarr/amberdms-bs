#!/usr/bin/php
<?php
/*
	include/repair/send_all_unsent_invoices.php

	Will run through all invoices dated with the specified date and if they haven't
	been sent and are for more than $0, will send them out to the customer.
*/


// custom includes
require("../accounts/inc_ledger.php");
require("../accounts/inc_invoices.php");
require("../accounts/inc_credits.php");
require("../services/inc_services.php");
require("../customers/inc_customers.php");



function page_execute($argv)
{
	/*
		Input Options
	*/

	$option_date		= NULL;


	if (empty($argv[2]))
	{
		die("You must provide a date option in form of YYYY-MM-DD\n");
	}

	if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $argv[2]))
	{
		$option_date = $argv[2];
	}
	else
	{
		die("You must provide a date option in form of YYYY-MM-DD - wrong format supplied\n");
	}


	/*
		Respect Configuration
	*/

	if ($GLOBALS["config"]["EMAIL_ENABLE"] != "enabled")
	{
		die("The configuration option EMAIL_ENABLE is disabled - you need to enable emailing before proceeding with this script");
	}


	/*
		Fetch all invoices for the period
	*/

	$obj_invoice_sql		= New sql_query;
	$obj_invoice_sql->string	= "SELECT id, code_invoice, date_sent, sentmethod FROM account_ar WHERE date_trans='$option_date'";
	$obj_invoice_sql->execute();

	if ($obj_invoice_sql->num_rows())
	{
		$obj_invoice_sql->fetch_array();

		foreach ($obj_invoice_sql->data as $data_invoice)
		{
			log_write("debug", "script", "Processing invoice ". $data_invoice["code_invoice"] ."");


			if ($data_invoice["sentmethod"])
			{
				// already sent
				log_write("debug", "script", "Invoice has already been sent on ". $data_invoice["data_sent"] .", not re-sending");
			}
			else
			{
				// never has been sent
				log_write("debug", "script", "Invoice has never been sent, preparing to send via email.");

				// load completed invoice data
				$invoice	= New invoice;
				$invoice->id	= $data_invoice["id"];
				$invoice->type	= "ar";

				$invoice->load_data();
				$invoice->load_data_export();

				if ($invoice->data["amount_total"] > 0)
				{
					// generate an email
					$email = $invoice->generate_email();

					// send email
					if ($invoice->email_invoice("system", $email["to"], $email["cc"], $email["bcc"], $email["subject"], $email["message"]))
					{
						// complete
						log_write("notification", "script", "Invoice ". $data_invoice["code_invoice"] ." has been emailed to customer (". $email["to"] .")");
					}
					else
					{
						// failure
						log_write("error", "script", "An error occured whilst attempting to send invoice ". $data_invoice["code_invoice"] ." to ". $email["to"] ."");
					}
				}
				else
				{
					// complete - invoice is for $0, so don't want to email out
					log_write("notification", "script", "Invoice ". $data_invoice["code_invoice"] ." has not been emailed to the customer due to invoice being for $0.");
				}

				unset ($invoice);

			} // end of invoice needs sending

		} // end of invoice loop

	}
	else
	{
		log_write("notification", "script", "There are no invoices for the supplied date period");
	}

} // end of page_execute

