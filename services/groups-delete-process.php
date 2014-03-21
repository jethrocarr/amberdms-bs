<?php
/*
	groups/delete-process.php

	access: services_write

	Deletes an unwanted group.
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

	$obj_service_group->id					= @security_form_input_predefined("int", "id_service_group", 1, "");
	
	// needed to make error handling work nicely
	@security_form_input_predefined("any", "group_name", 1, "");
	@security_form_input_predefined("any", "group_description", 0, "");
	
	// verify deletion
	@security_form_input_predefined("any", "delete_confirm", 1, "You must confirm the deletion");



	/*
		Error Handling
	*/


	// verify valid ID
	if (!$obj_service_group->verify_id())
	{
		log_write("error", "process", "The service group you have attempted to delete - ". $obj_service_group->id ." - does not exist in this system.");
	}


	// verify safe to delete
	if ($obj_service_group->check_delete_lock())
	{
		log_write("error", "process", "Sorry, the selected service group has services assigned to it and can therefore not be deleted.");
	}



	// return to input page if any errors occured
	if ($_SESSION["error"]["message"])
	{
		$_SESSION["error"]["form"]["service_group_view"] = "failed";
		header("Location: ../index.php?page=services/groups-delete.php&id=". $obj_service_group->id ."");
		exit(0);
	}


	/*
		Process Data
	*/

	// delete service group
	$obj_service_group->action_delete();


	// display updated details
	header("Location: ../index.php?page=services/groups.php");
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
