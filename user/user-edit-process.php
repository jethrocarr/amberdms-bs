<?php
//
// user/user-edit-process.php
//
// edit an administrator account
//

// includes
include_once("../include/database.php");
include_once("../include/user.php");
include_once("../include/security.php");


if (user_permissions_get("admin"))
{
	// check input
	$userid			= security_form_input("/^[0-9]*$/", "userid", 1, "No user ID supplied.");
	$realname		= security_form_input("/^[A-Za-z0-9.\s]*$/", "realname", 4, "Please enter a realname.");
	$email			= security_form_input("/^([A-Za-z0-9._-])+\@(([A-Za-z0-9-])+\.)+([A-Za-z0-9])+$/", "email", 4, "Please enter a valid email address.");


	// check password (if the user has requested to change it)
	if ($_POST["password"] || $_POST["password_confirm"])
	{
		$password		= security_form_input("/^\S*$/", "password", 4, "Please enter a password.");
		$password_confirm	= security_form_input("/^\S*$/", "password_confirm", 4, "Please enter a confirmation password.");

		if ($password != $password_confirm)
		{
			$_SESSION["error"]["message"]		.= "Your passwords do not match!";
			$_SESSION["error"]["password_confirm"]	= "error";
		}
	}

	if ($_SESSION["error"]["message"])
	{
		// errors occured
		$_SESSION["error"]["form"] = "edit";
		header("Location: ../../index.php?page=user/user-details.php&id=$userid");
		exit(0);
	}
	else
	{
		$_SESSION["error"] = array();

		// generate a new password and salt
		if ($password)
		{
			user_changepwd($userid, $password);
		}

		// update the account details
		$mysql_string = "UPDATE `users` SET realname='$realname', email='$email' WHERE id='$userid'";
		$mysql_result = mysql_query($mysql_string);
		if (!$mysql_result)
		{
			die('MySQL Error: ' . mysql_error());
		}
			
		$_SESSION["notification"]["message"] = "The user's preferences have been updated successfully.";
		

		// goto preferences page
		$_SESSION["error"]["form"] = "edit";
		header("Location: ../index.php?page=user/user-details.php&id=$userid");
		exit(0);

	} // if valid data input

	
	
} // end of "is user logged in?"
else
{
	// user does not have permissions to access this page.
	error_render_noperms();
}


?>
