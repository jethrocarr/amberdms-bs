<?php
/*
	SOAP SERVICE -> ACCOUNTS_TAXES_MANAGE

	access:		accounts_taxes_view
			accounts_taxes_write

	This service provides APIs for creating, updating and deleting taxes.

	Refer to the Developer API documentation for information on using this service
	as well as sample code.
*/


// include libraries
include("../../include/config.php");
include("../../include/amberphplib/main.php");

// custom includes
include("../../include/accounts/inc_taxes.php");



class accounts_taxes_manage_soap
{

	/*
		get_tax_details

		Fetch all the details for the requested tax
	*/
	function get_tax_details($id)
	{
		log_debug("taxes_manage_soap", "Executing get_tax_details($id)");

		if (user_permissions_get("accounts_taxes_view"))
		{
			$obj_tax = New tax;


			// sanitise input
			$obj_tax->id = security_script_input_predefined("int", $id);

			if (!$obj_tax->id || $obj_tax->id == "error")
			{
				throw new SoapFault("Sender", "INVALID_INPUT");
			}


			// verify that the ID is valid
			if (!$obj_tax->verify_id())
			{
				throw new SoapFault("Sender", "INVALID_ID");
			}


			// load data from DB for this tax
			if (!$obj_tax->load_data())
			{
				throw new SoapFault("Sender", "UNEXPECTED_ACTION_ERROR");
			}


			// to save SOAP users from having to do another lookup to find out what the chart name is, 
			// we fetch the details here
			if ($obj_tax->data["chartid"])
			{
				$obj_tax->data["chartid_label"]	= sql_get_singlevalue("SELECT CONCAT_WS('--', code_chart, description) as value FROM account_charts WHERE id='". $obj_tax->data["chartid"] ."'");
			}


			// return data
			$return = array($obj_tax->data["name_tax"], 
					$obj_tax->data["taxrate"], 
					$obj_tax->data["chartid"], 
					$obj_tax->data["chartid_label"], 
					$obj_tax->data["taxnumber"], 
					$obj_tax->data["description"]);

			return $return;
		}
		else
		{
			throw new SoapFault("Sender", "ACCESS_DENIED");
		}

	} // end of get_tax_details



	/*
		set_tax_details

		Creates/Updates an tax record.

		Returns
		0	failure
		#	ID of the tax
	*/
	function set_tax_details($id,
					$name_tax,
					$taxrate,
					$chartid,
					$taxnumber,
					$description)
	{
		log_debug("accounts_taxes_manage", "Executing set_tax_details($id, values...)");

		if (user_permissions_get("accounts_taxes_write"))
		{
			$obj_tax = New tax;

			
			/*
				Load SOAP Data
			*/
			$obj_tax->id				= security_script_input_predefined("int", $id);
			
			$obj_tax->data["name_tax"]		= security_script_input_predefined("any", $name_tax);
			$obj_tax->data["taxrate"]		= security_script_input_predefined("any", $taxrate);
			$obj_tax->data["chartid"]		= security_script_input_predefined("int", $chartid);
			$obj_tax->data["taxnumber"]		= security_script_input_predefined("any", $taxnumber);
			$obj_tax->data["description"]		= security_script_input_predefined("any", $description);
			
			foreach (array_keys($obj_tax->data) as $key)
			{
				if ($obj_tax->data[$key] == "error")
				{
					throw new SoapFault("Sender", "INVALID_INPUT");
				}
			}



			/*
				Error Handling
			*/

			// verify tax ID (if editing an existing tax)
			if ($obj_tax->id)
			{
				if (!$obj_tax->verify_id())
				{
					throw new SoapFault("Sender", "INVALID_ID");
				}
			}

			// make sure we choose a unique tax name
			if (!$obj_tax->verify_name_tax())
			{
				throw new SoapFault("Sender", "DUPLICATE_NAME_TAX");
			}

			// make sure that the chartid is valid
			if (!$obj_tax->verify_valid_chart())
			{
				throw new SoapFault("Sender", "INVALID_CHARTID");
			}


			/*
				Perform Changes
			*/

			if ($obj_tax->action_update())
			{
				return $obj_tax->id;
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

	} // end of set_tax_details



	/*
		delete_tax

		Deletes a tax, provided that the tax is not locked.

		Returns
		0	failure
		1	success
	*/
	function delete_tax($id)
	{
		log_debug("taxes", "Executing delete_tax_details($id, values...)");

		if (user_permissions_get("accounts_taxes_write"))
		{
			$obj_tax = New tax;

			
			/*
				Load SOAP Data
			*/
			$obj_tax->id = security_script_input_predefined("int", $id);

			if (!$obj_tax->id || $obj_tax->id == "error")
			{
				throw new SoapFault("Sender", "INVALID_INPUT");
			}



			/*
				Error Handling
			*/

			// verify tax ID
			if (!$obj_tax->verify_id())
			{
				throw new SoapFault("Sender", "INVALID_ID");
			}


			// check that the tax can be safely deleted
			if ($obj_tax->check_delete_lock())
			{
				throw new SoapFault("Sender", "LOCKED");
			}



			/*
				Perform Changes
			*/
			if ($obj_tax->action_delete())
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

	} // end of delete_tax



} // end of taxes_manage_soap class



// define server
$server = new SoapServer("taxes_manage.wsdl");
$server->setClass("accounts_taxes_manage_soap");
$server->handle();



?>
