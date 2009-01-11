<?php
/*
	SOAP SERVICE -> ACCOUNTS_CHARTS_MANAGE

	access:		accounts_charts_view
			accounts_charts_write

	This service provides APIs for creating, updating and deleting charts/accounts.

	Refer to the Developer API documentation for information on using this service
	as well as sample code.
*/


// include libraries
include("../../include/config.php");
include("../../include/amberphplib/main.php");

// custom includes
include("../../include/accounts/inc_charts.php");



class accounts_charts_manage_soap
{

	/*
		get_chart_type_list

		Return a list of all the avaliable charts
	*/

	function get_chart_type_list()
	{
		log_debug("charts_manage_soap", "Executing get_chart_type_list()");

		if (user_permissions_get("accounts_charts_view"))
		{
			// fetch chart data
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id, value, total_mode FROM account_chart_type";
			$sql_obj->execute();
			$sql_obj->fetch_array();


			// package data into array for passing back to SOAP client
			$return = NULL;
			foreach ($sql_obj->data as $data)
			{
				$return_tmp			= NULL;
				$return_tmp["id"]		= $data["id"];
				$return_tmp["value"]		= $data["value"];
				$return_tmp["total_mode"]	= $data["total_mode"];

				$return[] = $return_tmp;
			}

			return $return;
		}
		else
		{
			throw new SoapFault("Sender", "ACCESS_DENIED");
		}

	} // end of get_chart_type_list



	/*
		get_chart_details

		Fetch all the details for the requested chart
	*/
	function get_chart_details($id)
	{
		log_debug("charts_manage_soap", "Executing get_chart_details($id)");

		if (user_permissions_get("accounts_charts_view"))
		{
			$obj_chart = New chart;


			// sanitise input
			$obj_chart->id = security_script_input_predefined("int", $id);

			if (!$obj_chart->id || $obj_chart->id == "error")
			{
				throw new SoapFault("Sender", "INVALID_INPUT");
			}


			// verify that the ID is valid
			if (!$obj_chart->verify_id())
			{
				throw new SoapFault("Sender", "INVALID_ID");
			}


			// load data from DB for this chart
			if (!$obj_chart->load_data())
			{
				throw new SoapFault("Sender", "UNEXPECTED_ACTION_ERROR");
			}


			// to save SOAP users from having to do another lookup to find out what the account type
			// is, we fetch the type here and pass it as a string.
			if ($obj_chart->data["chart_type"])
			{
				$obj_chart->data["chart_type_label"] = sql_get_singlevalue("SELECT value FROM account_chart_type WHERE id='". $obj_chart->data["chart_type"] ."'");
			}



			// return data
			$return = array($obj_chart->data["code_chart"], 
					$obj_chart->data["description"], 
					$obj_chart->data["chart_type"], 
					$obj_chart->data["chart_type_label"]);

			return $return;
		}
		else
		{
			throw new SoapFault("Sender", "ACCESS_DENIED");
		}

	} // end of get_chart_details




	/*
		set_chart_details

		Creates/Updates an chart record.

		Returns
		0	failure
		#	ID of the chart
	*/
	function set_chart_details($id,
					$code_chart, 
					$description, 
					$chart_type)
	{
		log_debug("accounts_charts_manage", "Executing set_chart_details($id, values...)");

		if (user_permissions_get("accounts_charts_write"))
		{
			$obj_chart = New chart;

			
			/*
				Load SOAP Data
			*/
			$obj_chart->id				= security_script_input_predefined("int", $id);
			
			$obj_chart->data["code_chart"]		= security_script_input_predefined("int", $code_chart);
			$obj_chart->data["description"]		= security_script_input_predefined("any", $description);
			$obj_chart->data["chart_type"]		= security_script_input_predefined("int", $chart_type);
			
			foreach (array_keys($obj_chart->data) as $key)
			{
				if ($obj_chart->data[$key] == "error")
				{
					throw new SoapFault("Sender", "INVALID_INPUT");
				}
			}



			/*
				Error Handling
			*/

			// verify chart ID (if editing an existing chart)
			if ($obj_chart->id)
			{
				if (!$obj_chart->verify_id())
				{
					throw new SoapFault("Sender", "INVALID_ID");
				}
			}

			// make sure we don't choose a chart code that has already been taken
			if (!$obj_chart->verify_code_chart())
			{
				throw new SoapFault("Sender", "DUPLICATE_CODE_CHART");
			}


			/*
				Perform Changes
			*/

			if ($obj_chart->action_update_details())
			{
				return $obj_chart->id;
			}
			else
			{
				throw new SoapFault("Sender", "UNEXPECTED_ACTION_ERROR");
			}
 		}
		else
		{
			throw new SoapFault("Sender", "ACCESS DENIED");
		}

	} // end of set_chart_details




	/*
		delete_chart

		Deletes an chart, provided that the chart is not locked.

		Returns
		0	failure
		1	success
	*/
	function delete_chart($id)
	{
		log_debug("charts", "Executing delete_chart_details($id, values...)");

		if (user_permissions_get("accounts_charts_write"))
		{
			$obj_chart = New chart;

			
			/*
				Load SOAP Data
			*/
			$obj_chart->id = security_script_input_predefined("int", $id);

			if (!$obj_chart->id || $obj_chart->id == "error")
			{
				throw new SoapFault("Sender", "INVALID_INPUT");
			}



			/*
				Error Handling
			*/

			// verify chart ID
			if (!$obj_chart->verify_id())
			{
				throw new SoapFault("Sender", "INVALID_ID");
			}


			// check that the chart can be safely deleted
			if ($obj_chart->check_delete_lock())
			{
				throw new SoapFault("Sender", "LOCKED");
			}



			/*
				Perform Changes
			*/
			if ($obj_chart->action_delete())
			{
				return 1;
			}
			else
			{
				throw new SoapFault("Sender", "UNEXPECTED_ACTION_ERROR");
			}
 		}
		else
		{
			throw new SoapFault("Sender", "ACCESS DENIED");
		}

	} // end of delete_chart



} // end of charts_manage_soap class



// define server
$server = new SoapServer("charts_manage.wsdl");
$server->setClass("accounts_charts_manage_soap");
$server->handle();



?>
