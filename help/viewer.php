<?php
/*
	viewer.php

	this page is able to display help documents

	TODO: do we need to have this? either fix it up or get rid of it
*/

include_once("../include/database.php");
include_once("../include/user.php");
include_once("../include/security.php");


// make sure the connection is HTTPS
if (!$_SERVER["HTTPS"])
{
	die("Not connected via HTTPS, quitting");
}

// user must be logged on
if (!user_online())
{
	die("You must be logged in to use the help browser");
}

// get the ID.
$id = security_script_input("/^[0-9]*$/", $_GET["id"]);
if (!$id)
	$id = 1;

		
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Strict//EN" "http://www.w3.org/TR/REC-html40/strict.dtd">
<html>
<head>
	<title>Help Viewer</title>
	<meta name="copyright" content="(C)Copyright 2007 Amberdms Ltd.">
</head>

<style type="text/css">
@import url("../include/style.css");
</style>

<body style="background-color: #ffffff;">


<!-- Resize the window to a good size -->
<script language="javascript">
top.resizeTo(400,600);
</script>

<!-- Page Header -->
<table width="100%" cellspacing="0" cellpadding="0" class="layout-table" align="center">
<tr>
	<td bgcolor="#ffbf00"><img src="../images/amberos-configint-logo.png" alt="AMBEROS CONFIGINT"></td>
</tr>
<tr>
	<td id="header-right"></td>
</tr>
</table>




<!-- Main Table -->
<table width="100%" bgcolor="#ffffff">
<tr>
	<td width=\"100%\">
	<?php

	// check that the requested help page exists
	if (file_exists("docs/$id"))
	{
		include("docs/$id");
	}
	else
	{
		print "<p><b>Sorry, no such help file exists.</b></p>";
	}


	?>	

	<br><br>
	<p><b><a href="javascript:top.close()">close this window</a></b></p>
	</td>
</tr>
</table>


</body>
</html>
