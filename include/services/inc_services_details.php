<?php
/*
	include/services/inc_services_details.php

	Provides forms and functions for managing basic service details.
*/




/*
	FUNCTIONS
*/



/*
	services_form_details_render

	Values
	serviceid	ID of the service entry (none for an add form)
	mode		Either "add" or "edit"

	Return Codes
	0		failure
	1		success
*/
function services_form_details_render($serviceid, $mode)
{
	log_debug("inc_services_forms", "Executing services_forms_details_render($serviceid)");


	/*
		Define form structure
	*/
	$form = New form_input;
	$form->formname = "service_$mode";
	$form->language = $_SESSION["user"]["lang"];

	$form->action = "services/edit-process.php";
	$form->method = "post";

	// general
	$structure = NULL;
	$structure["fieldname"] 	= "name_service";
	$structure["type"]		= "input";
	$structure["options"]["req"]	= "yes";
	$form->add_input($structure);

	$structure = charts_form_prepare_acccountdropdown("chartid", 2);
	$structure["options"]["req"]	= "yes";
	$form->add_input($structure);
	
	$structure = NULL;
	$structure["fieldname"] 	= "description";
	$structure["type"]		= "textarea";
	$form->add_input($structure);


	// the service type can only be set at creation time.
	if ($mode == "add")
	{
		$structure = form_helper_prepare_radiofromdb("typeid", "SELECT id, name as label FROM service_types ORDER BY name");
		$structure["options"]["req"]	= "yes";
		$form->add_input($structure);
	}
	else
	{
		$structure			= NULL;
		$structure["fieldname"]		= "typeid";
		$structure["type"]		= "text";
		$form->add_input($structure);
	}

	// define subforms
	$form->subforms["service_details"]	= array("name_service", "chartid", "typeid", "description");
	$form->subforms["submit"]		= array("submit");


	/*
		Mode dependent options
	*/
	
	if ($mode == "add")
	{
		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Create Service";
		$form->add_input($structure);
	}
	else
	{
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
			

		$form->subforms["hidden"]	= array("id_service");
	}


	/*
		Load Data
	*/
	if ($mode == "add")
	{
		$form->load_data_error();
	}
	else
	{
		// load details data
		$form->sql_query = "SELECT services.name_service, services.chartid, services.description, service_types.name as typeid FROM `services` LEFT JOIN service_types ON service_types.id = services.typeid WHERE services.id='$serviceid' LIMIT 1";
		$form->load_data();
	}


	/*
		Display Form Information
	*/
	$form->render_form();


	return 1;
	
} // end of services_forms_details_render




/*
	service_form_details_process()

	Processes data from the services form page.
*/
function service_form_details_process()
{
	log_debug("inc_services_forms", "Executing service_form_details_process()");

	
	/*
		Fetch all form data
	*/


	// get the ID for an edit
	if ($_POST["id_service"])
	{
		$id	= security_form_input_predefined("int", "id_service", 1, "");
		$mode	= "edit";
	}
	else
	{
		$id	= NULL;
		$mode	= "add";
	}
	

	// general details
	$data["name_service"]		= security_form_input_predefined("any", "name_service", 1, "");
	$data["chartid"]		= security_form_input_predefined("int", "chartid", 1, "");
	$data["description"]		= security_form_input_predefined("any", "description", 0, "");


	// are we editing an existing service or adding a new one?
	if ($id)
	{
		$mode = "edit";

		// make sure that the service actually exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM services WHERE id='$id'";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			$_SESSION["error"]["message"][] = "The service you have attempted to edit - $id - does not exist in this system.";
		}
	}
	else
	{
		$mode = "add";
		
		// only fetch the type ID when adding new services
		$data["typeid"]	= security_form_input_predefined("int", "typeid", 1, "");
	}


	//// ERROR CHECKING ///////////////////////


	// make sure we don't choose a service name that is already in use
	if ($data["code_service"])
	{
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM services WHERE name_service='". $data["name_service"] ."'";
		
		if ($id)
			$sql_obj->string .= " AND id!='$id'";
	
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$_SESSION["error"]["message"][]			= "This service name is already in use by another service. Please choose a unique name.";
			$_SESSION["error"]["name_service-error"]	= 1;
		}
	}


	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"]["service_view"] = "failed";

		if ($mode == "add")
		{
			header("Location: ../index.php?page=services/add.php");
		}
		else
		{
			header("Location: ../index.php?page=services/view.php&id=$id");
		}
		
		exit(0);
	}
	else
	{
		// APPLY GENERAL OPTIONS
		if ($mode == "add")
		{
			/*
				Create new service
			*/
			
			$sql_obj		= New sql_query;
			$sql_obj->string	= "INSERT INTO services (name_service, typeid) VALUES ('".$data["name_service"]."', '". $data["typeid"] ."')";
			if (!$sql_obj->execute())
			{
				$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst attempting to create service";
			}

			$id = $sql_obj->fetch_insert_id();
		}

		if ($id)
		{
			/*
				Update general service details
			*/
			
			$sql_obj = New sql_query;
			
			$sql_obj->string = "UPDATE services SET "
						."name_service='". $data["name_service"] ."', "
						."chartid='". $data["chartid"] ."', "
						."description='". $data["description"] ."' "
						."WHERE id='$id'";
			
			if (!$sql_obj->execute())
			{
				$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst attempting to save changes";
			}
			else
			{
				if ($mode == "add")
				{
					$_SESSION["notification"]["message"][] = "Service successfully created.";
					journal_quickadd_event("services", $id, "Service successfully created");
					
					// take user to plan configuration page
					header("Location: ../index.php?page=services/plan.php&id=$id");
					exit(0);
				}
				else
				{
					$_SESSION["notification"]["message"][] = "Service successfully updated.";
					journal_quickadd_event("services", $id, "Service successfully updated");


					// display updated details
					header("Location: ../index.php?page=services/view.php&id=$id");
					exit(0);
				}
				
			}
			
		} // end if ID

	} // end if passed tests


} // end if service_form_details_process





?>
