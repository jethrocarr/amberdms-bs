<?php
/*
	SOAP SERVICE -> VENDORS_MANAGE

	access:		vendors_view
			vendors_write


	This service provides APIs for creating, updating and deleting vendors.

	Refer to the Developer API documentation for information on using this service
	as well as sample code.
*/


// include libraries
include("../../include/config.php");
include("../../include/amberphplib/main.php");

// custom includes
include("../../include/vendors/inc_vendors.php");



class vendors_manage_soap
{
	/*
		get_vendor_details

		Fetch all the details for the requested vendor
	*/
	function get_vendor_details($id)
	{
		log_debug("vendors_manage_soap", "Executing get_vendor_details($id)");

		if (user_permissions_get("vendors_view"))
		{
			$obj_vendor = New vendor;


			// sanitise input
			$obj_vendor->id = security_script_input_predefined("int", $id);

			if (!$obj_vendor->id || $obj_vendor->id == "error")
			{
				throw new SoapFault("Sender", "INVALID_INPUT");
			}


			// verify that the ID is valid
			if (!$obj_vendor->verify_id())
			{
				throw new SoapFault("Sender", "INVALID_ID");
			}


			// load data from DB for this vendor
			if (!$obj_vendor->load_data())
			{
				throw new SoapFault("Sender", "UNEXPECTED_ACTION_ERROR");
			}


			// to save SOAP users from having to do another lookup to find out what the name
			// of the tax_default is, we do a lookup here.
			if ($obj_vendor->data["tax_default"])
			{
				$obj_vendor->data["tax_default_label"] = sql_get_singlevalue("SELECT name_tax as value FROM account_taxes WHERE id='". $obj_vendor->data["tax_default"] ."'");
			}



			// return data
			$return = array($obj_vendor->data["code_vendor"], 
					$obj_vendor->data["name_vendor"], 
					$obj_vendor->data["name_contact"], 
					$obj_vendor->data["contact_email"], 
					$obj_vendor->data["contact_phone"],
					$obj_vendor->data["contact_fax"], 
					$obj_vendor->data["date_start"], 
					$obj_vendor->data["date_end"],
					$obj_vendor->data["tax_number"],
					$obj_vendor->data["tax_default"],
					$obj_vendor->data["tax_default_label"],
					$obj_vendor->data["address1_street"],
					$obj_vendor->data["address1_city"],
					$obj_vendor->data["address1_state"],
					$obj_vendor->data["address1_country"],
					$obj_vendor->data["address1_zipcode"],
					$obj_vendor->data["address2_street"],
					$obj_vendor->data["address2_city"],
					$obj_vendor->data["address2_state"],
					$obj_vendor->data["address2_country"],
					$obj_vendor->data["address2_zipcode"]);

			return $return;
		}
		else
		{
			throw new SoapFault("Sender", "ACCESS_DENIED");
		}

	} // end of get_vendor_details


	/*
		get_vendor_tax

		Return list of all taxes and mark whether they are enabled or not for this vendor.
	*/

	function get_vendor_tax($id)
	{
		log_debug("vendors_manage_soap", "Executing get_vendor_tax($id)");

		if (user_permissions_get("vendors_view"))
		{
			$obj_vendor = New vendor;


			// sanitise input
			$obj_vendor->id = security_script_input_predefined("int", $id);

			if (!$obj_vendor->id || $obj_vendor->id == "error")
			{
				throw new SoapFault("Sender", "INVALID_INPUT");
			}


			// verify that the ID is valid
			if (!$obj_vendor->verify_id())
			{
				throw new SoapFault("Sender", "INVALID_ID");
			}


			// fetch vendor status
			$enabled_taxes = NULL;

			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT taxid FROM vendors_taxes WHERE vendorid='$id'";
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

	} // end of get_vendor_tax




	/*
		set_vendor_details

		Creates/Updates an vendor record.

		Returns
		0	failure
		#	ID of the vendor
	*/
	function set_vendor_details($id,
					$code_vendor, 
					$name_vendor, 
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
		log_debug("vendors_manager", "Executing set_vendor_details($id, values...)");

		if (user_permissions_get("vendors_write"))
		{
			$obj_vendor = New vendor;

			
			/*
				Load SOAP Data
			*/
			$obj_vendor->id					= security_script_input_predefined("int", $id);
			
			$obj_vendor->data["code_vendor"]		= security_script_input_predefined("any", $code_vendor);
			$obj_vendor->data["name_vendor"]		= security_script_input_predefined("any", $name_vendor);
			$obj_vendor->data["name_contact"]		= security_script_input_predefined("any", $name_contact);
			
			$obj_vendor->data["contact_phone"]		= security_script_input_predefined("any", $contact_phone);
			$obj_vendor->data["contact_fax"]		= security_script_input_predefined("any", $contact_fax);
			$obj_vendor->data["contact_email"]		= security_script_input_predefined("email", $contact_email);
			$obj_vendor->data["date_start"]			= security_script_input_predefined("date", $date_start);
			$obj_vendor->data["date_end"]			= security_script_input_predefined("date", $date_end);

			$obj_vendor->data["address1_street"]		= security_script_input_predefined("any", $address1_street);
			$obj_vendor->data["address1_city"]		= security_script_input_predefined("any", $address1_city);
			$obj_vendor->data["address1_state"]		= security_script_input_predefined("any", $address1_state);
			$obj_vendor->data["address1_country"]		= security_script_input_predefined("any", $address1_country);
			$obj_vendor->data["address1_zipcode"]		= security_script_input_predefined("any", $address1_zipcode);
			
			$obj_vendor->data["address2_street"]		= security_script_input_predefined("any", $address2_street);
			$obj_vendor->data["address2_city"]		= security_script_input_predefined("any", $address2_city);
			$obj_vendor->data["address2_state"]		= security_script_input_predefined("any", $address2_state);
			$obj_vendor->data["address2_country"]		= security_script_input_predefined("any", $address2_country);
			$obj_vendor->data["address2_zipcode"]		= security_script_input_predefined("any", $address2_zipcode);
			
			$obj_vendor->data["tax_number"]			= security_script_input_predefined("any", $tax_number);
			$obj_vendor->data["tax_default"]		= security_script_input_predefined("int", $tax_default);


			foreach (array_keys($obj_vendor->data) as $key)
			{
				if ($obj_vendor->data[$key] == "error")
				{
					throw new SoapFault("Sender", "INVALID_INPUT");
				}
			}



			/*
				Error Handling
			*/

			// verify vendor ID (if editing an existing vendor)
			if ($obj_vendor->id)
			{
				if (!$obj_vendor->verify_id())
				{
					throw new SoapFault("Sender", "INVALID_ID");
				}
			}

			// make sure we don't choose a vendor name that has already been taken
			if (!$obj_vendor->verify_name_vendor())
			{
				throw new SoapFault("Sender", "DUPLICATE_NAME_VENDOR");
			}

			// make sure we don't choose a vendor code that has already been taken
			if (!$obj_vendor->verify_code_vendor())
			{
				throw new SoapFault("Sender", "DUPLICATE_CODE_VENDOR");
			}


			/*
				Perform Changes
			*/
			
			if ($obj_vendor->action_update())
			{
				return $obj_vendor->id;
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

	} // end of set_vendor_details



	/*
		set_vendor_tax

		Enables or disables the specified tax for the vendor

		Returns
		0	failure
		#	ID of the vendor
	*/
	function set_vendor_tax($id,
					$taxid,
					$status)
	{
		log_debug("vendor_manager", "Executing set_vendor_tax($id, values...)");

		if (user_permissions_get("vendors_write"))
		{
			$obj_vendor = New vendor;

			
			/*
				Load SOAP Data
			*/
			$obj_vendor->id		= security_script_input_predefined("int", $id);
			$taxid			= security_script_input_predefined("int", $taxid);
			$status			= security_script_input_predefined("any", $status);

			foreach (array_keys($obj_vendor->data) as $key)
			{
				if ($obj_vendor->data[$key] == "error")
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

			// verify vendor ID
			if (!$obj_vendor->verify_id())
			{
				throw new SoapFault("Sender", "INVALID_ID");
			}


			/*
				Perform Changes
			*/

			// fetch vendors's current tax status
			$sql_vendor_taxes_obj		= New sql_query;
			$sql_vendor_taxes_obj->string	= "SELECT taxid FROM vendors_taxes WHERE vendorid='". $obj_vendor->id."'";

			$sql_vendor_taxes_obj->execute();

			if ($sql_vendor_taxes_obj->num_rows())
			{
				$sql_vendor_taxes_obj->fetch_array();

				foreach ($sql_vendor_taxes_obj->data as $data_tax)
				{
					$obj_vendor->data["tax_". $data_tax["taxid"] ] = "on";

				}
			}

			// change the status of the supplied option
			if ($status == "on")
			{
				$obj_vendor->data["tax_". $taxid] = "on";
			}
			else
			{
				$obj_vendor->data["tax_". $taxid] = "";
			}

			
			if ($obj_vendor->action_update_taxes())
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

	} // end of set_vendor_tax




	/*
		delete_vendor

		Deletes an vendor, provided that the vendor is not locked.

		Returns
		0	failure
		1	success
	*/
	function delete_vendor($id)
	{
		log_debug("vendors", "Executing delete_vendor_details($id, values...)");

		if (user_permissions_get("vendors_write"))
		{
			$obj_vendor = New vendor;

			
			/*
				Load SOAP Data
			*/
			$obj_vendor->id = security_script_input_predefined("int", $id);

			if (!$obj_vendor->id || $obj_vendor->id == "error")
			{
				throw new SoapFault("Sender", "INVALID_INPUT");
			}



			/*
				Error Handling
			*/

			// verify vendor ID
			if (!$obj_vendor->verify_id())
			{
				throw new SoapFault("Sender", "INVALID_ID");
			}


			// check that the vendor can be safely deleted
			if ($obj_vendor->check_delete_lock())
			{
				throw new SoapFault("Sender", "LOCKED");
			}



			/*
				Perform Changes
			*/
			if ($obj_vendor->action_delete())
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

	} // end of delete_vendor



} // end of vendors_manage_soap class



// define server
$server = new SoapServer("vendors_manage.wsdl");
$server->setClass("vendors_manage_soap");
$server->handle();



?>
