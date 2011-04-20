#!/usr/bin/php
<?php
/*
	include/cron/orders.php

	Checks customers for orders and generates invoices for them if needed.

	This script is executed on a daily basis, but only runs if it is the end
	of the month and if the ORDERS_BILL_ENDOFMONTH option is enabled.

	Note that this script should be executed *after* the service billing script, since
	some customers may have their orders added to their services bills.
*/


// custom includes
require("../accounts/inc_invoices.php");
require("../accounts/inc_invoices_items.php");
require("../customers/inc_customers.php");

function page_execute()
{

	/*
		Check Configuration
	*/
	if (!empty($GLOBALS["config"]["ORDERS_BILL_ENDOFMONTH"]))
	{
		/*
			Check that today is the last day of the month
		*/
		if (time_calculate_monthdate_last( date("Y-m-d") ) == date("Y-m-d"))
		{
			log_write("notification", "cron_orders", "Today is the end of the month, time to process customer orders and convert into invoices.");


			/*
				Fetch all the customer ID for customers who currently have order items - no point going through
				*all* customers, only need to do those with items.
			*/

			$sql_customer_obj		= New sql_query;
			$sql_customer_obj->string	= "SELECT id_customer FROM customers_orders GROUP BY id_customer";
			$sql_customer_obj->execute();

			if ($sql_customer_obj->num_rows())
			{
				$sql_customer_obj->fetch_array();

				foreach ($sql_customer_obj->data as $data_customer)
				{
					/*
						Execute order processing for customer
					*/


					// generate the invoice
					$obj_customer			= New customer_orders;;
					$obj_customer->id		= $data_customer["id_customer"];
					$obj_customer->load_data();

					$invoiceid = $obj_customer->invoice_generate();

					// send the PDF (if desired)
					if ($GLOBALS["config"]["ACCOUNTS_INVOICE_AUTOEMAIL"])
					{
						$invoice	= New invoice;
						$invoice->id	= $invoiceid;
						$invoice->type	= "ar";

						$invoice->load_data();
						$invoice->load_data_export();

						if ($invoice->data["amount_total"] > 0)
						{
							// generate an email
							$email = $invoice->generate_email();

							// send email
							$invoice->email_invoice("system", $email["to"], $email["cc"], $email["bcc"], $email["subject"], $email["message"]);

							// complete
							log_write("notification", "cron_orders", "Invoice ". $invoice->data["code_invoice"] ." has been emailed to customer (". $email["to"] .")");
						}
						else
						{
							// complete - invoice is for $0, so don't want to email out
							log_write("notification", "cron_orders", "Invoice ". $invoice->data["code_invoice"] ." has not been emailed to the customer due to invoice being for $0.");
						}
						
						unset ($invoice);

					} // end if send PDF

				}
			}


			log_write("notification", "cron_orders", "Completed processing of orders, total of ". $sql_customer_obj->num_rows ." affected");
		}
		else
		{
			log_write("notification", "cron_orders", "Not processing orders, waiting until the end of the month");
		}
	}
	else
	{
		log_write("notification", "cron_orders", "Not processing monthly orders, ORDERS_BILL_ENDOFMONTH option is disabled");
	}

} // end of page_execute()


?>
