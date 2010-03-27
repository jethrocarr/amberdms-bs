<?php
/*
	services/cdr-override-delete-process.php

	access:	services_write 

	Deletes a call rate override.
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
	$obj_service->id					= @security_script_input_predefined("int", $_GET["id_service"]);


	$obj_rate_table						= New cdr_rate_table_rates_override;
	$obj_rate_table->id_rate_override			= @security_script_input_predefined("int", $_GET["id_rate_override"]);

	$obj_rate_table->option_type				= "service";
	$obj_rate_table->option_type_id				= $obj_service->id;


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
		header("Location: ../index.php?page=services/cdr-override.php&id_service=". $obj_service->id );
		exit(0);
	}
	else
	{
		/*
			Delete Rate Override
		*/
		$obj_rate_table->action_rate_delete_override();


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
