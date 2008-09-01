<?php
//
// include/security.php
//
// provides security functions, such as checking input from forms, and validating URL's.
//
// FUNCTIONS:
//
// security_localphp ( url )
//	checks that the provided URL is a LOCAL PHP SCRIPT. Prevents include exploits.
//
// security_form_input ( expression, valuename, numchars, errormsg )
//	reads in post data named "valuename" and does security checking (based on
//	supplied expression), and makes sure the field is as long as or
//	longer than "numchars". In event of an error, it displays errormsg.
//
// security_script_input ( expression, value )
//	checks data that gets provided to a script (eg: returned error messages,
//	get commands, etc). If data passes, it gets returned. If it doesn't NULL
//	is returned, and the value is set to "error".
//
// security_script_error_input ( expression, value )
//	processes error input that gets returned to a script.
//
//

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


function security_form_input($expression, $valuename, $numchars, $errormsg)
{
	// get post data
	$input = $_POST[$valuename];


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
			$_SESSION["error"][$valuename] = $input;
			return $input;
		}
		else
		{
			$_SESSION["error"]["message"] .= "$errormsg<br>";
			$_SESSION["error"]["". $valuename . "-error"] = 1;
			$_SESSION["error"][$valuename] = $input;
		}
	}
	else
	{
		$_SESSION["error"]["message"] .= "$errormsg<br>";
		$_SESSION["error"]["". $valuename . "-error"] = 1;
		$_SESSION["error"][$valuename] = $input;
	}

	return 0;
}

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

function security_script_error_input($expression, $valuename)
{
	// if the input matches the regex, all is good, otherwise set to "error".
	if (preg_match($expression, $_SESSION["error"][$valuename]))
	{
		$_SESSION["error"][$valuename] = stripslashes($_SESSION["error"][$valuename]);
	}
	else
	{
		$_SESSION["error"][$valuename] = "error";
	}		
}


?>
