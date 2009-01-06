<?php
/*
	user/user-edit-process.php

	Access: admin users only

	Updates or creates a user account based on the information provided to it.
*/


// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");


if (user_permissions_get('admin'))
{
	////// INPUT PROCESSING ////////////////////////

	$id				= security_form_input_predefined("int", "id_user", 0, "");
	
	$data["username"]		= security_form_input_predefined("any", "username", 1, "");
	$data["realname"]		= security_form_input_predefined("any", "realname", 1, "");
	$data["contact_email"]		= security_form_input_predefined("any", "contact_email", 1, "");

	// are we editing an existing user or adding a new one?
	if ($id)
	{
		$mode = "edit";

		// make sure the user actually exists
		$mysql_string		= "SELECT id FROM `users` WHERE id='$id'";
		$mysql_result		= mysql_query($mysql_string);
		$mysql_num_rows		= mysql_num_rows($mysql_result);

		if (!$mysql_num_rows)
		{
			$_SESSION["error"]["message"][] = "The user you have attempted to edit - $id - does not exist in this system.";
		}
	}
	else
	{
		$mode = "add";
	}


	// account options are for edits only
	if ($mode == "edit")
	{
		$data["option_lang"]		= security_form_input_predefined("any", "option_lang", 1, "");
		$data["option_dateformat"]	= security_form_input_predefined("any", "option_dateformat", 1, "");
		$data["option_debug"]		= security_form_input_predefined("any", "option_debug", 0, "");
	}





	///// ERROR CHECKING ///////////////////////

	// make sure we don't choose a user name that has already been taken
	$mysql_string	= "SELECT id FROM `users` WHERE username='". $data["username"] ."'";
	if ($id)
		$mysql_string .= " AND id!='$id'";
	$mysql_result	= mysql_query($mysql_string);
	$mysql_num_rows	= mysql_num_rows($mysql_result);

	if ($mysql_num_rows)
	{
		$_SESSION["error"]["message"][] = "This user name is already used for another user - please choose a unique name.";
		$_SESSION["error"]["username-error"] = 1;
	}


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
	else
	{
		// if adding a new user, a password *must* be provided
		if ($mode == "add")
		{
			$_SESSION["error"]["message"][]			= "You must supply a password!";
			$_SESSION["error"]["password-error"]		= 1;
			$_SESSION["error"]["password_confirm-error"]	= 1;
		}
	}




	//// PROCESS DATA ////////////////////////////


	if ($_SESSION["error"]["message"])
	{
		if ($mode == "edit")
		{
			$_SESSION["error"]["form"]["user_view"] = "failed";
			header("Location: ../index.php?page=user/user-view.php&id=$id");
			exit(0);
		}
		else
		{
			$_SESSION["error"]["form"]["user_add"] = "failed";
			header("Location: ../index.php?page=user/user-add.php");
			exit(0);
		}
	}
	else
	{
		$_SESSION["error"] = array();

		if ($mode == "add")
		{
			// create the user account
			$id = user_newuser($data["username"], $data["password"], $data["realname"], $data["contact_email"]);

			if ($id)
			{
				// assign the user "disabled" permissions
				$mysql_string = "INSERT INTO `users_permissions` (userid, permid) VALUES ('$id', '1')";
				
				if (!mysql_query($mysql_string))
				{
					$_SESSION["error"]["message"][] = "A fatal SQL error occured: ". mysql_error();
					$_SESSION["error"]["message"][] = "The user account may have incorrect permissions assigned to it.";
				}
				else
				{
					$_SESSION["notification"]["message"][] = "Successfully created user account. Note that the user is disabled by default, you will need to use the User Permissions page to assign them access rights.";
					journal_quickadd_event("users", $id, "Created user account.");
				}
			}
			else
			{
				$_SESSION["error"]["message"][] = "A fatal error occured whilst trying to create the new user account.";
			}
		}
		else
		{
			// generate a new password and salt
			if ($data["password"])
			{
				user_changepwd($id, $data["password"]);
			}

			// update the account details
			//
			// We kick the user when we update these details, since values such as the username
			// will be saved in the user's session arrays and we don't want any weird errors.
			//
			// By setting authkey to nothing, the user will be kicked.
			//
			$mysql_string = "UPDATE `users` SET "
					."username='". $data["username"] ."', "
					."realname='". $data["realname"] ."', "
					."contact_email='". $data["contact_email"] ."', "
					."authkey='' "
					."WHERE id='$id'";
			
			if (!mysql_query($mysql_string))
			{
				$_SESSION["error"]["message"][] = "A fatal SQL error occured: ". mysql_error();
			}
			else
			{
				$_SESSION["notification"]["message"][] = "The user's details have been updated successfully.";
				journal_quickadd_event("users", $id, "Updated user's details.");
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

			// date format
			$sql_obj		= New sql_query;
			$sql_obj->string	= "INSERT INTO users_options (userid, name, value) VALUES ($id, 'dateformat', '". $data["option_dateformat"] ."')";
			$sql_obj->execute();

			// timezone
			$sql_obj		= New sql_query;
			$sql_obj->string	= "INSERT INTO users_options (userid, name, value) VALUES ($id, 'timezone', '". $data["option_timezone"] ."')";
			$sql_obj->execute();


			// debugging
			$sql_obj		= New sql_query;
			$sql_obj->string	= "INSERT INTO users_options (userid, name, value) VALUES ($id, 'debug', '". $data["option_debug"] ."')";
			$sql_obj->execute();
			
		}

		// goto view page
		header("Location: ../index.php?page=user/user-view.php&id=$id");
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
