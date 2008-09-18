<?php
/*
	inc_user.php

	Contain user management and authentication functions.

	TODO: Put all information on authentication system methods here.
	TODO: Update this page to use the new sql_query class structure for all DB queries
*/



/*
	user_online()

	This function returns "1" if a valid user is logged in, or "0" otherwise.

	This function works by checking the user's authentication key from their session data against the SQL
	database to verify that their IP has not changed, and that they are who they say they are.

	For further details about the authentication system, please refer to the comments at the top of this file.
*/

function user_online()
{
	if (!$_SESSION["user"]["authkey"])					// if user has no login data, don't bother trying to check
		return 0;
	if (!preg_match("/^[a-zA-Z0-9]*$/", $_SESSION["user"]["authkey"]))	// make sure the key is valid info, NOT AN SQL INJECTION.
		return 0;
		
	// get user auth data
	$mysql_string	= "SELECT id, time FROM `users` WHERE authkey='" . $_SESSION["user"]["authkey"] . "' AND ipaddress='" . $_SERVER["REMOTE_ADDR"] . "' LIMIT 0, 1";
	$mysql_result	= mysql_query($mysql_string);
	$mysql_num_rows	= mysql_num_rows($mysql_result);

	if ($mysql_num_rows)
	{
		$mysql_data = mysql_fetch_array($mysql_result);

		$time = time();
		if ($time < ($mysql_data["time"] + 7200))
		{
			// we want to update the time value in the database, but we don't want to do this
			// on every single page load - no need, and a waste of performance.
			//
			// therefore, we only update the time record in the DB if it's older than 30 minutes. We use
			// this time to see if the user has been inactive for long periods of time, to log them out.
			if (($time -  $mysql_data["time"]) > 1800)
			{
				// update time field
				$mysql_string = "UPDATE `users` SET time='$time' WHERE authkey='". $_SESSION["user"]["authkey"] ."'";
				mysql_query($mysql_string);
			}

			// user is logged in.
			return 1;
		}
		else
		{
			// The user hasn't accessed a page for 2 hours, we log em' out for security reasons.
			
			// We save the query string, so they can easily log back in to where they were.			
			$_SESSION["login"]["previouspage"] = $_SERVER["QUERY_STRING"];
		
			// log user out
			user_logout();

			// set the timeout flag. (so the login message is different)
			$_SESSION["user"]["timeout"] = "flagged";
		}
	}

	
	return 0;
}


/*
	user_login($username, $password)

	This function performs two main tasks:
	* If enabled, it performs brute-force blacklisting defense, and will block authentication attempts from blacklisted IP addresses.
	* Checks the username/password and authenticates the user.

	The function returns 1 on success or 0 on failure.
*/
function user_login($username, $password)
{
	//
	// first we do a check of the IP against a brute-force table. Whilst admins should always lock down their interfaces
	// to trusted IPs only, in the real-world this often does not happen.
	//
	$mysql_query		= "SELECT value as blacklist_enable FROM `config` WHERE name='BLACKLIST_ENABLE' LIMIT 1";
	$mysql_result		= mysql_query($mysql_query);
	$mysql_data		= mysql_fetch_array($mysql_result);
	$blacklist_enable	= $mysql_data["blacklist_enable"];

	$mysql_query		= "SELECT value as blacklist_limit FROM `config` WHERE name='BLACKLIST_LIMIT' LIMIT 1";
	$mysql_result		= mysql_query($mysql_query);
	$mysql_data		= mysql_fetch_array($mysql_result);
	$blacklist_limit	= $mysql_data["blacklist_limit"];


	if ($blacklist_enable == "enabled")
	{
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

				if ($mysql_blacklist_data["failedcount"] >= $blacklist_limit && $mysql_blacklist_data["time"] >= (time() - 432000))
				{
					// if failed count >= blacklist limit, and if the last attempt was within
					// the last 5 days, block the user.
					
					$_SESSION["error"]["message"] = array("For brute-force security reasons, you have been locked out of the system interface.");
					return 0;
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
		}
	} // end of blacklist check



	// get user data
	$mysql_string	= "SELECT id, password, password_salt FROM `users` WHERE username='$username' LIMIT 1";
	$mysql_result	= mysql_query($mysql_string);
	$mysql_num_rows	= mysql_num_rows($mysql_result);

	if ($mysql_num_rows)
	{
		$mysql_data = mysql_fetch_array($mysql_result);

		// compare passwords
		if ($mysql_data["password"] == sha1($mysql_data["password_salt"] . "$password"))
		{
			///// password is correct

			// make sure the user is not disabled. (PERM ID = 1)
			$mysql_perms_string	= "SELECT id FROM `users_permissions` WHERE userid='" . $mysql_data["id"] . "' AND permid='1'";
			$mysql_perms_result	= mysql_query($mysql_perms_string);
			$mysql_perms_num_rows	= mysql_num_rows($mysql_perms_result);

			if ($mysql_perms_num_rows)
			{
				// user has been disabled
				$_SESSION["error"]["message"] = array("Your user account has been disabled. Please contact the system administrator to get it unlocked.");
				return 0;
			}
			else
			{
				// We have verified that the user is valid. We now assign them an authentication key, which is
				// like an additional session ID.
				//
				// This key is tied to their IP address, so if their IP changes, the user must re-authenticate.
				//
				// Most of the purpose of this auth key, is already provided by PHP sessions, but this key
				// method, provides additional protection in the event of any of the following scenarios:
				//
				// * PHP being used with session IDs passed via GET (since the attackers IP will most
				//   likely be different)
				//
				// * An exploit in the PHP session handling that allows a user to change their session
				//   information.
				//
				// * An exploit elsewhere in this application which allows the changing of any session variable will
				//   not allow a user to gain different authentication rights.
				//
				
				// generate an authentication key
                                $feed = "0123456789abcdefghijklmnopqrstuvwxyz";
				$authkey = null;
                                for ($i=0; $i < 40; $i++)
                                {
                                	$authkey .= substr($feed, rand(0, strlen($feed)-1), 1);
				}

				// get other information - IP address & time
				$ipaddress	= $_SERVER["REMOTE_ADDR"];
				$time		= time();

				// update user's login data
				$mysql_string = "UPDATE `users` SET authkey='$authkey', ipaddress='$ipaddress', time='$time' WHERE id='" . $mysql_data["id"] . "'";
				mysql_query($mysql_string);

				// set session variables
				$_SESSION["user"]["id"]		= $mysql_data["id"];
				$_SESSION["user"]["name"]	= $username;
				$_SESSION["user"]["authkey"]	= $authkey;

				// TODO: in future, allow the user to select their language
				$_SESSION["user"]["lang"]	= "en_us";


				// TODO: currently debugging is enabled for all users
				$_SESSION["user"]["debug"]	= "yes";


				// does the user need to change their password? If they have no salt, it means the password
				// is the system default and needs to be changed
				if ($mysql_data["password_salt"] == "")
				{
					$_SESSION["error"]["message"][] = "Your password is currently set to a default. It is highly important for you to change this password, which you can do <a href=\"index.php?page=user/user-details.php&id=". $mysql_data["id"] ."\">by clicking here</a>.";
				}


				//// user is logged in.
				return 1;
			}
		}		
	}

	// user authentication failed - incorrect password or username
	
	// add time delay to reduce effectiveness of rapid attacks.
	sleep(2);
	
		
        // update/create entry in blacklist section.
	// this is used to prevent brute-force attacks.
	if ($blacklist_enable == "enabled")
	{
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
			
			$data = "INSERT INTO `users_blacklist` (ipaddress, failedcount, time) VALUES ('" . $_SERVER["REMOTE_ADDR"] . "', '1', '$newtime')";
			mysql_query($data);
		}
		
	}

	// return failed authentication
	return 0;
}



/*
	user_logout()

	Logs the user out of the system and clears all session variables relating to their connection.
*/
function user_logout()
{
	if ($_SESSION["user"]["name"])
	{
		// log the user out.
		$_SESSION["user"] = array();
		$_SESSION["form"] = array();
		return 1;		
	}

	return 0;
}



/*
	user_newuser($username, $password, $realname, $email)

	Creates a new user account in the database and returns the ID of the new user account.
*/
function user_newuser($username, $password, $realname, $email)
{
	// make sure that the user running this command is an admin
	if (user_permissions_get("admin"))
	{
		// verify data
		if ($username && $password && $realname && $email)
		{
			// create the user account
			$mysql_string = "INSERT INTO `users` (username, realname, contact_email) VALUES ('$username', '$realname', '$email')";
			if (!mysql_query($mysql_string))
			{
				die('MySQL Error: ' . mysql_error());
			}
	
			$userid = mysql_insert_id();

			// set the password
			user_changepwd($userid, $password);

			return $userid;

		} // if data is valid
		
	} // if user is an admin

	return 0;
}



/*
	user_changepwd($userid, $password)

	Updates the user's password - regenerates the password salt and hashes
	the password with the salt and SHA algorithm.

	Returns 1 on success, 0 on failure.
*/
function user_changepwd($userid, $password)
{
	if (user_permissions_get("admin"))
	{
		if ($userid && $password)
		{
			//
			// Here we generate a password salt. This is used, so that in the event of an attacker
			// getting a copy of the users table, they can't brute force the passwords using pre-created
			// hash dictionaries.
			//
			// The salt requires them to have to re-calculate each password possibility for any passowrd
			// they wish to try and break.
			//
			$feed		= "0123456789abcdefghijklmnopqrstuvwxyz";
			$password_salt	= null;

			for ($i=0; $i < 20; $i++)
			{
				$password_salt .= substr($feed, rand(0, strlen($feed)-1), 1);
			}				
			
			// encrypt password with salt
			$password_crypt = sha1("$password_salt"."$password");

			// apply changes to DB.
			$mysql_string = "UPDATE `users` SET password='$password_crypt', password_salt='$password_salt' WHERE id='$userid'";
			if (!mysql_query($mysql_string))
			{
				die('MySQL Error: ' . mysql_error());
			}
		
			return 1;

		} // if data is valid
		
	} // if user is an admin

	return 0;
}



/*
	user_permissions_get($type)

	This function looks up the database to see if the user has the specified permission. If so,
	the function will return 1.

	If the user does not have the permission, the function will return 0.
*/
function user_permissions_get($type)
{

	// everyone (including guests) have the "public" permission, so don't waste cycles checking for it
	if ($type == "public")
	{
		return 1;
	}

	// other permissions... make sure user is valid, and logged in.
	if ($userid = user_information("id"))
	{
		// get the id of the permission
		$mysql_perm_string	= "SELECT id FROM `permissions` WHERE value='$type' LIMIT 1";
		$mysql_perm_result	= mysql_query($mysql_perm_string);
		$mysql_perm_num_rows	= mysql_num_rows($mysql_perm_result);

		// if nothing found, deny.
		if (!$mysql_perm_num_rows)
			return 0;

		// get the ID
		$mysql_perm_data	= mysql_fetch_array($mysql_perm_result);

		// see if the user has this particular permission.
		$mysql_user_string	= "SELECT id FROM `users_permissions` WHERE userid='$userid' AND permid='" . $mysql_perm_data["id"] . "'";
		$mysql_user_result	= mysql_query($mysql_user_string);
		$mysql_user_num_rows	= mysql_num_rows($mysql_user_result);

		if ($mysql_user_num_rows)
		{
			// user has an entry for that permission.
			return 1;
			
		} // if permission exists
		
	} // if user is logged in
	
	return 0;
}



/*
	user_information($field)

	This function looks up the specified field in the database's "users" table and returns the result.
*/
function user_information($field)
{
	// this verifys that the user session data is correct, and that they are currently logged in.
	if (user_online())
	{
		$mysql_string	= "SELECT $field FROM `users` WHERE username='" . $_SESSION["user"]["name"] . "' LIMIT 1";
		$mysql_result	= mysql_query($mysql_string);
		$mysql_num_rows	= mysql_num_rows($mysql_result);

		if ($mysql_num_rows)
		{
			$mysql_data = mysql_fetch_array($mysql_result);

			return $mysql_data[$field];
		}
		
	} // end "if (user_online())"

	return 0;
}




?>
