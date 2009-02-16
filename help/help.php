<?php
/*
	help.php

	This page contains links to all the tools that the user is allowed to access.
*/

class page_output
{
	function check_permissions()
	{
		// permit all users.
		return user_online();
	}


	function check_requirements()
	{
		// nothing todo
		return 1;
	}
	
	function execute()
	{
		// nothing todo
		return 1;
	}

	function render_html()
	{
		print "<h3>PROGRAM MANUALS</h3>";
		print "<p>This page lists all the program manuals available for download.</p>";


		// standard user guide
		print "<br><br>";
		print "<h3>USER GUIDE</h3>";
		print "<p>If you are looking for information about using and configuring the Amberdms Billing System, download the user guide using the button below</p>";
		print "<a target=\"new\" href=\"help/manuals/amberdms_billing_system_userguide.pdf\"><img src=\"images/buttons/button_user_guide.png\" border=\"0\"></img></a><br>";
		print "<br>";


		// administration guides
		print "<br><br>";
		print "<h3>SYSADMIN MANUALS</h3>";
		print "<p>The following manuals are intended for system administrators installing or upgrading this program.</p>";
		print "<a target=\"new\" href=\"help/manuals/amberdms_billing_system_installguide.pdf\">Amberdms Billing System Installation Guide</a><br>";
		print "<br>";


		// developer manuals
		print "<br><br>";
		print "<h3>DEVELOPER MANUALS</h3>";
		print "<p>The following manuals are intended for developers who want to write their own programs capable of talking to the Amberdms Billing
			System via the SOAP API, people interested in the code internals of the Amberdms Billing System or engineers wanting to intergrate
			their monitoring scripts with the service usage functions.</p>";

		print "<p><i>The developer documentation is currently undergoing final editing and accuracy checking and will be released with version 1.1.0
			on 2nd March 2009. If you would like a draft copy, you are welcome to request one from support@amberdms.com but there is no guarantee
			to their accuracy.</i></p>";
//		print "<a target=\"new\" href=\"help/manuals/amberdms_billing_system_API_developer_docs.pdf\">Amberdms Billing System API Developer Documentation</a><br>";
//		print "<br>";
//		print "<a target=\"new\" href=\"help/manuals/amberdms_billing_system_service_usage_integration.pdf\">Amberdms Billing System Service Usage Integration</a><br>";
//		print "<br>";


		// mailing lists
		print "<br><br>";
		print "<h3>MAILING LISTS</h3>";
		print "<p>The following mailing lists may also be useful for finding out additional information, upcomming product features or dicussing
			development details with the Amberdms programmers.</p>";
		print "<a target=\"new\" href=\"http://lists.amberdms.com/mailman/listinfo/amberdms-bs\">Amberdms Billing System General User Mailing List</a><br>";
		print "<a target=\"new\" href=\"http://lists.amberdms.com/mailman/listinfo/amberdms-bs-devel\">Amberdms Billing System Developers Mailing List</a><br>";
		print "<br>";
		
	}
}


?>
