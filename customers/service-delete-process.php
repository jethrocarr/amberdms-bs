<?php
/*
	customers/service-delete-process.php

	access: customers_write

	Deletes an unwanted service.
*/

// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");


if (user_permissions_get('customers_write'))
{
	/////////////////////////

	// basic input
	$customerid			= security_form_input_predefined("int", "customerid", 1, "");
	$services_customers_id		= security_form_input_predefined("int", "services_customers_id", 1, "");
	
	// these exist to make error handling work right
	$data["name_service"]		= security_form_input_predefined("any", "name_service", 0, "");
	$data["description"]		= security_form_input_predefined("any", "description", 0, "");

	// confirm deletion
	$data["delete_confirm"]		= security_form_input_predefined("any", "delete_confirm", 1, "You must confirm the deletion");
	


	//// ERROR CHECKING ///////////////////////


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
			$sql_obj->string	= "SELECT customerid, serviceid FROM `services_customers` WHERE id='$services_customers_id' LIMIT 1";
			$sql_obj->execute();

			if (!$sql_obj->num_rows())
			{
				$_SESSION["error"]["message"][] = "The service you have attempted to edit - $services_customers_id - does not exist in this system.";
			}
			else
			{
				$sql_obj->fetch_array();

				$serviceid = $sql_obj->data[0]["serviceid"];

				if ($sql_obj->data[0]["customerid"] != $customerid)
				{
					$_SESSION["error"]["message"][] = "The requested service does not match the provided customer ID. Potential application bug?";
				}
				
			}
		}
	}



		


	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"]["service_delete"] = "failed";
		header("Location: ../index.php?page=customers/service-delete.php&customerid=$customerid&serviceid=$services_customers_id");
		exit(0);
	}
	else
	{
		// fetch the name of the service, as we need this for some of the journal entries.
		$data["name_service"]	= sql_get_singlevalue("SELECT name_service as value FROM services WHERE id='$serviceid'");
	


		/*
			Start Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();


		/*
			Delete service
		*/
			
		$sql_obj->string	= "DELETE FROM services_customers WHERE id='$services_customers_id' LIMIT 1";
		$sql_obj->execute();

		$sql_obj->string	= "DELETE FROM services_customers_options WHERE services_customers_id='$services_customers_id'";
		$sql_obj->execute();



		/*
			Delete service period history
		*/
			
		$sql_obj->string	= "DELETE FROM services_customers_periods WHERE services_customers_id='$services_customers_id'";
		$sql_obj->execute();


		/*
			Delete service usage records
		*/
			
		$sql_obj->string	= "DELETE FROM service_usage_records WHERE services_customers_id='$services_customers_id'";
		$sql_obj->execute();



		/*
			Update Journal
		*/
		
		journal_quickadd_event("customers", $customerid, "Service ". $data["name_service"] ." has been deleted from this customers account.");



		/*
			Commit
		*/

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "process", "An error occured whilst attempting to delete the service from the customer's account. No changes were made.");
		
			$_SESSION["error"]["form"]["service_delete"] = "failed";
			header("Location: ../index.php?page=customers/service-delete.php&customerid=$customerid&serviceid=$services_customers_id");
			exit(0);
		}
		else
		{
			$sql_obj->trans_commit();

			log_write("notification", "process", "Service has been successfully deleted.");
		
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
