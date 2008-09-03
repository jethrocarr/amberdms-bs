<?php
/*
	language.php

	Provides translation functions.

	Translation is provided by a table in the DB called "language" which has translations
	for various labels. The functions in this file allow programs to lookup phrases and get
	the desired translation.
*/



/*
	language_translate($language, $label_array)

	This function translate all the labels specified in the $label_array
	and returns an associative array with the results.
*/
function language_translate($language, $label_array)
{
	if (!$language || !$label_array)
		print "Warning: Invalid input recieved for function language_translate<br>";
	
	// we can't be 100% sure that every field will have a translation
	// avaliable for it, so fill in the array with a fall back
	foreach ($label_array as $label)
	{
		$result[$label] = $label;
	}

	// prepare the SQL
	$mysql_string = "SELECT label, translation FROM `language` WHERE language='". $language ."' AND (";

	// add all the labels to the SQL query.
	$count = 0;
	foreach ($label_array as $label)
	{
		$count++;
			
		if ($count < count($label_array))
		{
			$mysql_string .= "label='$label' OR ";
		}
		else
		{
			$mysql_string .= "label='$label'";
		}
	}

	$mysql_string .= ")";


	// query
	$mysql_result	= mysql_query($mysql_string);
	$mysql_num_rows	= mysql_num_rows($mysql_result);

	if ($mysql_num_rows)
	{
		while ($mysql_data = mysql_fetch_array($mysql_result))
		{
			$result[ $mysql_data["label"] ] = $mysql_data["translation"];
		}
	}

	// return the results
	return $result;
	
} // end of language_translate function


/*
	language_translate_string($language, $string)

	Wrapper function for language_translate for transalting a single string
*/
function language_translate_string($language, $label)
{
	$label_array = array($label);

	$result = language_translate($language, $label_array);
	return $result[$label];
}


