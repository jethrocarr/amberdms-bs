<?php
/*
	services/cdr-rates-edit-process.php

	access:	services_write 

	Allows new CDR rate tables to be defined.
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
	$obj_rate_table						= New cdr_rate_table;
	$obj_rate_table->id					= @security_form_input_predefined("int", "id", 0, "");

	$obj_rate_table->data["rate_table_name"]		= @security_form_input_predefined("any", "rate_table_name", 1, "");
	$obj_rate_table->data["rate_table_description"]		= @security_form_input_predefined("any", "rate_table_description", 0, "");
	$obj_rate_table->data["id_vendor"]			= @security_form_input_predefined("int", "id_vendor", 1, "");
	$obj_rate_table->data["id_usage_mode"]			= @security_form_input_predefined("int", "id_usage_mode", 1, "");



	/*
		Verify Data
	*/


	// verify that the selected CDR rate table exists if one has been supplied.
	if ($obj_rate_table->id)
	{
		if (!$obj_rate_table->verify_id())
		{
			log_write("error", "process", "The CDR rate table you have attempted to edit - ". $obj_rate_table->id ." - does not exist in this system.");
		}
	}


	// verify that the name is unique
	if (!$obj_rate_table->verify_rate_table_name())
	{
		log_write("error", "process", "Another rate table already exists with the supplied name - unable to add another one with the same name");
	}



	/*
		Check for any errors
	*/
	if (error_check())
	{	
		if (!$obj_rate_table->id)
		{
			$_SESSION["error"]["form"]["cdr_rate_table_add"] = "failed";
			header("Location: ../index.php?page=services/cdr-rates-add.php");
			exit(0);
		}
		else
		{
			$_SESSION["error"]["form"]["cdr_rate_table_view"] = "failed";
			header("Location: ../index.php?page=services/cdr-rates-view.php&id=". $obj_rate_table->id ."");
			exit(0);
		}
	}
	else
	{
		/*
			Update/Create Rate Table
		*/
		$obj_rate_table->action_update();


		/*
			Complete
		*/
		header("Location: ../index.php?page=services/cdr-rates-view.php&id=". $obj_rate_table->id );
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
