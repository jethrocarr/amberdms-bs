<?php
/*
	admin/config_dependencies.php
	
	access: admin users only

	Tests for required modules.
*/

class page_output
{
	var $table_array = array();


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
		//check mail module and add data to array
		$name 		= "Mail";
		$location	= "Mail.php";
		$status 	= @include('Mail.php');
		
		$this->table_array[$name]["location"] = $location;
		$this->table_array[$name]["status"]	= $status;
		
	
		//check mail mime module and add data to array
		$name		= "Mail Mime";
		$location	= "Mail/mime.php";
		$status		= @include('Mail/mime.php');
		
		$this->table_array[$name]["location"] = $location;
		$this->table_array[$name]["status"]	= $status;
		
		
		//check mysql dump module and add data to array
		$name		= "MySQL Dump";
		$location	= sql_get_singlevalue("SELECT value FROM config WHERE name = 'APP_MYSQL_DUMP' LIMIT 1");
		$status		= file_exists($location);
		
		$this->table_array[$name]["location"] = $location;
		$this->table_array[$name]["status"]	= $status;
		
		
		//check html to pdf module and add data to array
		$name		= "WK HTML to PDF";
		$location	= sql_get_singlevalue("SELECT value FROM config WHERE name = 'APP_WKHTMLTOPDF' LIMIT 1");
		$status		= file_exists($location);
		
		$this->table_array[$name]["location"] = $location;
		$this->table_array[$name]["status"]	= $status;
		
		
		//check pdf latex module and add data to array
		$name		= "PDF LaTeX";
		$location	= sql_get_singlevalue("SELECT value FROM config WHERE name = 'APP_PDFLATEX' LIMIT 1");
		$status		= file_exists($location);
		
		$this->table_array[$name]["location"] = $location;
		$this->table_array[$name]["status"]	= $status;
		
		//check if temp directory has write permissions and add data to array
		$name		= "Temp Directory - Write";
		$location	= sql_get_singlevalue("SELECT value FROM config WHERE name = 'PATH_TMPDIR' LIMIT 1");
		$status 	= is_writable($location);
			
		$this->table_array[$name]["location"] = $location;
		$this->table_array[$name]["status"]	= $status;
		
		//check if temp directory has read permissions and add data to array
		$name		= "Temp Directory - Read";
		$status 	= is_readable($location);
			
		$this->table_array[$name]["location"] = $location;
		$this->table_array[$name]["status"]	= $status;
	}



	function render_html()
	{
		// Title + Summary
		print "<h3>DEPENDENCIES CHECK</h3><br>";
		print "<p>This page checks each of the billing system's dependencies. If a red 'x' appears in the status column, the system cannot find that module.</p>";
	
		print "<table class=\"table_content\" cellspacing=\"0\" width=\"100%\">";
			print "<tr>";
			print "<td class=\"header\">";
				print "<b>" .lang_trans("dependency_status"). "</b>";
			print "</td>";
			
			print "<td class=\"header\">";
				print "<b>" .lang_trans("dependency_name"). "</b>";
			print "</td>";
			
			print "<td class=\"header\">";
				print "<b>" .lang_trans("dependency_location"). "</b>";
			print "</td>";
			print "</tr>";
			
			//parse through the array to generate table rows
			foreach ($this->table_array as $name => $data)
			{
				print "<tr>";
				print "<td>";
					if ($data["status"])
					{
						print "<img alt=\"Y\" src=\"images/icons/tick_16.gif\">";
					}
					else
					{
						print "<img alt=\"N\" src=\"images/icons/cross_16.gif\">";
					}
				print "</td>";
					
				print "<td>";
					print "<b>" .$name. "</b>";
				print "</td>";
				
				print "<td>";
					print $data["location"];
				print "</td>";
				print "</tr>";
			}
		print "</table>";
	}

	
}

?>
