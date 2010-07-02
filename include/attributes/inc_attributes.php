<?php
/*
	attributes/inc_attributes.php

	Provides classes for managing attributes.
*/




/*
	CLASS: attributes_keys

	Provides functions for managing attribute keys
*/


class attributes_keys {

	var $id;		// holds attribute key ID
	var $data;		// holds values of record fields

	/*
		verify_id

		Checks that the provided ID is a valid attribute key

		Results
		0	Failure to find the ID
		1	Success - attribute key exists
	*/

	function verify_id()
	{
		log_debug("inc_attributes", "Executing verify_id()");

		if ($this->id)
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM `attributes_keys` WHERE id='". $this->id ."' LIMIT 1";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				return 1;
			}
		}

		return 0;

	} // end of verify_id

	/*
		verify_id

		Checks that the provided ID is a valid attribute key

		Results
		0	Failure to find the ID
		1	Success - attribute value exists
	*/

	function verify_id()
	{
		log_debug("inc_attributes", "Executing verify_id()");

		if ($this->id)
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM `attributes_values` WHERE id='". $this->id ."' LIMIT 1";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				return 1;
			}
		}

		return 0;

	} // end of verify_id
	

	/*
		check_delete_lock

		Returns whether the attribute key can be safely deleted or not.

		Results
		0	Unlocked
		1	Locked
	*/

	function check_delete_lock()
	{
		log_debug("inc_attributes", "Executing check_delete_lock()");

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
		log_debug("inc_attributes", "Executing load_data()");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT * FROM attributes_values WHERE id='". $this->id ."' LIMIT 1";
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
		log_debug("inc_attributes", "Executing action_create()");

		// create a new product
		$sql_obj		= New sql_query;
		$sql_obj->string	= "INSERT INTO `attributes_keys` ( key_name ) VALUES ( '".$this->data["key_name"]."' )";
		
		

		
		$sql_obj->execute();

		$this->id = $sql_obj->fetch_insert_id();


		return $this->id;

	} // end of action_create



	/*
		action_update_key

		Update an attribute key's details based on the data in $this->data. If no ID is provided,
		it will first call the action_create function.

		Returns
		0	failure 
		#	success - returns the ID
	*/
	function action_update()
	{
		log_debug("inc_attributes", "Executing action_update()");

		/*
			Start Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();


		/*
			If no ID exists, create a new attribute key first

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
			Update product details
		*/

		$sql_obj->string	= "UPDATE `attributes_keys` SET "
						."key_name ='".$this->data["key_name"]."'"
						."WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();



		/*
			Update Journal
		*/

		if ($mode == "update")
		{
			journal_quickadd_event("attributes_keys", $this->id, "Attribute key updated.");
		}
		else
		{
			journal_quickadd_event("attributes_keys", $this->id, "Attribute key created.");
		}


	
		/*
			Commit
		*/
		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "process", "An error occured whilst attempting to update the attribute key. No changes were made.");

			return 0;
		}
		else
		{
			$sql_obj->trans_commit();

			if ($mode == "update")
			{
				log_write("notification", "inc_attributes", "Attribute key successfully updated.");
			}
			else
			{
				log_write("notification", "inc_attributes", "Attribute key successfully created.");
			}


			return $this->id;
		}

	} // end of action_update_details
	

	/*
		action_delete_key

		Deletes an attribute key.

		Note: the check_delete_lock function should be executed before calling
		this function to ensure database integrity.

		Results
		0	failure
		1	success
	*/
	
	function action_delete()
	{
		log_debug("inc_attributes", "Executing action_delete()");

		// start transaction
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();


		// delete the product
		$sql_obj->string	= "DELETE FROM attributes_keys WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		journal_delete_entire("attributes_keys", $this->id);
		
		// commit
		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error". "inc_attributes". "An error occured whilst attempting to delete the product. No changes have been made.");
		}
		else
		{
			$sql_obj->trans_commit();

			log_write("notification", "inc_attributes", "Attribute Key has been successfully deleted.");
		}

		return 1;
	}
}




/*
	CLASS: attributes_keys

	Provides functions for managing attribute keys
*/

class attributes_values {

	var $id;		// holds attribute ID
	var $data;		// holds values of record fields

	/*
		verify_id

		Checks that the provided ID is a valid attribute key

		Results
		0	Failure to find the ID
		1	Success - attribute key exists
	*/

	function verify_id()
	{
		log_debug("inc_attributes", "Executing verify_id()");

		if ($this->id)
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM `attributes_values` WHERE id='". $this->id ."' LIMIT 1";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				return 1;
			}
		}

		return 0;

	} // end of verify_id
	

	/*
		check_delete_lock

		Returns whether the product can be safely deleted or not.

		Results
		0	Unlocked
		1	Locked
	*/

	function check_delete_lock()
	{
		log_debug("inc_attributes", "Executing check_delete_lock()");

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
		log_debug("inc_attributes", "Executing load_data()");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT * FROM attributes_values WHERE id='". $this->id ."' LIMIT 1";
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
		log_debug("inc_attributes", "Executing action_create()");

		// create a new product
		$sql_obj		= New sql_query;
		$sql_obj->string	= "INSERT INTO `attributes_values` ( id_owner ) VALUES ( '".$this->data["id_owner"]."' )";
		
		$sql_obj->execute();

		$this->id = $sql_obj->fetch_insert_id();


		return $this->id;

	} // end of action_create



	/*
		action_update_value

		Update an attribute value's details based on the data in $this->data. If no ID is provided,
		it will first call the action_create function.

		Returns
		0	failure 
		#	success - returns the ID
	*/
	function action_update()
	{
		log_debug("inc_attributes", "Executing action_update()");

		/*
			Start Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();


		/*
			If no ID exists, create a new attribute value first

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
			Update product details
		*/

		$sql_obj->string	= "UPDATE `attributes_values` SET "
						."id_owner='".$this->data["id_owner"]."',"
						."id_key'".$this->data["id_key"]."',"
						."type'".$this->data["type"]."',"
						."value'".$this->data["value"]."'"
						."WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();



		/*
			Update Journal
		*/

		if ($mode == "update")
		{
			journal_quickadd_event("attributes_values", $this->id, "Attribute value updated.");
		}
		else
		{
			journal_quickadd_event("attributes_values", $this->id, "Attribute value created.");
		}


	
		/*
			Commit
		*/
		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "process", "An error occured whilst attempting to update the attribute value. No changes were made.");

			return 0;
		}
		else
		{
			$sql_obj->trans_commit();

			if ($mode == "update")
			{
				log_write("notification", "inc_attributes", "Attribute value successfully updated.");
			}
			else
			{
				log_write("notification", "inc_attributes", "Attribute value successfully created.");
			}


			return $this->id;
		}

	} // end of action_update_details


	/*
		action_delete_value

		Deletes an attribute value.

		Note: the check_delete_lock function should be executed before calling
		this function to ensure database integrity.

		Results
		0	failure
		1	success
	*/
	
	function action_delete()
	{
		log_debug("inc_attributes", "Executing action_delete()");

		// start transaction
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();


		// delete the product
		$sql_obj->string	= "DELETE FROM attributes_values WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();


		journal_delete_entire("attributes_values", $this->id);

		// commit
		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error". "inc_attributes". "An error occured whilst attempting to delete the product. No changes have been made.");
		}
		else
		{
			$sql_obj->trans_commit();

			log_write("notification", "inc_attributes", "Attribute Key has been successfully deleted.");
		}

		return 1;
	}





}


?>