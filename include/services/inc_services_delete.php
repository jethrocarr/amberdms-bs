<?php
/*
	include/services/inc_services_delete.php

	Provides forms and processing code for deleting unwanted services.
*/


/*
	FUNCTIONS
*/




/*
	service_form_delete_render($id)

	This function provides a form to allow the user to confirm the deletion.

	Values
	id		ID of service to delete

	Return Codes
	0	failure
	1	success
*/
function service_form_delete_render($id)
{
	log_debug("inc_services_delete", "Executing service_form_delete_render($id)");

	
	/*
		Make sure service does exist!
	*/
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM services WHERE id='$id'";
	$sql_obj->execute();
		
	if (!$sql_obj->num_rows())
	{
		print "<p><b>Error: The requested service does not exist. <a href=\"index.php?page=services/services.php\">Try looking on the service list page.</a></b></p>";
		return 0;
	}


	/*
		Start Form
	*/
	$form = New form_input;
	$form->formname		= "service_delete";
	$form->language		= $_SESSION["user"]["lang"];

	$form->action		= "services/delete-process.php";
	$form->method		= "POST";
	


	/*
		Define form structure
	*/
	
	// basic details
	$structure = NULL;
	$structure["fieldname"] 	= "name_service";
	$structure["type"]		= "text";
	$form->add_input($structure);
	
	$structure = NULL;
	$structure["fieldname"] 	= "delete_confirm";
	$structure["type"]		= "checkbox";
	$structure["options"]["label"]	= "Yes, I wish to delete this service and realise that once deleted the data can not be recovered.";
	$form->add_input($structure);




	// ID
	$structure = NULL;
	$structure["fieldname"]		= "id_service";
	$structure["type"]		= "hidden";
	$structure["defaultvalue"]	= $id;
	$form->add_input($structure);	


	// submit
	$structure = NULL;
	$structure["fieldname"]		= "submit";
	$structure["type"]		= "submit";
	$structure["defaultvalue"]	= "Delete Service";
	$form->add_input($structure);


	// load data
	$form->sql_query = "SELECT name_service FROM services WHERE id='$id'";
	$form->load_data();


	$form->subforms["service_delete"]		= array("name_service", "delete_confirm");
	$form->subforms["hidden"]			= array("id_service");
	$form->subforms["submit"]			= array("submit");
	


	/*
		Render Form
	*/
	
	$form->render_form();


	return 1;
	
} // end of service_form_delete_render




/*
	service_form_delete_process($id)

	Process the delete form to delete the requested service.

*/
function service_form_delete_process()
{
	log_debug("inc_services_forms", "Executing service_form_delete_process()");

	
	/*
		Fetch all form data
	*/


	// get form data
	$id				= security_form_input_predefined("int", "id_service", 1, "");
	$data["delete_confirm"]		= security_form_input_predefined("any", "delete_confirm", 1, "You must confirm the deletion");



	//// ERROR CHECKING ///////////////////////
	
	// make sure the service actually exists
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM services WHERE id='$id' LIMIT 1";
	$sql_obj->execute();

	if (!$sql_obj->num_rows())
	{
		$_SESSION["error"]["message"][] = "The service you have attempted to edit - $id - does not exist in this system.";
	}


	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"]["service_delete"] = "failed";
		header("Location: ../index.php?page=services/delete.php&id=$id");
		exit(0);
	}
	else
	{
		/*
			Delete the service data
		*/
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM services WHERE id='$id'";
		$sql_obj->execute();


		/*
			Delete service journal data
		*/
		journal_delete_entire("services", $id);



		/*
			Complete
		*/
		header("Location: ../index.php?page=services/services.php&id=$id");
		exit(0);
			
	} // end if passed tests


} // end if service_form_delete_process


?>
