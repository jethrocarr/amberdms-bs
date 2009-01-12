<?php
/*
	include/accounts/inc_gl.php

	Provides functions and classes for working with GL transactions.
*/



/*
	CLASSES
*/

/*
	CLASS: gl_transaction

	Provides functions for managing GL transactions.
*/

class gl_transaction
{
	var $id;		// holds chart ID
	var $data;		// holds values of record fields



	/*
		verify_id

		Checks that the provided ID is a valid GL transaction

		Results
		0	Failure to find the ID
		1	Success - transaction exists
	*/

	function verify_id()
	{
		log_debug("inc_gl", "Executing verify_id()");

		if ($this->id)
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM `account_gl` WHERE id='". $this->id ."' LIMIT 1";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				return 1;
			}
		}

		return 0;

	} // end of verify_id



	/*
		verify_code_gl

		Checks that the code_gl value supplied has not already been taken.

		Results
		0	Failure - code in use
		1	Success - code is available
	*/

	function verify_code_gl()
	{
		log_debug("inc_gl", "Executing verify_code_chart()");

		$sql_obj			= New sql_query;
		$sql_obj->string		= "SELECT id FROM `account_gl` WHERE code_gl='". $this->data["code_gl"] ."' ";

		if ($this->id)
			$sql_obj->string	.= " AND id!='". $this->id ."'";

		$sql_obj->string		.= " LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			return 0;
		}
		
		return 1;

	} // end of verify_code_gl



	/*
		verify_valid_trans

		Checks that all the transactions currently defined in $this->data are balanced
		and therefore valid.

		Results
		-1	Failure - no transaction values
		0	Failure - transactions unbalanced
		1	Success
	*/
	
	function verify_valid_trans()
	{

		/*
			Check Balance
		*/

		// add transaction rows to total credits and debits
		for ($i = 0; $i < $this->data["num_trans"]; $i++)
		{
			$this->data["total_credit"]	+= $this->data["trans"][$i]["credit"];
			$this->data["total_debit"]	+= $this->data["trans"][$i]["debit"];
		}

		// pad values
		$this->data["total_credit"]		= sprintf("%0.2f", $this->data["total_credit"]);
		$this->data["total_debit"]		= sprintf("%0.2f", $this->data["total_debit"]);

		// total credit and debits need to match up
		if ($this->data["total_credit"] != $this->data["total_debit"])
		{
			log_write("error", "process", "The total credits and total debits need to be balance before the transaction can be saved.");
			return 0;
		}


		/*
			Check that financial figures have been supplied
		*/

		// make sure some values have been supplied
		if ($this->data["total_credit"] == "0.00" && $this->data["total_debit"] == "0.00")
		{
			log_write("error", "process", "You must enter some transaction information before you can save this transaction.");
			return -1;
		}

		// success
		return 1;

	} // end of verify_valid_trans


	/*
		check_lock

		Returns whether the transaction is locked or not.

		Results
		0	Unlocked
		1	Locked
		2	Failure (fail safe by reporting lock)
	*/

	function check_lock()
	{
		log_debug("inc_gl", "Executing check_lock()");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT locked FROM `account_gl` WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$sql_obj->fetch_array();

			return $sql_obj->data[0]["locked"];
		}

		// failure
		return 2;

	}  // end of check_lock



	/*
		check_delete_lock

		Checks if the GL transaction is safe to delete or not and returns the lock status.

		Results
		0	Unlocked
		1	Locked
		2	Failure (fail safe by reporting lock)
	*/

	function check_delete_lock()
	{
		log_debug("inc_gl", "Executing check_delete_lock()");

		return $this->check_lock();

	}  // end of check_delete_lock



	/*
		load_data

		Load the transaction information into the $this->data array.

		Returns
		0	failure
		1	success
	*/
	function load_data()
	{
		log_debug("inc_gl", "Executing load_data()");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT * FROM account_gl WHERE id='". $this->id ."' LIMIT 1";
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

		Create a new GL transaction based on the data in $this->data. This function only creates a place holder
		for the transaction to get the row ID, all the actual data insertion is handled by action_update.

		Results
		0	Failure
		#	Success - return ID
	*/
	function action_create()
	{
		log_debug("inc_gl", "Executing action_create()");

		// create a new transaction
		$sql_obj		= New sql_query;
		$sql_obj->string	= "INSERT INTO `account_gl` (description) VALUES ('". $this->data["description"]. "')";
		$sql_obj->execute();

		$this->id = $sql_obj->fetch_insert_id();

		return $this->id;

	} // end of action_create



	/*
		action_update

		Wrapper function that executes both action_update_details and action_update_rows

		Returns
		0	failure
		#	success - returns the ID
	*/
	function action_update()
	{
		log_debug("inc_gl", "Executing action_update()");


		/*
			If no ID exists, create a new transaction first
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


		// update details
		if (!$this->action_update_details())
		{
			return 0;
		}


		// update transaction rows
		if (!$this->action_update_rows())
		{
			return 0;
		}

		
		// notification
		if ($mode == "update")
		{
			log_write("notification", "inc_gl", "Transaction successfully updated.");
		}
		else
		{
			log_write("notification", "inc_gl", "Transaction successfully created.");
		}


		// success
		return $this->id;

	} // end of action_update



	/*
		action_update_details

		Update the transaction details based on the data in $this->data. If no ID is provided,
		it will first call the action_create function.

		Returns
		0	failure
		#	success - returns the ID
	*/
	function action_update_details()
	{
		log_debug("inc_gl", "Executing action_update_details()");


		/*
			If no ID exists, create a new account first

			(Note: if this function has been called by the action_update() wrapper function
			this step will already have been performed and we can just ignore it)
		*/
		if (!$this->id)
		{
			if (!$this->action_create())
			{
				return 0;
			}
		}


		/*
			All charts require a code_chart value. If one has not been provided, automatically
			generate one
		*/

		if (!$this->data["code_chart"])
		{
			$this->data["code_gl"] = config_generate_uniqueid("ACCOUNTS_GL_TRANSNUM", "SELECT id FROM account_gl WHERE code_gl='VALUE'");
		}



		/*
			Update chart details
		*/

		$sql_obj		= New sql_query;
		$sql_obj->string	= "UPDATE `account_gl` SET "
						."code_gl='". $this->data["code_gl"] ."', "
						."date_trans='". $this->data["date_trans"] ."', "
						."employeeid='". $this->data["employeeid"] ."', "
						."description='". $this->data["description"] ."', "
						."notes='". $this->data["chart_type"] ."' "
						."WHERE id='". $this->id ."'";

		if (!$sql_obj->execute())
		{
			log_write("error", "action_update", "Failure while executing update SQL query");
			return 0;
		}

		unset($sql_obj);



		// success
		return $this->id;

	} // end of action_update_details




	/*
		action_update_rows

		Post all the transaction rows to the database

		Structure:

		$this->data["num_trans"]			number of transaction rows
		$this->data["description_useall"]		set to "on" to replace all transaction
								descriptions with $this->data["description"]

		$this->data["trans"][$i]["date_trans"]
		$this->data["trans"][$i]["account"]
		$this->data["trans"][$i]["credit"]
		$this->data["trans"][$i]["debit"]
		$this->data["trans"][$i]["source"]
		$this->data["trans"][$i]["description"]

		Returns
		0	failure
		1	success
	*/

	function action_update_rows()
	{
		log_debug("inc_gl", "Executing action_update_rows()");


		// delete all existing transactions
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM account_trans WHERE type='gl' AND customid='". $this->id ."'";
		$sql_obj->execute();
	

		// run through all transactions
		for ($i = 0; $i < $this->data["num_trans"]; $i++)
		{
			// if enabled, overwrite any description fields of transactions with the master one
			if ($this->data["description_useall"] == "on")
			{
				$this->data["trans"][$i]["description"] = $this->data["description"];
			}


			// post transaction
			if ($this->data["trans"][$i]["account"])
			{
				if ($this->data["trans"][$i]["debit"] != "0.00")
				{
					ledger_trans_add("debit", "gl", $this->id, $this->data["date_trans"], $this->data["trans"][$i]["account"], $this->data["trans"][$i]["debit"], $this->data["trans"][$i]["source"], $this->data["trans"][$i]["description"]);
				}
				else
				{
					ledger_trans_add("credit", "gl", $this->id, $this->data["date_trans"], $this->data["trans"][$i]["account"], $this->data["trans"][$i]["credit"], $this->data["trans"][$i]["source"], $this->data["trans"][$i]["description"]);
				}
			}
		}

		// success
		return 1;

	} // end of action_update_rows



	/*
		action_delete

		Deletes a GL transaction.

		Note: the check_delete_lock function should be executed before calling
		this function to ensure database integrity.

		Results
		0	failure
		1	success
	*/
	function action_delete()
	{
		log_debug("inc_gl", "Executing action_delete()");



		/*
			Delete general ledger details
		*/
			
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM account_gl WHERE id='". $this->id ."'";
			
		if (!$sql_obj->execute())
		{
			log_write("error", "inc_gl", "A fatal SQL error occured whilst trying to delete the transaction");
			return 0;
		}

		unset($sql_obj);



		/*
			Delete transaction items
		*/
		
		$sql_obj		= New sql_query();
		$sql_obj->string	= "DELETE FROM account_trans WHERE type='gl' AND customid='". $this->id ."'";
		$sql_obj->execute();
		
		if (!$sql_obj->execute())
		{
			log_write("error", "inc_gl", "A fatal SQL error occured whilst trying to delete the transaction items.");
			return 0;
		}

		unset($sql_obj);



		// success
		log_write("notification", "inc_gl", "Transaction has been successfully deleted.");

		return 1;
	}


} // end of class:gl_transaction







?>
