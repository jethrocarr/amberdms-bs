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

	The translations are stored in a SQL database. This means that every time
	we want to perform a translation, we would have to execute a SQL query!

	A lot of the translations are often for the same thing on one page - so to
	reduce the number of queries, this function will cache it's in a $_SESSION
	variable.

	This data will remain caches until the PHP program finishes - so the cache will
	not survive page loads, but will cache for all lookups at each load.
	
*/
function language_translate($language, $label_array)
{
	log_debug("language", "Executing language_translate($language, label_array)");
	
	if (!$language || !$label_array)
		print "Warning: Invalid input recieved for function language_translate<br>";
	

	// store labels to fetch from DB in here
	$label_fetch_array = array();

	// run through the labels - see what ones we have cached, and what ones we need to query
	foreach ($label_array as $label)
	{
		if ($GLOBALS["cache"]["lang"][$label])
		{
			$result[$label] = $GLOBALS["cache"]["lang"][$label];
		}
		else
		{
			$label_fetch_array[] = $label;
		}
	}


	if ($label_fetch_array)
	{
		// there are some new labels for us to translate
		// we get the information from the database and then save it to the cache
		// to prevent future lookups.

		// prepare the SQL
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT label, translation FROM `language` WHERE language='". $language ."' AND (";

		// add all the labels to the SQL query.
		$count = 0;
		foreach ($label_fetch_array as $label)
		{
			$count++;
				
			if ($count < count($label_fetch_array))
			{
				$sql_obj->string .= "label='$label' OR ";
			}
			else
			{
				$sql_obj->string .= "label='$label'";
			}
		}

		$sql_obj->string .= ")";


		// query
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$sql_obj->fetch_array();
			foreach ($sql_obj->data as $data)
			{
				$result[ $data["label"] ]			= $data["translation"];
				$GLOBALS["cache"]["lang"][ $data["label"] ]	= $data["translation"];
			}
		}
		
	} // end if lookup required


	// if no value was returned for the particular label we looked up, it means
	// that no translation exists.
	//
	// In this case, just return the label as the translation and also add it to
	// the cache to prevent extra lookups.
	foreach ($label_array as $label)
	{
		if (!$result[$label])
		{
			$result[$label]				= $label;
			$GLOBALS["cache"]["lang"][$label]	= $label;
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
	log_debug("language", "Executing language_translate_string($language, $label)");

	$label_array = array($label);

	$result = language_translate($language, $label_array);
	return $result[$label];
}


