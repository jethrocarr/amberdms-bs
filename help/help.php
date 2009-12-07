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
		print "<p>This page lists all the program manual available for download.</p>";


		// standard user guide
		print "<br><br>";
		print "<h3>USER GUIDE</h3>";
		print "<p>If you are looking for information about using and configuring the Amberdms Billing System, download the user guide using the button below</p>";
		print "<a class=\"button\" target=\"new\" href=\"help/manual/amberdms_billing_system_userguide.pdf\">Download User Guide</a><br>";
		print "<br>";


		// administration guides
		// (these options differ depending on where you are running the application)
		$subscription_support	= sql_get_singlevalue("SELECT value FROM config WHERE name='SUBSCRIPTION_SUPPORT' LIMIT 1");

		switch ($subscription_support)
		{
			case "hosted":
			case "hosted_phone":
				print "<br><br>";
				print "<h3>SYSADMIN MANUALS</h3>";
				print "<p>You are running the hosted version of the Amberdms Billing System, so no installation or upgrades are required. However, if you are interested in deploying the Amberdms Billing System open source releases elsewhere, you can download the installation manuals from our website.</p>";
				print "<a target=\"new\" class=\"button\" href=\"http://www.amberdms.com/?cms=opensource_billing\">Installation Instructions</a><br>";
				print "<br>";
			break;

			case "none":
			case "opensource":
			default:
				print "<br><br>";
				print "<h3>SYSADMIN MANUALS</h3>";
				print "<p>The following manuals are intended for system administrators installing or upgrading this program.</p>";
				print "<a class=\"button\" target=\"new\" href=\"help/manual/amberdms_billing_system_install_supported.pdf\">Installation on Supported Platforms</a><br><br>";
				print "<a class=\"button\" target=\"new\" href=\"help/manual/amberdms_billing_system_install_manual.pdf\">Manual Installation for Unsupported Platforms</a><br>";
				print "<br>";
			break;
		}


		// developer manual
		print "<br><br>";
		print "<h3>DEVELOPER MANUALS</h3>";
		print "<p>The following manual are intended for developers who want to write their own programs capable of talking to the Amberdms Billing
			System via the SOAP API, people interested in the code internals of the Amberdms Billing System or engineers wanting to intergrate
			their monitoring scripts with the service usage functions.</p>";

		print "<a class=\"button\" target=\"new\" href=\"help/manual/amberdms_billing_system_SOAP_API.pdf\">SOAP API Developer Documentation</a><br>";
		print "<br>";
		print "<a class=\"button\" target=\"new\" href=\"help/manual/amberdms_billing_system_service_usage_collectors.pdf\">Service Usage Collectors/Integration Documentation</a><br>";
		print "<br>";


		// mailing lists
		print "<br><br>";
		print "<h3>MAILING LISTS</h3>";
		print "<p>The following mailing lists may also be useful for finding out additional information, upcomming product features or dicussing
			development details with the Amberdms programmers.</p>";
		
		print "<table cellpadding=\"5\">";
		print "<tr>";
			print "<td><b>General User Discussion List</b></td>";
			print "<td><a class=\"button\" href=\"http://lists.amberdms.com/mailman/listinfo/amberdms-bs\">Sign Up</a></td>";
			print "<td><a class=\"button\" href=\"http://lists.amberdms.com/pipermail/amberdms-bs/\">Archives</a></td>";
		print "</tr>";
		print "<tr>";
		       print "<td><b>Developers Mailing List</b></td>";
		       print "<td><a class=\"button\" href=\"http://lists.amberdms.com/mailman/listinfo/amberdms-bs-devel\">Sign Up</a></td>";
		       print "<td><a class=\"button\" href=\"http://lists.amberdms.com/pipermail/amberdms-bs-devel/\">Archives</a></td>";
		print "</tr>";
		print "</table>";
		
	}
}


?>
