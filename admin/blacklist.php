<?php
/*
	admin/blacklist.php

	Allows an admin to enable/disable IP-based blacklisting

	TODO: currently IPv4 only, need to test and make suitable changes for IPv6
*/

// only admins may access this page
if (user_permissions_get("admin"))
{
	$_SESSION["error"]["menuid"] = "21";
	
	function page_render()
	{
		print "<h3>BRUTE FORCE BLACKLIST</h3>";
	
		print "<p>It is recommended that you limit access to the Amberdms Billing System by using a firewall to lock access
			to your trusted networks. However, there are sometimes cases where you need to have the system open to the
			internet, or you have untrusted hosts on your internal network. To provide you with additional security in
			these situations, you can use the IP blacklist feature to automaticly block anyone trying to brute force
			their way into user accounts.</p>";
			
		print "<p>When enabled, blacklisting will block any IP address that has 10 incorrect password attempts in succession.</p>";
		
		print "<br>";



		// ENABLE/DISABLE BLACKLISTING

		/*
			Define form enable checkbox
		*/
		print "<table width=\"100%\" class=\"table_highlight\"><tr><td>";
		
		print "<h3>BLACKLIST SETTINGS</h3><br><br>";

		$form = New form_input;
		$form->formname = "blacklist_control";
		$form->language = $_SESSION["user"]["lang"];

		$form->action = "admin/blacklist-enable-process.php";
		$form->method = "post";
	
	
		// general
		$structure = NULL;
		$structure["fieldname"] 	= "blacklist_enable";
		$structure["type"]		= "checkbox";
		$structure["options"]["req"]	= "yes";
		$form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "blacklist_limit";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$form->add_input($structure);


		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Apply changes";
		$form->add_input($structure);


		// define subforms
		$form->subforms["blacklist_control"]	= array("blacklist_enable", "blacklist_limit");
		$form->subforms["submit"]		= array("submit");


		// fetch state from DB
		$form->sql_query = "SELECT value as blacklist_enable FROM `config` WHERE name='BLACKLIST_ENABLE' LIMIT 1";
		$form->load_data();
		
		$form->sql_query = "SELECT value as blacklist_limit FROM `config` WHERE name='BLACKLIST_LIMIT' LIMIT 1";
		$form->load_data();
		
		// display the form
		$form->render_form();

		print "</td></tr></table>";



		if ($form->structure["blacklist_enable"]["defaultvalue"] == "enabled")
		{
			// establish a new table object
			$blacklist_list = New table;

			$blacklist_list->language	= $_SESSION["user"]["lang"];
			$blacklist_list->tablename	= "blacklist_list";

			// define all the columns and structure
			$blacklist_list->add_column("timestamp", "time", "");
			$blacklist_list->add_column("standard", "ipaddress", "");
			$blacklist_list->add_column("standard", "failedcount", "");

			// defaults
			$blacklist_list->columns	= array("time", "ipaddress", "failedcount");
			$blacklist_list->columns_order	= array("time");

			// define SQL structure
			$blacklist_list->sql_obj->prepare_sql_settable("users_blacklist");
			$blacklist_list->sql_obj->prepare_sql_addfield("id", "");



			// heading
			print "<br><br><h3>BLACKLISTED ADDRESSES</h3><br><br>";

			// selected all the blacklist information
			$blacklist_list->generate_sql();
			$blacklist_list->load_data_sql();

			if (!$blacklist_list->data_num_rows)
			{
				print "<p><b>The blacklist is currently empty.</b></p>";
			}
			else
			{
				// replace any failedcount result equal to the defined limit with "blocked"
				for ($i=0; $i < count($blacklist_list->data); $i++)
				{
					if ($blacklist_list->data[$i]["failedcount"] == $form->structure["blacklist_limit"]["defaultvalue"])
					{
						$blacklist_list->data[$i]["failedcount"] = "blocked";
					}
				}
				
				
			
				// view link
				$structure = NULL;
				$structure["id"]["column"]	= "id";
				$structure["full_link"]		= "yes";
				$blacklist_list->add_link("delete", "admin/blacklist-delete-process.php", $structure);

				// display the table
				$blacklist_list->render_table();

			}

		}

	} // end of page_render()
	

// if user doesn't have access, display messages.
}
else
{
	error_render_noperms();
}
?>
