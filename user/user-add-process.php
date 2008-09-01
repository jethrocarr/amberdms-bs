<?php
//
// user/user-add-process.php
//
// create a new admin user account
//

// includes
include_once("../include/database.php");
include_once("../include/user.php");
include_once("../include/security.php");

$_SESSION["error"] = array();
$_SESSION["notification"] = array();


if (user_permissions_get("admin"))
{
	// check input
	$username		= security_form_input("/^[A-Za-z0-9]*$/", "username", 4, "Please enter a username (must be at least 4 chars).");
	$realname		= security_form_input("/^[A-Za-z0-9.\s]*$/", "realname", 1, "Please enter a realname.");
	$email			= security_form_input("/^([A-Za-z0-9._-])+\@(([A-Za-z0-9-])+\.)+([A-Za-z0-9])+$/", "email", 4, "Please enter a valid email address.");
	$password		= security_form_input("/^\S*$/", "password", 4, "Please enter a password.");
	$password_confirm	= security_form_input("/^\S*$/", "password_confirm", 4, "Please enter a confirmation password.");


	// check passwords
	if ($password != $password_confirm)
	{
		$_SESSION["error"]["message"]		.= "Your passwords do not match!";
		$_SESSION["error"]["password_confirm"]	= "error";
	}


	// make sure the username is not already in use
	$mysql_string	= "SELECT id FROM `users` WHERE username='$username'";
	$mysql_result	= mysql_query($mysql_string);
	$mysql_num_rows	= mysql_num_rows($mysql_result);

	if ($mysql_num_rows)
	{
		$_SESSION["error"]["message"]	.= "This username is already in use!";
		$_SESSION["error"]["username"]	= "error";
	}



	if ($_SESSION["error"]["message"])
	{
		// errors occured
		header("Location: ../index.php?page=user/user-add.php");
		exit(0);
	}
	else
	{
		$_SESSION["error"] = array();

		// create the admin account
		user_newuser($username, $password, $realname, $email);
		  
		// get the user's ID
		$mysql_string =		"SELECT id FROM `users` WHERE username='$username'";
		$mysql_result =		mysql_query($mysql_string);
		$mysql_data =		mysql_fetch_array($mysql_result);

		// create admin permission (ID=2)
		$mysql_string = "INSERT INTO `users_permissions` (userid, permid) VALUES ('" . $mysql_data["id"] . "', '2')";
		$mysql_result = mysql_query($mysql_string);
		if (!$mysql_result)
		{
			die('MySQL Error: ' . mysql_error());
		}

		// goto preferences page
		$_SESSION["notification"]["message"] = "User created successfully!";
		header("Location: ../index.php?page=user/user-details.php&id=" . $mysql_data["id"] . "");
		exit(0);

	} // if valid data input
	
	
} // end of "is user logged in?"
else
{
	// user does not have permissions to access this page.
	error_render_noperms();
}


?>
