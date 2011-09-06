<?php
/*
	services/traffic-types-view-process.php

	access:	services_write 

	Allows new traffic types to be defined, or existing ones to be edited.
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
	$obj_traffic_type					= New traffic_types;
	$obj_traffic_type->id					= @security_form_input_predefined("int", "id", 0, "");

	$obj_traffic_type->data["type_name"]			= @security_form_input_predefined("any", "type_name", 1, "");
	$obj_traffic_type->data["type_label"]			= @security_form_input_predefined("any", "type_label", 1, "");
	$obj_traffic_type->data["type_description"]		= @security_form_input_predefined("any", "type_description", 0, "");



	/*
		Verify Data
	*/


	// verify that the traffic type exists, if one is selected
	if ($obj_traffic_type->id)
	{
		if (!$obj_traffic_type->verify_id())
		{
			log_write("error", "process", "The traffic type you have attempted to edit - ". $obj_traffic_type->id ." - does not exist in this system.");
		}
	}


	// note, we don't verify name uniqueness - there may be valid reasons for having multiple traffic types for sales/plan purposes.


	/*
		Check for any errors
	*/
	if (error_check())
	{	
		if (!$obj_traffic_type->id)
		{
			$_SESSION["error"]["form"]["traffic_types_add"] = "failed";
			header("Location: ../index.php?page=services/traffic-types-add.php");
			exit(0);
		}
		else
		{
			$_SESSION["error"]["form"]["traffic_types_edit"] = "failed";
			header("Location: ../index.php?page=services/traffic-types-view.php&id=". $obj_traffic_type->id ."");
			exit(0);
		}
	}
	else
	{
		/*
			Update/Create Traffic Type
		*/
		$obj_traffic_type->action_update();


		/*
			Complete
		*/
		header("Location: ../index.php?page=services/traffic-types-view.php&id=". $obj_traffic_type->id );
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
