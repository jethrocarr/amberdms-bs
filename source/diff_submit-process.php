<?php
/*
	source/diff_submit-process.php

	access: all logged in users

	(we don't provide access to public users, since that could put performance strains on the server and
	 might reveal code that can't be made public)


	Takes the supplied patch data from the diff_generate.php page along with user information and submits it
	to Amberdms.
*/


// includes
require("../include/config.php");
require("../include/amberphplib/main.php");


if (user_online())
{
	/*
		Load POST data
	*/

	$data["patch"]			= $_SESSION["error"]["patch"]	= $_POST["patch"];

	$data["patch_submit_notes"]	= security_form_input_predefined("any", "patch_submit_notes", 0, "");
	$data["patch_submit_legal"]	= security_form_input_predefined("checkbox", "patch_submit_legal", 0, "");
	$data["patch_submit_contact"]	= security_form_input_predefined("email", "patch_submit_contact", 1, "");
	$data["patch_submit_credit"]	= security_form_input_predefined("any", "patch_submit_credit", 1, "");
	$data["patch_description"]	= security_form_input_predefined("any", "patch_description", 1, "");


	if (!$data["patch_submit_legal"])
	{
		log_write("error", "process", "You must agree to the legal ownership statement before a patch can be submitted to Amberdms.");
		error_flag_field("patch_submit_legal");
	}


	// return to input page if any errors occured
	if ($_SESSION["error"]["message"])
	{
		$_SESSION["error"]["form"]["diff_generate"] = "failed";
		header("Location: ../index.php?page=source/diff_generate.php");
		exit(0);
	}


	/*
		HTTP POST data to Amberdms's servers
	*/


	// set POST variables
	$url = "https://www.amberdms.com/api/opensource/amberdms_patch_submit.php";

	// encode values into a string suitable for upload
	$fields = array(
		"application"		=> urlencode("Amberdms Billing System"),
		"version"		=> urlencode($GLOBALS["config"]["app_version"]),
		"contact_email"		=> urlencode($data["patch_submit_contact"]),
		"author"		=> urlencode($data["patch_submit_credit"]),
		"description"		=> urlencode($data["patch_description"]),
		"patch"			=> urlencode($data["patch"])
	);


	foreach($fields as $key=>$value)
	{
		$fields_string .= $key.'='.$value.'&';
	}
	rtrim($fields_string,'&');

	// open connection
	$ch = curl_init();

	//set the url, number of POST vars, POST data
	curl_setopt($ch,CURLOPT_URL,$url);
	curl_setopt($ch,CURLOPT_POST,count($fields));
	curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 	// don't validate the cert, security is not key
								// here since it's OSS code we are submitting

	// execute post
	if (!curl_exec($ch))
	{
		log_write("error", "process", "Curl error whilst POSTing data: ". curl_error($ch));
		log_write("error", "process", "An unexpected error occured whilst attempting to submit the patch to Amberdms - try again later, or email the patch manually to <a href=\"mailto:developers@amberdms.com\">developers@amberdms.com</a> instead.");
	}
	else
	{
		// TODO: would be very nice to have a proper public tracking application for patches and bug reports
		log_write("notification", "process", "THANK YOU! Your patch has been successfully submitted to Amberdms, we will email you to keep you informed on the status of your submission");
	}

	//close connection
	curl_close($ch);



	// display updated details
	header("Location: ../index.php?page=source/getsource.php");
	exit(0);
	
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
