<?php
/*
	Formats pop up windows for the Amberdms Billing System
*/



/*
	Include configuration + libraries
*/
include("include/config.php");
include("include/amberphplib/main.php");


log_debug("popup", "Starting popup.php");


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
$page = $_GET["page"];
	
// perform security checks on the page
// security_localphp prevents any nasties, and then we check the the page exists.
$page_valid = 0;
if (!security_localphp($page))
{
	log_write("error", "popup", "Sorry, the requested page could not be found - please check your URL.");
}
else
{
	if (!@file_exists($page))
	{
		log_write("error", "popup", "Sorry, the requested page could not be found - please check your URL.");
	}
	else
        {
		/*
			Load the page
		*/

		log_debug("popup", "Loading page $page");


		// include PHP code
		include($page);


		// create new page object
		$page_obj = New page_output;


		// page is valid
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

	<?php

	// include theme's CSS files
	print "<link href=\"".$theme_path ."theme.css\" rel=\"stylesheet\" type=\"text/css\" />\n";

	// include page-specific css files
	if (isset($page_obj->requires["css"]))
	{
		foreach ($page_obj->requires["css"] as $includefile)
		{
			// we check if the file exists in the theme, if it does we use that, otherwise
			// we fall back to default theme.
			//
			// this allows people to write themes changing most of the application, without
			// going to levels as crazy as trying to tweaks ever single weird use case and special pages.

			if (file_exists($theme_path . $includefile))
			{
				log_write("debug", "main", "Including additional CSS file $theme_path$includefile");

				print "<link href=\"$includefile\" rel=\"stylesheet\" type=\"text/css\" />\n";
			}
			else
			{
				log_write("debug", "main", "Including additional CSS file from default theme themes/default/$includefile");

				print "<link href=\"$includefile\" rel=\"stylesheet\" type=\"text/css\" />\n";
			}
		}
	}

	
	
	?>

<script type="text/javascript" src="external/jquery/jquery-1.4.2.js"></script>
<script type="text/javascript" src="include/javascript/javascript.js"></script>


<?php

// include page-specific javascript files
if (isset($page_obj->requires["javascript"]))
{
	foreach ($page_obj->requires["javascript"] as $includefile)
	{
		print "<script type=\"text/javascript\" src=\"$includefile\"></script>\n";
	}
}

?>


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
		</tr>
		</table>
	</td>
</tr>


<?php

/*
	Check permissions, requirements and execute page
*/

if ($page_valid == 1)
{
	// check permissions
	if ($page_obj->check_permissions())
	{
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
	//print "<br><br><br><br><br><br><br>wtf<br><br><br><br><br><br><br><br><br><br></td>";
	print "</td></tr></table>";
	print "</td></tr>";
}
else
{
	// padding
	print "<tr><td id=\"data_td_outer\">";
	print "<table id=\"data_table_inner\">";

	print "<td id=\"data_td_inner\">";
	//print "<br><br><br><br><br><br><br><br>ooooh<br><br><br><br><br><br><br><br><br></td>";
	
	print "</td></tr></table>";
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

</body></html>


<?php

// erase error and notification arrays
$_SESSION["user"]["log_debug"] = array();
$_SESSION["error"] = array();
$_SESSION["notification"] = array();

?>
