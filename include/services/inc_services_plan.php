<?php
/*
	include/services/inc_services_plan.php

	Provides forms and functions for managing service plan data
*/




/*
	FUNCTIONS
*/



/*
	services_form_plan_render

	Values
	serviceid	ID of the service entry 

	Return Codes
	0		failure
	1		success
*/
function services_form_plan_render($serviceid)
{
	log_debug("inc_services_forms", "Executing services_forms_plan_render($serviceid)");


	/*
		Define form structure
	*/
	$form = New form_input;
	$form->formname = "service_plan";
	$form->language = $_SESSION["user"]["lang"];

	$form->action = "services/plan-edit-process.php";
	$form->method = "post";

	// general
	$structure = NULL;
	$structure["fieldname"] 	= "name_service";
	$structure["type"]		= "text";
	$form->add_input($structure);

	$structure = NULL;
	$structure["fieldname"] 	= "price";
	$structure["type"]		= "input";
	$structure["options"]["req"]	= "yes";
	$form->add_input($structure);
	
	$structure = NULL;
	$structure["fieldname"] 	= "included_units";
	$structure["type"]		= "input";
	$structure["options"]["req"]	= "yes";
	$form->add_input($structure);
	
	$structure = form_helper_prepare_radiofromdb("billing_cycle", "SELECT id, name as label FROM billing_cycles");
	$structure["options"]["req"]	= "yes";
	$form->add_input($structure);

	$structure = NULL;
	$structure["fieldname"] 	= "description";
	$structure["type"]		= "textarea";
	$form->add_input($structure);


	// submit button
	if (user_permissions_get("services_write"))
	{
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Save Changes";
		$form->add_input($structure);
	}
	else
	{
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "message";
		$structure["defaultvalue"]	= "<p><i>Sorry, you don't have permissions to make changes to services.</i></p>";
		$form->add_input($structure);
	}


	// hidden data
	$structure = NULL;
	$structure["fieldname"] 	= "id_service";
	$structure["type"]		= "hidden";
	$structure["defaultvalue"]	= "$serviceid";
	$form->add_input($structure);
	
	// define subforms
	$form->subforms["service_plan"]		= array("name_service", "price", "included_units", "billing_cycle");
	$form->subforms["submit"]		= array("submit");
	$form->subforms["hidden"]		= array("id_service");



	/*
		Load Data
	*/
	if ($mode == "add")
	{
		$form->load_data_error();
	}
	else
	{
		$form->sql_query = "SELECT * FROM `services` WHERE id='$serviceid' LIMIT 1";		
		$form->load_data();
	}


	/*
		Display Form Information
	*/
	$form->render_form();


	return 1;
	
} // end of services_forms_plan_render




/*
	service_form_plan_process()

	Processes data from the services form page.
*/
function service_form_plan_process()
{
	log_debug("inc_services_forms", "Executing service_form_plan_process()");

	
	/*
		Fetch all form data
	*/


	$id				= security_form_input_predefined("int", "id_service", 1, "");
	
	// general details
	$data["price"]			= security_form_input_predefined("money", "price", 1, "");
	$data["included_units"]		= security_form_input_predefined("int", "included_units", 1, "");
	$data["billing_cycle"]		= security_form_input_predefined("int", "billing_cycle", 1, "");


	// make sure that the service actually exists
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM services WHERE id='$id'";
	$sql_obj->execute();

	if (!$sql_obj->num_rows())
	{
		$_SESSION["error"]["message"][] = "The service you have attempted to edit - $id - does not exist in this system.";
	}



	//// ERROR CHECKING ///////////////////////


	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"]["service_plan"] = "failed";
		header("Location: ../index.php?page=services/plan.php&id=$id");
		
		exit(0);
	}
	else
	{
		/*
			Update plan details
		*/
			
		$sql_obj = New sql_query;
			
		$sql_obj->string = "UPDATE services SET "
					."price='". $data["price"] ."', "
					."included_units='". $data["included_units"] ."', "
					."billing_cycle='". $data["billing_cycle"] ."' "
					."WHERE id='$id'";
			
		if (!$sql_obj->execute())
		{
			$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst attempting to save changes";
		}
		else
		{
			$_SESSION["notification"]["message"][] = "Service successfully updated.";
			journal_quickadd_event("services", $id, "Service plan configuration changed");
		}

		// display updated details
		header("Location: ../index.php?page=services/plan.php&id=$id");
		exit(0);

	} // end if passed tests


} // end if service_form_plan_process





?>
