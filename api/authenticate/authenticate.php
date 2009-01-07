<?php
/*
	SOAP SERVICE -> AUTHENTICATE

	This SOAP service provides authentication facilities to SOAP applications
	and returns them a PHP session ID.
	
	This session ID can then be used when accessing any other SOAP services
	belonging to this application to gain access.

	Refer to the Developer API documentation for information on using this service
	as well as sample code.
*/

// include libraries
include("../../include/config.php");
include("../../include/amberphplib/main.php");



class authenticate
{
	function login($account, $username, $password)
	{
		log_debug("authenticate", "Executing login($account, $username, $password)");

		// sanitise input
		$account	= security_script_input_predefined("any", $account);
		$username	= security_script_input_predefined("any", $username);
		$password	= security_script_input_predefined("any", $password);


		// $account is only used by Amberdms's hosted billing system - for normal setups
		// it is unused, and simply exists to ensure a standard API across all product versions

		if ($result = user_login($username, $password))
		{
			// authenticated - return the session string
			$sid = session_name() ."=". session_id();
			return $sid;
		}
		else
		{
			// failed authentication - use SoapFault to gracefully return a human-readable errror
			switch ($result)
			{
				case "-2":
					throw new SoapFault("Sender", "The requested user has been disabled - contact the system administrator to get the user re-enabled");
				break;

				case "-1":
					throw new SoapFault("Sender", "User account has been blacklisted for excessive incorrect login attempts from your IP");
				break;

				case "0":
					throw new SoapFault("Sender", "Invalid username/password");
				break;
			}
		}
	}
}


// define server
$server = new SoapServer("authenticate.wsdl");
$server->setClass("authenticate");
$server->handle();


?>
