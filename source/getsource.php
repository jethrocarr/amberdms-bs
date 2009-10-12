<?php
/*
	source/getsource.php
	
	access: all logged in users

	(we don't provide access to public users, since that could put performance strains on the server and
	 might reveal code that can't be made public)

	Provides downloads of Amberdms source code:
	1. Download of offical Amberdms source code.
	2. Download of customised source code.
*/

class page_output
{

	function check_permissions()
	{
		return user_online();
	}

	function check_requirements()
	{
		// nothing to do
		return 1;
	}

	function execute()
	{
		// nothing todo
		return 1;
	}


	function render_html()
	{
		// Title + Summary
		print "<h3>DOWNLOAD SOURCE CODE</h3><br>";

		print "<p>The Amberdms Billing System is a fully open source program under the <a target=\"new\" href=\"help/docs/COPYING\">GNU AGPL software license</a> - this
			means anyone can download the source code for this program and make modifications or run it on your own servers. The AGPL also requires that anyone
			running the Amberdms Billing System provides a download for any changes that they have made to the software.</p>";
	

		print "<br><br>";
		print "<h3>DOWNLOAD AMBERDMS VERSION</h3>";
		print "<p>To download the offical source code as developed by Amberdms, click the button below:</p>";
		print "<p><a target=\"new\" class=\"button\" href=\"http://www.amberdms.com/?cms=opensource_billing_download\">Download Offical Amberdms Source Code</a></p>";


		print "<br><br>";
		print "<h3>DOWNLOAD CUSTOMISED VERSION</h3>";
		print "<p>If the administrators of this server have made any changes to the application, you can download these changes as a diff or fill in a
			simple form to commit the changes back to Amberdms, so that they can be included in future releases for everyone to benefit!</p>";
		print "<p><i>Please note, it make take up to a minute to generate a patch for download, please be patient and click the button below once.</i></p>";
		print "<p><a class=\"button\" href=\"index.php?page=source/diff_generate.php\">Download Customised Source Code</a></p>";
	}

	
}

?>
