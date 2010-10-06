<?php 
/*
	language/ajax/trans_update_translation.php

	Updates the translation database for the specified label and current language.

	Used by the translation tools to enable users to translate the Amberdms Billing System.
*/

require("../../include/config.php");
require("../../include/amberphplib/main.php");


if (user_permissions_get("devel_translate"))
{
	/*
		Get Form Data
	*/
	$trans_label 		= @security_script_input_predefined("any", $_GET['trans_label']);
	$trans_translation	= @security_script_input_predefined("any", $_GET['trans_translation']);




	/*
		Validate
	*/

	if (!$trans_label)
	{
		log_write("error", "process", "You must supply a valid label");
	}

	if (!$trans_translation)
	{
		log_write("error", "process", "You must supply a valid translation");
	}



	/*
		Error handle
	*/

	if (error_check())
	{
		print "failure";


		/*
		// return error as text, so that the AJAX page can display it.
		foreach ($_SESSION["error"]["message"] as $errormsg)
		{
			print "$errormsg\n";
		}
		*/

		exit(0);
	}



	/*
		Process
	*/

	$sql_obj		= New sql_query;
	$sql_obj->string	= "DELETE FROM `language` WHERE language='". $_SESSION["user"]["lang"] ."' AND label='$trans_label' LIMIT 1";
	$sql_obj->execute();

	$sql_obj		= New sql_query;
	$sql_obj->string	= "INSERT INTO `language` (language, label, translation) VALUES ('". $_SESSION["user"]["lang"] ."', '$trans_label', '$trans_translation')";
	$sql_obj->execute();



	/*
		Return success
	*/

	log_write("notification", "message", "Added new translation \"$trans_translation\" for label \"$trans_label\"");
/*
	foreach ($_SESSION["notification"]["message"] as $message)
	{
		print "$message\n";
	}
*/

	print "success";

	exit(0);
}

?>
