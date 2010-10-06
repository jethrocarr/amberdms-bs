<?php
/*
	accounts/ar/account-statemens-process.php

	access: accounts_ar_write
	
	Sends email to customers with selected overdue invoices
*/

// includes
require("../../include/config.php");
require("../../include/amberphplib/main.php");

// custom includes
//require("../../include/accounts/inc_invoices.php");
require("../../include/accounts/inc_invoices_process.php");


if (user_permissions_get('accounts_ar_write'))
{
	//get num records
	$num_records = @security_form_input_predefined("int", "num_records", 0, "");
	
	//cycle through each record to test if email needs to be sent to them
	for ($i = 0; $i < $num_records; $i++)
	{
		//check if reminder is set to be sent for this invoice
		$send_reminder = @security_form_input_predefined("checkbox", "send_reminder_$i", 0, "");
		
		//send reminder
		if ($send_reminder)
		{
			//fetch invoice id
			$invoice_id = @security_form_input_predefined("int", "invoice_id_$i", 1, "A problem occurred - no id seems to exist for this invoice");
			
			//fetch days overdue
			$days_overdue = @security_form_input_predefined("int", "days_overdue_$i", 0, "");
			
			//fetch basic invoice details
			$obj_sql_invoice		= New sql_query;
			$obj_sql_invoice->string	= "SELECT code_invoice, customerid FROM account_ar WHERE id='". $invoice_id ."' LIMIT 1";
			$obj_sql_invoice->execute();
			$obj_sql_invoice->fetch_array();
			
			
			//fetch basic customer details
			$obj_sql_contact		= New sql_query;
			$obj_sql_contact->string	= "SELECT id, contact FROM customer_contacts WHERE customer_id = '" .$obj_sql_invoice->data[0]["customerid"]. "' AND role = 'accounts'";
			$obj_sql_contact->execute();
			$obj_sql_contact->fetch_array();
			
			//fetch email to address, set error if no address is set
			$to = sql_get_singlevalue("SELECT detail AS value FROM customer_contact_records WHERE contact_id = '" .$obj_sql_contact->data[0]["id"]. "' AND type = 'email' LIMIT 1");
			
			if (!$to)
			{
				$_SESSION["error"]["message"][] = "Reminder for Invoice " .$obj_sql_invoice->data[0]["code_invoice"]. " was not sent as no email is set for the customer's default account.";
				continue;
			}
						
			
			//create invoice
			$obj_invoice		= New invoice;
			$obj_invoice->type	= "ar";
			$obj_invoice->id 	= $invoice_id;
		
			$obj_invoice->load_data();
			$obj_invoice->load_data_export();
			
			
			//create email message
			$invoice_data = $obj_invoice->invoice_fields;		
			$invoice_data_parts['keys'] =  array_keys($invoice_data);
			$invoice_data_parts['values'] = array_values($invoice_data);
		
			foreach($invoice_data_parts['keys'] as $index => $key)
			{
				$invoice_data_parts['keys'][$index] = "(".$key.")";
			} 	
			foreach($invoice_data_parts['values'] as $index => $value)
			{
				$invoice_data_parts['values'][$index] = trim($value);
			}
			
			$invoice_data_parts['keys'][] 	= "(days_overdue)";
			$invoice_data_parts['values'][]	= trim($days_overdue);
		
			
			$email_message	= sql_get_singlevalue("SELECT value FROM config WHERE name IN('TEMPLATE_INVOICE_REMINDER_EMAIL') LIMIT 1");		
			$email_message 	= str_replace($invoice_data_parts['keys'], $invoice_data_parts['values'], $email_message);
			
			
			//other email details
			$from	 	= sql_get_singlevalue("SELECT value FROM config WHERE name='COMPANY_CONTACT_EMAIL'");
			$subject	= "Overdue Notice: Invoice ". $obj_sql_invoice->data[0]["code_invoice"];

			
			// send email
			$obj_invoice->email_invoice($from, $to, "", "", $subject, $email_message);
			
			$_SESSION["notification"]["message"][] = "Reminder email for Invoice " .$obj_sql_invoice->data[0]["code_invoice"]. "was sent successfully.";
//			print "hi";
		}
	}
	
//	header("Location: index.php?page=accounts/ar/account-statements.php");
//	/development/amberdms/oss-amberdms-bs/trunk/accounts/index.php 
	header("Location: ../../index.php?page=accounts/ar/account-statements.php");
	exit(0);
//print "yoyo";
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../../index.php?page=message.php");
	exit(0);
}

