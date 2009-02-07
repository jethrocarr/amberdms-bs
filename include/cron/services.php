#!/usr/bin/php
<?php
/*
	include/cron/services.php

	This script is called daily and runs through all the customers and creates new service
	periods and invoices where required.

	Invoices are then set to customers via email automatically.
	
*/


// includes
require("../config.php");
require("../amberphplib/main.php");

// custom includes
require("../accounts/inc_ledger.php");
require("../accounts/inc_invoices.php");
require("../services/inc_services_invoicegen.php");



print "Checking for service invoices...\n";


// generate new service periods
service_periods_generate(NULL);

// generate any invoices required
service_invoices_generate(NULL);


print "Service invoicing complete.\n";


//print_r($_SESSION);

exit(0);

?>
