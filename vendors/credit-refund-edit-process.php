<?php
/*
	vendors/credit-refund-edit-process.php

	access: vendors_credit

	Allows a refund to be made, or adjusted.
*/

// includes
require("../include/config.php");
require("../include/amberphplib/main.php");

require("../include/accounts/inc_credits.php");
require("../include/accounts/inc_ledger.php");
require("../include/vendors/inc_vendors.php");



if (user_permissions_get('vendors_write'))
{
	/*
		Load Data
	*/

	$obj_vendor				= New vendor_credits;
	$obj_vendor->id                         = @security_form_input_predefined("int", "id_vendor", 1, "");

	$obj_refund				= New credit_refund;
	$obj_refund->type			= "vendor";
	$obj_refund->id				= @security_form_input_predefined("int", "id_refund", 0, "");

	/*
		Verify Data
	*/


	// check that the specified customer actually exists
	if (!$obj_vendor->verify_id())
	{
		log_write("error", "process", "The vendor you have attempted to edit - ". $obj_vendor->id ." - does not exist in this system.");
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
	$obj_refund->data["id_vendor"]			= $obj_vendor->id;
	
	@security_form_input_predefined("any", "type", 0, ""); // ignored, for error handling only



	// make sure the refund amount isn't more than the available credit
	$credit_balance	= sql_get_singlevalue("SELECT SUM(amount_total) as value FROM vendors_credits WHERE id_vendor='". $obj_vendor->id ."' AND id!='". $obj_refund->id ."'");

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
		header("Location: ../index.php?page=vendors/credit-refund.php&id_vendor=". $obj_vendor->id ."&id_refund=". $obj_refund->id);
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
		header("Location: ../index.php?page=vendors/credit.php&id=". $obj_vendor->id ."");
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
