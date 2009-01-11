<?php
/*
	user/options-process.php

	Access: all users

	Updates the user's account details and options
*/


// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");


if (user_online())
{
	////// INPUT PROCESSING ////////////////////////

	$id				= $_SESSION["user"]["id"];
	
	// general details
	$data["username"]		= security_form_input_predefined("any", "username", 1, "");
	$data["realname"]		= security_form_input_predefined("any", "realname", 1, "");
	$data["contact_email"]		= security_form_input_predefined("any", "contact_email", 1, "");
	
	// account options
	$data["option_lang"]			= security_form_input_predefined("any", "option_lang", 1, "");
	$data["option_dateformat"]		= security_form_input_predefined("any", "option_dateformat", 1, "");
	$data["option_timezone"]		= security_form_input_predefined("any", "option_timezone", 1, "");
	$data["option_debug"]			= security_form_input_predefined("any", "option_debug", 0, "");
	$data["option_concurrent_logins"]	= security_form_input_predefined("any", "option_concurrent_logins", 0, "");



	///// ERROR CHECKING ///////////////////////

	// check password (if the user has requested to change it)
	if ($_POST["password"] || $_POST["password_confirm"])
	{
		$data["password"]		= security_form_input_predefined("any", "password", 1, "");
		$data["password_confirm"]	= security_form_input_predefined("any", "password_confirm", 1, "");

		if ($data["password"] != $data["password_confirm"])
		{
			$_SESSION["error"]["message"][]		= "Your passwords do not match!";
			$_SESSION["error"]["password-error"]		= 1;
			$_SESSION["error"]["password_confirm-error"]	= 1;
		}
	}



	//// PROCESS DATA ////////////////////////////


	if ($_SESSION["error"]["message"])
	{
		$_SESSION["error"]["form"]["user_options"] = "failed";
		header("Location: ../index.php?page=user/options.php&id=$id");
		exit(0);
	}
	else
	{
		$_SESSION["error"] = array();

		/*
			Generate new password
		*/
		if ($data["password"])
		{
			user_changepwd($id, $data["password"]);
		}


		/*
			Update user account details
		*/

		$sql_obj		= New sql_query;
		$sql_obj->string	= "UPDATE `users` SET "
					."realname='". $data["realname"] ."', "
					."contact_email='". $data["contact_email"] ."' "
					."WHERE id='$id'";
		
		if (!$sql_obj->execute())
		{
			$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst trying to update user account details.";
		}


		/*
			Update user options
		*/

		// remove old user options
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM users_options WHERE userid='$id'";
		$sql_obj->execute();


		// language
		$sql_obj		= New sql_query;
		$sql_obj->string	= "INSERT INTO users_options (userid, name, value) VALUES ($id, 'lang', '". $data["option_lang"] ."')";
		$sql_obj->execute();
	
		// timezone
		$sql_obj		= New sql_query;
		$sql_obj->string	= "INSERT INTO users_options (userid, name, value) VALUES ($id, 'timezone', '". $data["option_timezone"] ."')";
		$sql_obj->execute();
			
		// dateformat
		$sql_obj		= New sql_query;
		$sql_obj->string	= "INSERT INTO users_options (userid, name, value) VALUES ($id, 'dateformat', '". $data["option_dateformat"] ."')";
		$sql_obj->execute();


		// administrator-only options
		if (user_permissions_get("admin"))
		{
			// debugging
			$sql_obj		= New sql_query;
			$sql_obj->string	= "INSERT INTO users_options (userid, name, value) VALUES ($id, 'debug', '". $data["option_debug"] ."')";
			$sql_obj->execute();

			// concurrent logins
			$sql_obj		= New sql_query;
			$sql_obj->string	= "INSERT INTO users_options (userid, name, value) VALUES ($id, 'concurrent_logins', '". $data["option_concurrent_logins"] ."')";
			$sql_obj->execute();
		}


		/*
			Apply changes to active session
		*/

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT name, value FROM users_options WHERE userid='$id'";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$sql_obj->fetch_array();
					
			foreach ($sql_obj->data as $data)
			{
				// save updated session data
				$_SESSION["user"][ $data["name"] ] = $data["value"];
			}
		}

		

		/*
			Complete
		*/
		
		$_SESSION["notification"]["message"][] = "Account changes applied.";
		journal_quickadd_event("users", $id, "User changed account options");

		header("Location: ../index.php?page=user/options.php&id=$id");
		exit(0);


	} // if valid data input
	
	
} // end of "is user logged in?"
else
{
	// user does not have permissions to access this page.
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
