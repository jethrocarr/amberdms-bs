<?php
/*
	services/bundle-service-edit.process

	Access: services_write

	Updates the service details and options for services assigned to bundles.
*/


// includes
require("../include/config.php");
require("../include/amberphplib/main.php");
require("../include/services/inc_services.php");



if (user_permissions_get('services_write'))
{
	/*
		Form Input
	*/
	$id		= @security_form_input_predefined("int", "id_bundle", 1, "");
	$id_component	= @security_form_input_predefined("int", "id_component", 1, "");


	/*
		Init Object
	*/
	$obj_service = New service;
	$obj_service->option_type	= "bundle";
	$obj_service->option_type_id	= $id_component;

	$obj_service->verify_id();
	$obj_service->load_data_options();



	/*
		Get Options
	*/
	$obj_service->data["name_service"]	= @security_form_input_predefined("any", "name_service", 1, "");
	$obj_service->data["description"]	= @security_form_input_predefined("any", "description_service", 0, "");



	/*
		Process Data
	*/

	if (error_check())
	{
		$_SESSION["error"]["form"]["services_bundles_service"] = "failed";
		header("Location: ../index.php?page=services/bundles-service-edit.php&id_bundle=$id&id_component=$id_component");
		exit(0);
	}
	else
	{
		error_clear();


		/*
			Execute
		*/

		$obj_service->action_update_options();


		/*
			Return
		*/
		header("Location: ../index.php?page=services/bundles.php&id=$id");
		exit(0);


	} // if valid data input
	
	
} // end of "is user logged in?"
else
{
	// user does not have permissions to access this page.
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
