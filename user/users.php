<?php
//
// users/users.php
//
// add, edit and delete users.
//


// only admins may access this page
if (user_permissions_get("admin"))
{
	$_SESSION["error"]["menuid"] = "20";
	
	function page_render()
	{
		print "<h2>ADMIN ACCESS</h2>";
		print "<p>Create, edit or remove administrator user accounts here.</p>";

		// add interface button
		print "<form method=\"get\" action=\"index.php\">";
		print "<input type=\"hidden\" name=\"page\" value=\"user/user-add.php\">";
		print "<input type=\"submit\" value=\"Add new admin\">";
		print "</form><br>";
		
		// get all the users
		$mysql_string	= "SELECT id, username, realname, email FROM `users` ORDER BY username";
		$mysql_result	= mysql_query($mysql_string);

		print "<table width=\"100%\" style=\"border: 1px #000000 dashed;\"><tr><td width=\"100%\">";
		
		print "<table width=\"100%\" cellpadding=\"3\" cellspacing=\"2\" border=\"0\">";
		print "<tr>";
		print "<td width=\"25%\"><b>Username</b></td>";
		print "<td width=\"25%\"><b>Realname</b></td>";
		print "<td width=\"40%\"><b>Email Address</b></td>";
		print "<td width=\"10%\"></td>";
		print "</tr>";

		
		// display a list of all the users.
		while ($mysql_data = mysql_fetch_array($mysql_result))
		{
			print "<tr>";

			// username
			print "<td width=\"25%\">" . $mysql_data["username"] . "</td>";

			// realname
			print "<td width=\"25%\">" . $mysql_data["realname"] . "</td>";

			// email
			print "<td width=\"40%\">" . $mysql_data["email"] . "</td>";

			// controls
			print "<td width=\"10%\"><a href=\"index.php?page=user/user-details.php&id=" . $mysql_data["id"] . "\">details</a></td>";
			print "</tr>";
		}
		
		print "</table>";
		print "</td></tr></table>";

	} // end of page_render()
	

// if user doesn't have access, display messages.
}
else
{
	error_render_noperms();
}
?>
