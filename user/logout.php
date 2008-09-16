<?php
/*
	user/logout.php

	allows a user to logout of the site.

*/

// match to menu entry
$_SESSION["error"]["menuid"] = "5";


function page_render()
{
	// is the user logged in?
	if (user_online())
	{

		/////////////////////////
	
		print "<h3>USER LOGOUT:</h3>";
		print "<p>Click below to logout. Remember: You must never leave a logged in session unattended!</p>";
	
		print "<form method=\"POST\" action=\"user/logout-process.php\">
		<input type=\"submit\" value=\"Logout\">
		</form>";
		/////////////////////////

	}
	else
	{
		print "<p><b>You are not logged in! Please <a href=\"index.php?page=user/login.php\">click here</a> to login.</b></p>";
	}
}



?>
