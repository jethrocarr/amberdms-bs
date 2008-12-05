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


log_debug("index", "Starting index-export.php");


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
	die("Sorry, the requested page could not be found - please check your URL.");
}
else
{
	if (!@file_exists($page))
	{
		die("Sorry, the requested page could not be found - please check your URL.");
	}
	else
        {
		$page_valid = 1;
	}
}


// get the mode to display
$mode = security_script_input("/^[a-z]*$/", $_GET["mode"]);

if (!$mode)
{
	die("No mode supplied!");
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

if ($_SESSION["error"]["message"])
{
	print "<tr><td>";
	log_error_render();
	print "</td></tr>";
}



/*
	Draw page data
*/

if ($page_valid)
{
	/*
		Setup HTTP headers we need for doing a file download
	*/

	$filename = "amberdms_bs_". mktime() .".$mode";
	
	// required for IE, otherwise Content-disposition is ignored
	if (ini_get('zlib.output_compression'))
		ini_set('zlib.output_compression', 'Off');

	// set the relevant content type
	$file_extension = strtolower(substr(strrchr($this->data["file_name"],"."),1));

	switch ($file_extension)
	{
		case "pdf": $ctype="application/pdf"; break;
		case "exe": $ctype="application/octet-stream"; break;
		case "zip": $ctype="application/zip"; break;
		case "doc": $ctype="application/msword"; break;
		case "xls": $ctype="application/vnd.ms-excel"; break;
		case "ppt": $ctype="application/vnd.ms-powerpoint"; break;
		case "gif": $ctype="image/gif"; break;
		case "png": $ctype="image/png"; break;
		case "jpeg":
		case "jpg": $ctype="image/jpg"; break;
		default: $ctype="application/force-download";
	}
	
	header("Pragma: public"); // required
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Cache-Control: private",false); // required for certain browsers 
	header("Content-Type: $ctype");
	
	header("Content-Disposition: attachment; filename=\"".basename($filename)."\";" );
	header("Content-Transfer-Encoding: binary");

// Not needed?
//		// tell the browser how big the file is (in bytes)
//		// most browers seem to ignore this, but it's vital in order to make IE 7 work.
//		header("Content-Length: ". $this->data["file_size"] ."");



	/*
		Output page data
	*/

	switch ($mode)
	{
		case "csv":
			$page_obj->render_csv();
		break;

		case "pdf":
			$page_obj->render_pdf();
		break;

		case "ps":
			$page_obj->render_ps();
		break;

		default:
			print "Invalid mode supplied";
		break;
	}
}

?>
