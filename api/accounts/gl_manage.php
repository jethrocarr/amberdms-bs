<?php
/*
	SOAP SERVICE -> ACCOUNTS_GL_MANAGE

	access:		accounts_gl_view
			accounts_gl_write

	This service provides APIs for creating, updating and deleting GL transaction. This
	service is a bit different from most, since the client app needs to make multiple
	requests to define all the transactions before being able to actually save the transaction.

	Refer to the Developer API documentation for information on using this service
	as well as sample code.
*/


// include libraries
include("../../include/config.php");
include("../../include/amberphplib/main.php");

// custom includes
include("../../include/accounts/inc_ledger.php");
include("../../include/accounts/inc_gl.php");



class accounts_gl_manage_soap
{
	var $data;

	/*
		get_gl_details

		Fetch all the details for the requested GL transaction
	*/
	function get_gl_details($id)
	{
		log_debug("gl_manage_soap", "Executing get_gl_details($id)");

		if (user_permissions_get("accounts_gl_view"))
		{
			$obj_gl = New gl_transaction;


			// sanitise input
			$obj_gl->id = security_script_input_predefined("int", $id);

			if (!$obj_gl->id || $obj_gl->id == "error")
			{
				throw new SoapFault("Sender", "INVALID_INPUT");
			}


			// verify that the ID is valid
			if (!$obj_gl->verify_id())
			{
				throw new SoapFault("Sender", "INVALID_ID");
			}


			// load data from DB for this transaction
			if (!$obj_gl->load_data())
			{
				throw new SoapFault("Sender", "UNEXPECTED_ACTION_ERROR");
			}


			// to save SOAP users from having to do another lookup to find out what
			// the employee name is, we fetch it for them now.
			if ($obj_gl->data["employeeid"])
			{
				$obj_gl->data["employeeid_label"] = sql_get_singlevalue("SELECT name_staff as value FROM staff WHERE id='". $obj_gl->data["employeeid"] ."'");
			}


			// return data
			$return = array($obj_gl->data["code_gl"], 
					$obj_gl->data["date_trans"], 
					$obj_gl->data["employeeid"], 
					$obj_gl->data["employeeid_label"],
					$obj_gl->data["description"],
					$obj_gl->data["notes"]);

			return $return;
		}
		else
		{
			throw new SoapFault("Sender", "ACCESS_DENIED");
		}

	} // end of get_gl_details



	/*
		get_gl_trans

		Fetches all the transaction records for the selected GL transaction
	*/

	function get_gl_trans($id)
	{
		log_debug("gl_manage_soap", "Executing get_gl_trans()");

		if (user_permissions_get("accounts_gl_view"))
		{
			$obj_gl = New gl_transaction;

			// sanitise input
			$obj_gl->id = security_script_input_predefined("int", $id);

			if (!$obj_gl->id || $obj_gl->id == "error")
			{
				throw new SoapFault("Sender", "INVALID_INPUT");
			}


			// verify that the ID is valid
			if (!$obj_gl->verify_id())
			{
				throw new SoapFault("Sender", "INVALID_ID");
			}


			// fetch list of all transactions
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id, amount_debit, amount_credit, chartid, source, memo FROM `account_trans` WHERE type='gl' AND customid='". $obj_gl->id ."'";
			$sql_obj->execute();


			// package up for sending to the client
			$return = NULL;

			if ($sql_obj->num_rows())
			{
				$sql_obj->fetch_array();

				foreach ($sql_obj->data as $data)
				{
					$return_tmp			= NULL;
					$return_tmp["id"]		= $data["id"];
					$return_tmp["chartid"]		= $data["chartid"];
					$return_tmp["chartid_label"]	= sql_get_singlevalue("SELECT CONCAT_WS('--', code_chart, description) as value FROM account_charts WHERE id='". $data["chartid"] ."'");
					$return_tmp["debit"]		= $data["amount_debit"];
					$return_tmp["credit"]		= $data["amount_credit"];
					$return_tmp["source"]		= $data["source"];
					$return_tmp["description"]	= $data["memo"];

					$return[] = $return_tmp;
				}
			}

			return $return;
		}
		else
		{
			throw new SoapFault("Sender", "ACCESS_DENIED");
		}

	} // end of get_gl_trans





	/*
		prepare_gl_details

		Loads the GL transaction details and stores them until the client calls set_gl_save($id).

		Returns
		0	failure
		1	success
	*/
	function prepare_gl_details($code_gl, 
					$date_trans, 
					$employeeid,
					$description,
					$description_useall,
					$notes)
	{
		log_debug("gl_manage_soap", "Executing prepare_gl_details(values...)");

		if (user_permissions_get("accounts_gl_write"))
		{
			$this->data["date_trans"]		= security_script_input_predefined("any", $date_trans);
			$this->data["employeeid"]		= security_script_input_predefined("int", $employeeid);
			$this->data["description"]		= security_script_input_predefined("any", $description);
			$this->data["description_useall"]	= security_script_input_predefined("any", $description_useall);
			$this->data["notes"]			= security_script_input_predefined("any", $notes);
			
			foreach (array("date_trans", "employeeid", "description", "description_useall", "notes") as $key)
			{
				if ($this->data[$key] == "error")
				{
					throw new SoapFault("Sender", "INVALID_INPUT");
				}
			}

			return 1;
		}
		else
		{
			throw new SoapFault("Sender", "ACCESS_DENIED");
		}


	} // end of prepare_gl_details


	/*
		prepare_gl_addtrans

		Add a new transaction item to the GL transaction and store it in the $this->data structure
		until the user calls set_gl_save($id) to write to the DB.

		Returns
		0	failure
		1	success
	*/
	function prepare_gl_addtrans($chartid,
					$credit, 
					$debit,
					$source,
					$description)
	{
		log_debug("gl_manage_soap", "Executing prepare_gl_addtrans(values...)");

		if (user_permissions_get("accounts_gl_write"))
		{
			// increment transaction row count

			if ($this->data["num_trans"] == NULL)
			{
				$this->data["num_trans"] = 0;
			}

			$i = $this->data["num_trans"];
			$this->data["num_trans"]++;


			/*
				Import SOAP Data
			*/

			$this->data["trans"][$i]["account"]			= security_script_input_predefined("int", $chartid);
			$this->data["trans"][$i]["credit"]			= security_script_input_predefined("money", $credit);
			$this->data["trans"][$i]["debit"]			= security_script_input_predefined("money", $debit);
			$this->data["trans"][$i]["source"]			= security_script_input_predefined("any", $source);
			$this->data["trans"][$i]["description"]			= security_script_input_predefined("any", $description);
		

			/*
				Error Handling
			*/

			// check for data import errors
			foreach (array("account", "credit", "debit", "source", "description") as $key)
			{
				if ($this->data["trans"][$i][$key] == "error")
				{
					throw new SoapFault("Sender", "INVALID_INPUT");
				}
			}


			// make sure both account and an amount have been supplied together
			if ($this->data["trans"][$i]["account"] && !$this->data["trans"][$i]["debit"] && !$this->data["trans"][$i]["credit"] )
			{
				throw new SoapFault("Sender", "MISSING_FINANCIAL_VALUES");
			}

			if ($this->data["trans"][$i]["debit"] != "0.00" || $this->data["trans"][$i]["credit"] != "0.00")
			{
				if (!$this->data["trans"][$i]["account"])
				{
					// no chartid supplied
					throw new SoapFault("Sender", "MISSING_FINANCIAL_VALUES");
				}
			}
			else
			{
				// no credit, debit, or account values
				throw new SoapFault("Sender", "MISSING_FINANCIAL_VALUES");
			}


			// make sure that both debit and credit are not set for one transaction
			if ($obj_gl->data["trans"][$i]["debit"] > 0 && $obj_gl->data["trans"][$i]["credit"] > 0)
			{
				throw new SoapFault("Sender", "BOTH_CREDIT_AND_DEBIT_SET");
			}

			return 1;
		}
		else
		{
			throw new SoapFault("Sender", "ACCESS_DENIED");
		}

	} // end of prepare_gl_addtrans


	/*
		set_gl_save

		Takes all the GL transaction information in $this->data and creates or updates a GL
		transaction from it.

		Clears the prepared data when finished.

		Returns
		0	Failure
		#	Success - ID of the GL transaction
	*/
	
	function set_gl_save($id)
	{
		log_debug("accounts_gl_manage", "Executing set_gl_save($id)");


		if (user_permissions_get("accounts_gl_write"))
		{
			$obj_gl = New gl_transaction;

			
			/*
				Load SOAP Data
			*/
			if (!preg_match("/^[0-9]*$/", $obj_gl->id))
			{
				throw new SoapFault("Sender", "INVALID_INPUT");
			}


			/*
				Load temporary stored data
			*/
			$obj_gl->data = $this->data;



			/*
				Error Handling
			*/

			// verify transaction ID (if editing an existing transaction)
			if ($obj_gl->id)
			{
				if (!$obj_gl->verify_id())
				{
					throw new SoapFault("Sender", "INVALID_ID");
				}


				// make sure transaction is not locked
				if ($obj_gl->checklock())
				{
					throw new SoapFault("Sender", "LOCKED");
				}
			}

			// make sure we don't choose a code_gl that has already been taken
			if (!$obj_gl->verify_code_gl())
			{
				throw new SoapFault("Sender", "DUPLICATE_CODE_GL");
			}

			// verify all the transaction rows
			$result = $obj_gl->verify_valid_trans();

			if ($result == 0)
			{
				throw new SoapFault("Sender", "UNBALANCED_TRANSACTIONS");
			}
			elseif ($result == -1)
			{
				throw new SoapFault("Sender", "MISSING_TRANS_DATA");
			}



			/*
				Perform Changes
			*/

			if ($obj_gl->action_update())
			{
				// clear the prepared data
				$this->data = array();

				return $obj_gl->id;
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

	} // end of set_gl_save


	/*
		delete_gl

		Deletes a GL transaction, provided that it is not locked.

		Returns
		0	failure
		1	success
	*/
	function delete_gl($id)
	{
		log_debug("gl_manage_soap", "Executing delete_gl($id)");


		if (user_permissions_get("accounts_gl_write"))
		{
			$obj_gl = New gl_transaction;

			
			/*
				Load SOAP Data
			*/
			$obj_gl->id = security_script_input_predefined("int", $id);

			if (!$obj_gl->id || $obj_gl->id == "error")
			{
				throw new SoapFault("Sender", "INVALID_INPUT");
			}



			/*
				Error Handling
			*/

			// verify transaction ID
			if (!$obj_gl->verify_id())
			{
				throw new SoapFault("Sender", "INVALID_ID");
			}


			// check that the transaction can be safely deleted
			if ($obj_gl->check_delete_lock())
			{
				throw new SoapFault("Sender", "LOCKED");
			}


			/*
				Perform Changes
			*/
			if ($obj_gl->action_delete())
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

	} // end of delete_gl



} // end of accounts_gl_manage_soap class



// define server
$server = new SoapServer("gl_manage.wsdl");
$server->setClass("accounts_gl_manage_soap");
$server->setPersistence(SOAP_PERSISTENCE_SESSION);
$server->handle();



?>
