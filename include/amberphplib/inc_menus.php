<?php
/*
	inc_menus.php

	Provides functions for drawing menus.
*/


/*
	render_menu_main
	
	Renders the main application menu based on the configuration
	in the menu table.

	Values
	page		Current page name

	Returns
	0		failure
	1		success
*/
function render_menu_main($page)
{
	log_debug("inc_menus", "Executing render_menu_main()");


	/*
		Fetch data for the entire menu from the database

		We fetch all the data at once, then run though it following the parent value as we run though
		all the items to determine what menu items need to be shown and in what order.

		We know that the single loop will match all the menu items correctly, since the menu items are ordered
		so we run though the order in the same direction. This saves us from having to do heaps of unnessacary loops. :-)
	*/

	$sql_menu_obj		= New sql_query;
	$sql_menu_obj->string	= "SELECT link, topic, permid, parent FROM menu ORDER BY priority DESC";
	$sql_menu_obj->execute();

	if ($sql_menu_obj->num_rows())
	{
		$sql_menu_obj->fetch_array();


		// array to store the order of the menu items
		$menu_order = array();
		
		// keep track of the topic we are looking for
		$target_topic = "";


		// loop though the menu items 
		foreach ($sql_menu_obj->data as $data)
		{
			if ($target_topic != "top")
			{
				if (!$target_topic)
				{
					// use the page link to find the first target
					if ($data["link"] == "$page")
					{
						$target_topic = $data["parent"];
						$menu_order[] = $data["parent"];
					}
				}
				else
				{
					// check the topic type
					if ($data["topic"] == $target_topic)
					{
						$target_topic = $data["parent"];
						$menu_order[] = $data["parent"];
					}
				}
			}
		}


		// now we reverse the order array, so we can
		// render the menus in the correct order
		if ($menu_order)
		{
			$menu_order = array_reverse($menu_order);
		}
		else
		{
			// if we have no sub-menu information, just set
			// to display the top menu only
			$menu_order = array("top");
		}

		


		/*
			get an array of all the permissions this user has
		*/
		log_debug("inc_menus", "Fetching array of all the permissions the user has for displaying the menu");
		
		$user_permissions	= array();

		$sql_obj 		= New sql_query;
		$sql_obj->string	= "SELECT permid FROM `users_permissions` WHERE userid='". $_SESSION["user"]["id"] ."'";

		$sql_obj->execute();
		$sql_obj->fetch_array();
			
		foreach ($sql_obj->data as $data)
		{
			$user_permissions[] = $data["permid"];
		}


		

		/*
			Now we display all the menus.

			Note that the "top" menu has the addition of a second column to the right
			for the user perferences/administration box.
		*/
		log_debug("inc_menus", "Drawing Menu");

		print "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">";


		// sort the data in the opposite direction for correct rendering
		$tmp_data = array_reverse($sql_menu_obj->data);
		
		for ($i = 0; $i <= count($menu_order); $i++)
		{
			print "<tr>";
			print "<td width=\"100%\" cellpadding=\"0\" cellborder=\"0\" cellspacing=\"0\">";
			print "<ul id=\"menu\">";


			// loop though the menu data
			foreach ($tmp_data as $data)
			{
				if ($data["parent"] == $menu_order[$i])
				{
					// check that the user has permissions to display this link
					if (in_array($data["permid"], $user_permissions) || $data["permid"] == 0)
					{
						// if this entry has no topic, it only exists for the purpose of getting a parent
						// link highlighted. In this case, ignore the current entry.

						if ($data["topic"])
						{
							// highlight the entry, if it's the parent of the next sub menu, or if this is a sub menu.
							if ($menu_order[$i + 1] == $data["topic"] || $data["link"] == $page)
							{
								print "<li><a style=\"background-color: #7e7e7e;\" href=\"index.php?page=". $data["link"] ."\" title=". $data["topic"] .">". $data["topic"] ."</a></li>";
							}
							else
							{
								print "<li><a href=\"index.php?page=". $data["link"] ."\" title=". $data["topic"] .">". $data["topic"] ."</a></li>";
							}
						}
					}
				}
				
			} // end of loop though menu data

			print "</ul>";
			print "</td>";
			print "</tr>";
			
		}
		
		print "</table>";

	} // end if menu items exist
	

	// free up memory taken by menu data
	unset($sql_menu_obj);
	unset($menu_order);
	unset($tmp_data);
	unset($user_permissions);

	log_debug("inc_menus", "Menu Complete");
	
} // end of render_main_menu

	


/*
	CLASS MENU_NAV

	The main application menu is built from the configuration in the MySQL database. However, it is often desirable
	to be able to create a custom menu for the page currently running, for uses such as spliting large pages into
	multiple sections (simular to tabs).

	Amberphplib provides a menu called the "nav menu" which can be used to define custom menus. These menus need to
	be defined at run time by the page being executed.
	        
	Usage (example):

		(The following code should go after user permissions verification, but before the page_render function)

		To enable the nav menu on the page:
		> $_SESSION["nav"]["active"] = 1;

		For each menu entry you wish to have, use the following syntax:
		> $_SESSION["nav"]["query"][] = "page=home/home.php";
		> $_SESSION["nav"]["title"][] = "Return to Home";

		To choose which one will be high-lighted when the menu is drawn, specify which
		page URL should be made current:			
		> $_SESSION["nav"]["current"] = "page=home/home.php"

*/

class menu_nav
{
	var $structure;		// holds the structure of the navigation menu


	/*
		add_item

		Add a new item to the menu bar

		Values
		title		human-readable title of the link
		link		URL in the form of page=<pagename>
		selected	Set to 1 to make this nav item selectred
	*/
	function add_item($title, $link, $selected = NULL)
	{
		log_write("debug", "Executing menu_navigation:menu_nav_add($title, $link, $selected)");

		$this->structure["links"][] = $link;
		$this->structure["title"][] = $title;

		if ($selected)
		{
			$this->structure["selected"] = $link;
		}
	}



	/*
		render_html

		Renders the navigiation menu.

	*/
	function render_html()
	{
		log_write("debug", "Executing menu_navigiation:menu_nav_render_html()");

		print "<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\">";
		print "<tr>";
		print "<td width=\"100%\" cellpadding=\"0\" cellborder=\"0\" cellspacing=\"0\">";

		print "<ul id=\"navmenu\">";

		        $j = count($this->structure["links"]);
			
		        for ($i=0; $i < $j; $i++)
		        {
				// are we viewing the current page?
				if ($this->structure["selected"] == $this->structure["links"][$i])
				{
					print "<li><a style=\"background-color: #60ae62;\" href=\"index.php?". $this->structure["links"][$i] ."\" title=\"". $this->structure["title"][$i] ."\">". $this->structure["title"][$i] ."</a></li>";
				}
				else
				{
					print "<li><a href=\"index.php?". $structure["links"][$i] ."\" title=\"". $this->structure["title"][$i] ."\">". $this->structure["title"][$i] ."</a></li>";
				}
			}

		
		print "</ul>";

		print "</td>";
		print "</tr>";
		print "</table>";
	}
	
} // end of class menu_nav


?>
