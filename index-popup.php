<?php
/*
	AMBERDMS BILLING SYSTEM
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


// set default page state
if (!$_SESSION["error"]["pagestate"])
	$_SESSION["error"]["pagestate"] = 1;



?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Strict//EN" "http://www.w3.org/TR/REC-html40/strict.dtd">
<html>
<head>
	<title>Amberos Configuration System</title>
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

<!-- Resize the window to a good size -->
<script language="javascript">
top.resizeTo(810,700);
</script>


<!-- Main Structure Table -->
<table width="800" cellspacing="5" cellpadding="0" align="center">

	<?php

	// display section.
        //      - display "up" link
        //      - display any errors & notifications
        //      - display the page.

        // CHECK AND LOAD THE PAGE
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
                        include($page);
			$page_valid = 1;
                }
        }


        // DRAW ERROR/NOTIFCATION MESSAGES
        if ($_SESSION["error"]["message"])
        {
                print "<tr><td></td><td><br></td></tr>";
                print "<tr><td bgcolor=\"#ffeda4\" style=\"border: 1px dashed #dc6d00; padding: 3px;\">";
                print "<p><b>Error:</b><br><br>" . $_SESSION["error"]["message"] . "</p>";
                print "</td></tr>";

        }
        elseif ($_SESSION["notification"]["message"])
        {
                print "<tr><td><br></td></tr>";
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
		<a href="http://www.amberdms.com"><img src="images/amberdms-poweredby.png" alt="Powered by Amberdms" border="0"></a>
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
