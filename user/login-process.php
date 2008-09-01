<?php
//
// user/login-process.php
//
// logs the user in
//


// includes
include_once("../include/database.php");
include_once("../include/user.php");
include_once("../include/security.php");

// erase any data - gets rid of stale errors and user sessions.
$_SESSION["error"] = array();
$_SESSION["user"] = array();


if (user_online())
{
	// user is already logged in!
	$_SESSION["error"]["message"] = "You are already logged in!";
	$_SESSION["error"]["username_amberdms_bs"] = "error";
	$_SESSION["error"]["password_amberdms_bs"] = "error";

}
else
{
	// check & convert input
	$username	= security_form_input("/^[A-Za-z0-9.]*$/", "username_amberdms_bs", 4, "Please enter a username.");
	$password	= security_form_input("/^\S*$/", "password_amberdms_bs", 4, "Please enter a password.");



	//
	// we now do a check of the IP against a brute-force table. Whilst admins should always lock down their interfaces
	// to trusted IPs only, in the real-world this often does not happen.
	//

	// check the database - is this IP in the bad list?
	$mysql_string                   = "SELECT failedcount, time FROM `users_blacklist` WHERE ipaddress='" . $_SERVER["REMOTE_ADDR"] . "'";
	$mysql_blacklist_result         = mysql_query($mysql_string);
	$mysql_blacklist_num_rows       = mysql_num_rows($mysql_blacklist_result);

	if ($mysql_blacklist_num_rows)
	{
		while ($mysql_blacklist_data = mysql_fetch_array($mysql_blacklist_result))
		{
			// IP is in bad list - but we need to check the count against the time, to see if it's just an innocent wrong password,
			// or if it's something more sinister.

			if ($mysql_blacklist_data["failedcount"] >= 10 && $mysql_blacklist_data["time"] >= (time() - 432000))
			{
				// if failed count >= 10 times, and if the last attempt was within
				// the last 5 days, block the user.
				
				$_SESSION["error"]["message"] = "For brute-force security reasons, you have been locked out of the system interface.";
				header("Location: ../index.php?page=user/login.php");
				exit();
			}
			elseif ($mysql_blacklist_data["time"] < (time() - 432000))
			{
				// It has been more than 5 days since the last attempt was blocked. Start clearing the counter, by
				// removing 2 attempts.
				
				if ($mysql_blacklist_data["failedcount"] > 2)
				{
					// decrease by 2.
					$newcount = $mysql_blacklist_data["failedcount"] - 2;
					$data = "UPDATE `users_blacklist` SET `failedcount`='$newcount' WHERE ipaddress='" . $_SERVER["REMOTE_ADDR"] . "'";
					mysql_query($data);
				}
				else
				{
					// time to remove the entry completely
					$data = "DELETE FROM `users_blacklist` WHERE ipaddress='" . $_SERVER["REMOTE_ADDR"] . "'";
					mysql_query($data);
				}
			}
		}
	} // end of blacklist check



	// proceed to authenticate user.
	if (user_login($username, $password))
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

		// if no errors were set for other reasons (eg: the user forgetting to input any password at all)
		// then display the incorrect username/password error.
		if (!$_SESSION["error"]["message"])
		{
			$_SESSION["error"]["message"] = "That username and/or password is invalid!";
			$_SESSION["error"]["username_amberdms_bs-error"] = 1;
			$_SESSION["error"]["password_amberdms_bs-error"] = 1;
		}
		
		// add time delay to reduce effectiveness of rapid attacks.
		sleep(2);
	
		
	        // update/create entry in blacklist section.
		// this is used to prevent brute-force attacks.

	        // check if there is already an entry.
	        $mysql_string                   = "SELECT failedcount FROM `users_blacklist` WHERE ipaddress='" . $_SERVER["REMOTE_ADDR"] . "'";
	        $mysql_blacklist_result         = mysql_query($mysql_string);
	        $mysql_blacklist_num_rows       = mysql_num_rows($mysql_blacklist_result);

	        if ($mysql_blacklist_num_rows)
	        {
	                // IP is in the list. Increase the failed count, and set the time to now.
	                while ($mysql_blacklist_data = mysql_fetch_array($mysql_blacklist_result))
	                {
       	        		$newcount       = $mysql_blacklist_data["failedcount"] + 1;
	                        $newtime        = time();
				
       	       		        $data = "UPDATE `users_blacklist` SET `failedcount`='$newcount', time='$newtime' WHERE ipaddress='" . $_SERVER["REMOTE_ADDR"] . "'";
        	                mysql_query($data);
        	        }
       	 	}
       	 	else
        	{
	                // IP is not currently in the list. We need to add it.
	                $newtime        = time();
	
        	        $data = "INSERT INTO `users_blacklist` (ipaddress, failedcount, time, username, cluster) VALUES ('" . $_SERVER["REMOTE_ADDR"] . "', '1', '$newtime', '$username', '$cluster')";
        	        mysql_query($data);
        	}

		// errors occured
		header("Location: ../index.php?page=user/login.php");
		exit(0);

	} // end of errors.
	
} // end of "is user logged in?"



?>
