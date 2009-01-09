<?php
/*
	hr/inc_customers.php

	Provides classes for managing customers.
*/




/*
	CLASS: customer

	Provides functions for managing customers
*/

class customer
{
	var $id;		// holds customer ID
	var $data;		// holds values of record fields



	/*
		verify_id

		Checks that the provided ID is a valid customer

		Results
		0	Failure to find the ID
		1	Success - employee exists
	*/

	function verify_id()
	{
		log_debug("inc_customers", "Executing verify_id()");

		if ($this->id)
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM `customers` WHERE id='". $this->id ."' LIMIT 1";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				return 1;
			}
		}

		return 0;

	} // end of verify_id



	/*
		verify_name_customer

		Checks that the name_customer value supplied has not already been taken.

		Results
		0	Failure - name in use
		1	Success - name is available
	*/

	function verify_name_customer()
	{
		log_debug("inc_customers", "Executing verify_name_customer()");

		$sql_obj			= New sql_query;
		$sql_obj->string		= "SELECT id FROM `customers` WHERE name_customer='". $this->data["name_customer"] ."' ";

		if ($this->id)
			$sql_obj->string	.= " AND id!='". $this->id ."'";

		$sql_obj->string		.= " LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			return 0;
		}
		
		return 1;

	} // end of verify_name_customer



	/*
		verify_code_customer

		Checks that the code_customer value supplied has not already been taken.

		Results
		0	Failure - name in use
		1	Success - name is available
	*/

	function verify_code_customer()
	{
		log_debug("inc_customers", "Executing verify_code_customer()");

		$sql_obj			= New sql_query;
		$sql_obj->string		= "SELECT id FROM `customers` WHERE code_customer='". $this->data["code_customer"] ."' ";

		if ($this->id)
			$sql_obj->string	.= " AND id!='". $this->id ."'";

		$sql_obj->string		.= " LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			return 0;
		}
		
		return 1;

	} // end of verify_code_customer



	/*
		verify_date_end

		Do not permit the customer to be set to closed if they still have active services on their account.

		Results
		0	Unable to close
		1	OK to close
	*/

	function verify_date_end()
	{
		log_debug("inc_customers", "Executing verify_date_end()");

		if ($this->id && $this->data["date_end"] != "0000-00-00")
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM services_customers WHERE customerid='". $this->id ."' AND active='1'";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				return 0;
			}
		}

		return 1;

	} // end of verify_date_end





	/*
		check_delete_lock

		Checks if the employee is safe to delete or not

		Results
		0	Unlocked
		1	Locked
	*/

	function check_delete_lock()
	{
		log_debug("inc_customers", "Executing check_delete_lock()");


		// make sure customer does not belong to any invoices
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_ar WHERE customerid='". $this->id ."'";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			return 1;
		}

		unset($sql_obj);


		// make sure customer has no time groups assigned to it
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM time_groups WHERE customerid='". $this->id ."'";
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

		Load the customer's information into the $this->data array.

		Returns
		0	failure
		1	success
	*/
	function load_data()
	{
		log_debug("inc_customers", "Executing load_data()");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT * FROM customers WHERE id='". $this->id ."' LIMIT 1";
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

		Create a new customer based on the data in $this->data

		Results
		0	Failure
		#	Success - return ID
	*/
	function action_create()
	{
		log_debug("inc_customers", "Executing action_create()");

		// create a new customer
		$sql_obj		= New sql_query;
		$sql_obj->string	= "INSERT INTO `customers` (name_customer) VALUES ('". $this->data["name_customer"]. "')";
		$sql_obj->execute();

		$this->id = $sql_obj->fetch_insert_id();

		return $this->id;

	} // end of action_create




	/*
		action_update

		Update a customer's details based on the data in $this->data. If no ID is provided,
		it will first call the action_create function.

		Returns
		0	failure
		#	success - returns the ID
	*/
	function action_update()
	{
		log_debug("inc_customers", "Executing action_update()");


		// if no ID exists, create a new customer first
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


		// create a unique customer code if none already exist
		if (!$this->data["code_customer"])
		{
			$this->data["code_customer"] = config_generate_uniqueid("CODE_CUSTOMER", "SELECT id FROM customers WHERE code_customer='VALUE'");
		}
	

		// update
		$sql_obj		= New sql_query;
		$sql_obj->string	= "UPDATE `customers` SET "
						."code_customer='". $this->data["code_customer"] ."', "
						."name_customer='". $this->data["name_customer"] ."', "
						."name_contact='". $this->data["name_contact"] ."', "
						."contact_phone='". $this->data["contact_phone"] ."', "
						."contact_email='". $this->data["contact_email"] ."', "
						."contact_fax='". $this->data["contact_fax"] ."', "
						."date_start='". $this->data["date_start"] ."', "
						."date_end='". $this->data["date_end"] ."', "
						."tax_number='". $this->data["tax_number"] ."', "
						."tax_default='". $this->data["tax_default"] ."', "
						."address1_street='". $this->data["address1_street"] ."', "
						."address1_city='". $this->data["address1_city"] ."', "
						."address1_state='". $this->data["address1_state"] ."', "
						."address1_country='". $this->data["address1_country"] ."', "
						."address1_zipcode='". $this->data["address1_zipcode"] ."', "
						."address2_street='". $this->data["address2_street"] ."', "
						."address2_city='". $this->data["address2_city"] ."', "
						."address2_state='". $this->data["address2_state"] ."', "
						."address2_country='". $this->data["address2_country"] ."', "
						."address2_zipcode='". $this->data["address2_zipcode"] ."' "
						."WHERE id='". $this->id ."'";
		if (!$sql_obj->execute())
		{
			return 0;
		}

		unset($sql_obj);


		// add journal entry
		if ($mode == "update")
		{
			journal_quickadd_event("customers", $this->id, "Customer details updated.");
			log_write("notification", "inc_customers", "Customer details successfully updated.");
		}
		else
		{
			journal_quickadd_event("customers", $this->id, "Initial Account Creation.");
			log_write("notification", "inc_customers", "Customer successfully created.");
		}


		// success
		return $this->id;

	} // end of action_update



	/*
		action_delete

		Deletes a customer.

		Note: the check_delete_lock function should be executed before calling this function to ensure database integrity.


		Results
		0	failure
		1	success
	*/
	function action_delete()
	{
		log_debug("inc_customers", "Executing action_delete()");


		/*
			Delete Customer
		*/
			
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM customers WHERE id='". $this->id ."'";
			
		if (!$sql_obj->execute())
		{
			log_write("error", "inc_customers", "A fatal SQL error occured whilst trying to delete the customer");
			return 0;
		}


		/*
			Delete Journal
		*/
		journal_delete_entire("customers", $this->id);


		log_write("notification", "inc_customers", "Customer has been successfully deleted.");

		return 1;
	}


} // end of class:customers


?>
