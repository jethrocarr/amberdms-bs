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
		get_customer_id_from_code

		Return the ID of the provided customer code
	*/
	function get_customer_id_from_code($code_customer)
	{
		log_debug("customers_manage_soap", "Executing get_customer_from_by_code($code_customer)");

		if (user_permissions_get("customers_view"))
		{
			// sanitise input
			$code_customer = security_script_input_predefined("any", $code_customer);

			if (!$code_customer || $code_customer == "error")
			{
				throw new SoapFault("Sender", "INVALID_INPUT");
			}

			
			// fetch the customer ID
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM customers WHERE code_customer='$code_customer' LIMIT 1";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				$sql_obj->fetch_array();

				return $sql_obj->data[0]["id"];
			}
			else
			{
				throw new SoapFault("Sender", "INVALID_ID");
			}
		}
		else
		{
			throw new SoapFault("Sender", "ACCESS_DENIED");
		}

	} // end of get_customer_id_from_code



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
					$obj_customer->data["address2_zipcode"],
					$obj_customer->data["discount"]);

			return $return;
		}
		else
		{
			throw new SoapFault("Sender", "ACCESS_DENIED");
		}

	} // end of get_customer_details


	/*
		get_customer_tax

		Return list of all taxes and mark whether they are enabled or not for this customer.
	*/

	function get_customer_tax($id)
	{
		log_debug("customers_manage_soap", "Executing get_customer_tax($id)");

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


			// fetch customer status
			$enabled_taxes = NULL;

			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT taxid FROM customers_taxes WHERE customerid='$id'";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				$sql_obj->fetch_array();

				foreach ($sql_obj->data as $data)
				{
					$enabled_taxes[] = $data["taxid"];
				}
			}


			// fetch list of all taxes
			$sql_tax_obj		= New sql_query;
			$sql_tax_obj->string	= "SELECT id, name_tax FROM account_taxes ORDER BY name_tax";
			$sql_tax_obj->execute();

			// package up for sending to the client
			$return = NULL;

			if ($sql_tax_obj->num_rows())
			{
				$sql_tax_obj->fetch_array();

				foreach ($sql_tax_obj->data as $data_tax)
				{
					$return_tmp			= NULL;
					$return_tmp["taxid"]		= $data_tax["id"];
					$return_tmp["name_tax"]		= $data_tax["name_tax"];

					if (in_array($data_tax["id"], $enabled_taxes))
					{
						$return_tmp["status"]	= "on";
					}
					else
					{
						$return_tmp["status"]	= "off";
					}

					$return[] = $return_tmp;
				}
			}

			return $return;
		}
		else
		{
			throw new SoapFault("Sender", "ACCESS_DENIED");
		}

	} // end of get_customer_tax




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
					$address2_zipcode,
					$discount)
	{
		log_debug("customers_manager", "Executing set_customer_details($id, values...)");

		if (user_permissions_get("customers_write"))
		{
			$obj_customer = New customer;

			
			/*
				Load SOAP Data
			*/
			$obj_customer->id				= security_script_input_predefined("int", $id);
			
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
			$obj_customer->data["discount"]			= security_script_input_predefined("float", $discount);


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
		set_customer_tax

		Enables or disables the specified tax for the customer

		Returns
		0	failure
		#	ID of the customer
	*/
	function set_customer_tax($id,
					$taxid,
					$status)
	{
		log_debug("customers_manager", "Executing set_customer_tax($id, values...)");

		if (user_permissions_get("customers_write"))
		{
			$obj_customer = New customer;

			
			/*
				Load SOAP Data
			*/
			$obj_customer->id	= security_script_input_predefined("int", $id);
			$taxid			= security_script_input_predefined("int", $taxid);
			$status			= security_script_input_predefined("any", $status);

			foreach (array_keys($obj_customer->data) as $key)
			{
				if ($obj_customer->data[$key] == "error")
				{
					throw new SoapFault("Sender", "INVALID_INPUT");
				}
			}

			if ($status != "on" && $status != "off")
			{
				throw new SoapFault("Sender", "INVALID_INPUT");
			}



			/*
				Error Handling
			*/

			// verify customer ID
			if (!$obj_customer->verify_id())
			{
				throw new SoapFault("Sender", "INVALID_ID");
			}


			/*
				Perform Changes
			*/

			// fetch customer's current tax status
			$sql_customer_taxes_obj		= New sql_query;
			$sql_customer_taxes_obj->string	= "SELECT taxid FROM customers_taxes WHERE customerid='". $obj_customer->id."'";

			$sql_customer_taxes_obj->execute();

			if ($sql_customer_taxes_obj->num_rows())
			{
				$sql_customer_taxes_obj->fetch_array();

				foreach ($sql_customer_taxes_obj->data as $data_tax)
				{
					$obj_customer->data["tax_". $data_tax["taxid"] ] = "on";

				}
			}

			// change the status of the supplied option
			if ($status == "on")
			{
				$obj_customer->data["tax_". $taxid] = "on";
			}
			else
			{
				$obj_customer->data["tax_". $taxid] = "";
			}

			
			if ($obj_customer->action_update_taxes())
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

	} // end of set_customer_tax




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

			if (!$obj_customer->id || $obj_customer->id == "error")
			{
				throw new SoapFault("Sender", "INVALID_INPUT");
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

