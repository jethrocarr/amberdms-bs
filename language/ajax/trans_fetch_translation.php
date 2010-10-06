<?php 
/*
	language/ajax/trans_get_translation.php

	Fetches the translation for the specified label. This function can
	be used both by the translation tools as well as general javascript
	in Amberphplib that needs to translate a string/field.

	Note that no caching takes effect with this AJAX function as it's used
	by some of the tools for doing translations.


	This page is very minimal, all the logic is done by the framework.

	TODO: Extend to be able to return multiple translations at once to
		provide a more useful translation capability that doesn't require
		a system reload.
*/

require("../../include/config.php");
require("../../include/amberphplib/main.php");


if (user_online())
{
	$trans_label 		= @security_script_input_predefined("any", $_GET['trans_label']);


	// select language translation
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT translation FROM `language` WHERE language='". $_SESSION["user"]["lang"] ."' AND label='$trans_label' LIMIT 1";
	$sql_obj->execute();


	// return translation
	if ($sql_obj->num_rows())
	{
		$sql_obj->fetch_array();

		print $sql_obj->data[0]["translation"];
	}
	

	exit(0);
}

?>
