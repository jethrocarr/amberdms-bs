<?php
/*
	hr/inc_vendors.php

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

		Load the vendor's information into the $this->data array.

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
		$sql_obj		= New sql_query;
		$sql_obj->string	= "UPDATE `vendors` SET "
						."code_vendor='". $this->data["code_vendor"] ."', "
						."name_vendor='". $this->data["name_vendor"] ."', "
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
			journal_quickadd_event("vendors", $this->id, "Vendor details updated.");
			log_write("notification", "inc_vendors", "Vendor details successfully updated.");
		}
		else
		{
			journal_quickadd_event("vendors", $this->id, "Initial Vendor Creation.");
			log_write("notification", "inc_vendors", "Vendor successfully created.");
		}


		// success
		return $this->id;

	} // end of action_update


	/*
		action_update_taxes

		Updates the vendor tax selection options
	*/
	function action_update_taxes()
	{
		log_debug("inc_vendors", "Executing action_update_taxes()");


		// delete existing tax options
		$sql_obj		= New sql_query;
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
					$sql_obj		= New sql_query;
					$sql_obj->string	= "INSERT INTO vendors_taxes (vendorid, taxid) VALUES ('". $this->id ."', '". $data_tax["id"] ."')";
					$sql_obj->execute();
				}
			}
		}

		return 1;
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


		/*
			Delete Vendor
		*/
			
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM vendors WHERE id='". $this->id ."'";
			
		if (!$sql_obj->execute())
		{
			log_write("error", "inc_vendors", "A fatal SQL error occured whilst trying to delete the vendor");
			return 0;
		}


		/*
			Delete vendor taxes
		*/
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM vendors_taxes WHERE vendorid='". $this->id ."'";
			
		if (!$sql_obj->execute())
		{
			log_write("error", "inc_vendors", "A fatal SQL error occured whilst trying to delete the taxes assigned to the vendor");
			return 0;
		}


		/*
			Delete Journal
		*/
		journal_delete_entire("vendors", $this->id);


		log_write("notification", "inc_vendors", "Vendor has been successfully deleted.");

		return 1;
	}


} // end of class:vendors


?>
