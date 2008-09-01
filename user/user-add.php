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
		print "<h2>ADMIN: CREATE ADMIN ACCOUNT</h3>";
		print "<p>Use this section if you wish to add an administrator user who is able to use AOConf.</p>";

		// get returned data
		security_script_input("/^[A-Za-z0-9.]*$/", $_SESSION["error"]["username"], 4);
		security_script_input("/^[A-Za-z0-9.\s]*$/", $_SESSION["error"]["realname"], 4);
		security_script_input("/^([A-Za-z0-9._-])+\@(([A-Za-z0-9-])+\.)+([A-Za-z0-9])+$/", $_SESSION["error"]["email"], 4);
		security_script_input("/^\S*$/", $_SESSION["error"]["password"], 4);
		security_script_input("/^\S*$/", $_SESSION["error"]["password_confirm"], 4);
		?>

		<table width="100%" style="border: 1px #000000 dashed;"><tr><td width="100%">

		<form method="POST" action="user/user-add-process.php">
		<table width="100%" cellpadding="3" cellspacing="0" border="0">
		<tr>
			<td width="25%"><b>Username:</b></td>
			<td width="40%"><input name="username" size="40" <?php error_render_input("username"); ?>></td>
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
			<td width="35%"></td>
		</tr>
		<tr>
			<td width="25%"><b>Password (Confirm):</b></td>
			<td width="40%"><input name="password_confirm" type="password" size="40" <?php error_render_input("password_confirm"); ?>></td>
			<td width="35%"></td>
		</tr>
		<tr>
			<td width="25%"></td>
			<td width="40%"></td>
			<td width="35%"><input type="submit" value="Create Account"></td>
		</tr>
		</table>
		</form>

		</td></tr></table>

		<?php
		
	} // end of page_render()
	

// if user doesn't have access, display messages.
}
else
{
	error_render_noperms();
}
?>
