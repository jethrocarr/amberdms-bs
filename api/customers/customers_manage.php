<?php
/*
	SOAP SERVICE -> CUSTOMERS_MANAGE

	access:		customers_view
			customers_write


	This service provides APIs for creating, updating and deleting customer
	accounts along with functions for portal customer authentication.

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
			$code_customer = @security_script_input_predefined("any", $code_customer);

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
			$obj_customer->id = @security_script_input_predefined("int", $id);

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
	

			// load customer contact information 
			/*
				TODO: API Upgrade Required

				Since the API spec is limited in order to retain backwards compatibility, we currently only return the information
				for the accounts contact and ignore the others.
			
				At a future stage, we need to extend the API specification to handle the new flexible contact capabilities.

			*/
			if (!$obj_customer->load_data_contacts())
			{
				throw new SoapFault("Sender", "UNEXPECTED_ACTION_ERROR");
			}
			else
			{
				// fetch the contact name
				$obj_customer->data["name_contact"] = $obj_customer->data["contacts"][0]["contact"];

				// fetch first phone, email and fax records as the primaries
				if (!empty($obj_customer->data["contacts"][0]["records"]))
				{
					foreach ($obj_customer->data["contacts"][0]["records"] as $record)
					{
						if (empty($obj_customer->data["contact_email"]))
						{
							if ($record["type"] == "email")
							{
								$obj_customer->data["contact_email"] = $record["detail"];
							}
						}

						if (empty($obj_customer->data["contact_phone"]))
						{
							if ($record["type"] == "phone")
							{
								$obj_customer->data["contact_phone"] = $record["detail"];
							}
						}

						if (empty($obj_customer->data["contact_fax"]))
						{
							if ($record["type"] == "fax")
							{
								$obj_customer->data["contact_fax"] = $record["detail"];
							}
						}


					}
				}
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
			$obj_customer->id = @security_script_input_predefined("int", $id);

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
			$obj_customer->id				= @security_script_input_predefined("int", $id);
			
			$obj_customer->data["code_customer"]		= @security_script_input_predefined("any", $code_customer);
			$obj_customer->data["name_customer"]		= @security_script_input_predefined("any", $name_customer);
			$obj_customer->data["name_contact"]		= @security_script_input_predefined("any", $name_contact);
			
			$obj_customer->data["contact_phone"]		= @security_script_input_predefined("any", $contact_phone);
			$obj_customer->data["contact_fax"]		= @security_script_input_predefined("any", $contact_fax);
			$obj_customer->data["contact_email"]		= @security_script_input_predefined("email", $contact_email);
			$obj_customer->data["date_start"]		= @security_script_input_predefined("date", $date_start);
			$obj_customer->data["date_end"]			= @security_script_input_predefined("date", $date_end);

			$obj_customer->data["address1_street"]		= @security_script_input_predefined("any", $address1_street);
			$obj_customer->data["address1_city"]		= @security_script_input_predefined("any", $address1_city);
			$obj_customer->data["address1_state"]		= @security_script_input_predefined("any", $address1_state);
			$obj_customer->data["address1_country"]		= @security_script_input_predefined("any", $address1_country);
			$obj_customer->data["address1_zipcode"]		= @security_script_input_predefined("any", $address1_zipcode);
			
			$obj_customer->data["address2_street"]		= @security_script_input_predefined("any", $address2_street);
			$obj_customer->data["address2_city"]		= @security_script_input_predefined("any", $address2_city);
			$obj_customer->data["address2_state"]		= @security_script_input_predefined("any", $address2_state);
			$obj_customer->data["address2_country"]		= @security_script_input_predefined("any", $address2_country);
			$obj_customer->data["address2_zipcode"]		= @security_script_input_predefined("any", $address2_zipcode);
			
			$obj_customer->data["tax_number"]		= @security_script_input_predefined("any", $tax_number);
			$obj_customer->data["tax_default"]		= @security_script_input_predefined("int", $tax_default);
			$obj_customer->data["discount"]			= @security_script_input_predefined("float", $discount);


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
				Customer Contacts Handling

				TODO: API Upgrade Required

				Since the API spec is limited in order to retain backwards compatibility, we currently only get provided with the
				primary contact details for the "account" contact entry.

				This requires some cleverness to adjust the data structure to the new internal associative array, before calling update.
			
				At a future stage, we need to extend the API specification to handle the new flexible contact capabilities.

			*/

			// ensure a default contact name is set
			if (empty($obj_customer->data["name_contact"]))
			{
				$obj_customer->data["name_contact"] = "Accounts";
			}

			if ($obj_customer->id)
			{
				// existing customer - we need to load the contacts and then adjust the values in the
				// primary records with the values provided by the API.

				if (!$obj_customer->load_data_contacts())
				{
					throw new SoapFault("Sender", "UNEXPECTED_ACTION_ERROR");
				}

				// overwrite the accounts primary values
				if (!empty($obj_customer->data["contacts"][0]["records"]))
				{
					$obj_customer->data["contacts"][0]["contact"] = $obj_customer->data["name_contact"];

					$set_email	= 0;
					$set_phone	= 0;
					$set_fax	= 0;

					// search and replace values for existing accounts records
					for ($i=0; $i < $obj_customer->data["contacts"][0]["num_records"]; $i++)
					{
						if (!$set_email)
						{
							if ($obj_customer->data["contacts"][0]["records"][$i]["type"] == "email")
							{
								$obj_customer->data["contacts"][0]["records"][$i]["detail"] = $obj_customer->data["contact_email"];
								$set_email = 1;
							}
						}

						if (!$set_phone)
						{
							if ($obj_customer->data["contacts"][0]["records"][$i]["type"] == "phone")
							{
								$obj_customer->data["contacts"][0]["records"][$i]["detail"] = $obj_customer->data["contact_phone"];
								$set_phone = 1;
							}
						}
						if (!$set_fax)
						{
							if ($obj_customer->data["contacts"][0]["records"][$i]["type"] == "fax")
							{
								$obj_customer->data["contacts"][0]["records"][$i]["detail"] = $obj_customer->data["contact_fax"];
								$set_fax = 1;
							}
						}
					}


					// no existing record existed, add a new one
					if (!$set_email)
					{
						$i = $obj_customer->data["contact"]["num_records"];
						$obj_customer->data["contact"]["num_records"]++;

						$obj_customer->data["contacts"][0]["records"][$i]["delete"]	= "false";
						$obj_customer->data["contacts"][0]["records"][$i]["type"]	= "email";
						$obj_customer->data["contacts"][0]["records"][$i]["label"]	= "Email";
						$obj_customer->data["contacts"][0]["records"][$i]["detail"]	= $obj_customer->data["contact_email"];
					}

					if (!$set_phone)
					{
						$i = $obj_customer->data["contact"]["num_records"];
						$obj_customer->data["contact"]["num_records"]++;

						$obj_customer->data["contacts"][0]["records"][$i]["delete"]	= "false";
						$obj_customer->data["contacts"][0]["records"][$i]["type"]	= "phone";
						$obj_customer->data["contacts"][0]["records"][$i]["label"]	= "Phone";
						$obj_customer->data["contacts"][0]["records"][$i]["detail"]	= $obj_customer->data["contact_phone"];
					}

					if (!$set_fax)
					{
						$i = $obj_customer->data["contact"]["num_records"];
						$obj_customer->data["contact"]["num_records"]++;

						$obj_customer->data["contacts"][0]["records"][$i]["delete"]	= "false";
						$obj_customer->data["contacts"][0]["records"][$i]["type"]	= "fax";
						$obj_customer->data["contacts"][0]["records"][$i]["label"]	= "Fax";
						$obj_customer->data["contacts"][0]["records"][$i]["detail"]	= $obj_customer->data["contact_fax"];
					}

				}
				else
				{
					// no valid contact records exist, re-define the entry
					$obj_customer->data["contacts"][0]["contact"]			= $obj_customer->data["name_contact"];
					$obj_customer->data["contacts"][0]["role"]			= "accounts";

					$obj_customer->data["contacts"][0]["records"][0]["delete"]	= "false";
					$obj_customer->data["contacts"][0]["records"][0]["type"]	= "email";
					$obj_customer->data["contacts"][0]["records"][0]["label"]	= "Email";
					$obj_customer->data["contacts"][0]["records"][0]["detail"]	= $obj_customer->data["contact_email"];

					$obj_customer->data["contacts"][0]["records"][1]["delete"]	= "false";
					$obj_customer->data["contacts"][0]["records"][1]["type"]	= "phone";
					$obj_customer->data["contacts"][0]["records"][1]["label"]	= "Phone";
					$obj_customer->data["contacts"][0]["records"][1]["detail"]	= $obj_customer->data["contact_phone"];

					$obj_customer->data["contacts"][0]["records"][2]["delete"]	= "false";
					$obj_customer->data["contacts"][0]["records"][2]["type"]	= "fax";
					$obj_customer->data["contacts"][0]["records"][2]["label"]	= "Fax";
					$obj_customer->data["contacts"][0]["records"][2]["detail"]	= $obj_customer->data["contact_fax"];

				}

			}
			else
			{
				// new customer, easy for us to define a new structure.
				$obj_customer->data["num_contacts"] = 1;
	
				$obj_customer->data["contacts"][0]["contact_id"]		= "";
				$obj_customer->data["contacts"][0]["contact"]			= $obj_customer->data["name_contact"];
				$obj_customer->data["contacts"][0]["role"]			= "accounts";
				$obj_customer->data["contacts"][0]["delete_contact"]		= "false";
				$obj_customer->data["contacts"][0]["num_records"]		= 3;

				$obj_customer->data["contacts"][0]["records"][0]["delete"]	= "false";
				$obj_customer->data["contacts"][0]["records"][0]["type"]	= "email";
				$obj_customer->data["contacts"][0]["records"][0]["label"]	= "Email";
				$obj_customer->data["contacts"][0]["records"][0]["detail"]	= $obj_customer->data["contact_email"];

				$obj_customer->data["contacts"][0]["records"][1]["delete"]	= "false";
				$obj_customer->data["contacts"][0]["records"][1]["type"]	= "phone";
				$obj_customer->data["contacts"][0]["records"][1]["label"]	= "Phone";
				$obj_customer->data["contacts"][0]["records"][1]["detail"]	= $obj_customer->data["contact_phone"];

				$obj_customer->data["contacts"][0]["records"][2]["delete"]	= "false";
				$obj_customer->data["contacts"][0]["records"][2]["type"]	= "fax";
				$obj_customer->data["contacts"][0]["records"][2]["label"]	= "Fax";
				$obj_customer->data["contacts"][0]["records"][2]["detail"]	= $obj_customer->data["contact_fax"];
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
			$obj_customer->id	= @security_script_input_predefined("int", $id);
			$taxid			= @security_script_input_predefined("int", $taxid);
			$status			= @security_script_input_predefined("any", $status);

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
			$obj_customer->id = @security_script_input_predefined("int", $id);

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



	/*
		customer_portal_auth

		Authenticates a customer against their portal attributes using the supplied
		password and either their code or ID.

		Returns
		0	Unable to authentication
		#	Successful authentication, customer ID returned
	*/

	function customer_portal_auth($id_customer = null, $username = null, $password_plaintext = null)
	{
		log_debug('customers', "Executing customer_portal_auth($id_customer, $username, *plaintextpassword*)");

		if(!user_permissions_get('customers_portal_auth')) {
            throw new SoapFault('Sender', 'ACCESS DENIED');
        }

		// load SOAP data
		$data['id'] = @security_script_input_predefined('int', $id_customer);
		$data['username'] = @security_script_input_predefined('any', $username);
		$data['password_plaintext']	= @security_script_input_predefined('any', $password_plaintext);

		foreach(array_keys($data) as $key) {
			if($data[$key] == 'error' && $data[$key] != 0) {
				throw new SoapFault('Sender', 'INVALID_INPUT');
			}
		}

        // create customer object
        $obj_customer = New customer_portal;

        // determine authentication method
        if(sql_get_singlevalue("SELECT value FROM config WHERE name='CUSTOMER_PORTAL_CONTACT_LOGIN' LIMIT 1") == 'enabled') {

            // verify the supplied customer code and fetch the ID from it
            $sql_obj = New sql_query;
            $sql_obj->string = "SELECT a.id, c.contact_id
                FROM customers a,
                    customer_contacts b,
                    customer_contact_records c
                WHERE a.id = b.customer_id
                    AND c.contact_id = b.id
                    AND LOWER(c.detail) = LOWER('". $data['username'] ."')
                LIMIT 1";
            $sql_obj->execute();

            if($sql_obj->num_rows()) {
                $sql_obj->fetch_array();
                $obj_customer->id = $sql_obj->data[0]['id'];
            } else {
                throw new SoapFault('Sender', 'INVALID_AUTHDETAILS');
            }

            // load up customer contacts
            $obj_customer->load_data_contacts();

            // determine the index of the contact we are logging in
            for($i=0; $i<$obj_customer->data['num_contacts']; $i++) {
                // verify index
                if($obj_customer->data['contacts'][$i]['contact_id'] == $sql_obj->data[0]['contact_id']) {
                    // verify password
                    if($obj_customer->auth_contact_login($i, $data['password_plaintext'])) {
                        return $obj_customer->id;
                    } else {
                        throw new SoapFault('Sender', 'INVALID_AUTHDETAILS');
                    }
                }
            }

        } else {

    		// fetch & verify customer ID
    		if(!$data['id']) {
    			// verify the supplied customer code and fetch the ID from it
    			$sql_obj = New sql_query;
    			$sql_obj->string = "SELECT id FROM customers WHERE code_customer='". $data['username'] ."' LIMIT 1";
    			$sql_obj->execute();

    			if($sql_obj->num_rows()) {
    				$sql_obj->fetch_array();
    				$obj_customer->id = $sql_obj->data[0]['id'];
    			} else {
    				throw new SoapFault('Sender', 'INVALID_AUTHDETAILS');
    			}
    		} else {
    			// use supplied ID
    			$obj_customer->id = $data['id'];

    			// verify valid ID
    			if(!$obj_customer->verify_id()) {
    				throw new SoapFault('Sender', 'INVALID_AUTHDETAILS');
    			}
    		}

    		// verify password
    		if($obj_customer->auth_login($data['password_plaintext'])) {
    			return $obj_customer->id;
    		} else {
    			throw new SoapFault('Sender', 'INVALID_AUTHDETAILS');
    		}

        }

	} // end of customer_portal_auth



} // end of customers_manage_soap class



// define server
$server = new SoapServer("customers_manage.wsdl");
$server->setClass("customers_manage_soap");
$server->handle();



?>

