<?php
/*
	customers/services-checkusage-process.php

	access: customers_view

	Fetches the latest customer's usage information and also emails any usage alerts at the same time.
*/

// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");

// custom includes
include_once("../include/accounts/inc_ledger.php");
include_once("../include/accounts/inc_invoices.php");
include_once("../include/services/inc_services_usage.php");


if (user_permissions_get('customers_write'))
{
	/////////////////////////

	$id				= security_script_input('/^[0-9]*$/', $_GET["customerid"]);
	$services_customers_id		= security_script_input('/^[0-9]*$/', $_GET["serviceid"]);
	
	
	// make sure the customer actually exists
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM `customers` WHERE id='$id' LIMIT 1";
	$sql_obj->execute();
	
	if (!$sql_obj->num_rows())
	{
		$_SESSION["error"]["message"][] = "The customer you have attempted to view - $id - does not exist in this system.";
	}


		
	//// ERROR CHECKING ///////////////////////


	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		header("Location: ../index.php?page=customers/service-history.php&customerid=$id&serviceid=$services_customers_id");
		exit(0);
	}
	else
	{
		// execute functions
		//
		// note: this will actually check all services for the customer, not just the selected one, but this
		// is not a bad thing and there is no reason not to, unless performance becomes an issue.
		//
		service_usage_alerts_generate($id);

	
		// display updated details
		header("Location: ../index.php?page=customers/service-history.php&customerid=$id&serviceid=$services_customers_id");
		exit(0);
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
