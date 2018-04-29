<?php
/*
	customers/credit-refund-delete-process.php

	access: customers_credit

	Deletes the selected credit refund from the customer
*/

// includes
require("../include/config.php");
require("../include/amberphplib/main.php");

require("../include/accounts/inc_credits.php");
require("../include/vendors/inc_vendors.php");



if (user_permissions_get('vendors_write'))
{
	/*
		Load Data
	*/

	$obj_vendor				= New vendor_credits;
	$obj_vendor->id			= @security_script_input_predefined("int", $_GET["id_vendor"]);

	$obj_refund				= New credit_refund;
	$obj_refund->type			= "vendor";
	$obj_refund->id				= @security_script_input_predefined("int", $_GET["id_refund"]);


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
		Check for any errors
	*/
	if (error_check())
	{	
		header("Location: ../index.php?page=vendors/credit.php&id=". $obj_vendor->id ."&id_refund=". $obj_refund->id);
		exit(0);
	}
	else
	{
		/*
			Delete Credit Refund
		*/

		$obj_refund->action_delete();

		if (!error_check())
		{
			log_write("notification", "process", "Successfully removed credit refund");	
		}


		/*
			Return
		*/
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
