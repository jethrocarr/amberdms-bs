<?php
/*
	user/users.php

	Administrator-only utility to create, edit or delete user accounts.
*/

// only admins may access this page
if (user_permissions_get("admin"))
{
	
	function page_render()
	{
		print "<h3>USER MANAGEMENT</h3>";
		print "<p>This page allows you to create, edit or delete user accounts, as well as allowing you to define the the account permissions.</p>";


		// establish a new table object
		$user_list = New table;

		$user_list->language	= $_SESSION["user"]["lang"];
		$user_list->tablename	= "user_list";
		$user_list->sql_table	= "users";

		// define all the columns and structure
		$user_list->add_column("standard", "username", "");
		$user_list->add_column("standard", "realname", "");
		$user_list->add_column("standard", "contact_email", "");
		$user_list->add_column("timestamp", "lastlogin_time", "time");
		$user_list->add_column("standard", "lastlogin_ipaddress", "ipaddress");

		// defaults
		$user_list->columns		= array("username", "realname", "contact_email", "lastlogin_time");
		$user_list->columns_order	= array("username");

		// custom SQL stuff
		$user_list->prepare_sql_addfield("id", "");

		// acceptable filter options
		$structure = NULL;
		$structure["fieldname"] = "searchbox";
		$structure["type"]	= "input";
		$structure["sql"]	= "username LIKE '%value%' OR realname LIKE '%value%' OR contact_email LIKE '%value%'";
		$user_list->add_filter($structure);


		// options form
		$user_list->load_options_form();
		$user_list->render_options_form();


		// fetch all the user information
		$user_list->generate_sql();
		$user_list->load_data_sql();

		if (!count($user_list->columns))
		{
			print "<p><b>Please select some valid options to display.</b></p>";
		}
		elseif (!$user_list->data_num_rows)
		{
			print "<p><b>No users that match your options were found.</b></p>";
		}
		else
		{
			// view link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$user_list->add_link("view", "user/user-view.php", $structure);

			// display the table
			$user_list->render_table();

			// TODO: display CSV download link
		}

	} // end of page_render()
	

// if user doesn't have access, display messages.
}
else
{
	error_render_noperms();
}
?>
