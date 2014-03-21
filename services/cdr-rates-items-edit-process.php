<?php
/*
	services/cdr-rates-items-edit-process.php

	access:	services_write 

	Add or adjust rate items in the rate table.
*/

// includes
require("../include/config.php");
require("../include/amberphplib/main.php");

require("../include/services/inc_services.php");
require("../include/services/inc_services_cdr.php");



if (user_permissions_get('services_write'))
{
	/*
		Load Data
	*/
	$obj_rate_table						= New cdr_rate_table_rates;
	$obj_rate_table->id					= @security_form_input_predefined("int", "id", 1, "");
	$obj_rate_table->id_rate				= @security_form_input_predefined("int", "id_rate", 0, "");

	$obj_rate_table->data_rate["rate_prefix"]		= @security_form_input_predefined("any", "rate_prefix", 1, "");
	$obj_rate_table->data_rate["rate_description"]		= @security_form_input_predefined("any", "rate_description", 0, "");
	$obj_rate_table->data_rate["rate_billgroup"]		= @security_form_input_predefined("int", "rate_billgroup", 0, "");
	$obj_rate_table->data_rate["rate_price_sale"]		= @security_form_input_predefined("float", "rate_price_sale", 0, "");
	$obj_rate_table->data_rate["rate_price_cost"]		= @security_form_input_predefined("float", "rate_price_cost", 0, "");



	/*
		Verify Data
	*/


	// verify that the selected CDR rate table exists if one has been supplied.
	if ($obj_rate_table->id_rate)
	{
		if (!$obj_rate_table->verify_id_rate())
		{
			log_write("error", "process", "The CDR rate value you have attempted to edit - ". $obj_rate_table->id_rate ." - does not exist in this system.");
		}
	}
	else
	{
		if (!$obj_rate_table->verify_id())
		{
			log_write("error", "process", "The CDR rate table you have attempted to edit - ". $obj_rate_table->id ." - does not exist in this system.");
		}
	}


	// verify that the name is unique
	if (!$obj_rate_table->verify_rate_prefix())
	{
		log_write("error", "process", "Another rate already exists with the supplied prefix - unable to add another one with the same prefix");
	}



	/*
		Check for any errors
	*/
	if (error_check())
	{	
		$_SESSION["error"]["form"]["cdr_rate_table_item_edit"] = "failed";
		header("Location: ../index.php?page=services/cdr-rates-items-edit.php&id=". $obj_rate_table->id ."&id_rate=". $obj_rate_table->id_rate);
		exit(0);
	}
	else
	{
		/*
			Update/Create Rate Table
		*/
		$obj_rate_table->action_rate_update();


		/*
			Complete
		*/
		header("Location: ../index.php?page=services/cdr-rates-items.php&id=". $obj_rate_table->id );
		exit(0);
			
	}

	/////////////////////////
	
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
