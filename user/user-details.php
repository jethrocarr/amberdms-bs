<?php
//
// users/user-details.php
//
// display user information.
//


// only admins may access this page
if (user_permissions_get("admin"))
{
	$_SESSION["error"]["menuid"] = "20";

	// CHECK THE USER ID
	$id = security_script_input("/^[0-9]*$/", $_GET["id"], 1);

	if (!$id)
	{
		$_SESSION["error"]["message"] = "No user ID was provided to the page!";
		$_SESSION["error"]["pagestate"] = 0;
	}
	else
	{
		// get the user's data
		$mysql_string		= "SELECT username FROM `users` WHERE id='$id'";
		$mysql_result		= mysql_query($mysql_string);
		$mysql_num_rows		= mysql_num_rows($mysql_result);
		
		if (!$mysql_num_rows)
		{
			$_SESSION["error"]["message"] = "No such user exists!";
			$_SESSION["error"]["pagestate"] = 0;
		}
	}

	function page_render()
	{

		
		///// NOW WE SPLIT THE PAGE INTO VARIOUS FUNCTIONS
		///// - page_edit()
		///// - page_permissions()
		///// - page_delete()


		function page_edit()
		{

			// get user id
			$id = security_script_input("/^[0-9]*$/", $_GET["id"], 1);


			if (!$_SESSION["error"]["message"])
			{
				// get the user's data
				$mysql_string		= "SELECT username, realname, email, ipaddress, time FROM `users` WHERE id='$id'";
				$mysql_result		= mysql_query($mysql_string);
				$mysql_data		= mysql_fetch_array($mysql_result);
				$_SESSION["error"]	= $mysql_data;
			}
			else
			{
				// get the user's data
				$mysql_string		= "SELECT ipaddress, time FROM `users` WHERE id='$id'";
				$mysql_result		= mysql_query($mysql_string);
				$mysql_data		= mysql_fetch_array($mysql_result);
			
				security_script_input("/^[A-Za-z0-9]*$/", $_SESSION["error"]["username"], 4);
				security_script_input("/^[A-Za-z0-9.\s]*$/", $_SESSION["error"]["realname"], 4);
				security_script_input("/^([A-Za-z0-9._-])+\@(([A-Za-z0-9-])+\.)+([A-Za-z0-9])+$/", $_SESSION["error"]["email"], 4);
				security_script_input("/^\S*$/", $_SESSION["error"]["password"], 4);
				security_script_input("/^\S*$/", $_SESSION["error"]["password_confirm"], 4);
			}
			

			?>
			<h2>ADMINS: EDIT USER:</h2>

			<p>This section allows you to view and edit the selected user's details</p>
			
			<table width="100%" style="border: 1px #000000 dashed;"><tr><td width="100%">

			<form method="POST" action="user/user-edit-process.php">
			<table width="100%" cellpadding="3" cellspacing="0" border="0">
			<tr>
				<td width="25%"><b>Username:</b></td>
				<td width="40%"><input name="username" size="40" disabled="yes" <?php error_render_input("username"); ?>></td>
				<td width="35%"></td>
			</tr>
			<tr>
				<td width="25%"><b>Realname:</b></td>
				<td width="40%"><input name="realname" size="40" <?php error_render_input("realname"); ?>></td>
				<td width="35%"></td>
			</tr>
			<tr>
				<td width="25%"><b>Email Address:</b></td>
				<td width="40%"><input name="email" size="40" <?php error_render_input("email"); ?>></td>
				<td width="35%"></td>
			</tr>
			<tr>
				<td width="25%"><b>Password:</b></td>
				<td width="40%"><input name="password" type="password" size="40" <?php error_render_input("password"); ?>></td>
				<td width="35%"><i style="font-size: 10px;">Only input if you wish to change their current one.</i></td>
			</tr>
			<tr>
				<td width="25%"><b>Password (Confirm):</b></td>
				<td width="40%"><input name="password_confirm" type="password" size="40" <?php error_render_input("password_confirm"); ?>></td>
				<td width="35%"><i style="font-size: 10px;">Only input if you wish to change their current one.</i></td>
			</tr>
			<tr>
				<td width="25%"><b>Last IP Address:</b></td>
				<td width="40%"><?php print $mysql_data["ipaddress"] ?></td>
				<td width="35%"></td>
			</tr>
			<tr>
				<td width="25%"><b>Last Logged in:</b></td>
				<td width="40%"><?php print date("D d F Y", $mysql_data["time"]); ?></td>
				<td width="35%"></td>
			</tr>
			</table>

			</td></tr></table>

			<?php
			
			// end form
			print "<input type=\"hidden\" name=\"userid\" value=\"$id\">";

			print "<table cellpadding=\"5\"><tr>";
			print "<td><input type=\"submit\" value=\"Update Account\"></form></td>";
			print "</tr></table>";
			
			print "</form><br>";
	
		} // end of page_edit()




		function page_delete()
		{
			// get user id
			$id = security_script_input("/^[0-9]*$/", $_GET["id"], 1);

			?>
			<h2>ADMINS: DELETE USER:</h2>

			<p>If you wish to permantly remove an administator, you should delete their account.</p>

			<table width="100%" style="border: 1px #000000 dashed;"><tr><td width="100%">

			<form method="POST" action="user/user-delete-process.php">
			<table width="100%" cellpadding="3" cellspacing="0" border="0">
			<tr>
				<td width="25%"><b>Confim:</b></td>
				<td width="40%" <?php error_render_input("confirm"); ?>><input type="checkbox" name="confirm" <?php error_render_checkbox("confirm"); ?>><i style="font-size: 10px;">Yes, I really do want to delete this user.</i></td>
				<td width="35%"></td>
			</tr>
			</table>

			</td></tr></table>

			<?php

			// end form
			print "<input type=\"hidden\" name=\"userid\" value=\"$id\">";

			print "<table cellpadding=\"5\"><tr>";
			print "<td><input type=\"submit\" value=\"Delete Account\"></form></td>";
			print "</tr></table>";
	
			print "</form><br>";
			
		} // end of page_delete()




		function page_permissions()
		{
			// get user id
			$id = security_script_input("/^[0-9]*$/", $_GET["id"], 1);

			?>
			<h2>ADMINS: USER PERMISSIONS:</h2>

			<p>This section allows you to set the permissions of this user.</b></p>

			<table width="100%" style="border: 1px #000000 dashed;"><tr><td width="100%">
			
			<form method="POST" action="user/user-permissions-process.php">
			<table width="100%" cellpadding="3" cellspacing="0" border="0">
			<?php

			// get all permissions
			$mysql_perms_string	= "SELECT * FROM `permissions` ORDER BY value";
			$mysql_perms_result	= mysql_query($mysql_perms_string);

			while ($mysql_perms_data = mysql_fetch_array($mysql_perms_result))
			{
				$value = $mysql_perms_data["value"];
				
				if (!$_SESSION["error"]["message"])
				{
					// see if the user has this particular permission.
					$mysql_user_string	= "SELECT id FROM `users_permissions` WHERE userid='$id' AND permid='" . $mysql_perms_data["id"] . "'";
					$mysql_user_result	= mysql_query($mysql_user_string);
					$mysql_user_num_rows	= mysql_num_rows($mysql_user_result);

					if ($mysql_user_num_rows)
					{
						// user has this permission
						$_SESSION["error"]["$value"] = "on";
					}
				}
				else
				{
					// get the permissions from the returned errors.
					security_script_input("/^on$/", $_SESSION["error"]["$value"], 0);
				}
				
				// draw option box			
				print "<tr>";
				print "<td width=\"25%\"><b>" . $mysql_perms_data["value"] . "</b></td>";
				
					// draw the checkbox correctly
					print "<td width=\"40%\"";
					error_render_table("$value");
					print "><input type=\"checkbox\" name=\"" . $mysql_perms_data["value"] . "\"";
					error_render_checkbox("$value");

					// the public permission is always on.
					if ($value == "public")
						print " checked disabled";


					print "></td>";
				print "<td width=\"35%\"></td>";
				print "</tr>";
			}
			
			?>
			</table>

			</td></tr></table>

			<?php

			// end form
			print "<input type=\"hidden\" name=\"userid\" value=\"$id\">";

			print "<table cellpadding=\"5\"><tr>";
			print "<td><input type=\"submit\" value=\"Apply Permissions\"></form></td>";
			print "</tr></table>";
			
			print "</form><br>";
		
		} // end of page_permissions()



		// depending on the error form value, display the right section
		switch ($_SESSION["error"]["form"])
		{
			case "edit":
				page_edit();
			break;

			case "permissions":
				page_permissions();
			break;

			case "delete":
				page_delete();
			break;


			// if no data, display all sections
			default:
				page_edit();
				page_permissions();
				page_delete();			
			break;
		}


		print "<br><br><br>";
		print "<table cellpadding=\"5\">";
		
		// display full form option if needed
		if ($_SESSION["error"]["form"])
		{
			$id = security_script_input("/^[0-9]*$/", $_GET["id"], 1);
			
			print "<tr><td>
				<form method=\"get\" action=\"index.php\">
				<input type=\"hidden\" name=\"id\" value=\"$id\">
				<input type=\"hidden\" name=\"page\" value=\"user/user-details.php\">
				<input type=\"submit\" value=\"View all details\">
			</form></td></tr>";
		}

		// return to administrators link
		print "<tr><td>
			<form method=\"get\" action=\"index.php\">
			<input type=\"hidden\" name=\"page\" value=\"user/users.php\">
			<input type=\"submit\" value=\"Return to Administrators\">
		</form></td></tr>";
	
		print "</table>";

 
	} // end of page_render()
		
// if user doesn't have access, display messages.
}
else
{
	error_render_noperms();
}
?>
