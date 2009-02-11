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
			print "<p>Welcome to the Amberdms Billing System.</p>";

			print "<p>To get started, use the links above or download the user guide for step-by-step instructions for
				using this program.</p>";

			print "<a target=\"new\" href=\"help/user_guide.pdf\"><img src=\"images/buttons/button_user_guide.png\" border=\"0\"></img></a><br>";

			print "<br><br>";



			/*
				COMMERCIAL SUPPORT PACKAGES

				This section links to the amberdms website based on the user's subscription
				status to make it easy to get commercial support.
			*/
			
			print "<br><br>";
			print "<h3>COMMERCIAL SUPPORT</h3>";

			// get the support setting from the DB to display the suitable support package information
			// to the end user.
			$support_package	= sql_get_singlevalue("SELECT value FROM config WHERE name='SUBSCRIPTION_SUPPORT' LIMIT 1");
			$subscription_id	= sql_get_singlevalue("SELECT value FROM config WHERE name='SUBSCRIPTION_ID' LIMIT 1");


			switch ($support_package)
			{
				case "package":

					print "<p>For technical support or user assistance, please use the button below to view
						how much support you have and support contact details.</p>";

					print "<a target=\"new\" href=\"http://www.amberdms.com/products/billing_system/getsupport.php?subscription=$subscription_id\"><img src=\"images/buttons/button_commercial_support.png\" border=\"0\"></img></a><br>";
				break;


				case "basic":
				
					print "<p>Amberdms provides unlimited support for any technical issues with your software
						subscription for no change, however you are also eligable for phone or email
						based user assistance.</p>";

					print "<p>There is no change for technical support, but user assistance is charged to your
						account and billed monthly - pricing information is available at the link below.</p>";

					print "<a target=\"new\" href=\"http://www.amberdms.com/products/billing_system/getsupport.php?subscription=$subscription_id\"><img src=\"images/buttons/button_commercial_support.png\" border=\"0\"></img></a><br>";

				break;



				case "none":
				default:
					
					print "<p>If you would commercial support for the Amberdms Billing System, please click the
						button below to view our technical & user support packages.</p>";

					print "<p>Amberdms also provided hosted versions of the Amberdms Billing System, eliminating
						the expense and hassles of running your own servers and can include different support
						packages with this service.</p>";

					print "<a target=\"new\" href=\"http://www.amberdms.com/products/billing_system/support.php\"><img src=\"images/buttons/button_commercial_support.png\" border=\"0\"></img></a><br>";
				break;
			}

			/*
				Customisations
			*/
			print "<br><br>";
			print "<h3>CUSTOMISATIONS</h3>";

			print "<p>Not everyone has the exact same business, which is why Amberdms offers product customisation services. If you
				require a particular feature or want to integrate other products with the Amberdms Billing System, talk to us
				and we can give you competitive quotes and service from the developers who designed and wrote this program.</p>";

			print "<a target=\"new\" href=\"http://www.amberdms.com/products/billing_system/customisations.php\"><img src=\"images/buttons/button_customisations.png\" border=\"0\"></img></a><br>";



			/*
				Open Source
			*/
			print "<br><br>";
			print "<h3>OPEN SOURCE</h3>";

			print "<p>The Amberdms Billing System is a fully open source program under
				the GNU AGPL software license - this means anyone can download the source
				code for this program and make modifications or run it on your own servers.</p>";

			print "<a target=\"new\" href=\"http://www.amberdms.com/products/billing_system/source.php\"><img src=\"images/buttons/button_getthesource.png\" border=\"0\"></img></a><br>";
		}
	}
}


?>
