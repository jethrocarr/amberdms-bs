<?php
/*
	services/delete.php

	access: services_write (write access)

	Allows users to delete services which have not been added to any customers.
*/


// include form functions
require("include/services/inc_services_delete.php");


if (user_permissions_get('services_write'))
{
	$id = $_GET["id"];
	
	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;

	$_SESSION["nav"]["title"][]	= "Service Details";
	$_SESSION["nav"]["query"][]	= "page=services/view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Service Plan";
	$_SESSION["nav"]["query"][]	= "page=services/plan.php&id=$id";
	
	$_SESSION["nav"]["title"][]	= "Service Journal";
	$_SESSION["nav"]["query"][]	= "page=services/journal.php&id=$id";
	
	$_SESSION["nav"]["title"][]	= "Delete Service";
	$_SESSION["nav"]["query"][]	= "page=services/delete.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=services/delete.php&id=$id";



	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);


		/*
			Title + Summary
		*/
		print "<h3>SERVICE DELETE</h3><br>";
		print "<p>This page allows you to delete unwanted services.</p>";


		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM `services` WHERE id='$id'";
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
			
			service_form_delete_render($id);

		}

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
