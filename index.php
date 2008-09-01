<?php
/*
	Amberdms Billing System
	(c) Copyright 2008 Amberdms Ltd

	www.amberdms.com
	Licenced under the GNU GPL version 2 only.
*/

// include the database connection file.
include("include/database.php");

// include the function pages
include("include/functions.php");
include("include/security.php");
include("include/errors.php");
include("include/user.php");


// get the page to display
$page = $_GET["page"];
if ($page == "")
	$page = "home.php";

	
// perform security checks on the page
// security_localphp prevents any nasties, and then we check the the page exists.
$page_valid = 0;
if (!security_localphp($page))
{
	$_SESSION["error"]["message"] = "Sorry, the requested page could not be found - please check your URL.";
}
else
{
	if (!@file_exists($page))
	{
		$_SESSION["error"]["message"] = "Sorry, the requested page could not be found - please check your URL.";
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
@import url("include/style_menu.css");
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
				print "<p style=\"font-size: 10px;\"><b>You are logged on as $username | <a href=\"index.php?page=user/logout.php\">logout</a></b></p>";


			}

			?>
			</td>
		</tr>
		</table>
	</td>
</tr>

<tr>
	<td width="100%">

	<table width="100%">
	<?php
	/*
		Here we draw the menu. All pages have the top menu displayed, then differing numbers of sub menus, depending
		on the page currently open.

		In future, this should be replaced with a more fancy javascript solution with roll over features, etc.
	*/

	if ($page_valid == 1)
	{
		// page is valid and exists
		// this means that the $page value has already been checked to prevent
		// SQL injection or other unwanted problems.

		$parents = array();

		// get the menu item for this page
		$mysql_string		= "SELECT parent FROM `menu` WHERE link='$page'";
		$mysql_menu_result	= mysql_query($mysql_string);
		$mysql_menu_num_rows	= mysql_num_rows($mysql_menu_result);

		if ($mysql_menu_num_rows)
		{
			while ($mysql_menu_data = mysql_fetch_array($mysql_menu_result))
			{
				$parents[] = $mysql_menu_data["parent"];
			}

			// we now sort the array, and end up with all the menu
			// levels in order
			$parents = array_reverse($parents);
		}
	}

	// if we have no sub-menu information, just display the top menu.
	if (!$parents)
	{
		$parents[] = "top";
	}

	/*
		Now we display all the menus.

		Note that the "top" menu has the addition of a second column to the right
		for the user perferences/administration box.
	*/
	foreach ($parents as $parent)
	{
		print "<tr class=\"table_header\">";
		if ($parent == "top")
		{
			print "<td width=\"85%\">";
		}
		else
		{
			print "<td width=\"100%\">";
		}
	
		// get the data for this menu
		$mysql_string		= "SELECT link, topic FROM `menu` WHERE parent='$parent'";
		$mysql_menu_result	= mysql_query($mysql_string);
		$mysql_menu_num_rows	= mysql_num_rows($mysql_menu_result);
			
		$count = 0;
		while ($mysql_menu_data = mysql_fetch_array($mysql_menu_result))
		{
			print "<b><a href=\"index.php?page=". $mysql_menu_data["link"] ."\">". $mysql_menu_data["topic"] ."</a></b>";

			// prevent an extra seperator from being added to the menu.
			$count++;
			if ($count != $mysql_menu_num_rows)
			{
				print " | ";
			}
		}

		print "</td>";

		// special preferences/administration option for top menu only
		if ($parent == "top")
		{
			print "<td width=\"15%\" align=\"right\">";
			
			if (user_permissions_get("admin"))
			{
				print "<b><a href=\"index.php?page=admin/admin.php\">Administration</a></b>";
			}
			else
			{
				print "<b><a href=\"index.php?page=user/user-details.php&id=". user_information("id") ."&mode=self\">preferences</a></b>";
			}
			
			print "</td>";
		}
		print "</tr>";
	}
	?>

	
	</table>
	
	</td></tr>

	<?php

	// display section.
        //      - display "up" link
        //      - display any errors & notifications
        //      - display the page.


	// LOAD THE PAGE AND PROCESS HEADER CODE
	if ($page_valid == 1)
	{
		include($page);
	}

        // DRAW ERROR/NOTIFCATION MESSAGES
        if ($_SESSION["error"]["message"])
        {
                print "<tr><td bgcolor=\"#ffeda4\" style=\"border: 1px dashed #dc6d00; padding: 3px;\">";
                print "<p><b>Error:</b><br><br>" . $_SESSION["error"]["message"] . "</p>";
                print "</td></tr>";

        }
        elseif ($_SESSION["notification"]["message"])
        {
                print "<tr><td bgcolor=\"#c7e8ed\" style=\"border: 1px dashed #374893; padding: 3px;\">";
                print "<p><b>Notification:</b><br><br>" . $_SESSION["notification"]["message"] . "</p>";
                print "</td></tr>";
        }


	// CENTER DATA
	print "<tr><td bgcolor=\"#ffffff\" style=\"border: 1px #000000 dashed; padding: 5px;\">";
	print "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\">";
	

        // DISPLAY THE PAGE (PROVIDING THAT ONE WAS LOADED)
        if ($_SESSION["error"]["pagestate"] && $page_valid)
        {

		// display the page
		print "<td valign=\"top\" style=\"padding: 5px;\">";
                page_render();
                print "<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br></td>";

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

		<td align="right">
		<p style="font-size: 10px">Licensed under the GNU GPL version 2 license.</a></p>
		</td>
	</tr>
	
	</td>
</tr>


</table>


</body></html>


<?php

// erase error and notification arrays
$_SESSION["error"] = array();
$_SESSION["notification"] = array();

?>
