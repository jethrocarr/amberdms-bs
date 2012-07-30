<?php
/*
	include/vendors/inc_vendors.php

	Provides classes for managing vendors.
*/




/*
	CLASS: vendor

	Provides functions for managing vendors
*/

class vendor
{
	var $id;		// holds vendor ID
	var $data;		// holds values of record fields



	/*
		verify_id

		Checks that the provided ID is a valid vendor

		Results
		0	Failure to find the ID
		1	Success - employee exists
	*/

	function verify_id()
	{
		log_debug("inc_vendors", "Executing verify_id()");

		if ($this->id)
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM `vendors` WHERE id='". $this->id ."' LIMIT 1";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				return 1;
			}
		}

		return 0;

	} // end of verify_id



	/*
		verify_name_vendor

		Checks that the name_vendor value supplied has not already been taken.

		Results
		0	Failure - name in use
		1	Success - name is available
	*/

	function verify_name_vendor()
	{
		log_debug("inc_vendors", "Executing verify_name_vendor()");

		$sql_obj			= New sql_query;
		$sql_obj->string		= "SELECT id FROM `vendors` WHERE name_vendor='". $this->data["name_vendor"] ."' ";

		if ($this->id)
			$sql_obj->string	.= " AND id!='". $this->id ."'";

		$sql_obj->string		.= " LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			return 0;
		}
		
		return 1;

	} // end of verify_name_vendor
	
	
	/*
		verify_uniqueness_contact

		Checks that each contact has a unique name

		Results
		$j [index of dup]	Failure - name has been assigned to two contacts
		unique			Success - contact name is unique
	*/
	function verify_uniqueness_contact ($index)
	{
		log_debug("inc_vendors", "Executing verify_uniqueness_contact($index)");

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
		log_debug("inc_vendors", "Executing verify_name_contact($index)");

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
		verify_code_vendor

		Checks that the code_vendor value supplied has not already been taken.

		Results
		0	Failure - name in use
		1	Success - name is available
	*/

	function verify_code_vendor()
	{
		log_debug("inc_vendors", "Executing verify_code_vendor()");

		$sql_obj			= New sql_query;
		$sql_obj->string		= "SELECT id FROM `vendors` WHERE code_vendor='". $this->data["code_vendor"] ."' ";

		if ($this->id)
			$sql_obj->string	.= " AND id!='". $this->id ."'";

		$sql_obj->string		.= " LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			return 0;
		}
		
		return 1;

	} // end of verify_code_vendor




	/*
		check_delete_lock

		Checks if the employee is safe to delete or not

		Results
		0	Unlocked
		1	Locked
	*/

	function check_delete_lock()
	{
		log_debug("inc_vendors", "Executing check_delete_lock()");


		// make sure vendor does not belong to any AP invoices
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_ap WHERE vendorid='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			return 1;
		}

		unset($sql_obj);


		// make sure vendor has not been assigned to any products
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM products WHERE vendorid='". $this->id ."' LIMIT 1";
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

		Load the vendor's information into the $this->data array. Excludes contacts, see load_data_contacts

		Returns
		0	failure
		1	success
	*/
	function load_data()
	{
		log_debug("inc_vendors", "Executing load_data()");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT * FROM vendors WHERE id='". $this->id ."' LIMIT 1";
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
		load_data_contacts

		Loads the vendor's contact information into $this->data["contacts"][ index ], with record data in $this->data["contacts"][ index ]["records"]

		The accounts contact is *always* loaded into id 0

		Returns
		0	failure
		1	success
	*/
	function load_data_contacts()
	{
		log_debug("inc_vendors", "Executing load_data_contacts()");

		// fetch all the contacts
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id, role, contact, description FROM vendor_contacts WHERE vendor_id='" .$this->id ."' ORDER BY role DESC";

		if (!$sql_obj->execute())
		{
			return 0;
		}

		$sql_obj->fetch_array();
		$this->data["num_contacts"] = $sql_obj->num_rows();
		
		for ($i=0; $i < $this->data["num_contacts"]; $i++)
		{
			$this->data["contacts"][$i]["contact_id"]	= $sql_obj->data[$i]["id"];
			$this->data["contacts"][$i]["contact"]		= $sql_obj->data[$i]["contact"];
			$this->data["contacts"][$i]["role"]		= $sql_obj->data[$i]["role"];
			$this->data["contacts"][$i]["description"]	= $sql_obj->data[$i]["description"];
			$this->data["contacts"][$i]["delete_contact"]	= "false"; // trick to work around the auction_create/update form-like expectations (TODO: fix this poor code)
			
			// contact records
			$sql_records_obj		= New sql_query;			
			$sql_records_obj->string	= "SELECT id, type, label, detail FROM vendor_contact_records WHERE contact_id= " .$sql_obj->data[$i]["id"]. " ORDER BY type";
			$sql_records_obj->execute();		

			$this->data["contacts"][$i]["num_records"] = $sql_records_obj->num_rows();

			if ($this->data["contacts"][$i]["num_records"])
			{
				$sql_records_obj->fetch_array();

				for ($j=0; $j < $sql_records_obj->data_num_rows; $j++)
				{
					$this->data["contacts"][$i]["records"][$j]["record_id"]	= $sql_records_obj->data[$j]["id"];
					$this->data["contacts"][$i]["records"][$j]["type"]	= $sql_records_obj->data[$j]["type"];
					$this->data["contacts"][$i]["records"][$j]["label"]	= $sql_records_obj->data[$j]["label"];
					$this->data["contacts"][$i]["records"][$j]["detail"]	= $sql_records_obj->data[$j]["detail"];
					$this->data["contacts"][$i]["records"][$j]["delete"]	= "false"; // trick to work around the auction_create/update form-like expectations (TODO: fix this poor code)
				}
			}
		}


		// success
		return 1;

	} // end of load_data_contacts





	/*
		action_create

		Create a new vendor based on the data in $this->data

		Results
		0	Failure
		#	Success - return ID
	*/
	function action_create()
	{
		log_debug("inc_vendors", "Executing action_create()");


		// create a new vendor
		$sql_obj		= New sql_query;
		$sql_obj->string	= "INSERT INTO `vendors` (name_vendor) VALUES ('". $this->data["name_vendor"]. "')";
		$sql_obj->execute();

		$this->id = $sql_obj->fetch_insert_id();

		return $this->id;

	} // end of action_create




	/*
		action_update

		Update a vendor's details based on the data in $this->data. If no ID is provided,
		it will first call the action_create function.

		Returns
		0	failure
		#	success - returns the ID
	*/
	function action_update()
	{
		log_debug("inc_vendors", "Executing action_update()");


		// transaction start
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();


		// if no ID exists, create a new vendor first
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


		// create a unique vendor code if none already exist
		if (!$this->data["code_vendor"])
		{
			$this->data["code_vendor"] = config_generate_uniqueid("CODE_VENDOR", "SELECT id FROM vendors WHERE code_vendor='VALUE' LIMIT 1");
		}
	

		// update
		$sql_obj->string	= "UPDATE `vendors` SET "
						."code_vendor='". $this->data["code_vendor"] ."', "
						."name_vendor='". $this->data["name_vendor"] ."', "
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
						."discount='". $this->data["discount"] ."' "
						."WHERE id='". $this->id ."'";
		if (!$sql_obj->execute())
		{
			log_write("error", "inc_vendors", "Unable to update vendor information");
		}
		
		
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


		// add journal entry
		if ($mode == "update")
		{
			journal_quickadd_event("vendors", $this->id, "Vendor details updated.");
		}
		else
		{
			journal_quickadd_event("vendors", $this->id, "Initial Vendor Creation.");
		}


		// commit
		if (error_check())
		{
			// failure
			$sql_obj->trans_rollback();

			log_write("error", "inc_vendors", "An error occured whilst saving vendor details, no changes have been made.");
			return 0;
		}
		else
		{
			// success
			$sql_obj->trans_commit();

			if ($mode == "update")
			{
				log_write("notification", "inc_vendors", "Vendor details successfully updated.");
			}
			else
			{
				log_write("notification", "inc_vendors", "Vendor successfully created.");
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
		log_debug("inc_vendors", "Executing action_update_contact($index)");


		$sql_obj = New sql_query;
	
		$sql_obj->string	= "UPDATE vendor_contacts SET
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
		log_debug("inc_vendors", "Executing action_create_contact($index)");


		$sql_obj = New sql_query;		
		$sql_obj->string	= "INSERT INTO vendor_contacts(vendor_id, contact, description, role)
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
		log_debug("inc_vendors", "Executing action_delete_contact($index)");

		$sql_obj = New sql_query;		
		$sql_obj->string	= "DELETE FROM vendor_contacts WHERE id ='" .$this->data["contacts"][$index]["contact_id"]. "'";
		$sql_obj->execute();
		
		$sql_obj->string	= "DELETE FROM vendor_contact_records WHERE contact_id ='" .$this->data["contacts"][$index]["contact_id"]. "'";
		$sql_obj->execute();
		
		$this->num_deleted_contacts++;
	}
	
	
	
	/*
	 	action_update_record($contact_index, $record_index)
	 	
	 	Updates an existing contact record
	 */
	function action_update_record($contact_index, $record_index)
	{
		log_debug("inc_vendors", "Executing action_update_record($contact_index, $record_index)");

		$sql_obj 	= New sql_query;
		
		$sql_obj->string	= "UPDATE vendor_contact_records SET
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
		$sql_obj->string	= "INSERT INTO vendor_contact_records(contact_id, type, label, detail)
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
		$sql_obj->string	= "DELETE FROM vendor_contact_records WHERE id='" .$this->data["contacts"][$contact_index]["records"][$record_index]["record_id"]. "'";
		$sql_obj->execute();
	}


	/*
		action_update_taxes

		Updates the vendor tax selection options
	*/
	function action_update_taxes()
	{
		log_debug("inc_vendors", "Executing action_update_taxes()");

		// start transaction
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();


		// delete existing tax options
		$sql_obj->string	= "DELETE FROM vendors_taxes WHERE vendorid='". $this->id ."'";
		$sql_obj->execute();

		// if the vendor has selected a default tax, make sure the default tax is enabled.
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
					// enable tax for vendor
					$sql_obj->string	= "INSERT INTO vendors_taxes (vendorid, taxid) VALUES ('". $this->id ."', '". $data_tax["id"] ."')";
					$sql_obj->execute();
				}
			}
		}

		// commit
		if (error_check())
		{
			$sql_obj->trans_rollback();
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

		Deletes a vendor.

		Note: the check_delete_lock function should be executed before calling this function to ensure database integrity.


		Results
		0	failure
		1	success
	*/
	function action_delete()
	{
		log_debug("inc_vendors", "Executing action_delete()");

		// start transaction
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();


		/*
			Delete Vendor
		*/
			
		$sql_obj->string	= "DELETE FROM vendors WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();



		/*
			Delete vendor taxes
		*/

		$sql_obj->string	= "DELETE FROM vendors_taxes WHERE vendorid='". $this->id ."'";
		$sql_obj->execute();
		
		
		/*
		 	Delete vendor contacts and records
		 */
		
		$sql_obj->string	= "SELECT id from vendor_contacts WHERE id='" .$this->id. "'";
		$sql_obj->execute();
		$sql_obj->fetch_array();		
		foreach ($sql_obj->data as $data)
		{
			$sql_obj->string	= "DELETE FROM vendor_contact_records WHERE contact_id='" .$data["id"]. "'";
			$sql_obj->execute();
		}
		
		$sql_obj->string	= "DELETE FROM vendor_contacts WHERE id='" .$this->id. "'";
		$sql_obj->execute();


		/*
			Delete Journal
		*/
		journal_delete_entire("vendors", $this->id);



		// commit
		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "inc_vendors", "An error occured whilst attempting to delete vendor. No changes have been made.");
			return 0;
		}
		else
		{
			$sql_obj->trans_commit();

			log_write("notification", "inc_vendors", "Vendor has been successfully deleted.");
			return 1;
		}
	}


} // end of class:vendors


?>
