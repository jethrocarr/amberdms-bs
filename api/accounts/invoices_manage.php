<?php
/*
	SOAP SERVICE -> ACCOUNTS_INVOICES_MANAGE

	access:		accounts_ar_view	{ AR
			accounts_ar_write	  INVOICES }

			accounts_ap_view	{ AP
			accounts_ap_write	  INVOICES }

	This service provides APIs for creating, updating and deleting invoices/accounts.

	Refer to the Developer API documentation for information on using this service
	as well as sample code.
*/


// include libraries
include("../../include/config.php");
include("../../include/amberphplib/main.php");

// custom includes
include("../../include/accounts/inc_invoices.php");
include("../../include/accounts/inc_invoices_items.php");



class accounts_invoices_manage_soap
{
	/*
		get_invoice_id_from_code

		Return the ID of the provided invoice code/number
	*/
	function get_invoice_id_from_code($code_invoice, $invoicetype)
	{
		log_debug("invoices_manage_soap", "Executing get_invoice_id_from_code($code_invoice, $invoicetype)");

		// check the invoicetype
		if ($invoicetype != "ar" && $invoicetype != "ap")
		{
			throw new SoapFault("Sender", "INVALID_INVOICE_TYPE");
		}


		if (user_permissions_get("accounts_". $invoicetype ."_view"))
		{
			// sanitise input
			$code_invoice = security_script_input_predefined("any", $code_invoice);

			if (!$code_invoice || $code_invoice == "error")
			{
				throw new SoapFault("Sender", "INVALID_INPUT");
			}

			
			// fetch the invoice ID
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM account_". $invoicetype ." WHERE code_invoice='$code_invoice' LIMIT 1";
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

	} // end of get_invoice_id_from_code


	/*
		get_invoice_details

		Fetch all the details for the requested invoice
	*/
	function get_invoice_details($id, $invoicetype)
	{
		log_debug("invoices_manage_soap", "Executing get_invoice_details($id, $invoicetype)");


		// check the invoicetype
		if ($invoicetype != "ar" && $invoicetype != "ap")
		{
			throw new SoapFault("Sender", "INVALID_INVOICE_TYPE");
		}


		if (user_permissions_get("accounts_". $invoicetype ."_view"))
		{
			$obj_invoice		= New invoice;
			$obj_invoice->type	= $invoicetype;


			// sanitise input
			$obj_invoice->id	= security_script_input_predefined("int", $id);

			if (!$obj_invoice->id || $obj_invoice->id == "error")
			{
				throw new SoapFault("Sender", "INVALID_INPUT");
			}


			// verify that the invoice is valid
			if (!$obj_invoice->verify_invoice())
			{
				throw new SoapFault("Sender", "INVALID_INVOICE");
			}


			// load data from DB for this invoice
			if (!$obj_invoice->load_data())
			{
				throw new SoapFault("Sender", "UNEXPECTED_ACTION_ERROR");
			}


			// we need to set the orgid fields depending on the invoice type
			if ($obj_invoice->type == "ar")
			{
				$obj_invoice->data["orgid"]		= $obj_invoice->data["customerid"];
				$obj_invoice->data["orgid_label"]	= sql_get_singlevalue("SELECT name_customer as value FROM customers WHERE id='". $obj_invoice->data["customerid"] ."'");
			}
			else
			{
				$obj_invoice->data["orgid"]		= $obj_invoice->data["vendorid"];
				$obj_invoice->data["orgid_label"]	= sql_get_singlevalue("SELECT name_vendor as value FROM vendors WHERE id='". $obj_invoice->data["vendorid"] ."'");
			}


			// do lookups of human-readable values on to save lots of additional SOAP calls just to lookup IDs.
			$obj_invoice->data["employeeid_label"]		= sql_get_singlevalue("SELECT name_staff as value FROM staff WHERE id='". $obj_invoice->data["employeeid"] ."'");
			$obj_invoice->data["dest_account_label"]	= sql_get_singlevalue("SELECT CONCAT_WS('--', code_chart, description) as value FROM account_charts WHERE id='". $obj_invoice->data["dest_account"] ."'");


			// return data
			$return = array($obj_invoice->data["locked"], 
					$obj_invoice->data["orgid"], 
					$obj_invoice->data["orgid_label"], 
					$obj_invoice->data["employeeid"], 
					$obj_invoice->data["employeeid_label"], 
					$obj_invoice->data["dest_account"], 
					$obj_invoice->data["dest_account_label"], 
					$obj_invoice->data["code_invoice"], 
					$obj_invoice->data["code_ordernumber"], 
					$obj_invoice->data["code_ponumber"], 
					$obj_invoice->data["date_due"], 
					$obj_invoice->data["date_trans"], 
					$obj_invoice->data["date_create"], 
					$obj_invoice->data["date_sent"], 
					$obj_invoice->data["sentmethod"], 
					$obj_invoice->data["amount_total"], 
					$obj_invoice->data["amount_tax"], 
					$obj_invoice->data["amount"], 
					$obj_invoice->data["amount_paid"], 
					$obj_invoice->data["notes"]);

			return $return;
		}
		else
		{
			throw new SoapFault("Sender", "ACCESS_DENIED");
		}

	} // end of get_invoice_details


	/*
		get_invoice_items

		Returns a list of all product, timegroup or standard items
		belonging to the selected invoice.
	*/

	function get_invoice_items($id, $invoicetype)
	{
		log_debug("invoices_manage_soap", "Executing get_invoice_items($id, $invoicetype)");


		// check the invoicetype
		if ($invoicetype != "ar" && $invoicetype != "ap")
		{
			throw new SoapFault("Sender", "INVALID_INVOICE_TYPE");
		}


		if (user_permissions_get("accounts_". $invoicetype ."_view"))
		{
			$obj_invoice		= New invoice;
			$obj_invoice->type	= $invoicetype;


			// sanitise input
			$obj_invoice->id	= security_script_input_predefined("int", $id);

			if (!$obj_invoice->id || $obj_invoice->id == "error")
			{
				throw new SoapFault("Sender", "INVALID_INPUT");
			}


			// verify that the invoice is valid
			if (!$obj_invoice->verify_invoice())
			{
				throw new SoapFault("Sender", "INVALID_INVOICE");
			}


			// fetch all items
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id, type, customid, chartid, quantity, units, amount, price, description FROM account_items WHERE invoiceid='". $obj_invoice->id ."' AND invoicetype='". $obj_invoice->type ."' AND type!='tax' AND type!='payment'";
			$sql_obj->execute();
			$sql_obj->fetch_array();


			// package data into array for passing back to SOAP client
			$return = NULL;
			foreach ($sql_obj->data as $data)
			{

				// fetch customid_label value
				switch ($data["type"])
				{
					case "product":
						// Fetch product name
						$data["customid_label"] = sql_get_singlevalue("SELECT name_product as value FROM products WHERE id='". $data["customid"] ."' LIMIT 1");

						// blank a few fields
						$data["timegroupid"]		= "";
						$data["timegroupid_label"]	= "";

						// fetch discount (if any)
						$data["discount"]		= sql_get_singlevalue("SELECT option_value as value FROM account_items_options WHERE itemid='". $data["id"] ."' AND option_name='DISCOUNT'");
					break;


					case "time":
						// fetch product name
						$data["customid_label"] = sql_get_singlevalue("SELECT name_product as value FROM products WHERE id='". $data["customid"] ."' LIMIT 1");

						// Fetch time group ID
						$data["timegroupid"]		= sql_get_singlevalue("SELECT option_value as value FROM account_items_options WHERE itemid='". $data["id"] ."' AND option_name='TIMEGROUPID'");
						$data["timegroupid_label"]	= sql_get_singlevalue("SELECT CONCAT_WS(' -- ', projects.code_project, time_groups.name_group) as value FROM time_groups LEFT JOIN projects ON projects.id = time_groups.projectid WHERE time_groups.id='". $data["timegroupid"] ."' LIMIT 1");

						// fetch discount (if any)
						$data["discount"]		= sql_get_singlevalue("SELECT option_value as value FROM account_items_options WHERE itemid='". $data["id"] ."' AND option_name='DISCOUNT'");
					break;


					case "standard":

						// blank a few fields
						$data["customid_label"] 	= "";
						$data["price"]			= "";
						$data["quantity"]		= "";
						$data["units"]			= "";
						$data["timegroupid"]		= "";
						$data["timegroupid_label"]	= "";
						$data["discount"]		= "";

						$data["taxes"]			= NULL;;

						/*
							Fetch taxes for this item

							Show all taxes and their status (enables vs disabled)
						*/

						$sql_tax_obj		= New sql_query;
						$sql_tax_obj->string	= "SELECT id, name_tax, description FROM account_taxes ORDER BY name_tax";
						$sql_tax_obj->execute();

						if ($sql_tax_obj->num_rows())
						{
							// run through all the taxes
							$sql_tax_obj->fetch_array();

							foreach ($sql_tax_obj->data as $data_tax)
							{
								// define tax checkbox
								$structure = NULL;
								$structure["taxid"]		= $data_tax["id"];
								$structure["taxid_label"]	= $data_tax["name_tax"] ." -- ". $data_tax["description"];
								
								// see if this tax is currently inuse for the item
								$sql_taxenabled_obj		= New sql_query;
								$sql_taxenabled_obj->string	= "SELECT id FROM account_items_options WHERE itemid='". $data["id"] ."' AND option_name='TAX_CHECKED' AND option_value='". $data_tax["id"] ."'";
								$sql_taxenabled_obj->execute();

								if ($sql_taxenabled_obj->num_rows())
								{
									$structure["status"] = "on";
								}
								else
								{
									$structure["status"] = "off";
								}

								unset($sql_taxenabled_obj);


								// add to return data
								$data["taxes"][] = $structure;
							}

						} // end of loop through taxes

					break;
				}


				// create return structure
				$return_tmp				= NULL;

				$return_tmp["itemid"]			= $data["id"];
				$return_tmp["type"]			= $data["type"];
				$return_tmp["customid"]			= $data["customid"];
				$return_tmp["customid_label"]		= $data["customid_label"];
				$return_tmp["chartid"]			= $data["chartid"];
				$return_tmp["chartid_label"]		= sql_get_singlevalue("SELECT CONCAT_WS('--', code_chart, description) as value FROM account_charts WHERE id='". $data["chartid"] ."'");
				$return_tmp["timegroupid"]		= $data["timegroupid"];
				$return_tmp["timegroupid_label"]	= $data["timegroupid_label"];
				$return_tmp["quantity"]			= $data["quantity"];
				$return_tmp["units"]			= $data["units"];
				$return_tmp["amount"]			= $data["amount"];
				$return_tmp["price"]			= $data["price"];
				$return_tmp["description"]		= $data["description"];
				$return_tmp["taxes"]			= $data["taxes"];
				$return_tmp["discount"]			= $data["discount"];

				$return[] = $return_tmp;
			}

			return $return;
		}
		else
		{
			throw new SoapFault("Sender", "ACCESS_DENIED");
		}

	} // end of get_invoice_items



	/*
		get_invoice_taxes

		Returns a list of all tax items belonging to the selected invoice.
	*/

	function get_invoice_taxes($id, $invoicetype)
	{
		log_debug("invoices_manage_soap", "Executing get_invoice_taxes($id, $invoicetype)");


		// check the invoicetype
		if ($invoicetype != "ar" && $invoicetype != "ap")
		{
			throw new SoapFault("Sender", "INVALID_INVOICE_TYPE");
		}


		if (user_permissions_get("accounts_". $invoicetype ."_view"))
		{
			$obj_invoice		= New invoice;
			$obj_invoice->type	= $invoicetype;


			// sanitise input
			$obj_invoice->id	= security_script_input_predefined("int", $id);

			if (!$obj_invoice->id || $obj_invoice->id == "error")
			{
				throw new SoapFault("Sender", "INVALID_INPUT");
			}


			// verify that the invoice is valid
			if (!$obj_invoice->verify_invoice())
			{
				throw new SoapFault("Sender", "INVALID_INVOICE");
			}


			// fetch all tax items
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id, customid, chartid, amount FROM account_items WHERE invoiceid='". $obj_invoice->id ."' AND invoicetype='". $obj_invoice->type ."' AND type='tax'";
			$sql_obj->execute();
			$sql_obj->fetch_array();


			// package data into array for passing back to SOAP client
			$return = NULL;
			foreach ($sql_obj->data as $data)
			{

				// fetch tax_id_label value
				$data["customid_label"] = sql_get_singlevalue("SELECT name_tax as value FROM account_taxes WHERE id='". $data["customid"] ."'");


				// create return structure
				$return_tmp			= NULL;

				$return_tmp["itemid"]		= $data["id"];
				$return_tmp["taxid"]		= $data["customid"];
				$return_tmp["taxid_label"]	= $data["customid_label"];
				$return_tmp["amount"]		= $data["amount"];

				$return[] = $return_tmp;
			}

			return $return;
		}
		else
		{
			throw new SoapFault("Sender", "ACCESS_DENIED");
		}

	} // end of get_invoice_taxes



	/*
		get_invoice_payments

		Returns a list of all payment items belonging to the selected invoice.
	*/

	function get_invoice_payments($id, $invoicetype)
	{
		log_debug("invoices_manage_soap", "Executing get_invoice_payments($id, $invoicetype)");


		// check the invoicetype
		if ($invoicetype != "ar" && $invoicetype != "ap")
		{
			throw new SoapFault("Sender", "INVALID_INVOICE_TYPE");
		}


		if (user_permissions_get("accounts_". $invoicetype ."_view"))
		{
			$obj_invoice		= New invoice;
			$obj_invoice->type	= $invoicetype;


			// sanitise input
			$obj_invoice->id	= security_script_input_predefined("int", $id);

			if (!$obj_invoice->id || $obj_invoice->id == "error")
			{
				throw new SoapFault("Sender", "INVALID_INPUT");
			}


			// verify that the invoice is valid
			if (!$obj_invoice->verify_invoice())
			{
				throw new SoapFault("Sender", "INVALID_INVOICE");
			}


			// fetch all payment items
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id, chartid, amount, description FROM account_items WHERE invoiceid='". $obj_invoice->id ."' AND invoicetype='". $obj_invoice->type ."' AND type='payment'";
			$sql_obj->execute();
			$sql_obj->fetch_array();


			// package data into array for passing back to SOAP client
			$return = NULL;
			foreach ($sql_obj->data as $data)
			{
				// fetch source message
				$data["source"]		= sql_get_singlevalue("SELECT option_value as value FROM account_items_options WHERE itemid='". $data["id"] ."' AND option_name='SOURCE'");

				// fetch payment date_trans
				$data["date_trans"]	= sql_get_singlevalue("SELECT option_value as value FROM account_items_options WHERE itemid='". $data["id"] ."' AND option_name='DATE_TRANS'");

				// fetch chartid label
				$data["chartid_label"]	= sql_get_singlevalue("SELECT CONCAT_WS('--', code_chart, description) as value FROM account_charts WHERE id='". $data["chartid"] ."'");


				// create return structure
				$return_tmp			= NULL;

				$return_tmp["itemid"]		= $data["id"];
				$return_tmp["date_trans"]	= $data["date_trans"];
				$return_tmp["chartid"]		= $data["chartid"];
				$return_tmp["chartid_label"]	= $data["chartid_label"];
				$return_tmp["amount"]		= $data["amount"];
				$return_tmp["source"]		= $data["source"];
				$return_tmp["description"]	= $data["description"];

				$return[] = $return_tmp;
			}

			return $return;
		}
		else
		{
			throw new SoapFault("Sender", "ACCESS_DENIED");
		}

	} // end of get_invoice_payments


	/*
		set_invoice_details

		Creates/Updates an invoice record.

		Returns
		0	failure
		#	ID of the invoice
	*/
	function set_invoice_details($id,
					$invoicetype,
					$locked,
					$orgid,
					$employeeid,
					$dest_account,
					$code_invoice,
					$code_ordernumber,
					$code_ponumber,
					$date_due,
					$date_trans,
					$date_sent,
					$sendmethod,
					$notes)
	{
		log_debug("accounts_invoices_manage", "Executing set_invoice_details($id, $invoicetype, values...)");


		// check the invoicetype
		if ($invoicetype != "ar" && $invoicetype != "ap")
		{
			throw new SoapFault("Sender", "INVALID_INVOICE_TYPE");
		}


		if (user_permissions_get("accounts_". $invoicetype ."_write"))
		{
			$obj_invoice		= New invoice;
			$obj_invoice->type	= $invoicetype;


			/*
				Load SOAP Data

				TODO: a number of these options might just be ignored by the action_update command - look
					into this possiblity
			*/
			$obj_invoice->id				= security_script_input_predefined("int", $id);
			$obj_invoice->data["locked"]			= security_script_input_predefined("int", $locked);

			if ($invoicetype == "ap")
			{
				$obj_invoice->data["vendorid"]		= security_script_input_predefined("int", $orgid);
			}
			else
			{
				$obj_invoice->data["customerid"]	= security_script_input_predefined("int", $orgid);
			}
			
			$obj_invoice->data["employeeid"]		= security_script_input_predefined("int", $employeeid);
			$obj_invoice->data["dest_account"]		= security_script_input_predefined("int", $dest_account);
			
			$obj_invoice->data["code_invoice"]		= security_script_input_predefined("any", $code_invoice);
			$obj_invoice->data["code_ordernumber"]		= security_script_input_predefined("any", $code_ordernumber);
			$obj_invoice->data["code_ponumber"]		= security_script_input_predefined("any", $code_ponumber);
			$obj_invoice->data["date_due"]			= security_script_input_predefined("date", $date_due);
			$obj_invoice->data["date_trans"]		= security_script_input_predefined("date", $date_trans);
			$obj_invoice->data["date_sent"]			= security_script_input_predefined("date", $date_sent);
			$obj_invoice->data["sentmethod"]		= security_script_input_predefined("any", $sentmethod);
			$obj_invoice->data["notes"]			= security_script_input_predefined("any", $notes);


			foreach (array_keys($obj_invoice->data) as $key)
			{
				// TODO: what the fuck is wrong with php here???
				//
				// weird bug work around - without the != 0 statement, $obj_invoice->data["locked"] will
				// match "error", despite equaling 0.

				if ($obj_invoice->data[$key] == "error" && $obj_invoice->data[$key] != 0)
				{
					throw new SoapFault("Sender", "INVALID_INPUT");
				}
			}



			/*
				Error Handling
			*/

			// verify invoice exisitance (if editing an existing one)
			if ($obj_invoice->id)
			{
				if (!$obj_invoice->verify_invoice())
				{
					throw new SoapFault("Sender", "INVALID_INVOICE");
				}
			
				// make sure invoice is not locked
				if ($obj_invoice->check_lock())
				{
					throw new SoapFault("Sender", "LOCKED");
				}
			}

			// make sure we don't choose a invoice code that has already been taken
			if (!$obj_invoice->prepare_code_invoice($obj_invoice->data["code_invoice"]))
			{
				throw new SoapFault("Sender", "DUPLICATE_CODE_INVOICE");
			}

			



			/*
				Perform Changes
			*/

			if ($obj_invoice->id)
			{
				// update existing invoice
				if ($obj_invoice->action_update())
				{
					return $obj_invoice->id;
				}
				else
				{
					throw new SoapFault("Sender", "UNEXPECTED_ACTION_ERROR");
				}
			}
			else
			{
				// create new invoice
				if ($obj_invoice->action_create())
				{
					return $obj_invoice->id;
				}
				else
				{
					throw new SoapFault("Sender", "UNEXPECTED_ACTION_ERROR");
				}
			}
 		}
		else
		{
			throw new SoapFault("Sender", "ACCESS DENIED");
		}

	} // end of set_invoice_details




	/*
		set_invoice_item_standard

		Creates/Updates a standard invoice item

		Returns
		0	failure
		#	ID of the item
	*/
	function set_invoice_item_standard($id,
					$invoicetype,
					$itemid,
					$chartid,
					$amount,
					$description)
	{
		log_debug("accounts_invoices_manage", "Executing set_invoice_item_standard($id, $invoicetype, values...)");


		// check the invoicetype
		if ($invoicetype != "ar" && $invoicetype != "ap")
		{
			throw new SoapFault("Sender", "INVALID_INVOICE_TYPE");
		}


		if (user_permissions_get("accounts_". $invoicetype ."_write"))
		{
			$obj_invoice_item			= New invoice_items;

			$obj_invoice_item->type_invoice		= $invoicetype;
			$obj_invoice_item->id_invoice		= security_script_input_predefined("int", $id);
			$obj_invoice_item->id_item		= security_script_input_predefined("any", $itemid);
			$obj_invoice_item->type_item		= "standard";

			/*
				Error Handling
			*/

			// verify invoice existance
			if (!$obj_invoice_item->verify_invoice())
			{
				throw new SoapFault("Sender", "INVALID_INVOICE");
			}

			// make sure invoice is not locked
			if ($obj_invoice_item->check_lock())
			{
				throw new SoapFault("Sender", "LOCKED");
			}

			// verify item existance (if editing one)
			if ($obj_invoice_item->id_item)
			{
				if (!$obj_invoice_item->verify_item())
				{
					throw new SoapFault("Sender", "INVALID_ITEMID");
				}
			}



			/*
				Load SOAP data
			*/


			$data["amount"]		= security_script_input_predefined("money", $amount);
			$data["chartid"]	= security_script_input_predefined("int", $chartid);
			$data["description"]	= security_script_input_predefined("any", $description);

			foreach (array_keys($data) as $key)
			{
				if ($data[$key] == "error" && $data[$key] != 0)
				{
					throw new SoapFault("Sender", "INVALID_INPUT");
				}
			}


			// process the data
			if (!$obj_invoice_item->prepare_data($data))
			{
				throw new SoapFault("Sender", "UNEXPECTED_PREP_ERROR");
			}




			/*
				Apply Data
			*/


			// start SQL transaction
			$sql_obj = New sql_query;
			$sql_obj->trans_begin();


			if (!$obj_invoice_item->id_item)
			{
				if (!$obj_invoice_item->action_create())
				{
					$sql_obj->trans_rollback();

					throw new SoapFault("Sender", "UNEXPECTED_ACTION_ERROR");
				}

			}


			if (!$obj_invoice_item->action_update())
			{
				$sql_obj->trans_rollback();

				throw new SoapFault("Sender", "UNEXPECTED_ACTION_ERROR");
			}




			// Re-calculate taxes, totals and ledgers as required
			// (does not need to be done for tax or payment items)
			
			$obj_invoice_item->action_update_tax();
			$obj_invoice_item->action_update_total();


			// Generate ledger entries.
			$obj_invoice_item->action_update_ledger();



			// commit
			if (error_check())
			{
				$sql_obj->trans_rollback();

				throw new SoapFault("Sender", "UNEXPECTED_ACTION_ERROR");
			}
			else
			{
				$sql_obj->trans_commit();

				return $obj_invoice_item->id_item;
			}
 		}
		else
		{
			throw new SoapFault("Sender", "ACCESS DENIED");
		}

	} // end of set_invoice_item_standard



	/*
		set_invoice_item_standard_tax

		Enables or disables a specific tax for the specified standard invoice item.

		Returns
		0	failure
		#	ID of the item
	*/
	function set_invoice_item_standard_tax($id,
					$invoicetype,
					$itemid,
					$taxid,
					$status)
	{
		log_debug("accounts_invoices_manage", "Executing set_invoice_item_standard_tax($id, $invoicetype, values...)");


		// check the invoicetype
		if ($invoicetype != "ar" && $invoicetype != "ap")
		{
			throw new SoapFault("Sender", "INVALID_INVOICE_TYPE");
		}


		if (user_permissions_get("accounts_". $invoicetype ."_write"))
		{
			$obj_invoice_item			= New invoice_items;

			$obj_invoice_item->type_invoice		= $invoicetype;
			$obj_invoice_item->id_invoice		= security_script_input_predefined("int", $id);
			$obj_invoice_item->id_item		= security_script_input_predefined("any", $itemid);
			$obj_invoice_item->type_item		= "standard";

			/*
				Error Handling
			*/

			// verify invoice existance
			if (!$obj_invoice_item->verify_invoice())
			{
				throw new SoapFault("Sender", "INVALID_INVOICE");
			}

			// make sure invoice is not locked
			if ($obj_invoice_item->check_lock())
			{
				throw new SoapFault("Sender", "LOCKED");
			}

			// verify item existance
			if (!$obj_invoice_item->verify_item())
			{
				throw new SoapFault("Sender", "INVALID_ITEMID");
			}

			// make sure item is a standard item
			if ($obj_invoice_item->type_item != "standard")
			{
				throw new SoapFault("Sender", "UNSUPPORTED_ITEM_TYPE");
			}


			/*
				Load SOAP data
			*/

			$data["taxid"]		= security_script_input_predefined("int", $taxid);
			$data["status"]		= security_script_input_predefined("any", $status);

			foreach (array_keys($data) as $key)
			{
				if ($data[$key] == "error")
				{
					throw new SoapFault("Sender", "INVALID_INPUT");
				}
			}


			/*
				Load Item Data

				We need to load the item's data so we can then add the new tax status and save it
				again. Note that the load_data() function will also load all the current tax status.
			*/

			$obj_invoice_item->load_data();
 


			/*
				Apply Data
			*/


			// we set the tax
			$obj_invoice_item->data["tax_". $data["taxid"] ] = $data["status"];


			// start SQL transaction
			$sql_obj = New sql_query;
			$sql_obj->trans_begin();


			// save changes
			if (!$obj_invoice_item->action_update())
			{
				$sql_obj->trans_rollback();

				throw new SoapFault("Sender", "UNEXPECTED_ACTION_ERROR");
			}

			// Re-calculate taxes, totals and ledgers as required
			$obj_invoice_item->action_update_tax();
			$obj_invoice_item->action_update_total();
			$obj_invoice_item->action_update_ledger();


			// commit
			if (error_check())
			{
				$sql_obj->trans_rollback();

				throw new SoapFault("Sender", "UNEXPECTED_ACTION_ERROR");
			}
			else
			{
				$sql_obj->trans_commit();

				return $obj_invoice_item->id_item;
			}

 		}
		else
		{
			throw new SoapFault("Sender", "ACCESS DENIED");
		}

	} // end of set_invoice_item_standard_tax



	/*
		set_invoice_item_product

		Creates/Updates a product invoice item

		Returns
		0	failure
		#	ID of the item
	*/
	function set_invoice_item_product($id,
					$invoicetype,
					$itemid,
					$price,
					$quantity,
					$units,
					$productid,
					$description,
					$discount)
	{
		log_debug("accounts_invoices_manage", "Executing set_invoice_item_product($id, $invoicetype, values...)");


		// check the invoicetype
		if ($invoicetype != "ar" && $invoicetype != "ap")
		{
			throw new SoapFault("Sender", "INVALID_INVOICE_TYPE");
		}


		if (user_permissions_get("accounts_". $invoicetype ."_write"))
		{
			$obj_invoice_item			= New invoice_items;

			$obj_invoice_item->type_invoice		= $invoicetype;
			$obj_invoice_item->id_invoice		= security_script_input_predefined("int", $id);
			$obj_invoice_item->id_item		= security_script_input_predefined("any", $itemid);
			$obj_invoice_item->type_item		= "product";

			/*
				Error Handling
			*/

			// verify invoice existance
			if (!$obj_invoice_item->verify_invoice())
			{
				throw new SoapFault("Sender", "INVALID_INVOICE");
			}

			// make sure invoice is not locked
			if ($obj_invoice_item->check_lock())
			{
				throw new SoapFault("Sender", "LOCKED");
			}

			// verify item existance (if editing one)
			if ($obj_invoice_item->id_item)
			{
				if (!$obj_invoice_item->verify_item())
				{
					throw new SoapFault("Sender", "INVALID_ITEMID");
				}
			}



			/*
				Load SOAP data
			*/


			$data["price"]		= security_script_input_predefined("money", $price);
			$data["quantity"]	= security_script_input_predefined("float", $quantity);
			$data["units"]		= security_script_input_predefined("any", $units);
			$data["customid"]	= security_script_input_predefined("int", $productid);
			$data["description"]	= security_script_input_predefined("any", $description);
			$data["discount"]	= security_script_input_predefined("float", $discount);

			foreach (array_keys($data) as $key)
			{
				if ($data[$key] == "error" && $data[$key] != 0)
				{
					throw new SoapFault("Sender", "INVALID_INPUT");
				}
			}


			// process the data
			if (!$obj_invoice_item->prepare_data($data))
			{
				throw new SoapFault("Sender", "UNEXPECTED_PREP_ERROR");
			}




			/*
				Apply Data
			*/

			// start SQL transaction
			$sql_obj = New sql_query;
			$sql_obj->trans_begin();


			// create/update item
			if (!$obj_invoice_item->id_item)
			{
				if (!$obj_invoice_item->action_create())
				{
					$sql_obj->trans_rollback();

					throw new SoapFault("Sender", "UNEXPECTED_ACTION_ERROR");
				}

			}


			if (!$obj_invoice_item->action_update())
			{
				$sql_obj->trans_rollback();

				throw new SoapFault("Sender", "UNEXPECTED_ACTION_ERROR");
			}



			// Re-calculate taxes, totals and ledgers as required
			$obj_invoice_item->action_update_tax();
			$obj_invoice_item->action_update_total();



			// Generate ledger entries.
			$obj_invoice_item->action_update_ledger();


			// commit
			if (error_check())
			{
				$sql_obj->trans_rollback();

				throw new SoapFault("Sender", "UNEXPECTED_ACTION_ERROR");
			}
			else
			{
				$sql_obj->trans_commit();

				return $obj_invoice_item->id_item;
			}

 		}
		else
		{
			throw new SoapFault("Sender", "ACCESS DENIED");
		}

	} // end of set_invoice_item_product



	/*
		set_invoice_item_time

		Creates/Updates a time invoice item

		Returns
		0	failure
		#	ID of the item
	*/
	function set_invoice_item_time($id,
					$invoicetype,
					$itemid,
					$price,
					$productid,
					$timegroupid,
					$description,
					$discount)
	{
		log_debug("accounts_invoices_manage", "Executing set_invoice_item_time($id, $invoicetype, values...)");


		// check the invoicetype
		if ($invoicetype == "ap")
		{
			throw new SoapFault("Sender", "NOT_AVAILABLE_FOR_INVOICE_TYPE");
		}

		if ($invoicetype != "ar")
		{
			throw new SoapFault("Sender", "INVALID_INVOICE_TYPE");
		}


		if (user_permissions_get("accounts_". $invoicetype ."_write"))
		{
			$obj_invoice_item			= New invoice_items;

			$obj_invoice_item->type_invoice		= $invoicetype;
			$obj_invoice_item->id_invoice		= security_script_input_predefined("int", $id);
			$obj_invoice_item->id_item		= security_script_input_predefined("any", $itemid);
			$obj_invoice_item->type_item		= "time";

			/*
				Error Handling
			*/

			// verify invoice existance
			if (!$obj_invoice_item->verify_invoice())
			{
				throw new SoapFault("Sender", "INVALID_INVOICE");
			}

			// make sure invoice is not locked
			if ($obj_invoice_item->check_lock())
			{
				throw new SoapFault("Sender", "LOCKED");
			}

			// verify item existance (if editing one)
			if ($obj_invoice_item->id_item)
			{
				if (!$obj_invoice_item->verify_item())
				{
					throw new SoapFault("Sender", "INVALID_ITEMID");
				}
			}



			/*
				Load SOAP data
			*/


			$data["price"]		= security_script_input_predefined("money", $price);
			$data["customid"]	= security_script_input_predefined("int", $productid);
			$data["timegroupid"]	= security_script_input_predefined("int", $timegroupid);
			$data["description"]	= security_script_input_predefined("any", $description);
			$data["units"]		= "hours";
			$data["discount"]	= security_script_input_predefined("float", $discount);



			foreach (array_keys($data) as $key)
			{
				if ($data[$key] == "error")
				{
					throw new SoapFault("Sender", "INVALID_INPUT");
				}
			}


			// fetch the customer ID for this invoice, so we can create
			// an array of acceptable time groups
			$customerid = sql_get_singlevalue("SELECT customerid as value FROM account_". $obj_invoice_item->type_invoice ." WHERE id='". $obj_invoice_item->id_invoice ."' LIMIT 1");
					

			// make sure the time_group is valid/available
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM time_groups "
						."WHERE customerid='$customerid' "
						."AND (invoiceitemid='0' OR invoiceitemid='". $obj_invoice_item->id_item ."') "
						."AND id='". $data["timegroupid"] ."'";

			$sql_obj->execute();

			if (!$sql_obj->num_rows())
			{
				throw new SoapFault("Sender", "INVALID_TIMEGROUPID");
			}

			unset($sql_obj);



			// process the data
			if (!$obj_invoice_item->prepare_data($data))
			{
				throw new SoapFault("Sender", "UNEXPECTED_PREP_ERROR");
			}




			/*
				Apply Data
			*/

			// start SQL transaction
			$sql_obj = New sql_query;
			$sql_obj->trans_begin();


			// create/update item
			if (!$obj_invoice_item->id_item)
			{
				if (!$obj_invoice_item->action_create())
				{
					$sql_obj->trans_rollback();

					throw new SoapFault("Sender", "UNEXPECTED_ACTION_ERROR");
				}

			}


			if (!$obj_invoice_item->action_update())
			{
				$sql_obj->trans_rollback();

				throw new SoapFault("Sender", "UNEXPECTED_ACTION_ERROR");
			}



			// Re-calculate taxes, totals and ledgers as required
			$obj_invoice_item->action_update_tax();
			$obj_invoice_item->action_update_total();



			// Generate ledger entries.
			$obj_invoice_item->action_update_ledger();



			// commit
			if (error_check())
			{
				$sql_obj->trans_rollback();

				throw new SoapFault("Sender", "UNEXPECTED_ACTION_ERROR");
			}
			else
			{
				$sql_obj->trans_commit();

				return $obj_invoice_item->id_item;
			}

 		}
		else
		{
			throw new SoapFault("Sender", "ACCESS DENIED");
		}

	} // end of set_invoice_item_time



	/*
		set_invoice_override_tax

		Overrides the amount of tax on an invoice - this is typically used to correct
		AP invoices when vendors supply invoices with $0.01 mistakes due to rounding errors.

		Note that if any changes are made to the items on the invoice, or if additional invoice
		items are added, the changes made to the tax amount will be lost and will need to be
		re-applied.

		Returns
		0	failure
		#	ID of the item
	*/
	function set_invoice_override_tax($id,
					$invoicetype,
					$itemid,
					$amount)
	{
		log_debug("accounts_invoices_manage", "Executing set_invoice_override_tax($id, $invoicetype, values...)");


		// check the invoicetype
		if ($invoicetype != "ar" && $invoicetype != "ap")
		{
			throw new SoapFault("Sender", "INVALID_INVOICE_TYPE");
		}


		if (user_permissions_get("accounts_". $invoicetype ."_write"))
		{
			$obj_invoice_item			= New invoice_items;

			$obj_invoice_item->type_invoice		= $invoicetype;
			$obj_invoice_item->id_invoice		= security_script_input_predefined("int", $id);
			$obj_invoice_item->id_item		= security_script_input_predefined("any", $itemid);
			$obj_invoice_item->type_item		= "tax";

			/*
				Error Handling
			*/

			// verify invoice existance
			if (!$obj_invoice_item->verify_invoice())
			{
				throw new SoapFault("Sender", "INVALID_INVOICE");
			}

			// make sure invoice is not locked
			if ($obj_invoice_item->check_lock())
			{
				throw new SoapFault("Sender", "LOCKED");
			}

			// verify item existance (if editing one)
			if ($obj_invoice_item->id_item)
			{
				if (!$obj_invoice_item->verify_item())
				{
					throw new SoapFault("Sender", "INVALID_ITEMID");
				}
			}

			// make sure invoice is not locked
			if ($obj_invoice_item->check_lock())
			{
				throw new SoapFault("Sender", "LOCKED");
			}



			/*
				Load tax item data

				We need to load the existing item data, update the amount field and then
				save it again.
			*/

			$obj_invoice_item->load_data();



			/*
				Load SOAP data
			*/

			$obj_invoice_item->data["amount"] = security_script_input_predefined("money", $amount);
			

			if ($obj_invoice_item->data["amount"] == "error")
			{
				throw new SoapFault("Sender", "INVALID_INPUT");
			}



			/*
				Apply Data
			*/

			// start SQL transaction
			$sql_obj = New sql_query;
			$sql_obj->trans_begin();


			// update tax amounts
			if (!$obj_invoice_item->action_update())
			{
				throw new SoapFault("Sender", "UNEXPECTED_ACTION_ERROR");
			}


			// Update invoice totals
			$obj_invoice_item->action_update_total();

			// Update ledger
			$obj_invoice_item->action_update_ledger();



			// commit
			if (error_check())
			{
				$sql_obj->trans_rollback();

				throw new SoapFault("Sender", "UNEXPECTED_ACTION_ERROR");
			}
			else
			{
				$sql_obj->trans_commit();

				return $obj_invoice_item->id_item;
			}

 		}
		else
		{
			throw new SoapFault("Sender", "ACCESS DENIED");
		}

	} // end of set_invoice_override_tax



	/*
		set_invoice_payment

		Creates/Updates a payment item.

		Returns
		0	failure
		#	ID of the item
	*/
	function set_invoice_payment($id,
					$invoicetype,
					$itemid,
					$date_trans,
					$chartid,
					$amount,
					$source,
					$description)
	{
		log_debug("accounts_invoices_manage", "Executing set_invoice_payment($id, $invoicetype, values...)");


		// check the invoicetype
		if ($invoicetype != "ar" && $invoicetype != "ap")
		{
			throw new SoapFault("Sender", "INVALID_INVOICE_TYPE");
		}


		if (user_permissions_get("accounts_". $invoicetype ."_write"))
		{
			$obj_invoice_item			= New invoice_items;

			$obj_invoice_item->type_invoice		= $invoicetype;
			$obj_invoice_item->id_invoice		= security_script_input_predefined("int", $id);
			$obj_invoice_item->id_item		= security_script_input_predefined("any", $itemid);
			$obj_invoice_item->type_item		= "payment";

			/*
				Error Handling
			*/

			// verify invoice existance
			if (!$obj_invoice_item->verify_invoice())
			{
				throw new SoapFault("Sender", "INVALID_INVOICE");
			}

			// make sure invoice is not locked
			if ($obj_invoice_item->check_lock())
			{
				throw new SoapFault("Sender", "LOCKED");
			}

			// verify item existance (if editing one)
			if ($obj_invoice_item->id_item)
			{
				if (!$obj_invoice_item->verify_item())
				{
					throw new SoapFault("Sender", "INVALID_ITEMID");
				}
			}



			/*
				Load SOAP data
			*/

			$data["date_trans"]	= security_script_input_predefined("date", $date_trans);
			$data["chartid"]	= security_script_input_predefined("int", $chartid);
			$data["amount"]		= security_script_input_predefined("money", $amount);
			$data["source"]		= security_script_input_predefined("any", $source);
			$data["description"]	= security_script_input_predefined("any", $description);


			foreach (array_keys($data) as $key)
			{
				if ($data[$key] == "error")
				{
					throw new SoapFault("Sender", "INVALID_INPUT");
				}
			}


			// process the data
			if (!$obj_invoice_item->prepare_data($data))
			{
				throw new SoapFault("Sender", "UNEXPECTED_PREP_ERROR");
			}




			/*
				Apply Data
			*/

			// create SQL transaction
			$sql_obj = New sql_query;
			$sql_obj->trans_begin();

			// create / update payment
			if (!$obj_invoice_item->id_item)
			{
				if (!$obj_invoice_item->action_create())
				{
					$sql_obj->trans_rollback();

					throw new SoapFault("Sender", "UNEXPECTED_ACTION_ERROR");
				}

			}


			if (!$obj_invoice_item->action_update())
			{
				$sql_obj->trans_rollback();

				throw new SoapFault("Sender", "UNEXPECTED_ACTION_ERROR");
			}


			// update invoice totals
			$obj_invoice_item->action_update_total();

			// Generate ledger entries.
			$obj_invoice_item->action_update_ledger();


			// commit
			if (error_check())
			{
				$sql_obj->trans_rollback();

				throw new SoapFault("Sender", "UNEXPECTED_ACTION_ERROR");
			}
			else
			{
				$sql_obj->trans_commit();

				return $obj_invoice_item->id_item;
			}

 		}
		else
		{
			throw new SoapFault("Sender", "ACCESS DENIED");
		}

	} // end of set_invoice_payment



	/*
		delete_invoice

		Deletes the selected invoice, provided that is not locked.

		Returns
		0	failure
		1	success
	*/
	function delete_invoice($id, $invoicetype)
	{
		log_debug("accounts_invoices_manage", "Executing delete_invoice($id, $invoicetype)");


		// check the invoicetype
		if ($invoicetype != "ar" && $invoicetype != "ap")
		{
			throw new SoapFault("Sender", "INVALID_INVOICE_TYPE");
		}


		if (user_permissions_get("accounts_". $invoicetype ."_write"))
		{
			$obj_invoice		= New invoice;

			/*
				Load SOAP Data
			*/
			$obj_invoice->id	= security_script_input_predefined("int", $id);
			$obj_invoice->type	= $invoicetype;



			/*
				Error Handling
			*/

			// verify invoice existance
			if (!$obj_invoice->verify_invoice())
			{
				throw new SoapFault("Sender", "INVALID_INVOICE");
			}
			
			// make sure invoice is safe to delete
			if ($obj_invoice->check_delete_lock())
			{
				throw new SoapFault("Sender", "LOCKED");
			}



			/*
				Perform Changes
			*/

			if ($obj_invoice->action_delete())
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

	} // end of delete_invoice



	/*
		delete_invoice_item

		Deletes the selected invoice item, provided that the invoice is not locked - this
		function can delete all item types (products, standard, time & tax)

		Returns
		0	failure
		1	success
	*/
	function delete_invoice_item($itemid)
	{
		log_debug("accounts_invoices_manage", "Executing delete_invoice_itemid($itemid)");

		// sanatise item ID
		$itemid = security_script_input_predefined("any", $itemid);

		// fetch the invoice ID and type
		$sql_item_obj		= New sql_query;
		$sql_item_obj->string	= "SELECT invoiceid, invoicetype FROM account_items WHERE id='". $itemid ."' LIMIT 1";
		$sql_item_obj->execute();

		if (!$sql_item_obj->num_rows())
		{
			throw new SoapFault("Sender", "INVALID_ITEMID");
		}

		$sql_item_obj->fetch_array();


		if (user_permissions_get("accounts_". $sql_item_obj->data[0]["invoicetype"] ."_write"))
		{
			$obj_invoice_item			= New invoice_items;

			$obj_invoice_item->type_invoice		= $sql_item_obj->data[0]["invoicetype"];
			$obj_invoice_item->id_invoice		= $sql_item_obj->data[0]["invoiceid"];
			$obj_invoice_item->id_item		= $itemid;


			// make sure invoice is not locked
			if ($obj_invoice_item->check_lock())
			{
				throw new SoapFault("Sender", "LOCKED");
			}


			/*
				Perform Changes
			*/

			// start SQL transaction
			$sql_obj = New sql_query;
			$sql_obj->trans_begin();


			if (!$obj_invoice_item->action_delete())
			{
				$sql_obj->trans_rollback();

				throw new SoapFault("Sender", "UNEXPECTED_ACTION_ERROR");
			}

			// re-calculate taxes, totals and ledgers as required
			$obj_invoice_item->action_update_tax();
			$obj_invoice_item->action_update_total();
			$obj_invoice_item->action_update_ledger();


			// commit
			if (error_check())
			{
				$sql_obj->trans_rollback();

				throw new SoapFault("Sender", "UNEXPECTED_ACTION_ERROR");
			}
			else
			{
				$sql_obj->trans_commit();

				return 1;
			}
		}
		else
		{
			throw new SoapFault("Sender", "ACCESS DENIED");
		}

	} // end of delete_invoice_item



} // end of invoices_manage_soap class



// define server
$server = new SoapServer("invoices_manage.wsdl");
$server->setClass("accounts_invoices_manage_soap");
$server->handle();



?>
