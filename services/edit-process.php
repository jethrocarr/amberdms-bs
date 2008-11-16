<?php
/*
	services/edit-process.php

	access: services_write

	Allows existing services to be adjusted, or new services to be added.
*/

// includes
require("../include/config.php");
require("../include/amberphplib/main.php");


// custom includes
require("../include/accounts/inc_charts.php");
require("../include/services/inc_services_details.php");


if (user_permissions_get('services_write'))
{
	/////////////////////////

	service_form_details_process();

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
