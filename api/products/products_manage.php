<?php
/*
	SOAP SERVICE -> PRODUCTS_MANAGE

	access:		products_view
			products_write

	This service provides APIs for creating, updating and deleting products.

	Refer to the Developer API documentation for information on using this service
	as well as sample code.
*/


// include libraries
include("../../include/config.php");
include("../../include/amberphplib/main.php");

// custom includes
include("../../include/products/inc_products.php");



class products_manage_soap
{

	/*
		get_product_details

		Fetch all the details for the requested product
	*/
	function get_product_details($id)
	{
		log_debug("products_manage_soap", "Executing get_product_details($id)");

		if (user_permissions_get("products_view"))
		{
			$obj_product = New product;


			// sanitise input
			$obj_product->id = @security_script_input_predefined("int", $id);

			if (!$obj_product->id || $obj_product->id == "error")
			{
				throw new SoapFault("Sender", "INVALID_INPUT");
			}


			// verify that the ID is valid
			if (!$obj_product->verify_id())
			{
				throw new SoapFault("Sender", "INVALID_ID");
			}


			// load data from DB for this product
			if (!$obj_product->load_data())
			{
				throw new SoapFault("Sender", "UNEXPECTED_ACTION_ERROR");
			}


			// to save SOAP clients from having to do another lookup to find the vendor name,
			// we fetch it now.
			if ($obj_product->data["vendorid"])
			{
				$obj_product->data["vendorid_label"] = sql_get_singlevalue("SELECT name_vendor as value FROM vendors WHERE id='". $obj_product->data["vendorid"] ."'");
			}
			
			// to save SOAP clients from having to do another lookup to find the account_sales and account_purchase
			// account names, we look them up now
			if ($obj_product->data["account_sales"])
			{
				$obj_product->data["account_sales_label"] = sql_get_singlevalue("SELECT CONCAT_WS('--', code_chart, description) as value FROM account_charts WHERE id='". $obj_product->data["account_sales"] ."'");
			}

			if ($obj_product->data["account_purchase"])
			{
				$obj_product->data["account_purchase_label"] = sql_get_singlevalue("SELECT CONCAT_WS('--', code_chart, description) as value FROM account_charts WHERE id='". $obj_product->data["account_purchase"] ."'");
			}



			// return data
			$return = array($obj_product->data["code_product"], 
					$obj_product->data["name_product"], 
					$obj_product->data["units"], 
					$obj_product->data["details"], 
					$obj_product->data["price_cost"], 
					$obj_product->data["price_sale"], 
					$obj_product->data["date_start"], 
					$obj_product->data["date_end"], 
					$obj_product->data["date_current"], 
					$obj_product->data["quantity_instock"], 
					$obj_product->data["quantity_vendor"], 
					$obj_product->data["vendorid"], 
					$obj_product->data["vendorid_label"], 
					$obj_product->data["code_product_vendor"], 
					$obj_product->data["account_sales"], 
					$obj_product->data["account_sales_label"],
					$obj_product->data["account_purchase"], 
					$obj_product->data["account_purchase_label"],
					$obj_product->data["discount"]);

			return $return;
		}
		else
		{
			throw new SoapFault("Sender", "ACCESS_DENIED");
		}

	} // end of get_product_details



	/*
		get_product_taxes

		Returns a list of all tax items belonging to the selected product
	*/
	function get_product_taxes($id)
	{
		log_debug("products_manage_soap", "Executing get_product_taxes($id)");


		if (user_permissions_get("products_view"))
		{
			$obj_product_tax = New product_tax;


			// sanitise input
			$obj_product_tax->id = @security_script_input_predefined("int", $id);

			if (!$obj_product_tax->id || $obj_product_tax->id == "error")
			{
				throw new SoapFault("Sender", "INVALID_INPUT");
			}


			// verify that the supplied product ID is valid
			if (!$obj_product_tax->verify_product_id())
			{
				throw new SoapFault("Sender", "INVALID_ID");
			}


			// fetch all the tax item data
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT * FROM products_taxes WHERE productid='". $obj_product_tax->id ."'";
			$sql_obj->execute();

			$return = NULL;
			if ($sql_obj->num_rows())
			{
				$sql_obj->fetch_array();

				// package data into array for passing back to SOAP client
				foreach ($sql_obj->data as $data)
				{
					// fetch tax_id_label value
					$data["taxid_label"] = sql_get_singlevalue("SELECT name_tax as value FROM account_taxes WHERE id='". $data["taxid"] ."'");

					// create return structure
					$return_tmp			= NULL;

					$return_tmp["itemid"]		= $data["id"];
					$return_tmp["taxid"]		= $data["taxid"];
					$return_tmp["taxid_label"]	= $data["taxid_label"];
					$return_tmp["manual_option"]	= $data["manual_option"];
					$return_tmp["manual_amount"]	= $data["manual_amount"];
					$return_tmp["description"]	= $data["description"];

					$return[] = $return_tmp;
				}
			}

			return $return;
		}
		else
		{
			throw new SoapFault("Sender", "ACCESS_DENIED");
		}

	} // end of get_product_taxes



	/*
		set_product_details

		Creates/Updates an product record.

		Returns
		0	failure
		#	ID of the product
	*/
	function set_product_details($id,
					$code_product, 
					$name_product,
					$units,
					$details,
					$price_cost,
					$price_sale,
					$date_start,
					$date_end,
					$date_current,
					$quantity_instock,
					$quantity_vendor,
					$vendorid,
					$code_product_vendor,
					$account_sales,
					$account_purchase,
					$discount)
	{
		log_debug("products_manage_soap", "Executing set_product_details($id, values...)");


		if (user_permissions_get("products_write"))
		{
			$obj_product = New product;

			
			/*
				Load SOAP Data
			*/
			$obj_product->id				= @security_script_input_predefined("int", $id);
					
			$obj_product->data["code_product"]		= @security_script_input_predefined("any", $code_product);
			$obj_product->data["name_product"]		= @security_script_input_predefined("any", $name_product);
			$obj_product->data["units"]			= @security_script_input_predefined("any", $units);
			$obj_product->data["account_sales"]		= @security_script_input_predefined("int", $account_sales);
			$obj_product->data["account_purchase"]		= @security_script_input_predefined("int", $account_purchase);

			$obj_product->data["date_start"]		= @security_script_input_predefined("date", $date_start);
			$obj_product->data["date_end"]			= @security_script_input_predefined("date", $date_end);
			$obj_product->data["date_current"]		= @security_script_input_predefined("date", $date_current);
			$obj_product->data["details"]			= @security_script_input_predefined("any", $details);
			
			$obj_product->data["price_cost"]		= @security_script_input_predefined("money", $price_cost);
			$obj_product->data["price_sale"]		= @security_script_input_predefined("money", $price_sale);
			
			$obj_product->data["quantity_instock"]		= @security_script_input_predefined("int", $quantity_instock);
			$obj_product->data["quantity_vendor"]		= @security_script_input_predefined("int", $quantity_vendor);

			$obj_product->data["vendorid"]			= @security_script_input_predefined("int", $vendorid);
			$obj_product->data["code_product_vendor"]	= @security_script_input_predefined("any", $code_product_vendor);

			$obj_product->data["discount"]			= @security_script_input_predefined("float", $discount);

			
			foreach (array_keys($obj_product->data) as $key)
			{
				if ($obj_product->data[$key] == "error" && $obj_product->data[$key] != 0)
				{
					throw new SoapFault("Sender", "INVALID_INPUT");
				}
			}



			/*
				Error Handling
			*/

			// verify product ID (if editing an existing product)
			if ($obj_product->id)
			{
				if (!$obj_product->verify_id())
				{
					throw new SoapFault("Sender", "INVALID_ID");
				}
			}

			// make sure we don't choose a product code that has already been taken
			if (!$obj_product->verify_code_product())
			{
				throw new SoapFault("Sender", "DUPLICATE_CODE_PRODUCT");
			}

			// make sure we don't choose a product name that has already been taken
			if (!$obj_product->verify_name_product())
			{
				throw new SoapFault("Sender", "DUPLICATE_NAME_PRODUCT");
			}


			/*
				Perform Changes
			*/

			if ($obj_product->action_update())
			{
				return $obj_product->id;
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

	} // end of set_product_details



	/*
		set_product_tax

		Creates/Updates a tax item assigned to a product.

		Returns
		0	failure
		#	ID of the product
	*/
	function set_product_tax($id,
					$itemid,
					$taxid,
					$manual_option,
					$manual_amount,
					$description)
	{
		log_debug("products_manage_soap", "Executing set_product_details($id, values...)");

		if (user_permissions_get("products_write"))
		{
			$obj_product_tax = New product_tax;

			
			/*
				Load SOAP Data
			*/
			$obj_product_tax->id				= @security_script_input_predefined("int", $id);
					
			$obj_product_tax->itemid			= @security_script_input_predefined("int", $itemid);
			$obj_product_tax->data["taxid"]			= @security_script_input_predefined("any", $taxid);
			$obj_product_tax->data["manual_option"]		= @security_script_input_predefined("int", $manual_option);
			$obj_product_tax->data["manual_amount"]		= @security_script_input_predefined("money", $manual_amount);
			$obj_product_tax->data["description"]		= @security_script_input_predefined("any", $description);

			
			foreach (array_keys($obj_product_tax->data) as $key)
			{
				if ($obj_product_tax->data[$key] == "error")
				{
					throw new SoapFault("Sender", "INVALID_INPUT");
				}
			}


			/*
				Error Handling
			*/
	
	
			// verify that the supplied product ID is valid
			if (!$obj_product_tax->verify_product_id())
			{
				throw new SoapFault("Sender", "INVALID_ID");
			}


			// verify that the item exists (if supplied)
			if ($obj_product_tax->itemid)
			{
				if (!$obj_product_tax->verify_item_id())
				{
					throw new SoapFault("Sender", "INVALID_ID");
				}
			}


			/*
				Perform Changes
			*/

			if ($obj_product_tax->action_update())
			{
				return $obj_product_tax->itemid;
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

	} // end of set_product_tax



	/*
		delete_product

		Deletes an product, provided that the product is not locked.

		Returns
		0	failure
		1	success
	*/
	function delete_product($id)
	{
		log_debug("products", "Executing delete_product_details($id)");

		if (user_permissions_get("products_write"))
		{
			$obj_product = New product;

			
			/*
				Load SOAP Data
			*/
			$obj_product->id = @security_script_input_predefined("int", $id);

			if (!$obj_product->id || $obj_product->id == "error")
			{
				throw new SoapFault("Sender", "INVALID_INPUT");
			}



			/*
				Error Handling
			*/

			// verify product ID
			if (!$obj_product->verify_id())
			{
				throw new SoapFault("Sender", "INVALID_ID");
			}


			// check that the product can be safely deleted
			if ($obj_product->check_delete_lock())
			{
				throw new SoapFault("Sender", "LOCKED");
			}



			/*
				Perform Changes
			*/
			if ($obj_product->action_delete())
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

	} // end of delete_product



	/*
		delete_product_tax

		Deletes a tax from a product. It does not matter whether the product is
		locked or not, since taxes only affect products when they are first added
		to invoices.

		Returns
		0	failure
		1	success
	*/
	function delete_product_tax($id, $itemid)
	{
		log_debug("products", "Executing delete_product_tax($id, $itemid)");

		if (user_permissions_get("products_write"))
		{
			$obj_product_tax = New product_tax;

			
			/*
				Load SOAP Data
			*/

			$obj_product_tax->id		= @security_script_input_predefined("int", $id);
			$obj_product_tax->itemid	= @security_script_input_predefined("int", $itemid);

			if (!$obj_product_tax->id || $obj_product_tax->id == "error")
			{
				throw new SoapFault("Sender", "INVALID_INPUT");
			}

			if (!$obj_product_tax->itemid || $obj_product_tax->itemid == "error")
			{
				throw new SoapFault("Sender", "INVALID_INPUT");
			}



			/*
				Error Handling
			*/

			// verify product ID
			if (!$obj_product_tax->verify_product_id())
			{
				throw new SoapFault("Sender", "INVALID_ID");
			}


			// verify tax item ID	
			if (!$obj_product_tax->verify_item_id())
			{
				throw new SoapFault("Sender", "INVALID_ID");
			}



			/*
				Perform Changes
			*/
			if ($obj_product_tax->action_delete())
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

	} // end of delete_product_tax



} // end of products_manage_soap class



// define server
$server = new SoapServer("products_manage.wsdl");
$server->setClass("products_manage_soap");
$server->handle();



?>
