<?php
/*
	security.php

	Provides a number of core security functions for tasks such as verification
	of input.
*/



/*
	security_localphp ($url)

	Verifies that the provided URL is for a local PHP script, to prevent exploits
	by an attacker including a remote file, or including another file on the local
	machine in order to read it's contents.

	Success: return 1
	Failure: return 0

*/
function security_localphp($url)
{
	// does the url start with a slash? (/)
	if (ereg("^/", $url))           { return 0; }

	// does the url start with a ../?
	if (ereg("^\.\./", $url))         { return 0; }
     
	// does the url (at any point) contain "://" (for ftp://, http://, etc)
	if (ereg("://", $url))          { return 0; }

	// make sure the file is a php file!
	if (!ereg(".php$", $url))       { return 0; }

	// everything was cool
	return 1;
}




/*
	security_form_input ( $expression, $valuename, $numchars, $errormsg )

	Verifies input from $_POST[$valuename] using the regex provided
	as well as checking the length of the variable.
	
	This function has 2 important roles:
	* Preventing SQL or HTML injection of page content
	* Check user input from the form to make sure it's valid - eg: email addresses, dates, etc.

	Success:	Sets the session variable for form errors.
			Returns the value

	Failure:	Sets the session variable for form errors.
			Flags the value as being an incorrect one.
			Appends the errormessage to the errormessage value
			Returns the value.
*/
function security_form_input($expression, $valuename, $numchars, $errormsg)
{
	// get post data
	$input = $_POST[$valuename];

	// if there is no errormsg supplied, set default
	if ($errormsg == "")
	{
		$translation	= language_translate_string($_SESSION["user"]["lang"], $valuename);
		$errormsg	= "Invalid $translation supplied, please correct.<br>";
	}
	

	// strip any HTML tags
	$input = strip_tags($input);

        // check if magic quotes is on or off and process the input correctly.
        //
        // this prevents SQL injections, by backslashing -- " ' ` \ -- etc.
        //
	if (get_magic_quotes_gpc() == 0)
	{
		$input = addslashes($input);
	}


	if (strlen($input) >= $numchars)
	{
		// make sure input is valid, and process accordingly.
		if (preg_match($expression, $input) || $input == "")
		{
			// valid input
			$_SESSION["error"][$valuename] = $input;
			return $input;
		}
		else
		{
			// invalid input - does not match regex
			$_SESSION["error"]["message"][] = "$errormsg";
			$_SESSION["error"]["". $valuename . "-error"] = 1;
			$_SESSION["error"][$valuename] = $input;
		}
	}
	else
	{
		// invalid input - input not long enough/no input
		$_SESSION["error"]["message"][] = "$errormsg";
		$_SESSION["error"]["". $valuename . "-error"] = 1;
		$_SESSION["error"][$valuename] = $input;
	}

	return 0;
}

/*
	security_form_input_predefined ($type, $valuename, $numchar, $errormsg)
	
	Wrapper function for the security_form_input function with various
	pre-defined checks.

	"type" options:
	* any		Allow any input (note: HTML tags will still be stripped)
	* date		date - TODO: write this one
	* email		Standard email address
	* int		Standard integer
	* ipv4		XXX.XXX.XXX.XXX IPv4 syntax

	For further details, refer to the commentsfor the security_form_input function.
*/
function security_form_input_predefined ($type, $valuename, $numchar, $errormsg)
{
	$expression = NULL;
	
	switch ($type)
	{
		case "any":
			$expression = "/^[\S\s]*$/";
		break;

		case "date":
			// dates are a special field, since they have to be passed
			// from the form as 3 different inputs, but we want to re-assemble them
			// into a single input and make it into a timestamp

			$date_dd	= intval($_POST[$valuename."_dd"]);
			$date_mm	= intval($_POST[$valuename."_mm"]);
			$date_yyyy	= intval($_POST[$valuename."_yyyy"]);

			// make sure a date has been provided
			if ($numchar)
			{
				if ($date_dd < 1 || $date_dd > 31)
					$errormsg_tmp = "Invalid date input";

				if ($date_mm < 1 || $date_mm > 12)
					$errormsg_tmp = "Invalid date input";
			
				if ($date_yyyy < 1600 || $date_yyyy > 2999)
					$errormsg_tmp = "Invalid date input";
			}
			else
			{
				// the date is not a required field, but we need to make sure any input is valid
				if ($date_dd > 31)
					$errormsg_tmp = "Invalid date input";
					
				if ($date_mm > 12)
					$errormsg_tmp = "Invalid date input";

				if ($date_yyyy > 2999)
					$errormsg_tmp = "Invalid date input";
			}

			// convert to timestamp
			// all dates are handled in the form of timestamps
			if ($date_dd == 0 && $date_mm == 0 && $date_yyyy == 0)
			{
				$timestamp = 0;
			}
			else
			{
				$timestamp = mktime(0,0,0,$_POST[$valuename."_mm"],$_POST[$valuename."_dd"],$_POST[$valuename."_yyyy"]);
			}
			
			// flag any errors
			if ($errormsg_tmp)
			{
				$_SESSION["error"]["message"][] = $errormsg_tmp;
				$_SESSION["error"]["". $valuename . "-error"] = 1;
				$_SESSION["error"][$valuename] = $timestamp;
			}

			return $timestamp;
			
		break;

		case "int":
			$expression = "/^[0-9]*$/";
		break;
		
		case "email":
			$expression = "/^([A-Za-z0-9._-])+\@(([A-Za-z0-9-])+\.)+([A-Za-z0-9])+$/";
		break;

		case "ipv4":
			$expression = "/^(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:[.](?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3}$/";
		break;

		default:
			print "Warning: No such security check for type $type<br>";
			$expression = "/^[\S\s]*$/";
		break;

	}

	return security_form_input($expression, $valuename, $numchar, $errormsg);
}


/*
	security_script_input ($expression, $value)

	Checks data that gets provided to a script (eg: returned error messages,
	get commands, etc). If data passes, it gets returned. If it doesn't NULL
	is returned, and the value is set to "error".
	
	Success: Returns the value.
	Failure: Returns "error".
*/
function security_script_input($expression, $value)
{
	// if the input matches the regex, all is good, otherwise set to "error".
	if (preg_match($expression, $value))
	{
	        // check if magic quotes is on or off and process the input correctly.
	        //
	        // this prevents SQL injections, by backslashing -- " ' ` \ -- etc.
	        //
		if (get_magic_quotes_gpc() == 0)
		{
			$value = addslashes($value);
		}

		return $value;
	}
	else
	{
		return "error";
	}		
}


?>
