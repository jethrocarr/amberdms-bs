<?php
/*
	include/services/inc_services_plan.php

	Provides forms and functions for managing service plan data.

	There are different types of services, and this page will show different options for each service type.
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
		Fetch plan type information
	*/
	$sql_plan_obj		 = New sql_query;
	$sql_plan_obj->string	 = "SELECT services.typeid, service_types.name FROM services LEFT JOIN service_types ON service_types.id = services.typeid WHERE services.id='$serviceid' LIMIT 1";
	$sql_plan_obj->execute();
	$sql_plan_obj->fetch_array();




	/*
		Define form structure
	*/
	$form = New form_input;
	$form->formname = "service_plan";
	$form->language = $_SESSION["user"]["lang"];

	$form->action = "services/plan-edit-process.php";
	$form->method = "post";


	// general details
	$structure = NULL;
	$structure["fieldname"] 	= "name_service";
	$structure["type"]		= "text";
	$form->add_input($structure);


	$structure = form_helper_prepare_radiofromdb("billing_cycle", "SELECT id, name as label FROM billing_cycles");
	$structure["options"]["req"]	= "yes";
	$form->add_input($structure);
	
	$structure = NULL;
	$structure["fieldname"] 	= "price";
	$structure["type"]		= "input";
	$structure["options"]["req"]	= "yes";
	$form->add_input($structure);


	$form->subforms["service_plan"]		= array("name_service", "price", "billing_cycle");



	/*
		Type-specific Form Options
	*/

	switch ($sql_plan_obj->data[0]["name"])
	{
		case "generic_with_usage":
			/*
				GENERIC_WITH_USAGE

				This service is to be used for any non-traffic, non-time accounting service that needs to track usage. Examples of this
				could be counting the number of API requests, size of disk usage on a vhost, etc.
			*/
			
			$structure = NULL;
			$structure["fieldname"]		= "plan_information";
			$structure["type"]		= "message";
			$structure["defaultvalue"]	= "<i>This section is where you define what units you wish to bill in, along with the cost of excess units. It is acceptable to leave the price for extra units set to 0.00 if you have some other method of handling excess usage (eg: rate shaping rather than billing). If you wish to create an uncapped/unlimited usage service, set both the price for extra units and the included units to 0.</i>";
			$form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"]			= "units";
			$structure["type"]			= "input";
			$structure["options"]["req"]		= "yes";
			$structure["options"]["autoselect"]	= "yes";	
			$form->add_input($structure);
	
			$structure = NULL;
			$structure["fieldname"] 	= "included_units";
			$structure["type"]		= "input";
			$structure["options"]["req"]	= "yes";
			$form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"] 	= "price_extraunits";
			$structure["type"]		= "input";
			$structure["options"]["req"]	= "yes";
			$form->add_input($structure);

			$structure = form_helper_prepare_radiofromdb("usage_mode", "SELECT id, description as label FROM service_usage_modes");
			$structure["options"]["req"]		= "yes";
			$form->add_input($structure);

			
			$form->subforms["service_plan_custom"] = array("plan_information", "units", "included_units", "price_extraunits", "usage_mode");
	
		break;
		
		case "licenses":
			/*
				LICENSES

			*/
			$structure = NULL;
			$structure["fieldname"]		= "plan_information";
			$structure["type"]		= "message";
			$structure["defaultvalue"]	= "<i>For licenses services, the price field and included units specify how much for the number of base licenses. The extra units price field specifies how much for additional licenses.</i>";
			$form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"]			= "units";
			$structure["type"]			= "input";
			$structure["options"]["req"]		= "yes";
			$structure["options"]["autoselect"]	= "yes";
			$form->add_input($structure);
	
			$structure = NULL;
			$structure["fieldname"] 	= "included_units";
			$structure["type"]		= "input";
			$structure["options"]["req"]	= "yes";
			$form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"] 	= "price_extraunits";
			$structure["type"]		= "input";
			$structure["options"]["req"]	= "yes";
			$form->add_input($structure);

			
			$form->subforms["service_plan_custom"] = array("plan_information", "units", "included_units", "price_extraunits");
		break;


		
		case "time":
		case "data_traffic":
			/*
				TIME or DATA_TRAFFIC

				Incrementing usage counters.
			*/
			$structure = NULL;
			$structure["fieldname"]		= "plan_information";
			$structure["type"]		= "message";
			$structure["defaultvalue"]	= "<i>This section is where you define what units you wish to bill in, along with the cost of excess units. It is acceptable to leave the price for extra units set to 0.00 if you have some other method of handling excess usage (eg: rate shaping rather than billing). If you wish to create an uncapped/unlimited usage service, set both the price for extra units and the included units to 0.</i>";
			$form->add_input($structure);

			$structure = form_helper_prepare_radiofromdb("units", "SELECT id, name as label, description as label1 FROM service_units WHERE typeid='". $sql_plan_obj->data[0]["typeid"] ."' ORDER BY name");
			$structure["options"]["req"]		= "yes";
			$structure["options"]["autoselect"]	= "yes";
			$form->add_input($structure);
	
			$structure = NULL;
			$structure["fieldname"] 	= "included_units";
			$structure["type"]		= "input";
			$structure["options"]["req"]	= "yes";
			$form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"] 	= "price_extraunits";
			$structure["type"]		= "input";
			$structure["options"]["req"]	= "yes";
			$form->add_input($structure);

			
			$form->subforms["service_plan_custom"] = array("plan_information", "units", "included_units", "price_extraunits");
		break;


		case "licenses":
		case "generic_no_usage":
		default:
			// no extra fields to display
		
		break;
	}



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
	$form->subforms["hidden"]		= array("id_service");
	$form->subforms["submit"]		= array("submit");



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
	$data["price"]			= security_form_input_predefined("money", "price", 0, "");
	$data["billing_cycle"]		= security_form_input_predefined("int", "billing_cycle", 1, "");

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
		case "data":
			$data["units"]			= security_form_input_predefined("int", "units", 1, "");
			$data["included_units"]		= security_form_input_predefined("int", "included_units", 0, "");
			$data["price_extraunits"]	= security_form_input_predefined("float", "price_extraunits", 0, "");
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
			case "data":

				$sql_obj->string = "UPDATE services SET "
						."price='". $data["price"] ."', "
						."units='". $data["units"] ."', "
						."price_extraunits='". $data["price_extraunits"] ."', "
						."included_units='". $data["included_units"] ."', "
						."billing_cycle='". $data["billing_cycle"] ."' "
						."WHERE id='$id'";
	
				
			break;
			
			case "generic_with_usage":

				$sql_obj->string = "UPDATE services SET "
						."price='". $data["price"] ."', "
						."units='". $data["units"] ."', "
						."price_extraunits='". $data["price_extraunits"] ."', "
						."included_units='". $data["included_units"] ."', "
						."billing_cycle='". $data["billing_cycle"] ."', "
						."usage_mode='". $data["usage_mode"] ."' "
						."WHERE id='$id'";
	
			break;
			

			case "licenses":
			
				$sql_obj->string = "UPDATE services SET "
						."price='". $data["price"] ."', "
						."units='". $data["units"] ."', "
						."price_extraunits='". $data["price_extraunits"] ."', "
						."included_units='". $data["included_units"] ."', "
						."billing_cycle='". $data["billing_cycle"] ."' "
						."WHERE id='$id'";
			break;
			
			case "generic_no_usage":
			default:

				$sql_obj->string = "UPDATE services SET "
						."price='". $data["price"] ."', "
						."billing_cycle='". $data["billing_cycle"] ."' "
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





?>
