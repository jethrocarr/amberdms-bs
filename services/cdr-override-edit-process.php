<?php
/*
	services/cdr-override-edit-process.php

	access:	services_write 

	Add or edit call rate overrides.
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

	$obj_service						= New service;
	$obj_service->id					= @security_form_input_predefined("int", "id_service", 1, "");


	$obj_rate_table						= New cdr_rate_table_rates_override;
	$obj_rate_table->id_rate_override			= @security_form_input_predefined("int", "id_rate_override", 0, "");

	$obj_rate_table->option_type				= "service";
	$obj_rate_table->option_type_id				= $obj_service->id;

	$obj_rate_table->data_rate["rate_prefix"]		= @security_form_input_predefined("any", "rate_prefix", 1, "");
	$obj_rate_table->data_rate["rate_description"]		= @security_form_input_predefined("any", "rate_description", 1, "");
	$obj_rate_table->data_rate["rate_price_sale"]		= @security_form_input_predefined("money", "rate_price_sale", 0, "");



	/*
		Verify Data
	*/

	// make sure a valid service ID has been supplied
	if (!$obj_service->verify_id())
	{
		log_write("error", "process", "The service you have requested - ". $obj_service->id ." - does not exist in this system");
	}

	// check the option id values
	if (!$obj_rate_table->verify_id_override())
	{
		// TODO: seriously need a better error message here, this means almost nothing to me and I wrote it....
		log_write("error", "process", "The service and rate ids do not correct match any known override");
	}

	// verify that the prefix is unique
	if (!$obj_rate_table->verify_rate_prefix_override())
	{
		log_write("error", "process", "Another rate override already exists with the supplied prefix - unable to add another one with the same prefix");
		error_flag_field("rate_prefix");
	}


	/*
		Check for any errors
	*/
	if (error_check())
	{	
		$_SESSION["error"]["form"]["cdr_override_edit"] = "failed";
		header("Location: ../index.php?page=services/cdr-override-edit.php&id_service=". $obj_service->id ."&id_rate_override=". $obj_rate_table->id_rate_override);
		exit(0);
	}
	else
	{
		/*
			Update/Create Rate Table
		*/
		$obj_rate_table->action_rate_update_override();


		/*
			Complete
		*/
		header("Location: ../index.php?page=services/cdr-override.php&id=". $obj_service->id );
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
