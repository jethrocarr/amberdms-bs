<?php
/*
	admin/config.php
	
	access: admin users only

	Redirect page to other configuration sub pages.
*/

class page_output
{
	var $obj_form;


	function check_permissions()
	{
		return user_permissions_get("admin");
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
		print "<h3>CONFIGURATION</h3><br>";


		print "<br>";

		format_linkbox("default", "index.php?page=admin/config_company.php", "<p><b>Company Configuration</b></p>
			<p>Configure and set company details and contact information including name, address, invoicing	options and more advanced application settings.</p>");


		print "<br>";

		format_linkbox("default", "index.php?page=admin/config_locale.php", "<p><b>Locale Configuration</b></p>
			<p>Configure and set currently, default date and language settings, theme and more.</p>");


		print "<br>";

		format_linkbox("default", "index.php?page=admin/config_application.php", "<p><b>Application Configuration</b></p>
			<p>Various system settings, such as default code numbering for entries, security settings and more.</p>");


		print "<br>";

		format_linkbox("default", "index.php?page=admin/config_integration.php", "<p><b>Integration Modules</b></p>
			<p>Enable, disable and configure features that link with other applications.</p>");


		print "<br>";

		format_linkbox("default", "index.php?page=admin/config_services.php", "<p><b>Services Configuration</b></p>
			<p>Service billing configuration features and options.</p>");



	}

	
}

?>
