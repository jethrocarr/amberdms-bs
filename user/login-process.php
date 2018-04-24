<?php
//
// user/login-process.php
//
// logs the user in
//


// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");

// erase any data - gets rid of stale errors and user sessions.
$_SESSION["error"] = array();
$_SESSION["user"] = array();


if (user_online())
{
	// user is already logged in!
	$_SESSION["error"]["message"][] = "You are already logged in!";
	$_SESSION["error"]["username_amberdms_bs"] = "error";
	$_SESSION["error"]["password_amberdms_bs"] = "error";

}
else
{
	// check & convert input
	if ($GLOBALS["config"]["instance"] == "hosted")
	{
		$instance = security_form_input("/^[0-9a-z]*$/", "instance_amberdms_bs", 1, "Please provide a valid customer instance ID.");
	}
	else
	{
		$instance = NULL;
	}

	$username	= @security_form_input_predefined("any", "username_amberdms_bs", 1, "Please enter a username.");
	$password	= @security_form_input_predefined("any", "password_amberdms_bs", 4, "Please enter a password.");


	if (isset($_SESSION["error"]["message"]))
	{
		// errors occured
		header("Location: ../index.php?page=user/login.php");
		exit(0);
	}




	// call the user functions to authenticate the user and handle blacklisting
	$result = user_login($instance, $username, $password);

	if ($result == 1)
	{
		// login succeded

		// if user has been redirected to login from a previous page, lets take them to that page.
		if ($_SESSION["login"]["previouspage"])
		{	
			header("Location: ../index.php?" . $_SESSION["login"]["previouspage"] . "");
			$_SESSION["login"] = array();
			exit(0);
		}
		else
		{
			// no page? take them to home.
			header("Location: ../index.php?page=home.php");
			exit(0);
		}
	}
	else
	{
		// login failed
		$_SESSION["error"]["form"]["login"] = "failed";

		// if no errors were set for other reasons (eg: the user forgetting to input any password at all)
		// then display the incorrect username/password error.
		if (!$_SESSION["error"]["message"])
		{
			$_SESSION["error"]["message"][] = "That username and/or password is invalid!";
			$_SESSION["error"]["username_amberdms_bs-error"] = 1;
			$_SESSION["error"]["password_amberdms_bs-error"] = 1;
		}

		if ($result == -3)
		{
			$_SESSION["error"]["instance_amberdms_bs-error"] = 1;
		}
		
		// errors occured
		header("Location: ../index.php?page=user/login.php");
		exit(0);

	} // end of errors.
	
} // end of "is user logged in?"



?>
