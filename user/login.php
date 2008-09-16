<?php
/*
	user/login.php

	provides the user login interface
*/



// render page
function page_render()
{

	// returned input from processing form
	if ($_SESSION["error"]["message"])
	{
		security_script_input("/^[A-Za-z0-9.]*$/","username_amberdms_bs");
		security_script_input("/^\S*$/", "password_amberdms_bs");
	}


	print "<h3>SYSTEM LOGIN:</h3>";


	if (user_online())
	{
		print "<p><b>Error: You are already logged in!</b></p>";
	}
	else
	{
		// make sure that the user's old session has been totally cleaned up
		// otherwise some very strange errors and logon behaviour can occur
		$_SESSION["user"] = array();
	
		?>
		
		<p>Please enter your username and password to login to the Amberdms Billing System.</p>
		
	
		<noscript>
			<table width="100%" cellspacing="0" cellpadding="5"><tr>
			<td bgcolor="#ffc274">
			<p><b>We have detected that you have the javascript capabilities of your browser turned off.</b><br>
			<br>
			The Amberdms Billing System will work without javascript, but will not provide the best experience. We highly recommend you use this website with a Javascript-enabled browser.
			</p>
			</td>
			</tr></table>
			
		</noscript>
	
		<form method="post" action="user/login-process.php">
		<table width="100%" cellpadding="3" cellspacing="0" border="0">
		<tr <?php error_render_table("usernameamberdms_bs"); ?>>
			<td width="25%"><b>Username:</b></td>
			<td width="25%"><input name="username_amberdms_bs" size="20" value="<?php print $_SESSION["error"]["username_amberdms_bs"]; ?>"></td>
			<td width="50%"></td>
		</tr>
		<tr <?php error_render_table("password_rtlb"); ?>>
			<td width="25%"><b>Password: </b></td>
			<td width="25%"><input type="password" name="password_amberdms_bs" size="20" value="<?php print $_SESSION["error"]["password_amberdms_bs"]; ?>"></td>
			<td width="50%"></td>
		</tr>
		<tr>
			<td width="25%"></td>
			<td width="25%"><br><input type="submit" value="Login"></td>
			<td width="50%"></td>
		</tr>
		</table>
		</form>

		<!-- Login Help -->
		<p id="troublestart"><br><br><a href="#" onclick="obj_show('trouble'); obj_hide('troublestart');"><b>Unable to login? Click here for help</b></a></p>
		<div id="trouble">
		<br><br><br><hr>

		<h3>LOGIN TROUBLE?</h3>
		<p>If you have forgotten your password, please refer to your system administrator. In the event that person is not able to help you, please read the administration manual for information on how to reset it.</p>
		</div>

		<script type="text/javascript">
		obj_hide('trouble');
		</script>



		<?php

		// clear errors
		$_SESSION["error"] = array();
		$_SESSION["notification"] = array();

	
	} // end if logged in


} // end of page_render function.

?>
