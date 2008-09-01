<?php
//
// users/blacklist.php
//
// add, edit and delete users.
//


// only admins may access this page
if (user_permissions_get("admin"))
{
	$_SESSION["error"]["menuid"] = "21";
	
	function page_render()
	{
		print "<h2>BLACKLIST</h2>";
		
		print "<p>It is recommended to firewall access to this device as much as possible. However, sometimes even
			then there is a risk of untrusted machines attempting to perform a brute-force attack on the server.</p>";
			
		print "<p>In this case, enabled brute force blacklisting, to provide defense by blacklisting any IP addresses with
			too many failed password attempts in a row (limit is 10 incorrect attempts).</p>";

		print "<br>";


		// ENABLE/DISABLE BLACKLISTING

		print "<h3>ENABLE/DISABLE BLACKLISTING</h3>";
		print "<table width=\"100%\" style=\"border: 1px #000000 dashed;\"><tr><td width=\"100%\">";
		

		if ($_SESSION["error"]["message"])
		{
			$data = $_SESSION["error"];
		}
		else
		{
			// get desired records
			$data['usr_blacklisting'] = db_get_value('cfg_basics', 'USR_BLACKLISTING');
		}


		// start form
		print "<form method=\"post\" action=\"user/blacklist-enable-process.php\">";


		// enable/disable option
		if ($data["usr_blacklisting"] == "enabled")
			$usr_blacklisting_checked = "checked";
			
		print "<table width=\"100%\">";
		print "<tr"; error_render_table("usr_blacklisting"); print ">";
			print "<td width=\"50%\"><input type=\"checkbox\" name=\"usr_blacklisting\" value=\"enabled\" $usr_blacklisting_checked> <b>Enable Blacklisting</b></td>";
			print "<td width=\"50%\" align=\"right\"><input type=\"submit\" value=\"Apply Changes\"></td>";
		print "</tr>";
		
		print "</table>";
		
		// end form		
		print "</td></tr></table>";
		print "</form><br>";



		if ($data['usr_blacklisting'] == "enabled")
		{

			// LIST OF WHITELISTED ADDRESSES
			// TODO: Write whitelisted address support

			print "<h3>WHITELISTED ADDRESSES</h3>";
			print "<p>This feature has not yet been implemented</p>";
			print "<br>";
		

			// LIST OF BLACKLISTED ADDRESSES
		
			print "<h3>BLACKLISTED ADDRESSES</h3>";
			
			// get all the entries
			$mysql_string	= "SELECT id, ipaddress, failedcount, time FROM `users_blacklist` ORDER BY time";
			$mysql_result	= mysql_query($mysql_string);

			print "<table width=\"100%\" style=\"border: 1px #000000 dashed;\"><tr><td width=\"100%\">";
			
			print "<table width=\"100%\" cellpadding=\"3\" cellspacing=\"2\" border=\"0\">";
			print "<tr>";
			print "<td width=\"40%\"><b>IP Address</b></td>";
			print "<td width=\"25%\"><b>Date/Time</b></td>";
			print "<td width=\"25%\"><b>Status</b></td>";
			print "<td width=\"10%\"></td>";
			print "</tr>";


			// list all blacklisted addresses	
			while ($mysql_data = mysql_fetch_array($mysql_result))
			{
				print "<tr>";

				// ipaddress / reverse IP
				$reverseip	= "";
				$cmd_output	= array();
				$cmd		= "/usr/bin/host ". $mysql_data["ipaddress"] ."";
				$cmd		= exec($cmd, $cmd_output);
				
				if (preg_match('/domain\sname\spointer\s(\S*)./', $cmd_output[0], $matches))
				{
					$reverseip = "($matches[1])";
				}

				print "<td width=\"40%\">" . $mysql_data["ipaddress"] . " $reverseip</td>";
				

				// time
				$time = date("Y-m-d H:i:s", $mysql_data["time"]);
				print "<td width=\"25%\">$time</td>";

				// status
				print "<td width=\"25%\">";

				if ($mysql_data["failedcount"] == "10")
				{
					print "<b>blocked (reached max 10 attempts)</b>";
				}
				else
				{
					print "attempt number ". $mysql_data["failedcount"];
				}
					
				print "</td>";

				// controls
				print "<td width=\"10%\" align=\"right\">";
				print "<a href=\"user/blacklist-delete-process.php?id=" . $mysql_data["id"] . "\">delete</a>";
				print "</td>";

				
				print "</tr>";
			
			}

			print "</table>";
			print "</td></tr></table>";
		}

	} // end of page_render()
	

// if user doesn't have access, display messages.
}
else
{
	error_render_noperms();
}
?>
