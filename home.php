<?php
/*
	home.php

	This page contains links to all the tools that the user is allowed to access.
*/

if (!user_online())
{
	// Because this is the default page to be directed to, if the user is not
	// logged in, they should go straight to the login page.
	//
	// All other pages will display an error and prompt the user to login.
	//
	include_once("user/login.php");
}
else
{
	class page_output
	{
		function check_permissions()
		{
			// this page has a special method for handling permissions - please refer to code comments above
			return 1;
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
			print "<h3>OVERVIEW</h3>";
			print "<p>Welcome to the Amberdms Billing System, a powerful, fully <a target=\"new\" href=\"http://www.amberdms.com/opensource\">open source</a> web-based application providing accounting, invoicing, service management and time management functions.</p>";


			format_msgbox("gettingstarted", "<p>To get started, use the menu above or download the product manuals (including a detailed user guide) using the buttons below.</p>");

			print "<p>";
				print "<a class=\"button\" target=\"new\" href=\"help/manual/amberdms_billing_system_userguide.pdf\">Download User Guide</a> ";
				print "<a class=\"button\" href=\"index.php?page=help/help.php\">Other Manuals</a> ";
			print "</p>";

			print "<br><br>";



			/*
				COMMERCIAL SUPPORT PACKAGES
			*/
			
			print "<br><br>";

			// get the support setting from the DB to display the suitable support package information
			// to the end user.
			$subscription_support	= sql_get_singlevalue("SELECT value FROM config WHERE name='SUBSCRIPTION_SUPPORT' LIMIT 1");
			$subscription_id	= sql_get_singlevalue("SELECT value FROM config WHERE name='SUBSCRIPTION_ID' LIMIT 1");

			switch ($subscription_support)
			{
				case "hosted":
					print "<h3>GET SUPPORT</h3>";

					format_msgbox("info", "<p>Amberdms provides unlimited support via email for any enquiries. To open a support ticket, please email support@amberdms.com and state your customer ID (#$subscription_id).</p>");
						
					print "<p><a class=\"button\" href=\"mailto:support@amberdms.com?subject=support request for customer $subscription_id\">Email Amberdms</a></p>";
				break;

				case "hosted_phone":
					print "<p>Amberdms provides unlimited support via email at support@amberdms.com or you can call us for phone support.</p>";
					print "<p>";
						print "<a target=\"new\" class=\"button\" href=\"mailto:support@amberdms.com\">support@amberdms.com</a> ";
						print " <a class=\"button\" href=\"http://www.amberdms.com/contact\"></a> ";
					print "</p>";

					format_msgbox("info", "Please state your customer ID ($subscription_id) in your email or when requested by a support representative");
				break;

				case "none":
				case "opensource":
				default:
					print "<h3>COMMERCIAL SUPPORT</h3>";

					print "<p>You are running the open source version of the Amberdms Billing System, if you would like to recieve
						commercial support from Amberdms, sign up to one of our low cost enterprise plans to get priority support services
						and unlimited bug resolutions.</p>";

					print "<p>Amberdms also provided hosted versions of the Amberdms Billing System, eliminating
						the expense and hassles of running your own servers and providing included support services.</p>";

					print "<p>Otherwise, you can get support services from Amberdms on an hourly or quoted basis, or alternatively join the community
						mailing list and recieve help from members of the community.</p>";

					print "<p>";
						print "<a target=\"new\" class=\"button\" href=\"http://www.amberdms.com/contact\">Contact Amberdms</a> ";
						print "<a target=\"new\" class=\"button\" href=\"http://www.amberdms.com/?cms=products_billing_hosted\">Hosted Version</a> ";
						print "<a target=\"new\" class=\"button\" href=\"http://www.amberdms.com/?cms=products_billing_plans\">Enterprise Version</a> ";
					print "</p>";

				break;
			}



			/*
				Community Mailing List
			*/
			print "<br><br>";
			print "<h3>COMMUNITY MAILING LIST</h3>";

			print "<p>You may also be able to get support and discuss the Amberdms Billing System on the community mailing lists:</p>";

			print "<table cellpadding=\"5\">";
			print "<tr>";
				print "<td><b>General User Discussion List</b></td>";
				print "<td><a target=\"new\" class=\"button\" href=\"http://lists.amberdms.com/mailman/listinfo/amberdms-bs\">Sign Up</a></td>";
				print "<td><a target=\"new\" class=\"button\" href=\"http://lists.amberdms.com/pipermail/amberdms-bs/\">Archives</a></td>";
			print "</tr>";
      			print "<tr>";
				print "<td><b>Developers Mailing List</b></td>";
				print "<td><a target=\"new\" class=\"button\" href=\"http://lists.amberdms.com/mailman/listinfo/amberdms-bs-devel\">Sign Up</a></td>";
				print "<td><a target=\"new\" class=\"button\" href=\"http://lists.amberdms.com/pipermail/amberdms-bs-devel/\">Archives</a></td>";
			print "</tr>";
			print "</table>";

						

			/*
				Customisations
			*/
			print "<br><br><br>";
			print "<h3>CUSTOMISATIONS</h3>";

			print "<p>Not everyone has the exact same business, which is why Amberdms offers product customisation services. If you
				require a particular feature or want to integrate other products with the Amberdms Billing System, talk to us
				and we can give you competitive quotes and service from the developers who designed and wrote this program.</p>";

			print "<a target=\"new\" class=\"button\" href=\"http://www.amberdms.com/contact\">Contact Amberdms</a><br>";



			/*
				Open Source
			*/
			print "<br><br><br>";
			print "<h3>OPEN SOURCE</h3>";

			print "<p>The Amberdms Billing System is a fully open source program under
				the <a target=\"new\" href=\"help/docs/COPYING\">GNU AGPL software license</a> - this means anyone can download the source
				code for this program and make modifications or run it on your own servers. The AGPL also requires that anyone running the Amberdms
				Billing System provides a download for any changes that they have made to the software.</p>";
	
			switch ($subscription_support)
			{
				case "hosted":
				case "hosted_phone":
					print "<p>All code in the hosted version of the Amberdms Billing System is available as part of our open
						source download as well as administrators being capable of <a href=\"index.php?page=admin/db_backup.php\">exporting all data in SQL format</a>.</p>";
			
					print "<p>";
						print "<a target=\"new\" class=\"button\" href=\"http://www.amberdms.com/?cms=opensource_billing\">Download Source Code</a> ";
						print "<a class=\"button\" href=\"index.php?page=admin/db_backup.php\">Export Database</a> ";
					print "</p>";
				break;

				case "enterprise":
				case "opensource":
				default:
					print "<p>There are two options for downloading the source code provided - you can either download the offical source code from the Amberdms website, or you
						can download a diff of the changes made to the Amberdms Billing System (if any).</p>";

					print "<p>";
						print "<a class=\"button\" href=\"index.php?page=source/getsource.php\">Download Source Code</a> ";
						print "<a class=\"button\" href=\"index.php?page=admin/db_backup.php\">Export Database</a> ";
					print "</p>";
				break;
			}

		}
	}
}


?>
