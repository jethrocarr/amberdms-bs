#!/usr/bin/php
<?php
/*
	include/cron/services.php

	This cronjob is called daily and will check for any services that need invoicing and
	generate appropiate invoices.
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

} // end of page_execute()


?>
