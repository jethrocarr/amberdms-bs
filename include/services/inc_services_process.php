<?php
/*
	include/services/inc_services_details.php

	Provides forms and functions for managing basic service details.
*/

// include required functions
require("../include/accounts/inc_charts.php");




/*
	service_form_details_process()

	Processes data from the services form page.
*/
function service_form_details_process()
{
	log_debug("inc_services_process", "Executing service_form_details_process()");

	
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



/*
	service_form_plan_process()

	Processes data from the services form page.
*/
function service_form_plan_process()
{
	log_debug("inc_services_process", "Executing service_form_plan_process()");

	
	/*
		Fetch all form data
	*/


	$id				= security_form_input_predefined("int", "id_service", 1, "");
	
	// general details
	$data["price"]			= security_form_input_predefined("money", "price", 0, "");
	$data["billing_cycle"]		= security_form_input_predefined("int", "billing_cycle", 1, "");
	$data["billing_mode"]		= security_form_input_predefined("int", "billing_mode", 1, "");

	// needed to handle errors, but not used
	$data["name_service"]		= security_form_input_predefined("any", "name_service", 0, "");


	// make sure that the service actually exists
	$sql_plan_obj		= New sql_query;
	$sql_plan_obj->string	 = "SELECT services.typeid, service_types.name FROM services LEFT JOIN service_types ON service_types.id = services.typeid WHERE services.id='$id' LIMIT 1";
	$sql_plan_obj->execute();

	if (!$sql_plan_obj->num_rows())
	{
		$_SESSION["error"]["message"][] = "The service you have attempted to edit - $id - does not exist in this system.";
	}
	else
	{
		$sql_plan_obj->fetch_array();
	}


	// fetch fields depending on the service type
	switch ($sql_plan_obj->data[0]["name"])
	{
		case "generic_with_usage":
			$data["units"]			= security_form_input_predefined("any", "units", 1, "");
			$data["included_units"]		= security_form_input_predefined("int", "included_units", 0, "");
			$data["price_extraunits"]	= security_form_input_predefined("float", "price_extraunits", 0, "");
			$data["usage_mode"]		= security_form_input_predefined("int", "usage_mode", 1, "");
		break;


		case "licenses":
			$data["units"]			= security_form_input_predefined("any", "units", 1, "");
			$data["included_units"]		= security_form_input_predefined("int", "included_units", 0, "");
			$data["price_extraunits"]	= security_form_input_predefined("money", "price_extraunits", 0, "");
		break;

		case "time":
		case "data_traffic":
			$data["units"]			= security_form_input_predefined("int", "units", 1, "");
			$data["included_units"]		= security_form_input_predefined("int", "included_units", 0, "");
			$data["price_extraunits"]	= security_form_input_predefined("float", "price_extraunits", 0, "");
			
			// force data usage/time to be incrementing
			$data["usage_mode"]		= sql_get_singlevalue("SELECT id as value FROM service_usage_modes WHERE name='incrementing' LIMIT 1");
		break;
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

		switch ($sql_plan_obj->data[0]["name"])
		{
			case "time":
			case "data_traffic":

				$sql_obj->string = "UPDATE services SET "
						."price='". $data["price"] ."', "
						."units='". $data["units"] ."', "
						."price_extraunits='". $data["price_extraunits"] ."', "
						."included_units='". $data["included_units"] ."', "
						."billing_cycle='". $data["billing_cycle"] ."', "
						."billing_mode='". $data["billing_mode"] ."', "
						."usage_mode='". $data["usage_mode"] ."' "
						."WHERE id='$id'";
	
				
			break;
			
			case "generic_with_usage":

				$sql_obj->string = "UPDATE services SET "
						."price='". $data["price"] ."', "
						."units='". $data["units"] ."', "
						."price_extraunits='". $data["price_extraunits"] ."', "
						."included_units='". $data["included_units"] ."', "
						."billing_cycle='". $data["billing_cycle"] ."', "
						."billing_mode='". $data["billing_mode"] ."', "
						."usage_mode='". $data["usage_mode"] ."' "
						."WHERE id='$id'";
	
			break;
			

			case "licenses":
			
				$sql_obj->string = "UPDATE services SET "
						."price='". $data["price"] ."', "
						."units='". $data["units"] ."', "
						."price_extraunits='". $data["price_extraunits"] ."', "
						."included_units='". $data["included_units"] ."', "
						."billing_cycle='". $data["billing_cycle"] ."', "
						."billing_mode='". $data["billing_mode"] ."' "
						."WHERE id='$id'";
			break;
			
			case "generic_no_usage":
			default:

				$sql_obj->string = "UPDATE services SET "
						."price='". $data["price"] ."', "
						."billing_cycle='". $data["billing_cycle"] ."', "
						."billing_mode='". $data["billing_mode"] ."' "
						."WHERE id='$id'";

			break;
		}

		if (!$sql_obj->execute())
		{
			$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst attempting to save changes";
		}




		
		if (!$_SESSION["error"]["message"])
		{
			$_SESSION["notification"]["message"][] = "Service successfully updated.";
			journal_quickadd_event("services", $id, "Service plan configuration changed");
		}

		// display updated details
		header("Location: ../index.php?page=services/plan.php&id=$id");
		exit(0);

	} // end if passed tests


} // end if service_form_plan_process






/*
	service_form_delete_process($id)

	Process the delete form to delete the requested service.

*/
function service_form_delete_process()
{
	log_debug("inc_services_process", "Executing service_form_delete_process()");

	
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
