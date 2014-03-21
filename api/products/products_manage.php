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
		get_product_tax

		Return list of all taxes and mark whether they are enabled or not for this product.
	*/

	function get_product_tax($id)
	{
		log_debug("products_manage_soap", "Executing get_product_tax($id)");

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


			// fetch product status
			$enabled_taxes = NULL;

			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT taxid FROM products_taxes WHERE productid='$id'";
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

	} // end of get_product_tax




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

		Enables or disables the specified tax for the product

		Returns
		0	failure
		#	ID of the product
	*/
	function set_product_tax($id,
					$taxid,
					$status)
	{
		log_debug("products_manager", "Executing set_product_tax($id, values...)");

		if (user_permissions_get("products_write"))
		{
			$obj_product = New product;

			
			/*
				Load SOAP Data
			*/
			$obj_product->id	= @security_script_input_predefined("int", $id);
			$taxid			= @security_script_input_predefined("int", $taxid);
			$status			= @security_script_input_predefined("any", $status);

			foreach (array_keys($obj_product->data) as $key)
			{
				if ($obj_product->data[$key] == "error")
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

			// verify product ID
			if (!$obj_product->verify_id())
			{
				throw new SoapFault("Sender", "INVALID_ID");
			}


			/*
				Perform Changes
			*/

			// fetch product's current tax status
			$sql_product_taxes_obj		= New sql_query;
			$sql_product_taxes_obj->string	= "SELECT taxid FROM products_taxes WHERE productid='". $obj_product->id."'";

			$sql_product_taxes_obj->execute();

			if ($sql_product_taxes_obj->num_rows())
			{
				$sql_product_taxes_obj->fetch_array();

				foreach ($sql_product_taxes_obj->data as $data_tax)
				{
					$obj_product->data["tax_". $data_tax["taxid"] ] = "on";

				}
			}

			// change the status of the supplied option
			if ($status == "on")
			{
				$obj_product->data["tax_". $taxid] = "on";
			}
			else
			{
				$obj_product->data["tax_". $taxid] = "";
			}

			
			if ($obj_product->action_update_taxes())
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


} // end of products_manage_soap class



// define server
$server = new SoapServer("products_manage.wsdl");
$server->setClass("products_manage_soap");
$server->handle();



?>
