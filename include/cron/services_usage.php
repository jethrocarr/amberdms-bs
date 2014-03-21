#!/usr/bin/php
<?php
/*
	include/cron/services_usage.php

	Runs through all active customer services and generates usage notifications for any customers
	for services configured to alert on specific usage options.

	This cronjob should be executed after monthly billing, otherwise usage notifications
	might be triggered for a period that's ended and been billed.
*/


// custom accounts includes
require("../accounts/inc_ledger.php");
require("../accounts/inc_invoices.php");		// TODO: required?

// custom service includes
require("../services/inc_services.php");
require("../services/inc_services_generic.php");
require("../services/inc_services_cdr.php");
require("../services/inc_services_traffic.php");
require("../services/inc_services_usage.php");
require("../customers/inc_customers.php");

// legacy includes
require("../services/inc_services_invoicegen.php");	// TODO: functionise out


function page_execute()
{
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
