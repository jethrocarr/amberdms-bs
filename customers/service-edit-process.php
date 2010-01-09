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

	$customerid			= @security_form_input_predefined("int", "customerid", 1, "");
	$services_customers_id		= @security_form_input_predefined("int", "services_customers_id", 0, "");
	
	if ($services_customers_id)
	{
		$mode = "edit";

		// standard fields
		$data["active"]			= @security_form_input_predefined("any", "active", 0, "");

		if ($data["active"])
			$data["active"]		= 1; // need to handle DB bool field
		
		// custom
		$data["quantity"]		= @security_form_input_predefined("int", "quantity", 0, "");

		if (!$data["quantity"])
			$data["quantity"] = 1;	// all services must have at least 1
	
	}
	else
	{
		$mode = "add";

		// standard fields
		$data["serviceid"]		= @security_form_input_predefined("any", "serviceid", 1, "");
		$data["date_period_first"]	= @security_form_input_predefined("date", "date_period_first", 1, "");
		$data["date_period_next"]	= $data["date_period_first"];
	}


	// general details		
	$data["description"]		= @security_form_input_predefined("any", "description", 0, "");




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
				Start Transaction
			*/

			$sql_obj = New sql_query;
			$sql_obj->trans_begin();


			/*
				Add new service
			*/
			
			$sql_obj->string	= "INSERT INTO `services_customers` (customerid, serviceid, date_period_first, date_period_next, description) VALUES ('$customerid', '". $data["serviceid"] ."', '". $data["date_period_first"] ."', '". $data["date_period_next"] ."', '". $data["description"] ."')";
			$sql_obj->execute();

			$services_customers_id = $sql_obj->fetch_insert_id();



			/*
				Update the Journal
			*/

			journal_quickadd_event("customers", $customerid, "New service ". $data["name_service"] ." added to account with start date of ". $data["date_period_first"] ."");


			/*
				Commit
			*/

			if (error_check())
			{
				$sql_obj->trans_rollback();

				log_write("error", "process", "An error occured whilst attemping to create the new service. No changes have been made.");
			}
			else
			{
				$sql_obj->trans_commit();
			
				log_write("notification", "process", "New service added successfully. You now need to fill in any additional fields and activate the service.");

				// flag the active field to make it clear to the user that they need to activate it.
				$_SESSION["error"]["active-error"] = 1;	
			}
			
			header("Location: ../index.php?page=customers/service-edit.php&customerid=$customerid&serviceid=$services_customers_id");
			exit(0);
		}
		else
		{
			/*
				Start Transaction
			*/

			$sql_obj = New sql_query;
			$sql_obj->trans_begin();



			/*
				Update service details
			*/

			$sql_obj->string	= "UPDATE `services_customers` SET "
							."active='". $data["active"] ."', "
							."quantity='". $data["quantity"] ."', "
							."description='". $data["description"] ."' "
							."WHERE id='$services_customers_id' LIMIT 1";

			$sql_obj->execute();



			/*
				Update the journal
			*/

			// note the status change
			if ($data["active_changed"] == "enabled")
			{
				journal_quickadd_event("customers", $customerid, "Service ". $data["name_service"] ." has been enabled.");
			}
			elseif ($data["active_changed"] == "disabled")
			{
				journal_quickadd_event("customers", $customerid, "Service ". $data["name_service"] ." has been disabled..");
			}
			
			journal_quickadd_event("customers", $customerid, "Service ". $data["name_service"] ." configuration has been updated.");



			/*
				Commit
			*/

			if (error_check())
			{
				$sql_obj->trans_rollback();

				log_write("error", "process", "An error occured whilst attempting to update service information. No changes have been made");
			}
			else
			{
				$sql_obj->trans_commit();

				log_write("notification", "process", "Service changes completed successfully.");


				// note the status change
				if ($data["active_changed"] == "enabled")
				{
					log_write("notification", "process", "Service changed to state enabled.");
				}
				elseif ($data["active_changed"] == "disabled")
				{
					log_write("notification", "process", "Service changed to state disabled.");
				}
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
