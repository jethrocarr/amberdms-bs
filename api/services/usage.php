<?php
/*
	SOAP SERVICE -> SERVICES_USAGE

	This service provides APIs for uploading and querying usage records for services
	and customer usage histories.

	Refer to the Developer API documentation for information on using this service
	as well as sample code.
*/

// include libraries
include("../../include/config.php");
include("../../include/amberphplib/main.php");


class services_usage
{
//	function get_id_service_customer()
//	{
//	}

	/*
		set_usage_record

		Adds a usage information to the services_usage_records database for the provided
		customer + date.

		Note that if a record already exists for this date, a new record will still be added,
		causing the usage to increment.

		Values
		collector		Name of the collector device/app. Does not affect billing.
		id_service_customer	ID of the service/customer
		date			Date in YYYY-MM-DD format.
		usage1			usage integer #1
		usage2			usage integer #2 (optional)

	*/
	function set_usage_record($collector, $id_service_customer, $date, $usage1, $usage2 = NULL)
	{
		log_debug("services_usage", "Executing set_usage_record");

		if (user_permissions_get("services_write_usage"))
		{
			// sanitise input
			$data["collector"]		= @security_script_input_predefined("any", $collector);
			$data["id_service_customer"]	= @security_script_input_predefined("int", $id_service_customer);
			$data["date"]			= @security_script_input_predefined("date", $date);
			$data["usage1"]			= @security_script_input_predefined("int", $usage1);
			$data["usage2"]			= @security_script_input_predefined("int", $usage2);

			foreach (array_keys($data) as $key)
			{
				if ($data[$key] == "error")
				{
					throw new SoapFault("Sender", "INVALID_INPUT");
				}
			}

			

			/*
				Verify that id_service_customer exists - this may seem unnessacary, but should be done
				to prevent data being inserted to IDs that don't yet belong - but may do in future.
				
				Would be nasty to have a lot of data sitting in the table waiting for a new customer to
				appear whom the ID matches too.

				Of course, this check does nothing to prevent data for one customer being accidently filed
				against another customer due to an incorrect ID.
			*/

			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM services_customers WHERE id='". $data["id_service_customer"] ."' LIMIT 1";
			$sql_obj->execute();

			if (!$sql_obj->num_rows())
			{
				throw new SoapFault("Sender", "INVALID_SERVICES_CUSTOMERS_ID");
			}

			unset($sql_obj);



			// add new row to DB
			$sql_obj		= New sql_query;
			$sql_obj->string	= "INSERT INTO service_usage_records ("
							."id_service_customer, "
							."date, "
							."usage1, "
							."usage2"
							.") VALUES ("
							."'". $data["id_service_customer"] ."', "
							."'". $data["date"] ."', "
							."'". $data["usage1"] ."', "
							."'". $data["usage2"] ."'"
							.")";
			if (!$sql_obj->execute())
			{
				throw new SoapFault("Sender", "UNEXPECTED_DB_ERROR");
			}

			return 1;
		}
		else
		{
			throw new SoapFault("Sender", "ACCESS_DENIED");
		}
	}
}


// define server
$server = new SoapServer("usage.wsdl");
$server->setClass("services_usage");
//$server->setPersistence(SOAP_PERSISTENCE_SESSION);
$server->handle();



?>

