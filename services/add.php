<?php
/*
	services/add.php
	
	access: services_write

	Form to add a new service to the database.
*/

// include form functions
require("include/accounts/inc_charts.php");
require("include/services/inc_services_details.php");


if (user_permissions_get('services_write'))
{
	function page_render()
	{
		/*
			Title + Summary
		*/
		print "<h3>ADD SERVICE</h3><br>";
		print "<p>This page allows you to add a new service.</p>";


		/*
			Render details form
		*/
		services_form_details_render(0, "add");

		
	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
