<?php
/*
	Amberdms Billing System
	(c) Copyright 2010 Amberdms Ltd

	www.amberdms.com/billing

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU Affero General Public License version 3
	only as published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU Affero General Public License for more details.

	You should have received a copy of the GNU Affero General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/



/*
	Include configuration + libraries
*/
include("include/config.php");
include("include/amberphplib/main.php");


log_debug("index", "Starting index.php");


/*
	Enforce HTTPS
*/
if (!$_SERVER["HTTPS"])
{
	header("Location: https://". $_SERVER["SERVER_NAME"] ."/".  $_SERVER['PHP_SELF'] );
	exit(0);
}




/*
	Fetch the page name to display, and perform security checks
*/

// get the page to display
if (!empty($_GET["page"]))
{
	$page = $_GET["page"];
}
else
{
	$page = "home.php";
}

	
// perform security checks on the page
// security_localphp prevents any nasties, and then we check the the page exists.
$page_valid = 0;
if (!security_localphp($page))
{
	log_write("error", "index", "Sorry, the requested page could not be found - please check your URL.");
}
else
{
	if (!@file_exists($page))
	{
		log_write("error", "index", "Sorry, the requested page could not be found - please check your URL.");
	}
	else
        {
		$page_valid = 1;
	}
}


/*
	Check if a custom theme has been selected and set the path variable accordingly. 
*/

if (isset($_SESSION["user"]["theme"]))
{
	$folder = sql_get_singlevalue("SELECT theme_name AS value FROM themes WHERE id = '". $_SESSION["user"]["theme"] ."'");
}
else
{
	$folder = sql_get_singlevalue("SELECT t.theme_name AS value FROM themes t, config c WHERE c.name = 'THEME_DEFAULT' AND c.value = t.id");
}

// create path
$theme_path = "themes/".$folder."/";


?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Strict//EN" "http://www.w3.org/TR/REC-html40/strict.dtd">
<html>
<head>
	<title>Amberdms Billing System</title>
	<meta name="copyright" content="(C)Copyright 2010 Amberdms Ltd.">

	<?php print "<link href=\"".$theme_path ."theme.css\" rel=\"stylesheet\" type=\"text/css\" />"; ?>
	
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


<body>


<!-- Main Structure Table -->
<table id="table_main_struct">


<!-- Header -->
<tr>
	<td id="header_td_outer">
		<table id="header_table_inner">
		<tr>
			<?php print "<td id=\"header_logo\"><img src=\"".$theme_path."logo.png\" alt=\"Amberdms Billing System\"></td>"; ?>
			<td id="header_logout">
			<?php

			if (user_online())
			{
				print "<p id=\"header_logout_text\">logged on as ". $_SESSION["user"]["name"] ." | <a href=\"index.php?page=user/options.php\">options</a> | <a href=\"index.php?page=user/logout.php\">logout</a></p>";
			}

			?>
			</td>
		</tr>
		</table>
	</td>
</tr>


<?php

	
/*
	Draw the main page menu
*/

if (user_online())
{
	if ($page_valid == 1)
	{
		print "<tr><td>";

		$obj_menu			= New menu_main;
		$obj_menu->page			= $page;

		if ($obj_menu->load_data())
		{
			$obj_menu->render_menu_standard();
		}


		print "</td></tr>";
	}
		
}



/*
	Load the page
*/

if ($page_valid == 1)
{
	log_debug("index", "Loading page $page");


	// include PHP code
	include($page);


	// create new page object
	$page_obj = New page_output;

	// check permissions
	if ($page_obj->check_permissions())
	{
		/*
			Draw navigiation menu
		*/
		
		if (!empty($page_obj->obj_menu_nav))
		{
			print "<tr><td>";
			$page_obj->obj_menu_nav->render_html();
			print "</tr></td>";
		}



		/*
			Check data
		*/
		$page_valid = $page_obj->check_requirements();


		/*
			Run page logic, provided that the data was valid
		*/
		if ($page_valid)
		{
			$page_obj->execute();
		}
	}
	else
	{
		// user has no valid permissions
		$page_valid = 0;
		error_render_noperms();
	}
}



/*
	Draw messages
*/

if (!empty($_SESSION["error"]["message"]))
{
	print "<tr><td>";
	log_error_render();
	print "</td></tr>";
}
else
{
	if (!empty($_SESSION["notification"]["message"]))
	{
		print "<tr><td>";
		log_notification_render();
		print "</td></tr>";
	}
}



/*
	Draw page data
*/

if ($page_valid)
{
	// HTML-formatted output
	print "<tr><td id=\"data_td_outer\">";
	print "<table id=\"data_table_inner\"><tr>";

	print "<td id=\"data_td_inner\">";
	$page_obj->render_html();
	print "<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br></td>";

	print "</tr></table>";
	print "</td></tr>";
}
else
{
	// padding
	print "<tr><td id=\"data_td_outer\">";
	print "<table id=\"data_table_inner\">";

	print "<td id=\"data_td_inner\">";
	print "<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br></td>";
	
	print "</tr></table>";
	print "</td></tr>";
}


// save query string, so the user can return here if they login. (providing none of the pages are in the user/ folder, as that will break some stuff otherwise.)
if (!preg_match('/^user/', $page))
{
	$_SESSION["login"]["previouspage"] = $_SERVER["QUERY_STRING"];
}

?>




<!-- Page Footer -->
<tr>
	<td id="footer_td_outer">

	<table id="footer_table_inner">
	<tr>
		<td id="footer_copyright">
		<p id="footer_copyright_text">(c) Copyright 2010 <a href="http://www.amberdms.com">Amberdms Ltd</a>.</p>
		</td>

		<td id="footer_version">
		<p id="footer_version_text">Version <?php print $GLOBALS["config"]["app_version"]; ?></p>
		</td>
	</tr>
	</table>
	
	</td>
</tr>

<?php

if (!empty($_SESSION["user"]["log_debug"]))
{
	print "<tr>";
	print "<td id=\"debug_td_outer\">";


	log_debug_render();


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

?>
