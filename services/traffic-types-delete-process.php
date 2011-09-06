<?php
/*
	services/traffic-types-delete-process.php

	access:	services_write 

	Deletes an unwanted (and unused) traffic type.
*/

// includes
require("../include/config.php");
require("../include/amberphplib/main.php");

require("../include/services/inc_services.php");
require("../include/services/inc_services_traffic.php");



if (user_permissions_get('services_write'))
{
	/*
		Load Data
	*/
	$obj_traffic_type			= New traffic_types;
	$obj_traffic_type->id			= @security_form_input_predefined("int", "id", 1, "");


	// confirm deletion
	@security_form_input_predefined("any", "delete_confirm", 1, "You must confirm the deletion");
	
	// load for error handling purposes only
	@security_form_input_predefined("any", "type_name", 0, "");
	@security_form_input_predefined("any", "type_label", 0, "");
	@security_form_input_predefined("any", "type_description", 0, "");



	/*
		Verify Data
	*/


	// verify selected traffic type exists
	if (!$obj_traffic_type->verify_id())
	{
		log_write("error", "process", "The traffic type you have attempted to delete ". $obj_traffic_type->id ." - does not exist in this system.");
	}


	// make sure it is not locked
	if ($obj_traffic_type->check_delete_lock())
	{
		log_write("error", "process", "The selected traffic type can not be deleted, as it is currently in use/locked.");
	}



	/*
		Check for any errors
	*/
	if (error_check())
	{	
		$_SESSION["error"]["form"]["traffic_types_delete"] = "failed";

		header("Location: ../index.php?page=services/traffic-types-delete.php&id=". $obj_traffic_type->id );
		exit(0);
	}
	else
	{
		/*
			Delete Rate Item
		*/

		$obj_traffic_type->action_delete();


		/*
			Complete
		*/
		header("Location: ../index.php?page=services/traffic-types.php");
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
