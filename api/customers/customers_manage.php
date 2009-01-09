<?php
/*
	SOAP SERVICE -> CUSTOMERS_MANAGE

	access:		customers_view
			customers_write


	This service provides APIs for creating, updating and deleting customer accounts.

	Refer to the Developer API documentation for information on using this service
	as well as sample code.
*/


// include libraries
include("../../include/config.php");
include("../../include/amberphplib/main.php");

// custom includes
include("../../include/customers/inc_customers.php");



class customers_manage_soap
{
	/*
		get_customer_details

		Fetch all the details for the requested customer
	*/
	function get_customer_details($id)
	{
		log_debug("customers_manage_soap", "Executing get_customer_details($id)");

		if (user_permissions_get("customers_view"))
		{
			$obj_customer = New customer;


			// sanitise input
			$obj_customer->id = security_script_input_predefined("int", $id);

			if (!$obj_customer->id || $obj_customer->id == "error")
			{
				throw new SoapFault("Sender", "INVALID_INPUT");
			}


			// verify that the ID is valid
			if (!$obj_customer->verify_id())
			{
				throw new SoapFault("Sender", "INVALID_ID");
			}


			// load data from DB for this customer
			if (!$obj_customer->load_data())
			{
				throw new SoapFault("Sender", "UNEXPECTED_ACTION_ERROR");
			}


			// to save SOAP users from having to do another lookup to find out what the name
			// of the tax_default is, we do a lookup here.
			if ($obj_customer->data["tax_default"])
			{
				$obj_customer->data["tax_default_label"] = sql_get_singlevalue("SELECT name_tax as value FROM account_taxes WHERE id='". $obj_customer->data["tax_default"] ."'");
			}



			// return data
			$return = array($obj_customer->data["code_customer"], 
					$obj_customer->data["name_customer"], 
					$obj_customer->data["name_contact"], 
					$obj_customer->data["contact_email"], 
					$obj_customer->data["contact_phone"],
					$obj_customer->data["contact_fax"], 
					$obj_customer->data["date_start"], 
					$obj_customer->data["date_end"],
					$obj_customer->data["tax_number"],
					$obj_customer->data["tax_default"],
					$obj_customer->data["tax_default_label"],
					$obj_customer->data["address1_street"],
					$obj_customer->data["address1_city"],
					$obj_customer->data["address1_state"],
					$obj_customer->data["address1_country"],
					$obj_customer->data["address1_zipcode"],
					$obj_customer->data["address2_street"],
					$obj_customer->data["address2_city"],
					$obj_customer->data["address2_state"],
					$obj_customer->data["address2_country"],
					$obj_customer->data["address2_zipcode"]);

			return $return;
		}
		else
		{
			throw new SoapFault("Sender", "ACCESS_DENIED");
		}

	} // end of get_customer_details




	/*
		set_customer_details

		Creates/Updates an customer record.

		Returns
		0	failure
		#	ID of the customer
	*/
	function set_customer_details($id,
					$code_customer, 
					$name_customer, 
					$name_contact, 
					$contact_email, 
					$contact_phone, 
					$contact_fax, 
					$date_start, 
					$date_end, 
					$tax_number, 
					$tax_default, 
					$address1_street, 
					$address1_city,
					$address1_state,
					$address1_country,
					$address1_zipcode,
					$address2_street, 
					$address2_city,
					$address2_state,
					$address2_country,
					$address2_zipcode)
	{
		log_debug("customers_manager", "Executing set_customer_details($id, values...)");

		if (user_permissions_get("customers_write"))
		{
			$obj_customer = New customer;

			
			/*
				Load SOAP Data
			*/
			$obj_customer->id				= security_script_input_predefined("int", $id_customer);
			
			$obj_customer->data["code_customer"]		= security_script_input_predefined("any", $code_customer);
			$obj_customer->data["name_customer"]		= security_script_input_predefined("any", $name_customer);
			$obj_customer->data["name_contact"]		= security_script_input_predefined("any", $name_contact);
			
			$obj_customer->data["contact_phone"]		= security_script_input_predefined("any", $contact_phone);
			$obj_customer->data["contact_fax"]		= security_script_input_predefined("any", $contact_fax);
			$obj_customer->data["contact_email"]		= security_script_input_predefined("email", $contact_email);
			$obj_customer->data["date_start"]		= security_script_input_predefined("date", $date_start);
			$obj_customer->data["date_end"]			= security_script_input_predefined("date", $date_end);

			$obj_customer->data["address1_street"]		= security_script_input_predefined("any", $address1_street);
			$obj_customer->data["address1_city"]		= security_script_input_predefined("any", $address1_city);
			$obj_customer->data["address1_state"]		= security_script_input_predefined("any", $address1_state);
			$obj_customer->data["address1_country"]		= security_script_input_predefined("any", $address1_country);
			$obj_customer->data["address1_zipcode"]		= security_script_input_predefined("any", $address1_zipcode);
			
			$obj_customer->data["address2_street"]		= security_script_input_predefined("any", $address2_street);
			$obj_customer->data["address2_city"]		= security_script_input_predefined("any", $address2_city);
			$obj_customer->data["address2_state"]		= security_script_input_predefined("any", $address2_state);
			$obj_customer->data["address2_country"]		= security_script_input_predefined("any", $address2_country);
			$obj_customer->data["address2_zipcode"]		= security_script_input_predefined("any", $address2_zipcode);
			
			$obj_customer->data["tax_number"]		= security_script_input_predefined("any", $tax_number);
			$obj_customer->data["tax_default"]		= security_script_input_predefined("int", $tax_default);


			foreach (array_keys($obj_customer->data) as $key)
			{
				if ($obj_customer->data[$key] == "error")
				{
					throw new SoapFault("Sender", "INVALID_INPUT");
				}
			}



			/*
				Error Handling
			*/

			// verify customer ID (if editing an existing customer)
			if ($obj_customer->id)
			{
				if (!$obj_customer->verify_id())
				{
					throw new SoapFault("Sender", "INVALID_ID");
				}
			}

			// make sure we don't choose a customer name that has already been taken
			if (!$obj_customer->verify_name_customer())
			{
				throw new SoapFault("Sender", "DUPLICATE_NAME_CUSTOMER");
			}

			// make sure we don't choose a customer code that has already been taken
			if (!$obj_customer->verify_code_customer())
			{
				throw new SoapFault("Sender", "DUPLICATE_CODE_CUSTOMER");
			}

			// prevent a customer with active services from having an date_end value set
			if (!$obj_customer->verify_date_end())
			{
				throw new SoapFault("Sender", "HAS_ACTIVE_SERVICES");
			}




			/*
				Perform Changes
			*/
			
			if ($obj_customer->action_update())
			{
				return $obj_customer->id;
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

	} // end of set_customer_details




	/*
		delete_customer

		Deletes an customer, provided that the customer is not locked.

		Returns
		0	failure
		1	success
	*/
	function delete_customer($id)
	{
		log_debug("customers", "Executing delete_customer_details($id, values...)");

		if (user_permissions_get("customers_write"))
		{
			$obj_customer = New customer;

			
			/*
				Load SOAP Data
			*/
			$obj_customer->id = security_script_input_predefined("int", $id);

			foreach (array_keys($data) as $key)
			{
				if ($data[$key] == "error")
				{
					throw new SoapFault("Sender", "INVALID_INPUT");
				}
			}



			/*
				Error Handling
			*/

			// verify customer ID
			if (!$obj_customer->verify_id())
			{
				throw new SoapFault("Sender", "INVALID_ID");
			}


			// check that the customer can be safely deleted
			if ($obj_customer->check_delete_lock())
			{
				throw new SoapFault("Sender", "LOCKED");
			}



			/*
				Perform Changes
			*/
			if ($obj_customer->action_delete())
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

	} // end of delete_customer



} // end of customers_manage_soap class



// define server
$server = new SoapServer("customers_manage.wsdl");
$server->setClass("customers_manage_soap");
$server->handle();



?>

