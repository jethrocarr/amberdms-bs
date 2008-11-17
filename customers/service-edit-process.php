<?php
/*
	customers/service-edit-process.php

	access: customers_write

	Allows new services to be added to customers, or existing ones to be modified
*/

// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");


if (user_permissions_get('customers_write'))
{
	/////////////////////////

	$customerid			= security_form_input_predefined("int", "customerid", 1, "");
	$services_customers_id		= security_form_input_predefined("int", "services_customers_id", 0, "");
	
	if ($services_customers_id)
	{
		$mode = "edit";

		// edit fields
		$data["active"]			= security_form_input_predefined("any", "active", 0, "");

		if ($data["active"])
			$data["active"]		= 1; // need to handle DB bool field
		
		$data["date_billed_last"]	= security_form_input_predefined("date", "date_billed_last", 0, "");
		$data["date_billed_next"]	= security_form_input_predefined("date", "date_billed_next", 0, "");
	}
	else
	{
		$mode = "add";
	
		// add fields
		$data["serviceid"]		= security_form_input_predefined("any", "serviceid", 1, "");
		$data["date_billed_first"]	= security_form_input_predefined("date", "date_billed_first", 1, "");
	}


	// general details		
	$data["description"]		= security_form_input_predefined("any", "description", 0, "");




	//// VERIFY PROJECT/PHASE IDS /////////////
	

	// check that the specified customer actually exists
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM `customers` WHERE id='$customerid'";
	$sql_obj->execute();
	
	if (!$sql_obj->num_rows())
	{
		$_SESSION["error"]["message"][] = "The customer you have attempted to edit - $customerid - does not exist in this system.";
	}
	else
	{
		if ($services_customers_id)
		{
			// are we editing an existing service? make sure it exists and belongs to this customer
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT customerid, serviceid, active FROM `services_customers` WHERE id='$services_customers_id' LIMIT 1";
			$sql_obj->execute();

			if (!$sql_obj->num_rows())
			{
				$_SESSION["error"]["message"][] = "The service you have attempted to edit - $services_customers_id - does not exist in this system.";
			}
			else
			{
				$sql_obj->fetch_array();

				// remember the service ID, we will use it further on
				$data["serviceid"] = $sql_obj->data[0]["serviceid"];


				// check the customer ID
				if ($sql_obj->data[0]["customerid"] != $customerid)
				{
					$_SESSION["error"]["message"][] = "The requested service does not match the provided customer ID. Potential application bug?";
				}


				// check if the active status has been changed
				if ($sql_obj->data[0]["active"] == 0 && $data["active"] == 1)
				{
					// enabled
					$data["active_changed"] = "enabled";
				}
				elseif ($sql_obj->data[0]["active"] == 1 && $data["active"] == 0)
				{
					// disabled
					$data["active_changed"] = "disabled";
				}
				
			}
		}
	}



		
	//// ERROR CHECKING ///////////////////////


	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"]["service_view"] = "failed";
		header("Location: ../index.php?page=customers/service-edit.php&customerid=$customerid&serviceid=$services_customers_id");
		exit(0);
	}
	else
	{
		// fetch the name of the service, as we need this for some of the journal entries.
		$data["name_service"]	= sql_get_singlevalue("SELECT name_service as value FROM services WHERE id='". $data["serviceid"] ."'");

	
		if ($mode == "add")
		{
			/*
				Add new service
			*/
			
			$sql_obj		= New sql_query;
			$sql_obj->string	= "INSERT INTO `services_customers` (customerid, serviceid, date_billed_first, description) VALUES ('$customerid', '". $data["serviceid"] ."', '". $data["date_billed_first"] ."', '". $data["description"] ."')";
			
			if (!$sql_obj->execute())
			{
				$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst attempting to subscribe customer to service";
			}

			$services_customers_id = $sql_obj->fetch_insert_id();


			/*
				Complete
			*/
			
			if (!$_SESSION["error"]["message"])
			{
				$_SESSION["notification"]["message"][] = "New service added successfully. You now need to fill in any additional fields and activate the service.";
				journal_quickadd_event("customers", $customerid, "New service ". $data["name_service"] ." added to account with start date of ". $data["date_billed_first"] ."");
			}
			
			header("Location: ../index.php?page=customers/service-edit.php&customerid=$customerid&serviceid=$services_customers_id");
			exit(0);
		}
		else
		{
			/*
				Update service details
			*/
			
			$sql_obj		= New sql_query;
			$sql_obj->string	= "UPDATE `services_customers` SET "
							."active='". $data["active"] ."', "
							."date_billed_last='". $data["date_billed_last"] ."', "
							."date_billed_next='". $data["date_billed_next"] ."', "
							."description='". $data["description"] ."' "
							."WHERE id='$services_customers_id'";

			if (!$sql_obj->execute())
			{
				$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst attempting to subscribe customer to service";
			}



			/*
				Complete
			*/

			// note the status change
			if ($data["active_changed"] == "enabled")
			{
				$_SESSION["notification"]["message"][] = "Service changed to state enabled.";
				journal_quickadd_event("customers", $customerid, "Service ". $data["name_service"] ." has been enabled.");
			}
			elseif ($data["active_changed"] == "disabled")
			{
				$_SESSION["notification"]["message"][] = "Service changed to state disabled.";
				journal_quickadd_event("customers", $customerid, "Service ". $data["name_service"] ." has been disabled..");
			}
			

			// success message
			if (!$_SESSION["error"]["message"])
			{
				$_SESSION["notification"]["message"][] = "Service changes completed successfully.";
				journal_quickadd_event("customers", $customerid, "Service ". $data["name_service"] ." configuration has been updated.");
			}


			// return to services page
			header("Location: ../index.php?page=customers/services.php&id=$customerid");
			exit(0);
			
		}
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
