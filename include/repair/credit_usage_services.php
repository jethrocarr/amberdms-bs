#!/usr/bin/php
<?php
/*
	include/repair/credit_services_usage.php

	Will take the specific service type and date and will create credit for all invoices generated on the specified
	date that have usage of the specific item.

	Will then uncheck the usage as having been billed in services_customers_periods to force the service usage to be
	rebilled on the following month's invoices.
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
	$option_type		= NULL;


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


	if (empty($argv[3]))
	{
		die("Service Type must be set.\n");
	}

	if (preg_match('/^\S\S*$/', $argv[3]))
	{
		$option_type	= $argv[3];
		$option_type_id	= sql_get_singlevalue("SELECT id as value FROM service_types WHERE name='$option_type' LIMIT 1");

		if (!$option_type_id)
		{
			die("Service type $option_type is unknown\n");
		}
	}
	else
	{
		die("You must provide a service type\n");
	}


	log_write("notification", "repair", "Executing usage charge rollback for invoices generated $option_date for service type $option_type (ID $option_type_id)");




	/*
		Fetch IDs of all sercices with selected option type
	*/

	$option_services		= array();

	$obj_sql_service		= New sql_query;
	$obj_sql_service->string	= "SELECT id FROM services WHERE typeid='". $option_type_id ."'";
	$obj_sql_service->execute();

	if ($obj_sql_service->num_rows())
	{
		$obj_sql_service->fetch_array();

		foreach ($obj_sql_service->data as $data)
		{
			$option_services[] = $data["id"];
		}
	}

	unset($obj_sql_service);

	log_write("notification", "repair", "Returned ID of matching services, array of ". format_arraytocommastring($option_services, NULL) ."");


	/*
		Start Transaction
	*/

	$obj_sql_trans = New sql_query;
	$obj_sql_trans->trans_begin();



	/*
		Fetch AR Invoices for selected period
	*/

	$obj_sql_ar		= New sql_query;
	$obj_sql_ar->string	= "SELECT id, customerid, code_invoice, dest_account, amount_total, amount_paid FROM account_ar WHERE date_trans='$option_date'";
	$obj_sql_ar->execute();

	if ($obj_sql_ar->num_rows())
	{
		$obj_sql_ar->fetch_array();

		foreach ($obj_sql_ar->data as $data_ar)
		{
			$invoice_items = array();	// store item details

			/*
				Fetch Invoice Items
			*/

			$obj_sql_items		= New sql_query;
			$obj_sql_items->string	= "SELECT id, customid, chartid, amount, description FROM account_items WHERE invoiceid='". $data_ar["id"] ."' AND invoicetype='ar' AND type='service_usage'";
			$obj_sql_items->execute();

			if ($obj_sql_items->num_rows())
			{
				$obj_sql_items->fetch_array();

				/*
					For each item, check the service type and whether it is one of the items
					that we want to credit.
				*/
				foreach ($obj_sql_items->data as $data_item)
				{
					if (in_array($data_item["customid"], $option_services))
					{
						// item is one of the target services, add details to array
						log_write("debug", "repair", "Invoice ID #". $data_ar["id"] .", (". $data_ar["code_invoice"] .") item ID #". $data_item["id"] ." is a valid service usage item to refund.");

						// add invoice items
						$invoice_items_tmp = array();

						$invoice_items_tmp["id"]		= $data_item["id"];
						$invoice_items_tmp["customid"]		= $data_item["customid"];
						$invoice_items_tmp["chartid"]		= $data_item["chartid"];
						$invoice_items_tmp["amount"]		= $data_item["amount"];
						$invoice_items_tmp["description"]	= $data_item["description"];

						// add to array
						$invoice_items[] = $invoice_items_tmp;
					}
				}

			} // end of AR invoice items loop



			/*
				If any items matched, we should create a credit note and add the items as credits
			*/

			if (!empty($invoice_items))
			{
				/*
					Create Credit Note

					We have all the information needed for the credit note from the invoice.
				*/

				$credit		= New credit;
				$credit->type	= "ar_credit";

				$credit->prepare_set_defaults();

				$credit->data["invoiceid"]	= $data_ar["id"];
				$credit->data["customerid"]	= $data_ar["customerid"];
				$credit->data["employeeid"]	= "0";
				$credit->data["date_trans"]	= date("Y-m-d");
				$credit->data["dest_account"]	= $data_ar["dest_account"];
				$credit->data["notes"]		= "Automatically generated credit by repair process to cover service usage refund of invoice ". $data_ar["code_invoice"] ."";


				// create a new credit
				if ($credit->action_create())
				{
					log_write("notification", "repair", "Credit note successfully created");
					journal_quickadd_event("account_ar_credit", $credit->id, "Credit Note successfully created");
				}
				else
				{
					log_write("error", "repair", "An unexpected fault occured whilst attempting to create the credit note");
				}


				/*
					Add Items

					We loop through each selected item and for each item, we create an appropiate credit note item.
				*/


				foreach ($invoice_items as $data_item)
				{
					// create credit item
					$item			= New invoice_items;
		
					$item->id_invoice	= $credit->id;

					$item->type_invoice	= "ar_credit";
					$item->type_item	= "credit";

					// set item details
					$data = array();
					$data["amount"]		= $data_item["amount"];
					$data["price"]		= $data_item["amount"];
					$data["chartid"]	= $data_item["chartid"];
					$data["description"]	= "Credit For: ". $data_item["description"];


					// fetch taxes for selected item
					$sql_tax_obj		= New sql_query;
					$sql_tax_obj->string	= "SELECT taxid FROM services_taxes WHERE serviceid='". $data_item["customid"]."'";
					$sql_tax_obj->execute();

					if ($sql_tax_obj->num_rows())
					{
						$sql_tax_obj->fetch_array();

						foreach ($sql_tax_obj->data as $data_tax)
						{
							$sql_cust_tax_obj		= New sql_query;
							$sql_cust_tax_obj->string	= "SELECT id FROM customers_taxes WHERE customerid='". $credit->data["customerid"] ."' AND taxid='". $data_tax["taxid"] ."'";
							$sql_cust_tax_obj->execute();

							if ($sql_cust_tax_obj->num_rows())
							{
								$data["tax_". $data_tax["taxid"] ] = "on";
							}
						}
					}

					unset($sql_tax_obj);

	
					if (!$item->prepare_data($data))
					{
						log_write("error", "process", "An error was encountered whilst processing supplied data.");
					}
					else
					{
						$item->action_create();
						$item->action_update();

					}

					unset($data);

				} // end of items loop



				/*
					Re-calculate Credit Note Totals
				*/

				$item->action_update_tax();
				$item->action_update_total();
				$item->action_update_ledger();

				// finsihed with credit items
				unset($item);



				/*
					Apply Credit Note against the invoice if it hasn't been paid in full.
				*/

				$amount_invoice = array();

				if ($data_ar["amount_total"] != $data_ar["amount_paid"])
				{
					// determine amount owing
					$amount_invoice["owing"]	= $data_ar["amount_total"] - $data_ar["amount_paid"];

					if ($amount_invoice["owing"] <= 0)
					{
						// nothing todo
						log_write("notification", "repair", "Ignoring overpaid invoice ". $data_ar["code_invoice"] ." and assigning credit note to customer account/pool instead");
					}
					else
					{
						// determine credit amount
						$amount_invoice["credit"] = sql_get_singlevalue("SELECT amount_total as value FROM account_ar_credit WHERE id='". $credit->id ."' LIMIT 1");

						if ($amount_invoice["credit"] > $amount_invoice["owing"])
						{
							// pay the amount owing which is less than the credit
							$amount_invoice["creditpay"] = $amount_invoice["owing"];
						}
						else
						{
							// customer owes more than the credit is for, make credit payment amount maximum
							$amount_invoice["creditpay"] = $amount_invoice["credit"];
						}


						// make credit payment against the invoice
						$item			= New invoice_items;
			
						$item->id_invoice	= $data_ar["id"];

						$item->type_invoice	= "ar";
						$item->type_item	= "payment";

						// set item details
						$data = array();
						$data["date_trans"]	= date("Y-m-d");
						$data["amount"]		= $amount_invoice["creditpay"];
						$data["chartid"]	= "credit";
						$data["source"]		= "CREDITED FUNDS (AUTOMATIC)";
						$data["description"]	= "Credit from credit note ". $credit->data["code_credit"] ." for service usage charge correction";

						if (!$item->prepare_data($data))
						{
							log_write("error", "process", "An error was encountered whilst processing supplied data for credit payment to invoice");
						}
						else
						{
							// create & update payment item
							$item->action_create();
							$item->action_update();

							// update invoice totals & ledger
							$item->action_update_tax();
							$item->action_update_total();
							$item->action_update_ledger();

							log_write("notification", "repair", "Applied credit of ". $amount_invoice["creditpay"] ."");
						}

						unset($item);



					} // end if credit payment made


				} // end if partial paid invoice
				else
				{
					log_write("notification", "repair", "Credited invoice ". $data_ar["code_invoice"] ." has already been paid in full, assigning credit note to customer's credit pool for future use.");
				}




				/*
					Email PDF credit notes and message.
				*/


				if ($GLOBALS["config"]["ACCOUNTS_INVOICE_AUTOEMAIL"] == 1 || $GLOBALS["config"]["ACCOUNTS_INVOICE_AUTOEMAIL"] == "enabled")
				{
					$email = $credit->generate_email();

					$credit->email_credit($email["sender"], $email["to"], $email["cc"], $email["bcc"], $email["subject"], $email["message"]);
				}
				else
				{
					log_write("notification", "repair", "No credit note email sent, ACCOUNTS_INVOICE_AUTOEMAIL is disabled.");
				}

				// unset the credit note
				unset($credit);


				/*
					Flag the refunded usage periods for re-billing.

					Now that we have refunded the usage on the selected invoice, we should then flag any service periods
					of the same service type and invoice ID, to cause the usge to be rebilled in the next service billing month.
				*/

				// fetch id_service_customer values from services where customer matches invoice
				$obj_sql_cust		= New sql_query;
				$obj_sql_cust->string	= "SELECT id FROM services_customers WHERE customerid='". $data_ar["customerid"] ."' AND serviceid IN (". format_arraytocommastring($option_services, NULL) .")";
				$obj_sql_cust->execute();

				if ($obj_sql_cust->num_rows())
				{
					$obj_sql_cust->fetch_array();

					foreach ($obj_sql_cust->data as $data_cust)
					{
						// update any periods for this customer-service which have the ID of the selected invoice as
						// the usage period invoice.
						//
						// these usage periods will then be re-invoiced at the next service invoicing run.
						//

						$obj_sql_period			= New sql_query;
						$obj_sql_period->string		= "UPDATE services_customers_periods SET invoiceid_usage='0', rebill='1' WHERE invoiceid_usage='". $data_ar["id"] ."' AND id_service_customer='". $data_cust["id"] ."'";
						$obj_sql_period->execute();

					}
					
					log_write("notification", "repair", "Flagged services for customer ". $data_ar["customerid"] ." to bill for usage periods.");
				}
				else
				{
					log_write("warning", "repair", "No usage periods found to flag for rebilling for customer ". $data_ar["customerid"] .", possibly the service has been deleted?");
				}

				unset($obj_sql_cust);


			} // if creditable items exist on the selected invoice


			if (error_check())
			{
				// there was an error, do not continue processing invoices.
				continue;
			}
		}

	} // end of AR invoice loop



	/*
		Close Transaction
	*/

	if (error_check())
	{
		// rollback/failure
		log_write("error", "repair", "An error occured whilst executing, rolling back DB changes");
		
		$obj_sql_trans->trans_rollback();
	}
	else
	{
		// commit
		log_write("notification", "repair", "Successful execution, applying DB changes");

		$obj_sql_trans->trans_commit();
	}


} // end of page_execute()


?>
