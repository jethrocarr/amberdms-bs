<?php
/*
	groups/edit-process.php

	access: services_write

	Allows existing services to be updated or new services to be added.
*/

// includes
require("../include/config.php");
require("../include/amberphplib/main.php");

// custom includes
require("../include/services/inc_services_groups.php");


if (user_permissions_get('services_write'))
{
	$obj_service_group	= New service_groups;


	/*
		Load POST data
	*/

	$obj_service_group->id					= @security_form_input_predefined("int", "id_service_group", 0, "");
	
	$obj_service_group->data["group_name"]			= @security_form_input_predefined("any", "group_name", 1, "");
	$obj_service_group->data["group_description"]		= @security_form_input_predefined("any", "group_description", 0, "");



	/*
		Error Handling
	*/


	// verify valid ID (if performing update)
	if ($obj_service_group->id)
	{
		if (!$obj_service_group->verify_id())
		{
			log_write("error", "process", "The service group you have attempted to edit - ". $obj_service_group->id ." - does not exist in this system.");
		}
	}


	// make sure we don't choose a service group name that has already been taken
	if (!$obj_service_group->verify_group_name())
	{
		log_write("error", "process", "This service group name is already used - please choose a unique name.");
		$_SESSION["error"]["group_name-error"] = 1;
	}


	// return to input page if any errors occured
	if ($_SESSION["error"]["message"])
	{
		if ($obj_service_group->id)
		{
			$_SESSION["error"]["form"]["service_group_view"] = "failed";
			header("Location: ../index.php?page=services/groups-view.php&id=". $obj_service_group->id ."");
			exit(0);
		}
		else
		{
			$_SESSION["error"]["form"]["service_group_add"] = "failed";
			header("Location: ../index.php?page=services/groups-add.php");
			exit(0);
		}
	}


	/*
		Process Data
	*/

	// update service group
	$obj_service_group->action_update();

	// display updated details
	header("Location: ../index.php?page=services/groups-view.php&id=". $obj_service_group->id);
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
