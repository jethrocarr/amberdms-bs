<?php
/*
	customers/inc_customers.php

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
		1	Success - customer exists
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
		verify_uniqueness_contact

		Checks that each contact has a unique name

		Results
		$j [index of dup]	Failure - name has been assigned to two contacts
		unique			Success - contact name is unique
	*/
	function verify_uniqueness_contact ($index)
	{
		log_debug("inc_customers", "Executing verify_uniqueness_contact($index)");

		$unique = "unique";
		if ($this->data["contacts"][$index]["delete_contact"] == "true")
		{
			$unique = "unique";
		}
		else
		{
			$name = $this->data["contacts"][$index]["contact"];
			for ($j=($this->data["num_contacts"]-1); $j > $index; $j--)
			{
				if ($this->data["contacts"][$j]["contact"] == $name && $this->data["contacts"][$j]["delete_contact"] == "false")
				{
					$unique = $j;
				}
			}
		}
		
		return $unique;
	}
	
	
	/*
		verify_name_contact

		Checks that each contact is assigned a name

		Results
		0	Failure - name field is empty
		1	Success - contact has been named
	*/
	function verify_name_contact ($index)
	{
		log_debug("inc_customers", "Executing verify_name_contact($index)");

		if ($this->data["contacts"][$index]["delete_contact"] == "true")
		{
			return 1;
		}
		else if (empty($this->data["contacts"][$index]["contact"]))
		{
			return 0;
		}
		else
		{
			return 1;
		}
	}



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

		Checks if the customer is safe to delete or not

		Results
		0	Unlocked
		1	Locked
	*/

	function check_delete_lock()
	{
		log_debug("inc_customers", "Executing check_delete_lock()");


		// make sure customer does not belong to any invoices
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_ar WHERE customerid='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			return 1;
		}

		unset($sql_obj);


		// make sure customer has no time groups assigned to it
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM time_groups WHERE customerid='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			return 1;
		}

		unset($sql_obj);


		// make sure customer has no services - services need to be removed before deleting the account
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM services_customers WHERE customerid='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			return 1;
		}

		unset($sql_obj);


		// make sue the customer has no orders - orders need to be removed before deleting the account.
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM customers_orders WHERE id_customer='". $this->id ."' LIMIT 1";
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
		/*
			Start Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();



		/*
			If no ID supplied, create a new customer first
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


		// create a unique customer code if none already exist
		if (!$this->data["code_customer"])
		{
			$this->data["code_customer"] = config_generate_uniqueid("CODE_CUSTOMER", "SELECT id FROM customers WHERE code_customer='VALUE'");
		}
	


		/*
			Update Customer Details
		*/

		$sql_obj->string	= "UPDATE `customers` SET "
						."code_customer='". $this->data["code_customer"] ."', "
						."name_customer='". $this->data["name_customer"] ."', "
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
						."address2_zipcode='". $this->data["address2_zipcode"] ."', "
						."reseller_customer='". $this->data["reseller_customer"] ."', "
						."reseller_id='". $this->data["reseller_id"] ."', "
						."discount='". $this->data["discount"] ."' "
						."WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		
		for ($i=0; $i < $this->data["num_contacts"]; $i++)
		{
			if (empty($this->data["contacts"][$i]["contact_id"]) && 
				$this->data["contacts"][$i]["delete_contact"] == "false" &&
				!empty($this->data["contacts"][$i]["contact"]))
			{
				// create new contact
				$this->action_create_contact($i);
			}
			else if ($this->data["contacts"][$i]["delete_contact"] == "true")
			{
				// delete contact
				$this->action_delete_contact($i);
			}
			else
			{
				// update contact
				$this->action_update_contact($i);
			}
		}

		/*
			Update the journal
		*/

		if ($mode == "update")
		{
			journal_quickadd_event("customers", $this->id, "Customer details updated.");
		}
		else
		{
			journal_quickadd_event("customers", $this->id, "Initial Account Creation.");
		}



		/*
			Commit
		*/

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "inc_customers", "An error occurred when updating customer details.");

			return 0;
		}
		else
		{
			$sql_obj->trans_commit();

			if ($mode == "update")
			{
				log_write("notification", "inc_customers", "Customer details successfully updated.");
			}
			else
			{
				log_write("notification", "inc_customers", "Customer successfully created.");
			}
			
			return $this->id;
		}

	} // end of action_update
	
	
	
	/*
	  	action_update_contact($index)
	 
	 	Updates the contact
	 */
	function action_update_contact($index)
	{
		log_debug("inc_customers", "Executing action_update_contact($index)");


		$sql_obj = New sql_query;
	
		$sql_obj->string	= "UPDATE customer_contacts SET
						contact = '" .$this->data["contacts"][$index]["contact"]. "', 
						description = '" .$this->data["contacts"][$index]["description"]. "', 
						role = '" .$this->data["contacts"][$index]["role"]. "'
						WHERE id = '" .$this->data["contacts"][$index]["contact_id"]. "'";		
		$sql_obj->execute();
		
		//create records
		for ($i=0; $i<$this->data["contacts"][$index]["num_records"]; $i++)
		{
			if (empty($this->data["contacts"][$index]["records"][$i]["record_id"]) && $this->data["contacts"][$index]["records"][$i]["delete"] == "false")
			{
				$this->action_create_record($index, $i);
			}
			else if ($this->data["contacts"][$index]["records"][$i]["delete"] == "true")
			{
				$this->action_delete_record($index, $i);
			}
			else
			{
				$this->action_update_record($index, $i);
			}
		}
	}
	
	
	
	/*
	  	action_create_contact($index)
	 
	 	Creates a new contact
	 */
	function action_create_contact($index)
	{
		log_debug("inc_customers", "Executing action_create_contact($index)");


		$sql_obj = New sql_query;		
		$sql_obj->string	= "INSERT INTO customer_contacts(customer_id, contact, description, role)
						VALUES ('" .$this->id. "', '" .$this->data["contacts"][$index]["contact"]. "', '" .$this->data["contacts"][$index]["description"]. "', '" .$this->data["contacts"][$index]["role"]. "')";
		$sql_obj->execute();
		
		$this->data["contacts"][$index]["contact_id"] = $sql_obj->fetch_insert_id();
		
		for ($i=0; $i<$this->data["contacts"][$index]["num_records"]; $i++)
		{
			if ($this->data["contacts"][$index]["records"][$i]["delete"] == "false")
			{
				$this->action_create_record($index, $i);
			}
		}
	}
	
	
	
	/*
	 	action_delete_contact($index)
	 	
	 	Deletes a contact
	 */
	function action_delete_contact($index)
	{
		log_debug("inc_customers", "Executing action_delete_contact($index)");

		$sql_obj = New sql_query;		
		$sql_obj->string	= "DELETE FROM customer_contacts WHERE id ='" .$this->data["contacts"][$index]["contact_id"]. "'";
		$sql_obj->execute();
		
		$sql_obj->string	= "DELETE FROM customer_contact_records WHERE contact_id ='" .$this->data["contacts"][$index]["contact_id"]. "'";
		$sql_obj->execute();
		
		$this->num_deleted_contacts++;
	}
	
	
	
	/*
	 	action_update_record($contact_index, $record_index)
	 	
	 	Updates an existing contact record
	 */
	function action_update_record($contact_index, $record_index)
	{
		log_debug("inc_customers", "Executing action_update_record($contact_index, $record_index)");

		$sql_obj 	= New sql_query;
		
		$sql_obj->string	= "UPDATE customer_contact_records SET
						detail = '" .$this->data["contacts"][$contact_index]["records"][$record_index]["detail"]. "'
						WHERE id = '" .$this->data["contacts"][$contact_index]["records"][$record_index]["record_id"]. "'";
		$sql_obj->execute();
	}
	
	
	/*
	 	action_create_record($contact_index, $record_index)
	 	
	 	Creates a new contact record
	 */
	function action_create_record($contact_index, $record_index)
	{
		$sql_obj = New sql_query;
		$sql_obj->string	= "INSERT INTO customer_contact_records(contact_id, type, label, detail)
						VALUES('" .$this->data["contacts"][$contact_index]["contact_id"]. "', '"
						.$this->data["contacts"][$contact_index]["records"][$record_index]["type"]. "', '"
						.$this->data["contacts"][$contact_index]["records"][$record_index]["label"]. "', '"
						.$this->data["contacts"][$contact_index]["records"][$record_index]["detail"]. "')";
		$sql_obj->execute();
	}
	
	
	
	/*
	 	action_delete_record($contact_index, $record_index)
	 	
	 	Deletes a record
	 */
	function action_delete_record($contact_index, $record_index)
	{
		$sql_obj = New sql_query;		
		$sql_obj->string	= "DELETE FROM customer_contact_records WHERE id='" .$this->data["contacts"][$contact_index]["records"][$record_index]["record_id"]. "'";
		$sql_obj->execute();
	}


	
	/*
		action_update_taxes

		Updates the customer tax selection options
	*/
	function action_update_taxes()
	{
		log_debug("inc_customers", "Executing action_update_taxes()");

		// start transaction
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();

		// delete existing tax options
		$sql_obj->string	= "DELETE FROM customers_taxes WHERE customerid='". $this->id ."'";
		$sql_obj->execute();

		// if the customer has selected a default tax, make sure the default tax is enabled.
		if ($this->data["tax_default"])
		{
			$this->data["tax_". $this->data["tax_default"] ] = "on";
		}

		// run through all the taxes and if the user has selected the tax to be enabled, enable it
		$sql_taxes_obj		= New sql_query;
		$sql_taxes_obj->string	= "SELECT id FROM account_taxes";
		$sql_taxes_obj->execute();

		if ($sql_taxes_obj->num_rows())
		{
			$sql_taxes_obj->fetch_array();

			foreach ($sql_taxes_obj->data as $data_tax)
			{
				if ($this->data["tax_". $data_tax["id"]])
				{
					// enable tax for customer
					$sql_obj->string	= "INSERT INTO customers_taxes (customerid, taxid) VALUES ('". $this->id ."', '". $data_tax["id"] ."')";
					$sql_obj->execute();		
				}
			}
		}

		// commit
		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "inc_customers", "A fatal error occured whilst attempting to update customer tax information.");

			return 0;
		}
		else
		{
			$sql_obj->trans_commit();

			return 1;
		}
	}



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
			Start Transaction
		*/

		$sql_obj = New sql_query;
		$sql_obj->trans_begin();


		/*
			Delete Customer
		*/
			
		$sql_obj->string	= "DELETE FROM customers WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();


		/*
			Delete customer taxes
		*/
		
		$sql_obj->string	= "DELETE FROM customers_taxes WHERE customerid='". $this->id ."'";
		$sql_obj->execute();
		
		/*
		 	Delete customer contacts and records
		 */
		
		$sql_obj->string	= "SELECT id from customer_contacts WHERE id='" .$this->id. "'";
		$sql_obj->execute();
		$sql_obj->fetch_array();		
		foreach ($sql_obj->data as $data)
		{
			$sql_obj->string	= "DELETE FROM customer_contact_records WHERE contact_id='" .$data["id"]. "'";
			$sql_obj->execute();
		}
		
		$sql_obj->string	= "DELETE FROM customer_contacts WHERE id='" .$this->id. "'";
		$sql_obj->execute();
		

		/*
			Delete Journal
		*/

		journal_delete_entire("customers", $this->id);


		/*
			Commit
		*/
		
		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "inc_customers", "An error occured whilst trying to delete the customer.");

			return 0;
		}
		else
		{
			$sql_obj->trans_commit();

			log_write("notification", "inc_customers", "Customer has been successfully deleted.");

			return 1;
		}
	}


} // end of class:customer




/*
	CLASS customer_services

	Functions for assigning services to customers, has logic for adding multiple services when a bundle is added to a customer.
*/
class customer_services extends customer
{
	var $id_service_customer;		// ID of service assigned to customer

	var $obj_service;



	/*
		Constructor
	*/
	function customer_services()
	{
		log_write("debug", "customer_services", "Executing customer_services()");

		// init service object
		$this->obj_service = New service_bundle;

	}



	/*
		service_list

		Returns an array of all the service IDs that have been assigned to the customer.

		Returns
		0		Failure
		array		Returns array of id_service_customers
	*/
	function service_list()
	{
		log_write("debug", "service_bundle", "Executing service_list()");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM `services_customers` WHERE customerid='". $this->id  ."'";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$sql_obj->fetch_array();

			$return = array();

			foreach ($sql_obj->data as $data)
			{
				$return[] = $data["id"];
			}
		}

		return $return;
	}



	/*
		verify_id_service_customer

		Checks that the provided customer-service ID is valid and belongs to the selected customer, or if no customer is selected,
		it will fetch the ID for the customer.

		Results
		0	Failure to find the ID
		1	Success
	*/

	function verify_id_service_customer()
	{
		log_debug("service_customer", "Executing verify_id_service_customer()");

		if ($this->id_service_customer)
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT serviceid, customerid FROM `services_customers` WHERE id='". $this->id_service_customer ."' LIMIT 1";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				$sql_obj->fetch_array();

				if ($this->id)
				{
					if ($sql_obj->data[0]["customerid"] == $this->id)
					{
						return 1;
					}
					else
					{
						log_write("error", "customers_services", "The seleced service-customer (". $this->id_service_customer .") does not match the selected customer (". $this->id .").");
						return 0;
					}
				}
				else
				{
					$this->id = $sql_obj->data[0]["customerid"];
					return 1;
				}
			}
		}

		return 0;

	} // end of verify_id



	/*
		load_data_service

		Loads the service object and it's associated data

		Results
		0	Failure
		1	Success
	*/
	function load_data_service()
	{
		log_write("debug", "customer_services", "Executing load_data_service()");


		$this->obj_service->option_type		= "customer";
		$this->obj_service->option_type_id	= $this->id_service_customer;

		if (!$this->obj_service->verify_id_options())
		{
			log_write("error", "customers_services", "Unable to verify service ID of ". $this->id_service_customer ." as being valid, no changes have been made to service configuration.");
			return 0;
		}
		
		$this->obj_service->load_data();
		$this->obj_service->load_data_options();

		return 1;
	}
	


	/*
		service_get_status

		Fetches the current status of the service

		Returns
		0		Service is not active or an error occured
		1		Service is active/enabled
	*/

	function service_get_status()
	{
		log_write("debug", "customers_services", "Executing service_get_status()");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT active FROM services_customers WHERE id='". $this->id_service_customer ."' LIMIT 1";
		$sql_obj->execute();

		$sql_obj->fetch_array();

		if ($sql_obj->data[0]["active"])
		{
			return 1;
		}
		else
		{
			return 0;
		}	
	}


	/*
		service_check_datechangesafe

		Returns whether it is safe to adjust the service start dates. This is possibly only
		when the service is recently added and has not yet been billed at all.

		Returns
		0	Unlocked
		1	Locked
		2	Locked // Is part of a bundle
	*/

	function service_check_datechangesafe()
	{
		log_write("debug", "customers_service", "Executing service_check_datechangesafe()");


		// check for bundle lock
		if ($this->service_get_is_bundle_item())
		{
			// bundle lock
			return 2;
		}


		// check if the service has current periods
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM `services_customers_periods` WHERE id_service_customer='". $this->id_service_customer ."' AND (invoiceid!='0' OR invoiceid_usage!='0') LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			// active periods
			return 1;
		}


		// open to change
		return 0;
	}


	/*
		service_check_delete_lock

		Returns whether or not the selected service can be deleted/is locked.

		Returns
		0	Unlocked
		1	Locked
		2	Locked // Is part of a bundle
	*/

	function service_check_delete_lock()
	{
		log_write("debug", "customers_services", "Executing service_check_delete_lock()");


		// check for bundle lock
		if ($this->service_get_is_bundle_item())
		{
			log_write("error", "customers_services", "Service is part of a bundle and can not be deleted.");

			return 2;
		}

		// check if the service has current periods - if so, service can not be deleted until periods are terminated
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT date_end FROM `services_customers_periods` WHERE id_service_customer='". $this->id_service_customer ."' ORDER BY date_end DESC LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$sql_obj->fetch_array();

			if (time_date_to_timestamp($sql_obj->data[0]["date_end"]) > time())
			{
				// period end date is larger than today - we can not delete the service
				// until after the period completes.

				log_write("error", "customers_services", "Service has current periods, can not be deleted until the final period terminates.");

				return 1;
			}
		}

		return 0;
	}


	/*
		service_get_is_bundle_item

		Returns whether or not the selected service is a bundle item.

		Returns
		0		Service is not a bundle item
		#		ID of the bundle item it belongs to.
	*/

	function service_get_is_bundle_item()
	{
		log_write("debug", "customers_services", "Executing service_get_is_bundle_item()");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT bundleid FROM services_customers WHERE id='". $this->id_service_customer ."' LIMIT 1";
		$sql_obj->execute();

		$sql_obj->fetch_array();

		if ($sql_obj->data[0]["bundleid"])
		{
			return $sql_obj->data[0]["bundleid"];
		}
		else
		{
			return 0;
		}	
	}




	/*
		service_add

		Fields
		date_period_start	date to activate the service from.

		Returns
		0	Failure
		#	ID of service-bundle maping

		Adds the requested service to a customer. If the service is a bundle, it will process this and
		add all the child services to the customer.

		The service to add is defined by $this->obj_service->id
	*/
	function service_add($date_period_start)
	{
		log_debug("debug", "inc_services", "Executing service_add($date_period_start)");



		/*
			Begin Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();



		/*
			Create service entry
		*/

		$sql_obj->string	= "INSERT INTO `services_customers` (customerid,
										serviceid,
										date_period_first,
										date_period_next,
										description)
									VALUES
										('". $this->id ."',
										'". $this->obj_service->id ."',
										'". $date_period_start ."',
										'". $date_period_start ."',
										'". $obj_service->data["description"] ."')";
		$sql_obj->execute();

		$this->id_service_customer = $sql_obj->fetch_insert_id();



		/*
			Process Bundle Items (if any)
		*/
		if ($this->obj_service->data["typeid_string"] == "bundle")
		{
			log_write("debug", "customer_services", "Service being added is a bundle service - processing components and adding them to customer as well.");

			// fetch bundle component services
			$components = $this->obj_service->bundle_service_list();

			// add each component service
			foreach ($components as $id_component)
			{
				$obj_component				= New service;
				$obj_component->option_type		= "bundle";
				$obj_component->option_type_id		= $id_component;

				$obj_component->verify_id_options();
				$obj_component->load_data();
				$obj_component->load_data_options();

				$sql_obj->string	= "INSERT INTO `services_customers` (customerid,
												serviceid,
												bundleid,
												bundleid_component,
												date_period_first,
												date_period_next,
												description) 
											VALUES
												('". $this->id ."',
												'". $obj_component->id ."',
												'". $this->id_service_customer ."',
												'". $id_component ."',
												'". $date_period_start ."',
												'". $date_period_start ."',
												'". $obj_component->data["description"] ."')";
				$sql_obj->execute();

				log_write("notification", "process", "Added new service ". $obj_component->data["name_service"] ." as part of the bundle service ". $this->obj_service->data["name_service"] ."");
			}
		}



		/*
			Update the Journal
		*/
		journal_quickadd_event("customers", $this->id, "New service ". $obj_service->data["name_service"] ." added to account with start date of ". $date_period_start ."");

		

		/*
			Commit
		*/

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "process", "An error occured whilst attemping to create the new service. No changes have been made.");
			
			return 0;
		}
		else
		{
			$sql_obj->trans_commit();
		
			log_write("notification", "process", "New service ". $obj_service->data["name_service"] ." added successfully.");

			return $this->id_service_customer;
		}


	}




	/*
		service_enable

		Enables the selected service for the customer.

		Returns
		0			Unable to enable service
		1			Service enabled successfully.

		TODO: Future provisioning hooks will need to go here.
	*/

	function service_enable()
	{
		log_write("debug", "customers_services", "Executing service_enable()");



		/*
			Begin Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();




		/*
			Process Bundle Components
		*/
		if ($this->obj_service->data["typeid_string"] == "bundle")
		{
			log_write("debug", "customer_services", "Service is a bundle, activiting all service components");


			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM services_customers WHERE bundleid='". $this->id_service_customer ."'";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				$sql_obj->fetch_array();

				foreach ($sql_obj->data as $data_component)
				{
					// load service information
					$obj_component			= New service;
					$obj_component->option_type	= "customer";
					$obj_component->option_type_id	= $data_component["id"];

					$obj_component->verify_id_options();
					$obj_component->load_data();
					$obj_component->load_data_options;


					// activate service
					$sql_obj		= New sql_query;
					$sql_obj->string	= "UPDATE services_customers SET active='1' WHERE id='". $data_component["id"] ."' LIMIT 1";
		
					if (!$sql_obj->execute())
					{
						log_write("error", "customers_services", "An error occured whilst attempting to enable the bundle component service ". $obj_component->data["name_service"] ."");
					}
					else
					{
						journal_quickadd_event("customers", $this->id, "Enabled service bundle item \"". $obj_component->data["name_service"] ."\"");

						log_write("notification", "customer_services", "Enabled service bundle item \"". $obj_component->data["name_service"] ."\"");
					}


				} // end of loop through bundle components

			}
		} // end of if bundle



		/*
			Activate Service
		*/

		$sql_obj		= New sql_query;
		$sql_obj->string	= "UPDATE services_customers SET active='1' WHERE id='". $this->id_service_customer ."' LIMIT 1";
		
		if (!$sql_obj->execute())
		{
			log_write("error", "customers_services", "An error occured whilst attempting to enable the selected service");
		}



		/*
			Commit
		*/

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "process", "Unexpected errors occured whilst trying to activate services, no changes have been made as a result.");
			
			return 0;
		}
		else
		{
			$sql_obj->trans_commit();
		
			journal_quickadd_event("customers", $this->id, "Enabled service \"". $this->obj_service->data["name_service"] ."\"");

			log_write("notification", "customer_services", "Enabled service \"". $this->obj_service->data["name_service"] ."\"");


			return 1;
		}

	}



	/*
		service_disable

		Disables the selected service for the customer.

		Returns
		0			Unable to disable service
		1			Service enabled successfully
		
		TODO: Future provisioning hooks will need to go here.
	*/

	function service_disable()
	{
		log_write("debug", "customers_services", "Executing service_disable()");



		/*
			Begin Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();



		/*
			Process Bundle Components
		*/
		if ($this->obj_service->data["typeid_string"] == "bundle")
		{
			log_write("debug", "customer_services", "Service is a bundle, disabling all service components");


			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM services_customers WHERE bundleid='". $this->id_service_customer ."'";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				$sql_obj->fetch_array();

				foreach ($sql_obj->data as $data_component)
				{
					// load service information
					$obj_component			= New service;
					$obj_component->option_type	= "customer";
					$obj_component->option_type_id	= $data_component["id"];

					$obj_component->verify_id_options();
					$obj_component->load_data();
					$obj_component->load_data_options;


					// activate service
					$sql_obj		= New sql_query;
					$sql_obj->string	= "UPDATE services_customers SET active='0', date_period_next='0000-00-00' WHERE id='". $data_component["id"] ."' LIMIT 1";
		
					if (!$sql_obj->execute())
					{
						log_write("error", "customers_services", "An error occured whilst attempting to disable the bundle component service ". $obj_component->data["name_service"] ."");
					}
					else
					{
						journal_quickadd_event("customers", $this->id, "Disabled service bundle item \"". $obj_component->data["name_service"] ."\"");

						log_write("notification", "customer_services", "Disabled service bundle item \"". $obj_component->data["name_service"] ."\"");
					}


				} // end of loop through bundle components

			}
		} // end of if bundle



		/*
			De-activate Service
		*/

		$sql_obj		= New sql_query;
		$sql_obj->string	= "UPDATE services_customers SET active='0', date_period_next='0000-00-00' WHERE id='". $this->id_service_customer ."' LIMIT 1";
		
		if (!$sql_obj->execute())
		{
			log_write("error", "customers_services", "An error occured whilst attempting to disable the selected service");
		}



		/*
			Commit
		*/

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "process", "Unexpected errors occured whilst trying to disable services, no changes have been made as a result.");
			
			return 0;
		}
		else
		{
			$sql_obj->trans_commit();
		
			journal_quickadd_event("customers", $this->id, "Disabled service \"". $this->obj_service->data["name_service"] ."\"");

			log_write("notification", "customer_services", "Disabled service \"". $this->obj_service->data["name_service"] ."\"");


			return 1;
		}
	}



	/*
		service_delete

		Deletes a service from the selected customer

		Returns
		0		Unexpected Failure
		1		Success
	*/
	function service_delete()
	{
		log_write("debug", "customers_services", "Executing service_delete())");


		/*
			Begin Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();

		
			
		/*
			Process Bundle Components
		*/
		if ($this->obj_service->data["typeid_string"] == "bundle")
		{
			log_write("debug", "customer_services", "Service is a bundle, disabling all service components");


			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM services_customers WHERE bundleid='". $this->id_service_customer ."'";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				$sql_obj->fetch_array();

				foreach ($sql_obj->data as $data_component)
				{
					// load service information
					$obj_component			= New service;
					$obj_component->option_type	= "customer";
					$obj_component->option_type_id	= $data_component["id"];

					$obj_component->verify_id_options();
					$obj_component->load_data();
					$obj_component->load_data_options;


					/*
						Delete Service Customer Mapping
					*/

					$sql_obj->string	= "DELETE FROM services_customers WHERE id='". $data_component["id"] ."' LIMIT 1";
					$sql_obj->execute();

					$sql_obj->string	= "DELETE FROM services_options WHERE option_type='customer' AND option_type_id='". $data_component["id"] ."'";
					$sql_obj->execute();


					/*
						Delete CDR service options (if any)
					*/

					$sql_obj->string	= "DELETE FROM cdr_rate_tables_overrides WHERE option_type='customer' AND option_type_id='". $data_component["id"] ."'";
					$sql_obj->execute();


					/*
						Delete service period history
					*/
			
					$sql_obj->string	= "DELETE FROM services_customers_periods WHERE id_service_customer='". $data_component["id"] ."'";
					$sql_obj->execute();


					/*
						Delete service usage records
					*/
			
					$sql_obj->string	= "DELETE FROM service_usage_records WHERE id_service_customer='". $data_component["id"] ."'";
					$sql_obj->execute();


					/*
						Delete the service DDI and IPv4 attributes (if any)
					*/

					$sql_obj->string	= "DELETE FROM services_customers_ddi WHERE id_service_customer='". $data_component["id"] ."'";
					$sql_obj->execute();

					$sql_obj->string	= "DELETE FROM services_customers_ipv4 WHERE id_service_customer='". $data_component["id"] ."'";
					$sql_obj->execute();


					/*
						Update Journal
					*/

					journal_quickadd_event("customers", $this->id, "Service bundle component \"". $obj_component->data["name_service"] ."\" has been deleted from this customer's account.");

					log_write("notification", "process", "Service bundle component \"". $obj_component->data["name_service"] ."\" has been removed from this customer's account.");


				} // end of loop through bundle components

			}
		} // end of if bundle





		/*
			Delete service-customer entry
		*/
			
		$sql_obj->string	= "DELETE FROM services_customers WHERE id='". $this->id_service_customer ."' LIMIT 1";
		$sql_obj->execute();

		$sql_obj->string	= "DELETE FROM services_options WHERE option_type='customer' AND option_type_id='". $this->id_service_customer ."'";
		$sql_obj->execute();
		

		/*
			Delete CDR service options (if any)
		*/

		$sql_obj->string	= "DELETE FROM cdr_rate_tables_overrides WHERE option_type='customer' AND option_type_id='". $this->id_service_customer ."'";
		$sql_obj->execute();


		/*
			Delete service period history
		*/
			
		$sql_obj->string	= "DELETE FROM services_customers_periods WHERE id_service_customer='". $this->id_service_customer ."'";
		$sql_obj->execute();


		/*
			Delete service usage records
		*/
			
		$sql_obj->string	= "DELETE FROM service_usage_records WHERE id_service_customer='". $this->id_service_customer ."'";
		$sql_obj->execute();



		/*
			Update Journal
		*/
		
		journal_quickadd_event("customers", $this->id, "Service ". $this->obj_service->data["name_service"] ." has been deleted from this customer's account.");



		/*
			Commit
		*/

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "process", "An error occured whilst attempting to delete the service from the customer's account. No changes were made.");
			return 0;
		}
		else
		{
			$sql_obj->trans_commit();

			log_write("notification", "process", "Service \"". $this->obj_service->data["name_service"] ."\" has been removed from this customer's account.");
			return 0;
		}
		

		return 0;
	}



	/*
		service_render_summarybox()

		Displays a summary box of the service information and whether the service is enabled or disabled for the customer.

		Return Codes
		0	failure
		1	sucess
	*/
	function service_render_summarybox()
	{
		log_debug("customer_services", "service_render_summarybox()");

		if ($this->service_get_status())
		{
			// service is enabled
			print "<table width=\"100%\" class=\"table_highlight_open\">";
			print "<tr>";
				print "<td>";
				print "<b>Service ". $this->obj_service->data["name_service"] ." is enabled.</b>";
		
				print "<table cellpadding=\"4\">";
						
					print "<tr>";
						print "<td>Service-Customer ID:</td>";
						print "<td>". $this->id_service_customer ."</td>";
					print "</tr>";

					print "<tr>";
						print "<td>Service Type:</td>";
						print "<td>". $this->obj_service->data["typeid_string"] ."</td>";
					print "</tr>";
						
					if ($this->obj_service->data["discount"])
					{
						// work out the price after discount
						$discount_calc	= $this->obj_service->data["discount"] / 100;
						$discount_calc	= $this->obj_service->data["price"] * $discount_calc;

						print "<tr>";
							print "<td>Service Charges:</td>";
							print "<td>". format_money($this->obj_service->data["price"] - $discount_calc) ." (discount of ". format_money($discount_calc) ." included, excluding usage charges and taxes)</td>";
						print "</tr>";

					}
					else
					{
						print "<tr>";
							print "<td>Service Charges:</td>";
							print "<td>". format_money($this->obj_service->data["price"]) ." (excluding usage charges and taxes)</td>";
						print "</tr>";
					}
					

				print "</table>";

				print "</td>";

			print "</tr>";
			print "</table>";
		}
		else
		{
			// service is not yet enabled
			print "<table width=\"100%\" class=\"table_highlight_important\">";
			print "<tr>";
				print "<td>";
				print "<b>Service ". $this->obj_service->data["name_service"] ."</b>";
				print "<p>This service is currently disabled, no processing will take place and the customer will not recieve any invoices. Use the \"<a href=\"index.php?page=customers/service-edit.php&id_customer=". $this->id ."&id_service_customer=". $this->id_service_customer ."\">service details</a>\" page to enable the service if desired.</p>";
				print "</td>";
			print "</tr>";
			print "</table>";
		}

		print "<br>";
	}



} // end of class: customer_services




/*
	CLASS customer_orders

	Functions for handling orders made against a customer.
*/
class customer_orders extends customer
{
	var $id_order;		// ID of the order item made
	var $data_order;	// Order Data

	/*
		Constructor
	*/
	function customer_orders()
	{
		log_write("debug", "customer_orders", "Executing customer_orders()");

		// nothing todo

	}


	/*
		check_orders_num

		Returns the number of order items.

		Returns
		#		Number of order items.
	*/

	function check_orders_num()
	{
		log_write("debug", "customer_orders", "Executing check_orders_num()");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM `customers_orders` WHERE id_customer='". $this->id ."'";
		$sql_obj->execute();

		return $sql_obj->num_rows();

	} // end of check_orders_num


	/*
		verify_id_order

		Verifies that the provided order item specified both exists and is assigned to the current customer.

		Results
		0	Failure to find the ID
		1	Success
	*/

	function verify_id_order()
	{
		log_debug("customer_orders", "Executing verify_id_order()");

		if ($this->id_order)
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id, id_customer FROM `customers_orders` WHERE id='". $this->id_order ."' LIMIT 1";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				$sql_obj->fetch_array();

				if ($this->id)
				{
					if ($sql_obj->data[0]["id_customer"] == $this->id)
					{
						return 1;
					}
					else
					{
						log_write("error", "customer_orders", "The selected customer order (". $this->id_order .") does not match the selected customer (". $this->id .").");
						return 0;
					}
				}
				else
				{
					$this->id = $sql_obj->data[0]["id_customer"];
					return 1;
				}
			}
		}

		return 0;

	} // end of verify_id_order



	/*
		load_data_order

		Loads the data for the selected order item into $this->data_orders.

		Results
		0	Failure
		1	Success
	*/
	function load_data_order()
	{
		log_write("debug", "customer_orders", "Executing load_data_order()");

		$this->data_orders = array();

		$obj_sql		= New sql_query;
		$obj_sql->string	= "SELECT * FROM customers_orders WHERE id='". $this->id_order ."' LIMIT 1";
		$obj_sql->execute();

		if ($obj_sql->num_rows())
		{
			$obj_sql->fetch_array();

			$this->data_orders = $obj_sql->data[0];

			return 1;
		}

		return 0;

	} // end of load_data_order()
	


	/*
		action_create_orders

		Creates a new order item. This function is typically called by action_update automatically
		when required.

		Results
		0	Failure
		#	Success - return ID of order
	*/
	function action_create_orders()
	{
		log_debug("inc_customers", "Executing action_create_orders()");

		// create a new customer
		$sql_obj		= New sql_query;
		$sql_obj->string	= "INSERT INTO `customers_orders` (id_customer) VALUES ('". $this->id. "')";
		$sql_obj->execute();

		$this->id_order = $sql_obj->fetch_insert_id();

		return $this->id_order;

	} // end of action_create_orders




	/*
		action_update_orders

		Update the order with information in $this->data_orders, if no order ID exists, it will first call
		the action_create function.

		Returns
		0	failure
		#	success - returns the ID
	*/
	function action_update_orders()
	{
		log_debug("inc_customers", "Executing action_update_orders()");


		/*
			Start Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();



		/*
			If no ID supplied, create a new order first
		*/
		if (!$this->id_order)
		{
			$mode = "create";

			if (!$this->action_create_orders())
			{
				return 0;
			}
		}
		else
		{
			$mode = "update";
		}


		/*
			Calculate the amount from the price
		*/

		// total amount
		$this->data_orders["amount"]	= $this->data_orders["price"] * $this->data_orders["quantity"];

		// discount
		if ($this->data_orders["discount"])
		{
			$discount_calc			= $this->data_orders["discount"] / 100;
			$discount_calc			= $this->data_orders["amount"] * $discount_calc;

			$this->data_orders["amount"]	= $this->data_orders["amount"] - $discount_calc;
		}



		/*
			Update Order Details
		*/

		$sql_obj->string	= "UPDATE `customers_orders` SET "
						."date_ordered='". $this->data_orders["date_ordered"] ."', "
						."type='". $this->data_orders["type"] ."', "
						."customid='". $this->data_orders["customid"] ."', "
						."quantity='". $this->data_orders["quantity"] ."', "
						."units='". $this->data_orders["units"] ."', "
						."amount='". $this->data_orders["amount"] ."', "
						."price='". $this->data_orders["price"] ."', "
						."discount='". $this->data_orders["discount"] ."', "
						."description='". $this->data_orders["description"] ."' "
						."WHERE id='". $this->id_order ."' LIMIT 1";
		$sql_obj->execute();



		/*
			Update the journal
		*/

		if ($mode == "update")
		{
			journal_quickadd_event("customers", $this->id, "Customer order item adjusted.");
		}
		else
		{
			journal_quickadd_event("customers", $this->id, "Order item added to customer.");
		}



		/*
			Commit
		*/

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "inc_customers", "An error occurred when updating customer order.");

			return 0;
		}
		else
		{
			$sql_obj->trans_commit();

			if ($mode == "update")
			{
				log_write("notification", "inc_customers", "Customer order successfully updated.");
			}
			else
			{
				log_write("notification", "inc_customers", "Customer order created.");
			}
			
			return $this->id;
		}

	} // end of action_update_orders
	


	/*
		action_delete_orders

		Deletes the selected order.
	*/

	function action_delete_orders()
	{
		log_write("debug", "inc_customers", "Executing action_delete_orders()");
		
		$sql_obj		= New sql_query;		
		$sql_obj->string	= "DELETE FROM `customers_orders` WHERE id='" .$this->id_order ."'";
		$sql_obj->execute();

		return 1;

	} // end of action_delete_orders




	/*
		invoice_date_calc

		Calculates the next invoicing date which will bill for the current orders - it can be one of:
		* Next service bill to be generated (as per ORDERS_BILL_ONSERVICE configuration option)
		* End of the calender month (as per ORDERS_BILL_ENDOFMONTH)
		* No automatic invoice date, manually only


		Returns
		date		YYYY-MM-DD format string
	*/

	function invoice_date_calc()
	{
		log_write("debug", "inc_customers", "Executing invoice_date_calc()");
	

		// we add all the dates to the array and choose the latest date
		$dates = array();


		// next service billing date
		if ($GLOBALS["config"]["ORDERS_BILL_ONSERVICE"] == 1)
		{
			log_write("debug", "inc_customers", "Fetching latest service billing dates for  customer ". $this->id ."");

			// check the next service billing date
			$service_ids = sql_get_singlecol("SELECT id as col FROM services_customers WHERE customerid='". $this->id ."'");

			if (is_array($service_ids))
			{
				foreach ($service_ids as $serviceid)
				{
					$obj_sql		= New sql_query;
					$obj_sql->string	= "SELECT date_billed FROM services_customers_periods WHERE id_service_customer='". $serviceid ."' ORDER BY id DESC LIMIT 1";
					$obj_sql->execute();

					if ($obj_sql->num_rows())
					{
						$obj_sql->fetch_array();
						
						$dates[] = $obj_sql->data[0]["date_billed"];
					}

					log_write("debug", "inc_customers", "No periods exist for id_service_customer of ". $serviceid .", perhaps this service has yet to be activated");
				}
			}
		}


		// end of month date
		if ($GLOBALS["config"]["ORDERS_BILL_ENDOFMONTH"] == 1)
		{
			log_write("debug", "inc_customers", "Fetching end of month date");

			$dates[] = time_calculate_monthdate_last(date("Y-m-d"));
		}


		// determine the latest date
		$timestamp_today	= time_date_to_timestamp(date("Y-m-d"));		// we use this to avoid hours/mins
		$timestamp_nextbill;

		foreach ($dates as $date)
		{
			$date_t = explode("-", $date);
			$date_t = mktime(0, 0, 0, $date_t[1], $date_t[2] , $date_t[0]);
			
			if ($date_t >= $timestamp_today)
			{
				// future date
				if (empty($timestamp_nextbill))
				{
					$timestamp_nextbill = $date_t;
				}
				else
				{
					if ($date_t < $timestamp_nextbill)
					{
						// closer than current date
						$timestamp_nextbill = $date_t;
					}
				}
			}
		}

		if (empty($timestamp_nextbill))
		{
			$date = "Manual Invoice Only";
		}
		else
		{
			$date = date("Y-m-d", $timestamp_nextbill);
		}
	
		log_write("debug", "inc_customers", "Calculated next billing date for customer orders to be \"$date\"");

		return $date;

	} // end of invoice_date_calc()



	/*
		invoice_generate

		Converts all current orders belonging to the selected customer into an invoice and returns
		the ID of the invoice.

		values
		invoiceid	[optional] ID of the invoice to add orders to, or blank to create a new one

		Return codes
		0		failure
		#		success - returns invoiceid
	*/
	
	function invoice_generate($invoiceid = NULL)
	{
		log_write("debug", "inc_customers", "Executing invoice_generate()");

		// we don't need to worry about checking if this is the appropiate date, that logic is handled by
		// other functions before calling this one.


		// initatiate SQL
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();


		// do we need to create an invoice?
		if (!$invoiceid)
		{
			$obj_invoice		= New invoice;

			$obj_invoice->type			= "ar";
			$obj_invoice->data["customerid"]	= $this->id;
			$obj_invoice->data["employeeid"]	= 1;
			$obj_invoice->data["notes"]		= "Invoice generated from customer orders page.";

			$obj_invoice->prepare_date_shift();
			$obj_invoice->action_create();

			$invoiceid	= $obj_invoice->id;

			log_write("debug", "inc_customers", "Creating a new invoice with ID of $invoiceid");
		}
		else
		{
			// reuse existing
			log_write("debug", "inc_customers", "Using specified invoice $invoiceid for orders invoicing");
		}


		// run through the items and add to the invoice
		$obj_orders_sql			= New sql_query;
		$obj_orders_sql->string		= "SELECT * FROM customers_orders WHERE id_customer='". $this->id ."'";
		$obj_orders_sql->execute();

		if ($obj_orders_sql->num_rows())
		{
			$obj_orders_sql->fetch_array();

			foreach ($obj_orders_sql->data as $data_order)
			{
				log_write("debug", "inc_customers", "Adding order item ". $data_order["id"] ." to invoice ". $invoiceid ." for customer ". $this->id ."");

			
				// select values desired, certain safety checks
				$data_order_tmp = array();

				$data_order_tmp["customid"]	= $data_order["customid"];
				$data_order_tmp["quantity"]	= $data_order["quantity"];
				$data_order_tmp["units"]	= addslashes($data_order["units"]);
				$data_order_tmp["amount"]	= $data_order["amount"];
				$data_order_tmp["price"]	= $data_order["price"];
				$data_order_tmp["discount"]	= $data_order["discount"];
				$data_order_tmp["description"]	= addslashes($data_order["description"]);


				// Add each order as an item on the invoice.
				$obj_item = New invoice_items;

				$obj_item->id_invoice		= $invoiceid;
				$obj_item->type_invoice		= "ar";
				$obj_item->type_item		= $data_order["type"];

				$obj_item->prepare_data($data_order_tmp);

				$obj_item->action_create();
				$obj_item->action_update();


				// delete the item now that it's been added
				$obj_delete_sql			= New sql_query;
				$obj_delete_sql->string		= "DELETE FROM customers_orders WHERE id='". $data_order["id"] ."' LIMIT 1";
				$obj_delete_sql->execute();

				unset($obj_delete_sql);
			}

			// update invoice summary information
			$obj_item->action_update_tax();
			$obj_item->action_update_total();
			$obj_item->action_update_ledger();


			unset($obj_item);
		
		} // end if order items.


		// make automated payments - such as customer credit pool or auto-pay credit card functionality
		if ($GLOBALS["config"]["ACCOUNTS_AUTOPAY"])
		{
			log_write("debug", "inc_services_invoicegen", "Autopay Functionality Enabled, running appropiate functions for invoice ID $invoiceid");

			$obj_autopay			= New invoice_autopay;
			$obj_autopay->id_invoice	= $invoiceid;
			$obj_autopay->type_invoice	= "ar";

			$obj_autopay->autopay();

			unset($obj_autopay);
		}


		// save changes
		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "inc_customers", "An error occurred whilst attempting to generate an invoice.");

			return 0;
		}
		else
		{
			$sql_obj->trans_commit();

			log_write("notification", "inc_customers", "Successfully generate invoice ". $obj_invoice->data["code_invoice"] ." for customer ". $this->data["name_customer"] ."");
		
			return $invoiceid;
		}


	} // end of invoice_generate()



	/*
		order_render_summarybox()

		Displays a summary box of information relating to customer orders - total amount invoiced
		and next expected automated invoicing date.

		Return Codes
		0	failure
		1	sucess
	*/
	function order_render_summarybox()
	{
		log_debug("inc_customers", "Executing order_render_summarybox");


		// load customer details if we haven't already
		if (empty($this->data))
		{
			$this->load_data();
		}


		// are there orders outstanding?
		$obj_sql 		= New sql_query;
		$obj_sql->string	= "SELECT id FROM `customers_orders` WHERE id_customer='". $this->id ."'";
		$obj_sql->execute();

		if ($obj_sql->num_rows())
		{

			// fetch general details
			$order_total_amount	= sql_get_singlevalue("SELECT SUM(amount) as value FROM customers_orders WHERE id_customer='". $this->id ."'");
			$order_invoice_date	= $this->invoice_date_calc();

			// display orders summary information
			print "<table width=\"100%\" class=\"table_highlight_open\">";
			print "<tr>";
				print "<td>";
				print "<b>Customer ". $this->obj_customer->data["name_customer"] ." has unbilled order items.</b>";
		
				print "<table cellpadding=\"4\">";
						
					print "<tr>";
						print "<td>Total Amount:</td>";
						print "<td>". format_money($order_total_amount) ." [exc sales tax]</td>";
					print "</tr>";

					print "<tr>";
						print "<td>Next Invoice Date:</td>";
						print "<td>". $order_invoice_date ."</td>";
					print "</tr>";
						
				print "</table>";

				print "</td>";

			print "</tr>";
			print "</table>";
		}
		else
		{
			// no current orders
			print "<table width=\"100%\" class=\"table_highlight_info\">";
			print "<tr>";
				print "<td>";
				print "<b>Customer ". $this->obj_customer->data["name_customer"] ." has no currently ordered items.</b>";
				print "<p>The customer currently has no order items to bill. Use the \"<a href=\"index.php?page=customers/orders-view.php&id_customer=". $this->id ."\">create order</a>\" page to start preparing a customer order.</p>";
				print "</td>";
			print "</tr>";
			print "</table>";
		}

		print "<br>";
	}



} // end of class: customer_orders




/*
	CLASS: customer_credits

	Functions for managing credits belonging to a customer - note that this does not integrate with the
	more accounting focused credit note functionality, but rather with the allocation of credit funds from
	the customer to an invoice and reporting of credits.
*/

class customer_credits extends customer
{

	/*
		credit_render_summarybox()

		Displays a summary box with information about the customer's credit status such as balance.

		Return Codes
		0	failure
		1	sucess
	*/
	function credit_render_summarybox()
	{
		log_debug("inc_customers", "Executing credit_render_summarybox");


		// load customer details if we haven't already
		if (empty($this->data))
		{
			$this->load_data();
		}


		// are there any credits? If there are none, balance is simple.
		$obj_sql 		= New sql_query;
		$obj_sql->string	= "SELECT id FROM `customers_credits` WHERE id_customer='". $this->id ."'";
		$obj_sql->execute();

		if ($obj_sql->num_rows())
		{
			// fetch general details
			$credit_total_amount	= sql_get_singlevalue("SELECT SUM(amount_total) as value FROM customers_credits WHERE id_customer='". $this->id ."'");

			// display credits summary information
			if ($credit_total_amount > 1)
			{
				// current credit
				print "<table width=\"100%\" class=\"table_highlight_open\">";
				print "<tr>";
					print "<td>";
					print "<b>Customer ". $this->data["name_customer"] ." has current outstanding credit.</b>";
			
					print "<table cellpadding=\"4\">";
							
						print "<tr>";
							print "<td>Total Amount:</td>";
							print "<td>". format_money($credit_total_amount) ."</td>";
						print "</tr>";

					print "</table>";

					print "</td>";

				print "</tr>";
				print "</table>";
			}
			else
			{
				// no credit, but there have been in the past
				print "<table width=\"100%\" class=\"table_highlight_open\">";
				print "<tr>";
					print "<td>";
					print "<b>Customer ". $this->obj_customer->data["name_customer"] ." has a zero credit balance.</b>";
			
					print "<table cellpadding=\"4\">";
							
						print "<tr>";
							print "<td>Total Amount:</td>";
							print "<td>". format_money($order_total_amount) ."</td>";
						print "</tr>";

					print "</table>";

					print "</td>";

				print "</tr>";
				print "</table>";
			}
		}
		else
		{
			// no credits
			print "<table width=\"100%\" class=\"table_highlight_info\">";
			print "<tr>";
				print "<td>";
				print "<b>Customer ". $this->obj_customer->data["name_customer"] ." has no credits against their account.</b>";
				print "<p>To credit a customer account, create a credit note against a past invoice.</p>";
				print "</td>";
			print "</tr>";
			print "</table>";
		}

		print "<br>";
	}



}





/*
	CLASS: customer_portal

	Functions for handling portal authentications against customer records as well
	as updating the customer's password.

	TODO: In future this should be expanded to support different authentication backends
		such as LDAP databases with customer information.
*/

class customer_portal extends customer
{

	/*
		auth_login($password_plaintext)

		Authenticates the customer using the supplied plaintext password by comparing the password
		against the database and returning the result.

		Note that this function does not provide any brute-force blacklisting defenses, these could
		potentially be added in future if desired.

		The other possibility is to extend the Amberphplib user_auth framework to handle authentication
		from different databases and use a seporate DB to store the information and track failed logins.

		Returns
		0	Failure to Authentication // Invalid Password
		1	Successful Authentication
	*/
	function auth_login($password_plaintext)
	{
		log_write("debug", "customer_portal", "Executing auth_login(*plaintextpassword*)");


		// fetch the password & salt from DB
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT portal_salt, portal_password FROM customers WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();
		$sql_obj->fetch_array();

		// general encrypted PWD from DB salt and supplied password
		$password_crypt = sha1( $sql_obj->data[0]["portal_salt"]  ."$password_plaintext");

		// verify
		if ($sql_obj->data[0]["portal_password"] == $password_crypt)
		{
			log_write("debug", "customer_portal", "Successful Authentication");

			// update DB with session login information
			$sql_obj		= New sql_query;
			$sql_obj->string	= "UPDATE `customers` SET portal_login_time='". time() ."', portal_login_ipaddress='". $_SERVER["REMOTE_ADDR"] ."' WHERE id='". $this->id ."' LIMIT 1";
			$sql_obj->execute();

			return 1;
		}
		else
		{
			log_write("debug", "customer_portal", "Authentication Failure");
			return 0;
		}
	} // end of auth_login



	/*
		auth_changepwd

		Updates the customer's portal password entry in the database with the provided plaintext
		passpharase.

		Returns
		0	Unexpected failure
		1	Success
	*/
	function auth_changepwd($password_plaintext)
	{
		log_write("debug", "customer_portal", "Executing auth_changepwd(*plaintextpassword*)");


		// Here we generate a password salt. This is used, so that in the event of an attacker
		// getting a copy of the users table, they can't brute force the passwords using pre-created
		// hash dictionaries.
		//
		// The salt requires them to have to re-calculate each password possibility for any passowrd
		// they wish to try and break.
		//
		$feed		= "0123456789abcdefghijklmnopqrstuvwxyz";
		$password_salt	= null;

		for ($i=0; $i < 20; $i++)
		{
			$password_salt .= substr($feed, rand(0, strlen($feed)-1), 1);
		}				
			
		// encrypt password with salt
		$password_crypt = sha1("$password_salt"."$password_plaintext");

		// apply changes to DB.
		$sql_obj		= New sql_query;
		$sql_obj->string	= "UPDATE `customers` SET portal_password='". $password_crypt ."', portal_salt='". $password_salt ."' WHERE id='". $this->id ."' LIMIT 1";

		if ($sql_obj->execute())
		{
			log_write("notification", "customer_portal", "Successfully updated customer's portal password");
			return 1;
		}
		else
		{
			log_write("error", "customer_portal", "An unexpected failure occured whilst attempting to update the selected customer's portal password");
			return 0;
		}

	} // end of auth_changepwd



} // end of customer_portal



?>
