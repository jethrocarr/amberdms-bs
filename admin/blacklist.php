<?php
/*
	admin/blacklist.php

	Allows an admin to enable/disable IP-based blacklisting

	TODO: currently IPv4 only, need to test and make suitable changes for IPv6
*/


class page_output
{
	var $obj_form;
	var $obj_table_blacklist;

	function check_permissions()
	{
		return user_permissions_get("admin");
	}

	function check_requirements()
	{
		// nothing todo
		return 1;
	}

	function execute()
	{
		/*
			Define blacklist options form
		*/
		
		$this->obj_form = New form_input;
		$this->obj_form->formname = "blacklist_control";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "admin/blacklist-enable-process.php";
		$this->obj_form->method = "post";
	
	
		// general
		$structure = NULL;
		$structure["fieldname"] 	= "blacklist_enable";
		$structure["type"]		= "checkbox";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "blacklist_limit";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);


		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Apply changes";
		$this->obj_form->add_input($structure);


		// define subforms
		$this->obj_form->subforms["blacklist_control"]	= array("blacklist_enable", "blacklist_limit");
		$this->obj_form->subforms["submit"]		= array("submit");


		// fetch state from DB
		$this->obj_form->sql_query = "SELECT value as blacklist_enable FROM `config` WHERE name='BLACKLIST_ENABLE' LIMIT 1";
		$this->obj_form->load_data();
		
		$this->obj_form->sql_query = "SELECT value as blacklist_limit FROM `config` WHERE name='BLACKLIST_LIMIT' LIMIT 1";
		$this->obj_form->load_data();



		/*
			Define blacklisted address table

			(only needed if blacklisting is enabled)
		*/
		if ($this->obj_form->structure["blacklist_enable"]["defaultvalue"] == "enabled")
		{
			// establish a new table object
			$this->obj_table_blacklist = New table;

			$this->obj_table_blacklist->language	= $_SESSION["user"]["lang"];
			$this->obj_table_blacklist->tablename	= "blacklist_list";

			// define all the columns and structure
			$this->obj_table_blacklist->add_column("timestamp", "time", "");
			$this->obj_table_blacklist->add_column("standard", "ipaddress", "");
			$this->obj_table_blacklist->add_column("standard", "failedcount", "");

			// defaults
			$this->obj_table_blacklist->columns		= array("time", "ipaddress", "failedcount");
			$this->obj_table_blacklist->columns_order	= array("time");

			// define SQL structure
			$this->obj_table_blacklist->sql_obj->prepare_sql_settable("users_blacklist");
			$this->obj_table_blacklist->sql_obj->prepare_sql_addfield("id", "");

			// selected all the blacklist information
			$this->obj_table_blacklist->generate_sql();
			$this->obj_table_blacklist->load_data_sql();

			if ($this->obj_table_blacklist->data_num_rows)
			{
				// replace any failedcount result equal to the defined limit with "blocked"
				for ($i=0; $i < count($this->obj_table_blacklist->data); $i++)
				{
					if ($this->obj_table_blacklist->data[$i]["failedcount"] == $this->obj_form->structure["blacklist_limit"]["defaultvalue"])
					{
						$this->obj_table_blacklist->data[$i]["failedcount"] = "blocked";
					}
				}
			}
			
		} // end if blacklisitng enabled
	}



	function render_html()
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

		
		// display the form
		$this->obj_form->render_form();

		print "</td></tr></table>";



		if ($this->obj_form->structure["blacklist_enable"]["defaultvalue"] == "enabled")
		{
			// heading
			print "<br><br><h3>BLACKLISTED ADDRESSES</h3><br><br>";

			if (!$this->obj_table_blacklist->data_num_rows)
			{
				format_msgbox("info", "<p>The blacklist is currently empty.</p>");
			}
			else
			{
				// view link
				$structure = NULL;
				$structure["id"]["column"]	= "id";
				$structure["full_link"]		= "yes";
				$this->obj_table_blacklist->add_link("delete", "admin/blacklist-delete-process.php", $structure);

				// display the table
				$this->obj_table_blacklist->render_table_html();
			}

		}

	}

}


?>
