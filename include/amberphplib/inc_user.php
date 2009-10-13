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
	log_debug("inc_user", "Executing user_online()");

	if (!$_SESSION["user"]["authkey"])					// if user has no login data, don't bother trying to check
		return 0;
	if (!preg_match("/^[a-zA-Z0-9]*$/", $_SESSION["user"]["authkey"]))	// make sure the key is valid info, NOT AN SQL INJECTION.
		return 0;


	if ($GLOBALS["cache"]["user"]["online"])
	{
		// we have already checked if the user is online, so don't bother checking again
		return 1;
	}
	else
	{
		// get user session data
		$sql_session_obj		= New sql_query;
		$sql_session_obj->string 	= "SELECT id, time FROM `users_sessions` WHERE authkey='" . $_SESSION["user"]["authkey"] . "' AND ipaddress='" . $_SERVER["REMOTE_ADDR"] . "' LIMIT 1";
		$sql_session_obj->execute();

		if ($sql_session_obj->num_rows())
		{
			$sql_session_obj->fetch_array();

			$time = time();
			if ($time < ($sql_session_obj->data[0]["time"] + 7200))
			{
				// we want to update the time value in the database, but we don't want to do this
				// on every single page load - no need, and a waste of performance.
				//
				// therefore, we only update the time record in the DB if it's older than 30 minutes. We use
				// this time to see if the user has been inactive for long periods of time, to log them out.
				if (($time -  $sql_session_obj->data[0]["time"]) > 1800)
				{
					// update time field
					$sql_obj		= New sql_query;
					$sql_obj->string	= "UPDATE `users_sessions` SET time='$time' WHERE authkey='". $_SESSION["user"]["authkey"] ."' LIMIT 1";
					$sql_obj->execute();
				}

				// save to cache
				$GLOBALS["cache"]["user"]["online"] = 1;

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
	}

	
	return 0;
}


/*
	user_login($instance, $username, $password)

	This function performs two main tasks:
	* If enabled, it performs brute-force blacklisting defense, and will block authentication
	  attempts from blacklisted IP addresses.
	* Checks the username/password and authenticates the user.

	Return Codes
	-5	Login disabled due to database-application version mismatch
	-4	Instance has been disabled
	-3	Invalid instance ID
	-2	User account has been disabled
	-1	IP is blacklisted due to brute-force attempts
	0	Invalid username/password
	1	Success
*/
function user_login($instance, $username, $password)
{
	log_debug("inc_user", "Executing user_login($instance, $username, password)");


	// get the database schema version
	$schema_version = sql_get_singlevalue("SELECT value FROM config WHERE name='SCHEMA_VERSION' LIMIT 1");

	if ($schema_version != $GLOBALS["config"]["schema_version"])
	{
		log_write("error", "inc_user", "The Amberdms Billing System application has been updated, but the database has not been upgraded to match. Login is disabled until this is resolved.");
		return -5;
	}


	//
	// check the instance (if required) and select the required database
	//
	if ($GLOBALS["config"]["instance"] == "hosted")
	{
		$sql_instance_obj		= New sql_query;
		$sql_instance_obj->string	= "SELECT active, db_hostname FROM `instances` WHERE instanceid='$instance' LIMIT 1";
		$sql_instance_obj->execute();
		
		if ($sql_instance_obj->num_rows())
		{
			$sql_instance_obj->fetch_array();

			if ($sql_instance_obj->data[0]["active"])
			{
				// Instance exists and access is permitted - now use the details
				// to establish a connection to the instance database (note that this
				// database may be on a different server)


				// if the hostname is blank, default to the current
				if ($sql_instance_obj->data[0]["db_hostname"] == "")
				{
					$sql_instance_obj->data[0]["db_hostname"] = $GLOBALS["config"]["db_host"];
				}

				// if the instance database is on a different server, initate a connection
				// to the new server.
				if ($sql_instance_obj->data[0]["db_hostname"] != $GLOBALS["config"]["db_host"])
				{
					// TODO: does this connect statement need to be moved into the sql_obj framework?
					$link = mysql_connect($sql_instance_obj->data[0]["db_hostname"], $config["db_user"], $config["db_pass"]);

					if (!$link)
					{
						log_write("error", "inc_users", "Unable to connect to database server for instance $instance - error: " . mysql_error());
						return -3;
					}
				}


				// select the instance database
				$dbaccess = mysql_select_db($GLOBALS["config"]["db_name"] ."_$instance");
	
				if (!$dbaccess)
				{
					// invalid instance ID
					// ID has a record in the instance table, but does not have a valid database
					log_write("error", "inc_user", "Instance ID has record but no database accessible - error: ". mysql_error());
					return -3;
				}
				else
				{
					// save the instance value
					$_SESSION["user"]["instance"]["id"]		= $instance;
					$_SESSION["user"]["instance"]["db_hostname"]	= $sql_instance_obj->data[0]["db_hostname"];
				}
			}
			else
			{
				// instance exists but is disabled
				log_write("error", "inc_user", "Your account has been disabled - please contact the system administrator if you belive this to be a mistake.");
				return -4;
			}
		}
		else
		{
			// no such instance
			log_write("error", "inc_user", "Please provide a valid customer instance ID.");
			return -3;
		}

	}


	//
	// perform a check of the IP against a brute-force table. Whilst admins should always lock down their interfaces
	// to trusted IPs only, in the real-world this often does not happen.
	//

	$blacklist_enable	= sql_get_singlevalue("SELECT value FROM `config` WHERE name='BLACKLIST_ENABLE' LIMIT 1");
	$blacklist_limit	= sql_get_singlevalue("SELECT value as blacklist_limit FROM `config` WHERE name='BLACKLIST_LIMIT' LIMIT 1");


	if ($blacklist_enable == "enabled")
	{
		// check the database - is this IP in the bad list?
		$sql_blacklist_obj		= New sql_query;
		$sql_blacklist_obj->string	= "SELECT failedcount, time FROM `users_blacklist` WHERE ipaddress='" . $_SERVER["REMOTE_ADDR"] . "'";
		$sql_blacklist_obj->execute();

		if ($sql_blacklist_obj->num_rows())
		{
			foreach ($sql_blacklist_obj->data as $data_blacklist)
			{
				// IP is in bad list - but we need to check the count against the time, to see if it's just an
				// innocent wrong password, or if it's something more sinister.

				if ($data_blacklist["failedcount"] >= $blacklist_limit && $data_blacklist["time"] >= (time() - 432000))
				{
					// if failed count >= blacklist limit, and if the last attempt was within
					// the last 5 days, block the user.

					log_write("error", "inc_user", "For brute-force security reasons, you have been locked out of the system interface.");
					return -1;
				}
				elseif ($data_blacklist["time"] < (time() - 432000))
				{
					// It has been more than 5 days since the last attempt was blocked. Start clearing the counter, by
					// removing 2 attempts.
					
					if ($data_blacklist["failedcount"] > 2)
					{
						// decrease by 2.
						$newcount		= $data_blacklist["failedcount"] - 2;

						$sql_obj		= New sql_query;
						$sql_obj->string	= "UPDATE `users_blacklist` SET `failedcount`='$newcount' WHERE ipaddress='" . $_SERVER["REMOTE_ADDR"] . "' LIMIT 1";
						$sql_obj->execute();
					}
					else
					{
						// time to remove the entry completely
						$sql_obj		= New sql_query;
						$sql_obj->string	= "DELETE FROM `users_blacklist` WHERE ipaddress='" . $_SERVER["REMOTE_ADDR"] . "' LIMIT 1";
						$sql_obj->execute();
					}
				}
			}
		}
	} // end of blacklist check


	// get user data
	$sql_user_obj		= New sql_query;
	$sql_user_obj->string	= "SELECT id, password, password_salt FROM `users` WHERE username='$username' LIMIT 1";
	$sql_user_obj->execute();

	if ($sql_user_obj->num_rows())
	{
		$sql_user_obj->fetch_array();

		// compare passwords
		if ($sql_user_obj->data[0]["password"] == sha1($sql_user_obj->data[0]["password_salt"] . "$password"))
		{
			///// password is correct

			// make sure the user is not disabled. (PERM ID = 1)
			$sql_perms_obj		= New sql_query;
			$sql_perms_obj->string	= "SELECT id FROM `users_permissions` WHERE userid='" . $sql_user_obj->data[0]["id"] . "' AND permid='1' LIMIT 1";
			$sql_perms_obj->execute();

			if ($sql_perms_obj->num_rows())
			{
				// user has been disabled
				log_write("error", "inc_user", "Your user account has been disabled. Please contact the system administrator to get it unlocked.");
				return -2;
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
				// The authentication key is stored in the seporate users_sessions tables, which is capable
				// of supporting concurrent logins. The session table will automatically clean out any expired
				// session records whenever a user logs in.
				//
				// Note: The users_sessions table is intentionally not a memory table, in order to support this application
				// when running on load-balancing clusters with replicated MySQL databases. If this application is
				// running on a standalone server only, a memory table would have been acceptable.
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


				// perform session table cleanup - remove any records older than 12 hours
				$time_expired = $time - 43200;

				$sql_obj		= New sql_query;
				$sql_obj->string	= "DELETE FROM `users_sessions` WHERE time < '$time_expired'";
				$sql_obj->execute();


				// if concurrent logins is not enabled, delete any old sessions belonging to this user.
				if (sql_get_singlevalue("SELECT value FROM users_options WHERE userid='". $sql_user_obj->data[0]["id"] ."' AND name='concurrent_logins' LIMIT 1") != "on")
				{
					log_write("debug", "inc_users", "User account does not permit concurrent logins, removing all old sessions");

					$sql_obj		= New sql_query;
					$sql_obj->string	= "DELETE FROM `users_sessions` WHERE userid='". $sql_user_obj->data[0]["id"] ."'";
					$sql_obj->execute();
				}



				// create session entry for user login
				$sql_obj		= New sql_query;
				$sql_obj->string	= "INSERT INTO `users_sessions` (userid, authkey, ipaddress, time) VALUES ('". $sql_user_obj->data[0]["id"] ."', '$authkey', '$ipaddress', '$time')";
				$sql_obj->execute();


				// update user's last-login data
				$sql_obj		= New sql_query;
				$sql_obj->string	= "UPDATE `users` SET ipaddress='$ipaddress', time='$time' WHERE id='" . $sql_user_obj->data[0]["id"] . "'";
				$sql_obj->execute();


				// set session variables
				$_SESSION["user"]["id"]		= $sql_user_obj->data[0]["id"];
				$_SESSION["user"]["name"]	= $username;
				$_SESSION["user"]["authkey"]	= $authkey;


				// fetch user options from the database
				$sql_obj		= New sql_query;
				$sql_obj->string	= "SELECT name, value FROM users_options WHERE userid='". $sql_user_obj->data[0]["id"] . "'";
				$sql_obj->execute();

				if ($sql_obj->num_rows())
				{
					$sql_obj->fetch_array();
					
					foreach ($sql_obj->data as $data)
					{
						$_SESSION["user"][ $data["name"] ] = $data["value"];
					}
				}



				// does the user need to change their password? If they have no salt, it means the password
				// is the system default and needs to be changed
				if ($sql_user_obj->data[0]["password_salt"] == "")
				{
					$_SESSION["error"]["message"][] = "Your password is currently set to a default. It is highly important for you to change this password, which you can do <a href=\"index.php?page=user/options.php\">by clicking here</a>.";
				}

				
				/*
					If enabled, run the phone home feature now - this submits non-private
					data to Amberdms to better understand the size and requirements of
					our userbase.
				*/

				$phone_home = New phone_home();

				if ($phone_home->check_enabled())
				{
					if ($phone_home->check_phone_home_timer())
					{
						// time to update
						$phone_home->stats_generate();
						$phone_home->stats_submit();
					}
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
		$sql_blacklist_obj		= New sql_query;
		$sql_blacklist_obj->string	= "SELECT failedcount FROM `users_blacklist` WHERE ipaddress='" . $_SERVER["REMOTE_ADDR"] . "'";
		$sql_blacklist_obj->execute();

	        if ($sql_blacklist_obj->num_rows())
        	{
			$sql_blacklist_obj->fetch_array();

	                // IP is in the list. Increase the failed count, and set the time to now.
       			foreach ($sql_blacklist_obj->data as $data_blacklist)
                	{
				$newcount       	= $data_blacklist["failedcount"] + 1;
				$newtime        	= time();

				$sql_obj		= New sql_query;
				$sql_obj->string	= "UPDATE `users_blacklist` SET `failedcount`='$newcount', time='$newtime' WHERE ipaddress='" . $_SERVER["REMOTE_ADDR"] . "'";
				$sql_obj->execute();
			}
		}
		else
		{
			// IP is not currently in the list. We need to add it.
			$newtime       		= time();

			$sql_obj		= New sql_query;
			$sql_obj->string	= "INSERT INTO `users_blacklist` (ipaddress, failedcount, time) VALUES ('" . $_SERVER["REMOTE_ADDR"] . "', '1', '$newtime')";
			$sql_obj->execute();
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
	log_debug("inc_user", "Executing user_logout()");

	if ($_SESSION["user"]["name"])
	{
		// remove session entry from DB
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM `users_sessions` WHERE authkey='" . $_SESSION["user"]["authkey"] . "' LIMIT 1";
		$sql_obj->execute();

		// log the user out.
		$GLOBALS["cache"]["user"]	= array();
		$_SESSION["user"]		= array();
		$_SESSION["form"]		= array();

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
	log_debug("inc_user", "Executing user_newuser($username, $password, $realname, $email)");

	// make sure that the user running this command is an admin
	if (user_permissions_get("admin"))
	{
		// verify data
		if ($username && $password && $realname && $email)
		{
			// TODO: Fix ACID compliance here

			// create the user account
			$sql_obj		= New sql_query;
			$sql_obj->string	= "INSERT INTO `users` (username, realname, contact_email) VALUES ('$username', '$realname', '$email')";
			$sql_obj->execute();

			$userid = $sql_obj->fetch_insert_id() ;

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
	log_debug("inc_user", "Executing user_changepwd($userid, password)");

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
			$sql_obj		= New sql_query;
			$sql_obj->string	= "UPDATE `users` SET password='$password_crypt', password_salt='$password_salt' WHERE id='$userid' LIMIT 1";
			$sql_obj->execute();
		
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
	log_debug("inc_user", "Executing user_permissions_get($type)");


	// everyone (including guests) have the "public" permission, so don't waste cycles checking for it
	if ($type == "public")
	{
		return 1;
	}


	if ($GLOBALS["cache"]["user"]["perms"][$type])
	{
		return 1;
	}
	else
	{
		// other permissions... make sure user is valid, and logged in.
		if ($userid = user_information("id"))
		{
			// get the id of the permission
			$permid = sql_get_singlevalue("SELECT id as value FROM `permissions` WHERE value='$type' LIMIT 1");

			// if nothing found, deny.
			if (!$permid)
				return 0;

			// see if the user has this particular permission.
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM `users_permissions` WHERE userid='$userid' AND permid='$permid' LIMIT 1";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				// user has an entry for that permission.

				// save to cache & return success
				$GLOBALS["cache"]["user"]["perms"][$type] = 1;
				return 1;
				
			} // if permission exists
			
		} // if user is logged in
	}
	
	// default to deny
	return 0;
}



/*
	user_information($field)

	This function looks up the specified field in the database's "users" table and returns the result.
*/
function user_information($field)
{
	log_debug("inc_user", "Executing user_information($field)");


	if ($GLOBALS["cache"]["user"]["info"][$field])
	{
		return $GLOBALS["cache"]["user"]["info"][$field];
	}
	else
	{
		// verify user is logged in
		if (user_online())
		{
			// fetch the value
			$value = sql_get_singlevalue("SELECT $field as value FROM `users` WHERE username='" . $_SESSION["user"]["name"] . "' LIMIT 1");

			// cache the value
			$GLOBALS["cache"]["user"]["info"][$field] = $value;

			// return the value
			return $value;
		}
	}

	return 0;
}





/*
	user_permissions_staff_get($type, $staffid)

	This function looks up the database to see if the user has the specified permission
	in their access rights configuration for the requested employee.

	If the user has the correct permissions for this employee, the function will return 1,
	otherwise the function will return 0.
*/
function user_permissions_staff_get($type, $staffid)
{
	log_debug("user/permissions_staff", "Executing user_permissions_staff_get($type, $staffid)");

	// get ID of permissions record
	$sql_query		= New sql_query;
	$sql_query->string	= "SELECT id FROM permissions_staff WHERE value='$type' LIMIT 1";
	$sql_query->execute();
	
	if ($sql_query->num_rows())
	{
		$sql_query->fetch_array();
		$permid	= $sql_query->data[0]["id"];

		// check if the user has this permission for this staff member
		$user_perms		= New sql_query;
		$user_perms->string	= "SELECT id FROM users_permissions_staff WHERE userid='". $_SESSION["user"]["id"] ."' AND staffid='$staffid' AND permid='$permid' LIMIT 1";
		$user_perms->execute();

		if ($user_perms->num_rows())
		{
			// user has permissions
			return 1;
		}
	}
	
	return 0;
}


/*
	user_permissions_staff_getarray($type)

	This functions returns an array of all the staff IDs that the current user
	has access too.
*/
function user_permissions_staff_getarray($type)
{
	log_debug("user/permissions_staff", "Executing user_permissions_staff_getarray($type)");


	// get ID of permissions record
	$sql_query		= New sql_query;
	$sql_query->string	= "SELECT id FROM permissions_staff WHERE value='$type' LIMIT 1";
	$sql_query->execute();
	
	if ($sql_query->num_rows())
	{
		$sql_query->fetch_array();
		$permid	= $sql_query->data[0]["id"];

		$access_staff_ids = array();

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT staffid FROM `users_permissions_staff` WHERE userid='". $_SESSION["user"]["id"] ."' AND permid='$permid'";
		$sql_obj->execute();
		$sql_obj->fetch_array();

		foreach ($sql_obj->data as $data_sql)
		{
			$access_staff_ids[] = $data_sql["staffid"];
		}

		unset($sql_obj);

	}

	return $access_staff_ids;	
}




?>
