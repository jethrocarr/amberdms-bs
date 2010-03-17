<?php
/*
	services/bundle-service-add.process

	Access: services_write

	Adds a service to a bundle as an component.
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
	$id_service	= @security_form_input_predefined("int", "id_service", 1, "");


	/*
		Init Object
	*/
	$obj_bundle	= New service_bundle;
	$obj_bundle->id	= $id;
		


	/*
		Error Checking
	*/

	// check that service exists and is a bundle
	if (!$obj_bundle->verify_is_bundle())
	{
		log_write("error", "process", "The service you have attempted to edit - $id - either does not exist or is not a bundle");
	}

	// check that the service we are adding exists and is not a bundle
	$obj_service_tmp	= New service_bundle;
	$obj_service_tmp->id	= $id_service;
	
	switch ($obj_service_tmp->verify_is_bundle())
	{
		case -1;
			log_write("error", "process", "The requested service component - $id_service - does not exist");
		break;

		case 1;
			log_write("error", "process", "Service $id_service is a bundle and can not be added to another bundle");
		break;
	}

	unset($obj_service_tmp);



	/*
		Process Data
	*/

	if (error_check())
	{
		$_SESSION["error"]["form"]["services_bundles_service"] = "failed";
		header("Location: ../index.php?page=services/bundles-service-add.php&id_bundle=$id");
		exit(0);
	}
	else
	{
		error_clear();


		/*
			Execute
		*/

		$obj_bundle->bundle_service_create($id_service);


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
