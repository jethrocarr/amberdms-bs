<?php
/*
	groups/delete-process.php

	access: products_write

	Deletes an unwanted product group.
*/

// includes
require("../include/config.php");
require("../include/amberphplib/main.php");

// custom includes
require("../include/products/inc_products_groups.php");


if (user_permissions_get('products_write'))
{
	$obj_product_group	= New product_groups;


	/*
		Load POST data
	*/

	$obj_product_group->id					= @security_form_input_predefined("int", "id_product_group", 1, "");
	
	// needed to make error handling work nicely
	@security_form_input_predefined("any", "group_name", 1, "");
	@security_form_input_predefined("any", "group_description", 0, "");
	
	// verify deletion
	@security_form_input_predefined("any", "delete_confirm", 1, "You must confirm the deletion");



	/*
		Error Handling
	*/


	// verify valid ID
	if (!$obj_product_group->verify_id())
	{
		log_write("error", "process", "The product group you have attempted to delete - ". $obj_product_group->id ." - does not exist in this system.");
	}


	// verify safe to delete
	if ($obj_product_group->check_delete_lock())
	{
		log_write("error", "process", "Sorry, the selected product group has products assigned to it and can therefore not be deleted.");
	}



	// return to input page if any errors occured
	if ($_SESSION["error"]["message"])
	{
		$_SESSION["error"]["form"]["product_group_view"] = "failed";
		header("Location: ../index.php?page=products/groups-delete.php&id=". $obj_product_group->id ."");
		exit(0);
	}


	/*
		Process Data
	*/

	// delete product group
	$obj_product_group->action_delete();


	// display updated details
	header("Location: ../index.php?page=products/groups.php");
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
