<?php
/*
	include/services/inc_services_usages.php

	Provides functions for managing usage of services, including functions to add usage
	records and functions to fetch total amounts of usage to be used for billing/display
	purposes.
*/




/*
	FUNCTIONS
*/



/*
	CLASSES
*/

class service_usage
{
	var $services_customers_id;			// ID of the service-customer mapping
	var $serviceid;					// ID of the service
	
	var $date_start;				// start of usage period
	var $date_end;					// end of usage period

	var $sql_service_obj;				// service data is loaded in here.

	var $data;					// usage information is saved here.


	/*
		Constructor
	*/
	function service_usage()
	{
		log_debug("service_usage", "Executing service_usage()");

		// define service object
		$this->sql_service_obj = New sql_query;
	}



	/*
		prepare_load_servicedata
		
		Load service information by using the services_customers_id

		Returns
		0		failure
		1		success
	*/
	function prepare_load_servicedata()
	{
		log_debug("service_usage", "Executing prepare_load_servicedata");

		// fetch the serviceid
		$this->serviceid = sql_get_singlevalue("SELECT serviceid as value FROM services_customers WHERE id='". $this->services_customers_id ."' LIMIT 1");

		if (!$this->serviceid)
		{
			log_debug("service_usage", "Error: Unable to fetch serviceid from services_customers");
			return 0;
		}

		// fetch the service information we require
		$this->sql_service_obj->string = "SELECT id, usage_mode, units, typeid FROM services WHERE id='". $this->serviceid ."'";
		$this->sql_service_obj->execute();

		if ($this->sql_service_obj->num_rows())
		{
			$this->sql_service_obj->fetch_array();
			return 1;
		}
		else
		{
			log_debug("service_usage", "Error: Unable to find service with ID of ". $this->serviceid . "");
			return 0;
		}
		
	} // end of prepare_load_servicedata



	/*
		fetch_usagedata

		Fetch the usage for the supplied period and perform any processing required for the usage mode type. Results are
		saved into the $this->data array, with the following structure:

		Data structure:
		
			["usage1"]		Value of usage1 as per usage mode
			["usage2"]		Value of usage2 as per usage mode
			["total"]		Addition of usage1 + usage2

			["total_byunits"]	total is further processed by the service unit to provide a value suitable for billing.


		Examples:
		
			["usage1"]		1024
			["usage2"]		2048
			["total"]		3072

			["total_byunits"]	3		(using MiB unit to return number of MBs)
			

		Note: All math calculations are performed using MySQL, since the SQL engine is capable of handling 64-bit intergers without
		wrapping them on 32bit systems. If PHP was used to perform these calculations on a 32bit CPU, values over 32bits would wrap
		and give incorrect results.

		For further details about 32bit counter wraps, please refer to the Amberdms Billing System design +  developer documentation.


		Returns
		0		failure
		1		success
	*/
	function fetch_usagedata()
	{
		log_debug("service_usage", "Executing fetch_usagedata()");

		/*
			Fetch usage mode
		*/
		$this->data["usage_mode"] = sql_get_singlevalue("SELECT name as value FROM service_usage_modes WHERE id='". $this->sql_service_obj->data[0]["usage_mode"] ."' LIMIT 1");

		if (!$this->data["usage_mode"])
		{
			log_debug("service_usage", "Error: Unable to determine usage mode of service");
			return 0;
		}



		/*
			Fetch usage data

			We need to fetch in different ways, depending on the usage mode.
		*/
		switch ($this->data["usage_mode"])
		{
			case "incrementing":
				/*
					INCREMENTING

					Data during the usage period increments and we bill based on the total amount of data. This is typically
					used for time or data_traffic service types.
				*/
				
				$sql_obj			= New sql_query;
				$sql_obj->string		= "SELECT SUM(usage1) as usage1, SUM(usage2) as usage2 FROM service_usage_records WHERE services_customers_id='". $this->services_customers_id ."' AND date>='". $this->date_start ."' AND date<='". $this->date_end ."'";
				$sql_obj->execute();
				$sql_obj->fetch_array();

				$this->data["usage1"]	= $sql_obj->data[0]["usage1"];
				$this->data["usage2"]	= $sql_obj->data[0]["usage2"];

			break;


			case "peak":
				/*
					PEAK USAGE

					Only bill for the highest amount used during the period. This is suitable for use with services such as online backup
					or storage services charging for the amount of space consumed.
				*/
				
				$sql_obj			= New sql_query;
				$sql_obj->string		= "SELECT MAX(usage1) as usage1, MAX(usage2) as usage2 FROM service_usage_records WHERE services_customers_id='". $this->services_customers_id ."' AND date>='". $this->date_start ."' AND date<='". $this->date_end ."'";
				$sql_obj->execute();
				$sql_obj->fetch_array();

				$this->data["usage1"]	= $sql_obj->data[0]["usage1"];
				$this->data["usage2"]	= $sql_obj->data[0]["usage2"];
				
			break;

			case "average":
				/*
					AVERAGE USAGE

					Only bill for the average amount used during the period. This is suitable for use with services such as online backup
					or storage services charging for the amount of space consumed.
				*/
				
				$sql_obj			= New sql_query;
				$sql_obj->string		= "SELECT AVG(usage1) as usage1, AVG(usage2) as usage2 FROM service_usage_records WHERE services_customers_id='". $this->services_customers_id ."' AND date>='". $this->date_start ."' AND date<='". $this->date_end ."'";
				$sql_obj->execute();
				$sql_obj->fetch_array();

				$this->data["usage1"]	= $sql_obj->data[0]["usage1"];
				$this->data["usage2"]	= $sql_obj->data[0]["usage2"];
				
			break;

			default:
				log_debug("service_usage", "Error: Unknown usage mode provided.");
				return 0;
			break;
			
		} // end of usage mode switch


		// create a total of both usage columns
		$sql_obj			= New sql_query;
		$sql_obj->string		= "SELECT '". $this->data["usage1"] ."' + '". $this->data["usage2"] ."' as totalusage";
		$sql_obj->execute();
		$sql_obj->fetch_array();
	
		$this->data["total"] 	= $sql_obj->data[0]["totalusage"];


		
		/*
			Fetch usage by units

			Not all services do this, but we can tell, if the units is just a number, that it is an ID to the service_units table for us to use.
		*/
		if (preg_match("/^[0-9]*$/", $this->service_obj->data[0]["units"]))
		{
			// fetch the number of raw units to unit ratio
			$this->data["numrawunits"] = sql_get_singlevalue("SELECT numrawunits as value FROM service_units WHERE id='". $this->sql_service_obj->data[0]["units"] ."' LIMIT 1");

			if (!$this->data["numrawunits"])
			{
				log_debug("service_usage", "Error: Unable to fetch number of raw units for the units type");
				return 0;
			}
			
			// calculate
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT '". $this->data["total"] ."' / '". $this->data["numrawunits"] ."' as value";
			$sql_obj->execute();
			$sql_obj->fetch_array();

			$this->data["total_byunits"]	= $sql_obj->data[0]["value"];
		}


		// complete :-)
		return 1;
		
	} // end of fetch_usagedata



} // END OF SERVICE_USAGE CLASS


?>
