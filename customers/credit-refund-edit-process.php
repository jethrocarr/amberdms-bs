<?php
/*
	customers/credit-refund-edit-process.php

	access: customers_credit

	Allows a refund to be made, or adjusted.
*/

// includes
require("../include/config.php");
require("../include/amberphplib/main.php");

require("../include/accounts/inc_credits.php");
require("../include/accounts/inc_ledger.php");
require("../include/customers/inc_customers.php");



if (user_permissions_get('customers_credit'))
{
	/*
		Load Data
	*/

	$obj_customer				= New customer_credits;
	$obj_customer->id			= @security_form_input_predefined("int", "id_customer", 1, "");

	$obj_refund				= New credit_refund;
	$obj_refund->type			= "customer";
	$obj_refund->id				= @security_form_input_predefined("int", "id_refund", 0, "");

	/*
		Verify Data
	*/


	// check that the specified customer actually exists
	if (!$obj_customer->verify_id())
	{
		log_write("error", "process", "The customer you have attempted to edit - ". $obj_customer->id ." - does not exist in this system.");
	}
	else
	{
		if ($obj_refund->id)
		{
			// are we editing an existing refund? make sure it exists and belongs to this customer
			if (!$obj_refund->verify_id())
			{
				log_write("error", "process", "The refund you have attempted to edit - ". $obj_refund->id ." - does not exist in this system.");
			}
			else
			{
				$obj_refund->load_data();
			}
		}
	}



	/*
		Fetch Standard Data
	*/
	
	$obj_refund->data["date_trans"]				= @security_form_input_predefined("date", "date_trans", 1, "");
	$obj_refund->data["description"]			= @security_form_input_predefined("any", "description", 0, "");
	$obj_refund->data["amount_total"]			= @security_form_input_predefined("money", "amount", 1, "");
	$obj_refund->data["account_dest"]			= @security_form_input_predefined("int", "account_dest", 1, "");
	$obj_refund->data["account_asset"]			= @security_form_input_predefined("int", "account_asset", 1, "");
	$obj_refund->data["id_employee"]			= @security_form_input_predefined("int", "id_employee", 1, "");
	$obj_refund->data["id_customer"]			= $obj_customer->id;
	
	@security_form_input_predefined("any", "type", 0, ""); // ignored, for error handling only



	// make sure the refund amount isn't more than the available credit
	$credit_balance	= sql_get_singlevalue("SELECT SUM(amount_total) as value FROM customers_credits WHERE id_customer='". $obj_customer->id ."' AND id!='". $obj_refund->id ."'");

	if ($obj_refund->data["amount_total"] > $credit_balance)
	{
		log_write("error", "process", "Refund amount can not be more than the credit balance of ". format_money($credit_balance) ."");
	}



	/*
		Check for any errors
	*/
	if (error_check())
	{	
		$_SESSION["error"]["form"]["credit-refund_view"] = "failed";
		header("Location: ../index.php?page=customers/credit-refund.php&id_customer=". $obj_customer->id ."&id_order=". $obj_customer->id_order);
		exit(0);
	}
	else
	{
		/*
			Update the database
		*/

		// update
		if ($obj_refund->id)
		{
			log_write("notification", "process", "Successfully updated the refund.");

			$obj_refund->action_update();
		}
		else
		{
			log_write("notification", "process", "Successfully created a new refund transaction.");

			$obj_refund->action_create();
		}

		// return to services page
		header("Location: ../index.php?page=customers/credit.php&id_customer=". $obj_customer->id ."");
		exit(0);
			
	}

	
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
