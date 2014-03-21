<?php
/*
	groups/edit-process.php

	access: products_write

	Allows product groups to be adjusted or created.
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

	$obj_product_group->id					= @security_form_input_predefined("int", "id_product_group", 0, "");
	
	$obj_product_group->data["group_name"]			= @security_form_input_predefined("any", "group_name", 1, "");
	$obj_product_group->data["group_description"]		= @security_form_input_predefined("any", "group_description", 0, "");
	$obj_product_group->data["id_parent"]			= @security_form_input_predefined("int", "id_parent", 0, "");
	



	/*
		Error Handling
	*/


	// verify valid ID (if performing update)
	if ($obj_product_group->id)
	{
		if (!$obj_product_group->verify_id())
		{
			log_write("error", "process", "The product group you have attempted to edit - ". $obj_product_group->id ." - does not exist in this system.");
		}
	}


	// make sure we don't choose a product group name that has already been taken
	if (!$obj_product_group->verify_group_name())
	{
		log_write("error", "process", "This product group name is already used - please choose a unique name.");
		$_SESSION["error"]["group_name-error"] = 1;
	}


	// return to input page if any errors occured
	if ($_SESSION["error"]["message"])
	{
		if ($obj_product_group->id)
		{
			$_SESSION["error"]["form"]["product_group_view"] = "failed";
			header("Location: ../index.php?page=products/groups-view.php&id=". $obj_product_group->id ."");
			exit(0);
		}
		else
		{
			$_SESSION["error"]["form"]["product_group_add"] = "failed";
			header("Location: ../index.php?page=products/groups-add.php");
			exit(0);
		}
	}


	/*
		Process Data
	*/

	// update product group
	$obj_product_group->action_update();

	// display updated details
	header("Location: ../index.php?page=products/groups-view.php&id=". $obj_product_group->id);
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
