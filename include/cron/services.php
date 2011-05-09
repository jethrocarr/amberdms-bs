#!/usr/bin/php
<?php
/*
	include/cron/services.php

	This script is called daily and runs through all the customers and performs the following service tasks
	1. Creates new service periods (when required)
	2. Creates invoices (when required)
	3. Checks customer usage and sends alerts if required.
	
*/


// custom includes
require("../accounts/inc_ledger.php");
require("../accounts/inc_invoices.php");

// custom service includes
require("../services/inc_services.php");
require("../services/inc_services_invoicegen.php");
require("../services/inc_services_generic.php");
require("../services/inc_services_cdr.php");
require("../services/inc_services_traffic.php");
require("../services/inc_services_usage.php");
require("../customers/inc_customers.php");



function page_execute()
{
	print "Checking for service invoices...\n";

	// generate new service periods
	service_periods_generate(NULL);

	// generate any invoices required
	service_invoices_generate(NULL);


	print "Service invoicing complete.\n";


	// send customers usage alerts
	print "Checking customer usage alerts...\n";

	$return = service_usage_alerts_generate(NULL);

	if ($return == -1)
	{
		print "No alerts sent - EMAIL_ENABLE is disabled.\n";
	}

	print "Alerting complete.\n";

} // end of page_execute()


?>
