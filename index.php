<?php
/*
	Amberdms Billing System
	(c) Copyright 2008 Amberdms Ltd

	www.amberdms.com
	Licenced under the GNU GPL version 2 only.
*/


/*
	Include configuration + libraries
*/
include("include/config.php");
include("include/amberphplib/main.php");


log_debug("index", "Starting index.php");


/*
	Fetch the page name to display, and perform security checks
*/

// get the page to display
$page = $_GET["page"];
if ($page == "")
	$page = "home.php";

	
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


// REMOVE?
//// set default page state
//if (!$_SESSION["error"]["pagestate"])
//	$_SESSION["error"]["pagestate"] = 1;



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


<?php

	
/*
	Draw the main page menu
*/

if (user_online())
{
	if ($page_valid == 1)
	{
		print "<tr><td>";
		render_menu_main($page);
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
		
		if ($page_obj->obj_menu_nav)
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
		error_render_noperms();
	}
}



/*
	Draw messages
*/

if ($_SESSION["error"]["message"])
{
	print "<tr><td>";
	log_error_render();
	print "</td></tr>";
}
else
{
	if ($_SESSION["notification"]["message"])
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
	print "<tr><td bgcolor=\"#ffffff\" style=\"border: 1px #000000 dashed; padding: 5px;\">";
	print "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\"><tr>";

	print "<td valign=\"top\" style=\"padding: 5px;\">";
	$page_obj->render_html();
	print "<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br></td>";

	print "</tr></table>";
	print "</td></tr>";
}
else
{
	// padding
	print "<tr><td bgcolor=\"#ffffff\" style=\"border: 1px #000000 dashed; padding: 5px;\">";
	print "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\">";

	print "<td valign=\"top\" style=\"padding: 5px;\">";
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
$_SESSION["lang_cache"] = array();

?>
