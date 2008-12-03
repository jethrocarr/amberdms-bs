<?php
/*
	Amberdms Billing System
	(c) Copyright 2008 Amberdms Ltd

	www.amberdms.com
	Licenced under the GNU GPL version 2 only.
*/

// includes
include("include/config.php");
include("include/amberphplib/main.php");

log_debug("index", "Starting index.php");


// get the page to display
$page = $_GET["page"];
if ($page == "")
	$page = "home.php";

	
// perform security checks on the page
// security_localphp prevents any nasties, and then we check the the page exists.
$page_valid = 0;
if (!security_localphp($page))
{
	$_SESSION["error"]["message"][] = "Sorry, the requested page could not be found - please check your URL.";
}
else
{
	if (!@file_exists($page))
	{
		$_SESSION["error"]["message"][] = "Sorry, the requested page could not be found - please check your URL.";
	}
	else
        {
		$page_valid = 1;
	}
}



// set default page state
if (!$_SESSION["error"]["pagestate"])
	$_SESSION["error"]["pagestate"] = 1;



?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Strict//EN" "http://www.w3.org/TR/REC-html40/strict.dtd">
<html>
<head>
	<title>Amberdms Billing System</title>
	<meta name="copyright" content="(C)Copyright 2008 Amberdms Ltd.">


<script type="text/javascript">

function obj_hide(obj)
{
	document.getElementById(obj).style.display = 'none';
}
function obj_show(obj)
{
	document.getElementById(obj).style.display = '';
}

</script>
	
</head>

<style type="text/css">
@import url("include/style.css");
</style>


<body>


<!-- Main Structure Table -->
<table width="1000" cellspacing="5" cellpadding="0" align="center">



<!-- Header -->
<tr>
	<td bgcolor="#ffbf00" style="border: 1px #747474 dashed;">
		<table width="100%">
		<tr>
			<td width="50%" align="left"><img src="images/amberdms-billing-system-logo.png" alt="Amberdms Billing System"></td>
			<td width="50%" align="right" valign="top">
			<?php

			if ($username = user_information("username"))
			{
				print "<p style=\"font-size: 10px;\"><b>logged on as $username | <a href=\"index.php?page=user/options.php\">options</a> | <a href=\"index.php?page=user/logout.php\">logout</a></b></p>";
			}

			?>
			</td>
		</tr>
		</table>
	</td>
</tr>

<tr>
	<td width="100%">

	<table width="100%" cellspacing="0" cellpadding="0" border="0">
	<?php
	/*
		Here we draw the menu. All pages have the top menu displayed, then differing numbers of sub menus, depending
		on the page currently open.
	*/

	if (user_online())
	{
		if ($page_valid == 1)
		{
			// page is valid and exists
			// this means that the $page value has already been checked to prevent
			// SQL injection or other unwanted problems.

			log_debug("index", "Generating Menu Structure");


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
				log_debug("index", "Fetching array of all the permissions the user has for displaying the menu");
				
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
				log_debug("index", "Drawing Menu");


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
		
			} // end if menu items exist
			

			// free up memory taken by menu data
			unset($sql_menu_obj);
			unset($menu_order);
			unset($tmp_data);
			unset($user_permissions);

			log_debug("index", "Menu Complete");

		} // end if page valid
			
	} // end if user is online
	?>

	
	</table>
	
	</td></tr>

	<?php

	// display section.
        //      - display navigation menu
        //      - display any errors & notifications
        //      - display the page.


	// LOAD THE PAGE AND PROCESS HEADER CODE
	if ($page_valid == 1)
	{
		log_debug("index", "Including page $page");
		include($page);
	}


	/*
		DRAW NAVIGATION MENU

		The main application menu is built from the configuration in the MySQL database. However, it is often desirable
		to be able to create a custom menu for the page currently running, for uses such as spliting large pages into
		multiple sections (simular to tabs).
	        
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
	if ($_SESSION["nav"]["active"])
	{
		print "<tr>";
		print "<td width=\"100%\" cellpadding=\"0\" cellborder=\"0\" cellspacing=\"0\">";

		print "<ul id=\"navmenu\">";

		        $j = count($_SESSION["nav"]["query"]);
		        for ($i=0; $i < $j; $i++)
		        {
				// are we viewing the current page?
				if ($_SESSION["nav"]["current"] == $_SESSION["nav"]["query"][$i])
				{
					print "<li><a style=\"background-color: #60ae62;\" href=\"index.php?". $_SESSION["nav"]["query"][$i] ."\" title=\"". $_SESSION["nav"]["title"][$i] ."\">". $_SESSION["nav"]["title"][$i] ."</a></li>";
				}
				else
				{
					print "<li><a href=\"index.php?". $_SESSION["nav"]["query"][$i] ."\" title=\"". $_SESSION["nav"]["title"][$i] ."\">". $_SESSION["nav"]["title"][$i] ."</a></li>";
				}
			}

		
		print "</ul>";

		print "</td>";
		print "</tr>";
	}
	
	

        // DRAW ERROR/NOTIFCATION MESSAGES
        if ($_SESSION["error"]["message"])
        {
                print "<tr><td bgcolor=\"#ffeda4\" style=\"border: 1px dashed #dc6d00; padding: 3px;\">";
                print "<p><b>Error:</b><br><br>";

		foreach ($_SESSION["error"]["message"] as $errormsg)
		{
			print "$errormsg<br>";
		}
		
		print "</p>";
                print "</td></tr>";

        }
        elseif ($_SESSION["notification"]["message"])
        {
                print "<tr><td bgcolor=\"#c7e8ed\" style=\"border: 1px dashed #374893; padding: 3px;\">";
                print "<p><b>Notification:</b><br><br>";
		
		foreach ($_SESSION["notification"]["message"] as $notificationmsg)
		{
			print "$notificationmsg<br>";
		}

		print "</p>";
                print "</td></tr>";
        }


	// CENTER DATA
	print "<tr><td bgcolor=\"#ffffff\" style=\"border: 1px #000000 dashed; padding: 5px;\">";
	print "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\">";
	

        // DISPLAY THE PAGE (PROVIDING THAT ONE WAS LOADED)
        if ($_SESSION["error"]["pagestate"] && $page_valid)
        {
		log_debug("index", "Executing page functions");


		// perform page processing
		$page_obj = New page_output;		
		$page_obj->process();


		// display the page
		print "<td valign=\"top\" style=\"padding: 5px;\">";
		
		$page_obj->render_html();
		
                print "<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br></td>";


		// free up page memory
		unset($page_obj);
		

                // save query string, so the user can return here if they login. (providing none of the pages are in the user/ folder, as that will break some stuff otherwise.)
                if (!preg_match('/^user/', $page))
                {
                        $_SESSION["login"]["previouspage"] = $_SERVER["QUERY_STRING"];
                }
        }
	else
	{
		// draw the content page table column to keep everything neat
                print "<td><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br></td>";
	}

	?>

	</tr>
	</table>

	</td>
	</tr>


<!-- Page Footer -->
<tr>
	<td bgcolor="#ffbf00" style="border: 1px #747474 dashed;">

	<table width="100%">
	<tr>
		<td align="left">
		<p style="font-size: 10px">(c) Copyright 2008 <a href="http://www.amberdms.com">Amberdms Ltd</a>.</p>
		</td>
	</tr>
	</table>
	
	</td>
</tr>

<?php

if ($_SESSION["user"]["log_debug"])
{
	print "<tr>";
	print "<td bgcolor=\"#ffffff\" style=\"border: 1px #000000 dashed;\">";


	debug_log_render();


	print "</td>";
	print "</tr>";
}

?>


</table>

<br><br><br><br><br>
<br><br><br><br><br>
<br><br><br><br><br>
<br><br><br><br><br>
<br><br><br><br><br>
<br><br><br><br><br>

</body></html>


<?php

// erase error and notification arrays
$_SESSION["user"]["log_debug"] = array();
$_SESSION["error"] = array();
$_SESSION["notification"] = array();
$_SESSION["nav"] = array();
$_SESSION["lang_cache"] = array();

?>
