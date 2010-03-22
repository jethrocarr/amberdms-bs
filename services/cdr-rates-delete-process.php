<?php
/*
	services/cdr-rates-delete-process.php

	access:	services_write 

	Deletes an unwanted (and unused) rate table.
*/

// includes
require("../include/config.php");
require("../include/amberphplib/main.php");

require("../include/services/inc_services_cdr.php");



if (user_permissions_get('services_write'))
{
	/*
		Load Data
	*/
	$obj_rate_table				= New cdr_rate_table;
	$obj_rate_table->id			= @security_form_input_predefined("int", "id", 1, "");


	// confirm deletion
	@security_form_input_predefined("any", "delete_confirm", 1, "You must confirm the deletion");



	/*
		Verify Data
	*/


	// verify that the selected CDR rate table exists
	if (!$obj_rate_table->verify_id())
	{
		log_write("error", "process", "The CDR rate table you have attempted to delete ". $obj_rate_table->id ." - does not exist in this system.");
	}



	/*
		Check for any errors
	*/
	if (error_check())
	{	
		header("Location: ../index.php?page=services/cdr-rates-delete.php&id=". $obj_rate_table->id );
		exit(0);
	}
	else
	{
		/*
			Delete Rate Item
		*/

		$obj_rate_table->action_delete();


		/*
			Complete
		*/
		header("Location: ../index.php?page=services/cdr-rates.php");
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
