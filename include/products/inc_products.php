<?php
/*
	include/accounts/inc_products.php

	Provides functions and classes for working with products.
*/


/*
	CLASSES
*/




/*
	CLASS: product

	Provides functions for managing products.
*/

class product
{
	var $id;		// holds product ID

	var $data;		// holds values of record fields



	/*
		verify_id

		Checks that the provided ID is a valid product

		Results
		0	Failure to find the ID
		1	Success - product exists
	*/

	function verify_id()
	{
		log_debug("inc_products", "Executing verify_id()");

		if ($this->id)
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM `products` WHERE id='". $this->id ."' LIMIT 1";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				return 1;
			}
		}

		return 0;

	} // end of verify_id



	/*
		verify_code_product

		Checks that the code_product value supplied has not already been taken.

		Results
		0	Failure - code is in use
		1	Success - code is available
	*/

	function verify_code_product()
	{
		log_debug("inc_products", "Executing verify_code_product()");

		$sql_obj			= New sql_query;
		$sql_obj->string		= "SELECT id FROM `products` WHERE code_product='". $this->data["code_product"] ."' ";

		if ($this->id)
			$sql_obj->string	.= " AND id!='". $this->id ."'";

		$sql_obj->string		.= " LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			return 0;
		}
		
		return 1;

	} // end of verify_code_product



	/*
		verify_name_product

		Checks that the name_product value supplied has not already been taken.

		Results
		0	Failure - name is in use
		1	Success - name is available
	*/

	function verify_name_product()
	{
		log_debug("inc_products", "Executing verify_name_product()");

		$sql_obj			= New sql_query;
		$sql_obj->string		= "SELECT id FROM `products` WHERE name_product='". $this->data["name_product"] ."' ";

		if ($this->id)
			$sql_obj->string	.= " AND id!='". $this->id ."'";

		$sql_obj->string		.= " LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			return 0;
		}
		
		return 1;

	} // end of verify_name_product





	/*
		check_delete_lock

		Returns whether the product can be safely deleted or not.

		Results
		0	Unlocked
		1	Locked
	*/

	function check_delete_lock()
	{
		log_debug("inc_products", "Executing check_delete_lock()");

		// check if the product belongs to any invoices
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_items WHERE (type='product' OR type='time') AND customid='". $this->id ."'";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			return 1;
		}
	
		unset($sql_obj);



		// unlocked
		return 0;

	}  // end of check_delete_lock



	/*
		load_data

		Load the product's information into the $this->data array.

		Returns
		0	failure
		1	success
	*/
	function load_data()
	{
		log_debug("inc_products", "Executing load_data()");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT * FROM products WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$sql_obj->fetch_array();

			$this->data = $sql_obj->data[0];

			return 1;
		}

		// failure
		return 0;

	} // end of load_data





	/*
		action_create

		Create a new product based on the data in $this->data

		Results
		0	Failure
		#	Success - return ID
	*/
	function action_create()
	{
		log_debug("inc_products", "Executing action_create()");

		// create a new product
		$sql_obj		= New sql_query;
		$sql_obj->string	= "INSERT INTO `products` (name_product) VALUES ('". $this->data["name_product"]. "')";
		$sql_obj->execute();

		$this->id = $sql_obj->fetch_insert_id();


		return $this->id;

	} // end of action_create



	/*
		action_update

		Update a product's details based on the data in $this->data. If no ID is provided,
		it will first call the action_create function.

		Returns
		0	failure
		#	success - returns the ID
	*/
	function action_update()
	{
		log_debug("inc_products", "Executing action_update()");


		/*
			Start Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();


		/*
			If no ID exists, create a new product first

			(Note: if this function has been called by the action_update() wrapper function
			this step will already have been performed and we can just ignore it)
		*/
		if (!$this->id)
		{
			$mode = "create";

			if (!$this->action_create())
			{
				return 0;
			}
		}
		else
		{
			$mode = "update";
		}


		/*
			All products require a code_product value. If one has not been provided, automatically
			generate one
		*/

		if (!$this->data["code_product"])
		{
			$this->data["code_product"] = config_generate_uniqueid("CODE_PRODUCT", "SELECT id FROM products WHERE code_product='VALUE'");
		}



		/*
			Update product details
		*/

		$sql_obj->string	= "UPDATE `products` SET "
						."name_product='". $this->data["name_product"] ."', "
						."units='". $this->data["units"] ."', "
						."code_product='". $this->data["code_product"] ."', "
						."account_sales='". $this->data["account_sales"] ."', "
						."account_purchase='". $this->data["account_purchase"] ."', "
						."date_start='". $this->data["date_start"] ."', "
						."date_end='". $this->data["date_end"] ."', "
						."date_current='". $this->data["date_current"] ."', "
						."details='". $this->data["details"] ."', "
						."price_cost='". $this->data["price_cost"] ."', "
						."price_sale='". $this->data["price_sale"] ."', "
						."quantity_instock='". $this->data["quantity_instock"] ."', "
						."quantity_vendor='". $this->data["quantity_vendor"] ."', "
						."vendorid='". $this->data["vendorid"] ."', "
						."code_product_vendor='". $this->data["code_product_vendor"] ."' "
						."WHERE id='". $this->id ."' LIMIT 1";

		$sql_obj->execute();



		/*
			Update Journal
		*/

		if ($mode == "update")
		{
			journal_quickadd_event("products", $this->id, "Product details updated.");
		}
		else
		{
			journal_quickadd_event("products", $this->id, "Product created.");
		}


	
		/*
			Commit
		*/
		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "process", "An error occured whilst attempting to update the product. No changes were made.");

			return 0;
		}
		else
		{
			$sql_obj->trans_commit();

			if ($mode == "update")
			{
				log_write("notification", "inc_products", "Product successfully updated.");
			}
			else
			{
				log_write("notification", "inc_products", "Product successfully created.");
			}


			return $this->id;
		}

	} // end of action_update_details



	/*
		action_delete

		Deletes a product.

		Note: the check_delete_lock function should be executed before calling
		this function to ensure database integrity.

		Results
		0	failure
		1	success
	*/
	function action_delete()
	{
		log_debug("inc_products", "Executing action_delete()");

		// start transaction
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();


		// delete the product
		$sql_obj->string	= "DELETE FROM products WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();


		// delete the product taxes
		$sql_obj->string	= "DELETE FROM products_taxes WHERE productid='". $this->id ."'";
		$sql_obj->execute();


		// delete product journal
		journal_delete_entire("products", $this->id);


		// commit
		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error". "inc_products". "An error occured whilst attempting to delete the product. No changes have been made.");
		}
		else
		{
			$sql_obj->trans_commit();

			log_write("notification", "inc_products", "Product has been successfully deleted.");
		}

		return 1;
	}


} // end of class:product





/*
	CLASS: product_tax

	Provides functions for managing product tax items
*/

class product_tax
{
	var $id;		// holds product ID
	var $itemid;		// holds ID of tax item (if any is selected)

	var $data;		// holds values of record fields



	/*
		verify_product_id

		Checks that the provided ID is a valid product

		Results
		0	Failure to find the ID
		1	Success - product exists
	*/

	function verify_product_id()
	{
		log_debug("inc_products", "Executing verify_product_id()");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM `products` WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			return 1;
		}

		return 0;

	} // end of verify_id



	/*
		verify_item_id

		Checks that the provided ID is a valid item

		Results
		0	Failure to find the ID
		1	Success - tax item exists
	*/

	function verify_item_id()
	{
		log_debug("inc_products", "Executing verify_item_id()");

		if ($this->itemid)
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM `products_taxes` WHERE id='". $this->itemid ."' LIMIT 1";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				return 1;
			}
		}

		return 0;

	} // end of verify_item_id



	/*
		load_data

		Load the tax item's information into the $this->data array.

		Returns
		0	failure
		1	success
	*/
	function load_data()
	{
		log_debug("inc_products", "Executing load_data()");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT * FROM products_taxes WHERE id='". $this->itemid ."' LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$sql_obj->fetch_array();

			$this->data = $sql_obj->data[0];

			return 1;
		}

		// failure
		return 0;

	} // end of load_data



	/*
		action_create

		Create a new tax item based on the data in $this->data

		Results
		0	Failure
		#	Success - return ID
	*/
	function action_create()
	{
		log_debug("inc_products", "Executing action_create()");

		// create a new product
		$sql_obj		= New sql_query;
		$sql_obj->string	= "INSERT INTO `products_taxes` (taxid) VALUES ('". $this->data["taxid"]. "')";
		$sql_obj->execute();

		$this->itemid = $sql_obj->fetch_insert_id();

		return $this->itemid;

	} // end of action_create



	/*
		action_update

		Update the details of the a tax item based on the data in $this->data. If no ID is provided,
		it will first call the action_create function.

		Returns
		0	failure
		#	success - returns the item ID
	*/
	function action_update()
	{
		log_debug("inc_products", "Executing action_update()");

		/*
			Start Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();


		/*
			If no ID exists, create a new tax item first
		*/
		if (!$this->itemid)
		{
			$mode = "create";

			if (!$this->action_create())
			{
				return 0;
			}
		}
		else
		{
			$mode = "update";
		}
		
		/*
			Convert data inputs
		*/
		if ($this->data["manual_option"])
		{
			$this->data["manual_option"] = 1;
		}


		/*
			Update tax item details
		*/

		$sql_obj->string	= "UPDATE `products_taxes` SET "
						."taxid='". $this->data["taxid"] ."', "
						."description='". $this->data["description"] ."', "
						."manual_amount='". $this->data["manual_amount"] ."', "
						."manual_option='". $this->data["manual_option"] ."', "
						."productid='". $this->id ."' "
						."WHERE id='". $this->itemid ."' LIMIT 1";

		$sql_obj->execute();


		/*
			Update the Journal
		*/
		if ($mode == "update")
		{
			journal_quickadd_event("products", $this->id, "Tax item details updated.");
		}
		else
		{
			journal_quickadd_event("products", $this->id, "New tax item added.");
		}



		/*
			Commit
		*/
		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "process", "An error occured whilst updating product details. No changes were made.");

			return 0;
		}
		else
		{
			$sql_obj->trans_commit();

			if ($mode == "update")
			{
				log_write("notification", "inc_products", "Tax item successfully updated.");
			}
			else
			{
				log_write("notification", "inc_products", "Tax item successfully created.");
			}


			// success
			return $this->itemid;
		}



	} // end of action_update



	/*
		action_delete

		Deletes a tax item

		Results
		0	failure
		1	success
	*/
	function action_delete()
	{
		log_debug("inc_products", "Executing action_delete()");


		// delete the tax item
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM products_taxes WHERE id='". $this->itemid ."' LIMIT 1";
			
		if (!$sql_obj->execute())
		{
			log_write("error", "inc_products", "A fatal error occured whilst trying to delete the product");
			return 0;
		}

		// success
		log_write("notification", "inc_products", "Tax item has been successfully deleted.");

		return 1;
	}


} // end of class:product_tax






?>
