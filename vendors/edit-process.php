<?php
/*
	vendors/edit-process.php

	access: vendors_write

	Allows existing vendors to be adjusted, or new vendors to be added.
*/

// includes
require("../include/config.php");
require("../include/amberphplib/main.php");

// custom includes
require("../include/vendors/inc_vendors.php");


if (user_permissions_get('vendors_write'))
{
	$obj_vendor = New vendor;


	/*
		Load POST Data
	*/
	
	$obj_vendor->id				= @security_form_input_predefined("int", "id_vendor", 0, "");
	
	$obj_vendor->data["code_vendor"]	= @security_form_input_predefined("any", "code_vendor", 0, "");
	$obj_vendor->data["name_vendor"]	= @security_form_input_predefined("any", "name_vendor", 1, "You must set a vendor name");
	$obj_vendor->data["name_contact"]	= @security_form_input_predefined("any", "name_contact", 0, "");
	
	$obj_vendor->data["contact_phone"]	= @security_form_input_predefined("any", "contact_phone", 0, "");
	$obj_vendor->data["contact_fax"]	= @security_form_input_predefined("any", "contact_fax", 0, "");
	$obj_vendor->data["contact_email"]	= @security_form_input_predefined("email", "contact_email", 0, "There is a mistake in the supplied email address, please correct.");
	$obj_vendor->data["date_start"]		= @security_form_input_predefined("date", "date_start", 1, "");
	$obj_vendor->data["date_end"]		= @security_form_input_predefined("date", "date_end", 0, "");

	$obj_vendor->data["address1_street"]	= @security_form_input_predefined("any", "address1_street", 0, "");
	$obj_vendor->data["address1_city"]	= @security_form_input_predefined("any", "address1_city", 0, "");
	$obj_vendor->data["address1_state"]	= @security_form_input_predefined("any", "address1_state", 0, "");
	$obj_vendor->data["address1_country"]	= @security_form_input_predefined("any", "address1_country", 0, "");
	$obj_vendor->data["address1_zipcode"]	= @security_form_input_predefined("any", "address1_zipcode", 0, "");
	
	$obj_vendor->data["address2_street"]	= @security_form_input_predefined("any", "address2_street", 0, "");
	$obj_vendor->data["address2_city"]	= @security_form_input_predefined("any", "address2_city", 0, "");
	$obj_vendor->data["address2_state"]	= @security_form_input_predefined("any", "address2_state", 0, "");
	$obj_vendor->data["address2_country"]	= @security_form_input_predefined("any", "address2_country", 0, "");
	$obj_vendor->data["address2_zipcode"]	= @security_form_input_predefined("any", "address2_zipcode", 0, "");
	
	$obj_vendor->data["tax_number"]		= @security_form_input_predefined("any", "tax_number", 0, "");

	$obj_vendor->data["discount"]		= @security_form_input_predefined("float", "discount", 0, "");


	// get tax selection options
	$sql_taxes_obj		= New sql_query;
	$sql_taxes_obj->string	= "SELECT id FROM account_taxes";
	$sql_taxes_obj->execute();

	if ($sql_taxes_obj->num_rows())
	{
		$sql_taxes_obj->fetch_array();

		// only get the default tax if taxes exist
		$obj_vendor->data["tax_default"] = @security_form_input_predefined("int", "tax_default", 0, "");


		// fetch all the taxes and see which ones are enabled for the customer
		foreach ($sql_taxes_obj->data as $data_tax)
		{
			$obj_vendor->data["tax_". $data_tax["id"] ] = @security_form_input_predefined("any", "tax_". $data_tax["id"], 0, "");
		}
	}




	/*
		Error Handling
	*/

	// verify that vendor exists (if editing an exisiting vendor)
	if ($obj_vendor->id)
	{
		if (!$obj_vendor->verify_id())
		{
			log_write("error", "process", "The vendor you have attempted to edit - ". $obj_vendor->id ." - does not exist in this system.");
		}
	}


		
	// make sure we don't choose a vendor name that has already been taken
	if (!$obj_vendor->verify_name_vendor())
	{
		log_write("error", "process", "This vendor name is already used for another vendor - please choose a unique name.");
		$_SESSION["error"]["name_vendor-error"] = 1;
	}


	// make sure we don't choose a vendor code that has already been taken
	if (!$obj_vendor->verify_code_vendor())
	{
		log_write("error", "process", "This vendor code is already used for another vendor - please choose a unique code, or leave it blank to recieve an auto-generated value.");
		$_SESSION["error"]["code_vendor-error"] = 1;
	}


	// return to the input page in the event of an error
	if ($_SESSION["error"]["message"])
	{	
		if ($obj_vendor->id)
		{
			$_SESSION["error"]["form"]["vendor_view"] = "failed";
			header("Location: ../index.php?page=vendors/view.php&id=". $obj_vendor->id);
			exit(0);
		}
		else
		{
			$_SESSION["error"]["form"]["vendor_add"] = "failed";
			header("Location: ../index.php?page=vendors/add.php");
			exit(0);
		}
	}


	/*
		Process Vendor Data
	*/

	$sql_obj = New sql_query;
	$sql_obj->trans_begin();
	
	$obj_vendor->action_update();
	$obj_vendor->action_update_taxes();

	if (error_check())
	{
		$sql_obj->trans_rollback();
	}
	else
	{
		$sql_obj->trans_commit();
	}


	// display updated details
	header("Location: ../index.php?page=vendors/view.php&id=". $obj_vendor->id);
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
