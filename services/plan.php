<?php
/*
	services/plan.php

	access: services_view (read-only)
		services_write (write access)

	Displays the selected service's plan and pricing information and if the user
	has correct permissions, allows the service to be updated.
*/


// include form functions
require("include/accounts/inc_charts.php");
require("include/services/inc_services_plan.php");



if (user_permissions_get('services_view'))
{
	$id = $_GET["id"];
	
	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "Service Details";
	$_SESSION["nav"]["query"][]	= "page=services/view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Service Plan";
	$_SESSION["nav"]["query"][]	= "page=services/plan.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=services/plan.php&id=$id";
	
	$_SESSION["nav"]["title"][]	= "Service Journal";
	$_SESSION["nav"]["query"][]	= "page=services/journal.php&id=$id";

	if (user_permissions_get('services_write'))
	{
		$_SESSION["nav"]["title"][]	= "Delete Service";
		$_SESSION["nav"]["query"][]	= "page=services/delete.php&id=$id";
	}


	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		print "<h3>SERVICE PLAN CONFIGURATION</h3><br>";
		print "<p>This page allows you to view and adjust the service. Note that any changes will only affect the next invoice for customers, it will not adjust any invoices that have already been created.</p>";

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM services WHERE id='$id'";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			print "<p><b>Error: The requested service does not exist. <a href=\"index.php?page=services/services.php\">Try looking for your service on the service list page.</a></b></p>";
		}
		else
		{
			/*
				Render details form
			*/
			
			services_form_plan_render($id);

		}

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
