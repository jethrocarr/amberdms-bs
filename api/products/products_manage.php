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
			$obj_product->id = security_script_input_predefined("int", $id);

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

			// to save SOAP clients from having to do another lookup to find the account_sales
			// chart name, we fetch it now
			if ($obj_product->data["account_sales"])
			{
				$obj_product->data["account_sales_label"] = sql_get_singlevalue("SELECT CONCAT_WS('--', code_chart, description) as value FROM account_charts WHERE id='". $obj_product->data["account_sales"] ."'");
			}


			// return data
			$return = array($obj_product->data["code_product"], 
					$obj_product->data["name_product"], 
					$obj_product->data["details"], 
					$obj_product->data["price_cost"], 
					$obj_product->data["price_sale"], 
					$obj_product->data["date_current"], 
					$obj_product->data["quantity_instock"], 
					$obj_product->data["quantity_vendor"], 
					$obj_product->data["vendorid"], 
					$obj_product->data["vendorid_label"], 
					$obj_product->data["code_product_vendor"], 
					$obj_product->data["account_sales"], 
					$obj_product->data["account_sales_label"]);

			return $return;
		}
		else
		{
			throw new SoapFault("Sender", "ACCESS_DENIED");
		}

	} // end of get_product_details



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
					$details,
					$price_cost,
					$price_sale,
					$date_current,
					$quantity_instock,
					$quantity_vendor,
					$vendorid,
					$code_product_vendor,
					$account_sales)
	{
		log_debug("accounts_products_manage", "Executing set_product_details($id, values...)");

		if (user_permissions_get("products_write"))
		{
			$obj_product = New product;

			
			/*
				Load SOAP Data
			*/
			$obj_product->id				= security_script_input_predefined("int", $id);
					
			$obj_product->data["code_product"]		= security_script_input_predefined("any", $code_product);
			$obj_product->data["name_product"]		= security_script_input_predefined("any", $name_product);
			$obj_product->data["account_sales"]		= security_script_input_predefined("int", $account_sales);

			$obj_product->data["date_current"]		= security_script_input_predefined("date", $date_current);
			$obj_product->data["details"]			= security_script_input_predefined("any", $details);
			
			$obj_product->data["price_cost"]		= security_script_input_predefined("money", $price_cost);
			$obj_product->data["price_sale"]		= security_script_input_predefined("money", $price_sale);
			
			$obj_product->data["quantity_instock"]		= security_script_input_predefined("int", $quantity_instock);
			$obj_product->data["quantity_vendor"]		= security_script_input_predefined("int", $quantity_vendor);

			$obj_product->data["vendorid"]			= security_script_input_predefined("int", $vendorid);
			$obj_product->data["code_product_vendor"]	= security_script_input_predefined("any", $code_product_vendor);

			
			foreach (array_keys($obj_product->data) as $key)
			{
				if ($obj_product->data[$key] == "error")
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
		delete_product

		Deletes an product, provided that the product is not locked.

		Returns
		0	failure
		1	success
	*/
	function delete_product($id)
	{
		log_debug("products", "Executing delete_product_details($id, values...)");

		if (user_permissions_get("products_write"))
		{
			$obj_product = New product;

			
			/*
				Load SOAP Data
			*/
			$obj_product->id = security_script_input_predefined("int", $id);

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


} // end of products_manage_soap class



// define server
$server = new SoapServer("products_manage.wsdl");
$server->setClass("products_manage_soap");
$server->handle();



?>
