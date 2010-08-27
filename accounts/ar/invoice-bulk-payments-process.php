<?php 
// includes
require("../../include/config.php");
require("../../include/amberphplib/main.php");
require("../../include/accounts/inc_invoices_items.php");
require("../../include/accounts/inc_invoices.php");


if (user_permissions_get('accounts_ar_write'))
{
	//get data
	$highest_invoice_id 	= @security_form_input_predefined("int", "highest_invoice_id", 1, "");
	$data 			= array();
	$data["date_trans"]		= @security_form_input_predefined("date", "payment_date", 1, "You must enter a payment date");
	$data["chartid"]		= @security_form_input_predefined("int", "chartid", 1, "You must select an account to pay to");

	//start SQL query
	$sql_obj = New sql_query;
	$sql_obj->trans_begin();
	
	for ($i = 0; $i <= $highest_invoice_id; $i++)
	{
		//only process rows that have been ticked 'to pay'
		$checked = @security_form_input_predefined("any", "checked_status_invoice_$i", 0, "");
		$ticked = @security_form_input_predefined("any", "pay_invoice_$i", 0, "");
		if ($checked == "true")
		{
			//create new invoice item
			$item = New invoice_items;
			$item->id_invoice = $i;
			$item->type_invoice = "ar";
			$item->type_item = "payment";
			
			//verify invoice exists
			if(!$item->verify_invoice())
			{
				log_write("error", "process", "The requested invoice does not exist.");
			}

			//get amount
			$data["amount"] = @security_form_input_predefined("money", "amount_invoice_$i", 0, "");

			//process
			if (!$item->prepare_data($data))
			{
				log_write("error", "process", "An error was encountered whilst processing supplied data.");
			}
			
			$item->action_create();
			$item->action_update();	
			$item->action_update_total();	
			$item->action_update_ledger();	
		}
	}
	
	//error checking
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"]["invoice-bulk-payments"] = "failed";
	}
	
	//if there are errors, do not execute any queries
	if (error_check())
	{
		$sql_obj->trans_rollback();	
		log_write("error", "invoice-bulk-payments", "An error occured whilst updating the invoice item. No changes have been made.");
	}
	else
	{
		$sql_obj->trans_commit();
	}
	
	// display updated details
	header("Location: ../../index.php?page=accounts/ar/invoice-bulk-payments.php");
	exit(0);
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}
?>