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
		$account	= @security_script_input_predefined("any", $account);
		$username	= @security_script_input_predefined("any", $username);
		$password	= @security_script_input_predefined("any", $password);


		// $account is only used by Amberdms's hosted billing system - for single instance configurations
		// it is unused, and simply exists to ensure a standard API across all product versions

		$result = user_login($account, $username, $password);

		switch ($result)
		{
			case "-5":
				throw new SoapFault("Sender", "DATABASE_VERSION_MISMATCH");
			break;

			case "-4":
				throw new SoapFault("Sender", "USER_DISABLED");
			break;

			case "-3":
				throw new SoapFault("Sender", "INVALID_AUTHDETAILS");
			break;

			case "-2":
				throw new SoapFault("Sender", "USER_DISABLED");
			break;

			case "-1":
				throw new SoapFault("Sender", "BLACKLISTED");
			break;

			case "0":
			default:
				throw new SoapFault("Sender", "INVALID_AUTHDETAILS");
			break;

			case "1":
				// successful authentication
				$sid = session_name() ."=". session_id();
				return $sid;
			break;
		}
	}
}


// define server
$server = new SoapServer("authenticate.wsdl");
$server->setClass("authenticate");
$server->handle();


?>
