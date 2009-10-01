<?php
/*
	admin/db_backup
	
	access: admin users only

	Allows an administrator to perform an export of the entire MySQL database for the current running instance, this
	is usable by both the open source version of the product and the hosted SaaS version, ensuring that there is
	never any vendor lock-in.
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
		print "<h3>DATABASE BACKUP</h3><br>";
		print "<p>This page allows an administrator to perform an export of the entire MySQL database and download it as a file. This feature
			ensures that no matter who runs your instance of the Amberdms Billing System, your data can always be retrieved.</p>";

		print "<p>The file generated is a standard SQL file compressed with gzip, it can be easily restored using the MySQL command line or
			via a utility such as phpmyadmin.</p>";


		// report on usage
		$sql_obj = New sql_query;
		$usage = $sql_obj->stats_diskusage();

		format_msgbox("info", "<p>Estimated download size: ". format_size_human($usage) ."</p>");

		

		// run check for file-system based journal files
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM file_uploads WHERE file_location != 'db' LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			format_msgbox("important", "<p>Some of the journal files are not inside the database, these will need to be downloaded seporately from the web-server</p>");
		}



		// export link	
		print "<br>";
		print "<a class=\"button\" href=\"admin/db_backup-process.php\">Export Database</a>";
	}

	
}

?>
