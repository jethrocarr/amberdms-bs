<?php
/*
	include/account/inc_credit.php

	Provides classes and functions for handing customer credits/prepayments/refunds.
*/



/*
	CLASS: credit

	Functions for creating, assigning and manipulating a credit transaction.

*/
class credit
{
	var $id;			// ID of the credit transaction
	var $id_organisation;		// ID of the organisation that the credit applies to
	var $id_organisation_type;	// Type of the organisation (vendor or customer)

	var $data;			// data of the transactions
	

	/*
		get_org_balance

		Returns the balance of the credits for the selected organisation

		Values
		$this->id_organisation
		$this->id_organisation_type

		Return
		-1		Unable to find a result
		#		Current balance of credits
	*/
	function get_org_balance()
	{
		log_write("debug", "credit", "Executing get_org_balance()");


		$sql_obj		= New sql_query;

		if ($id_organisation_type == "customer")
		{
			$sql_obj->string	= "SELECT SUM(amount) as balance FROM account_credit WHERE id_organisation='". $this->id_organisation ."' AND (type='refund' OR type='prepay' OR type='payment')";
		}
		else
		{
			$sql_obj->string	= "SELECT SUM(amount) as balance FROM account_credit WHERE id_organisation='". $this->id_organisation ."' AND (type='vendor' OR type='purchase')";
		}

		if (!$sql_obj->execute())
		{
			return -1;
		}
		else
		{
			$sql_obj->fetch_array();

			return $sql_obj->data[0]["balance"];
		}

	} // end of get_org_balance



	/*
		verify_id

		Verify that the selected credit transaction is valid

		Results
		0	Failure to find the ID
		1	Success
	*/

	function verify_id()
	{
		log_write("debug", "credit", "Executing verify_id()");

		if ($this->id)
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id_organisation FROM `account_credit` WHERE id='". $this->id ."' LIMIT 1";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				$sql_obj->fetch_array();


				// verify ID of customer/vendor
				if ($this->id_organisation)
				{
					if ($this->id_organisation == $sql_obj->data[0]["id_organisation"])
					{
						log_write("debug", "credit", "The selected ". $this->id_organisation_type ." matches the returned value");
					}
					else
					{
						log_write("error", "credit", "The selected ". $this->id_organisation_type ." of ". $this->id_organisation ." does not match the returned value of (". $sql_obj->data[0]["id_organisation"] .").");
						return 0;
					}
				}
				else
				{
					$this->id_organisation = $sql_obj->data[0]["id_organisation"];

					log_write("debug", "credit", "Setting ". $this->id_organisation_type ." to ". $this->id_organisation ."");
				}
			}
		}

		return 0;

	} // end of verify_id



	/*
		load_data

		Load the selected credit transaction

		Results
		0	Failure
		1	Success
	*/
	function load_data()
	{
		log_write("debug", "credit", "Executing load_data()");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT * FROM account_credit WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$sql_obj->fetch_array();

			$this->data = $sql_obj->data[0];

			return 1;
		}

		return 0;

	} // end of load_data
	


	/*
		action_create

		Creates a new credit transaction based on the value in $this->data

		Results
		0	Failure
		#	Success - return ID
	*/
	function action_create()
	{
		log_debug("credit", "Executing action_create()");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "INSERT INTO `account_credit` (id_organisation) VALUES ('". $this->id_organisation . "')";
		$sql_obj->execute();

		$this->id = $sql_obj->fetch_insert_id();

		return $this->id;

	} // end of action_create




	/*
		action_update

		Update the credit transaction

		Returns
		0	failure
		#	success - returns the ID
	*/
	function action_update()
	{
		log_debug("credit", "Executing action_update()");


		/*
			Start Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();



		/*
			If no ID supplied, create a new credit transaction
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
			Update transaction
		*/

		$sql_obj->string	= "UPDATE `account_credit` SET "
						."locked='". $this->data[""] ."', "
						."id_organisation='". $this->data["id_organisation"] ."', "
						."id_employee='". $this->data["id_employee"] ."', "
						."id_custom='". $this->data["id_customer"] ."', "
						."type='". $this->data["type"] ."', "
						."code_credit='". $this->data["code_credit"] ."', "
						."date_trans='". $this->data["date_trans"] ."', "
						."amount='". $this->data["amount"] ."', "
						."description='". $this->data["description"] ."' "
						."WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		
		/*
			Commit
		*/

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "credit", "An error occured when updating credit transaction");

			return 0;
		}
		else
		{
			$sql_obj->trans_commit();

			if ($mode == "update")
			{
				log_write("notification", "credit", "Credit transaction successfully updated.");
			}
			else
			{
				log_write("notification", "credit", "Credit transaction successfully created.");
			}
			
			return $this->id;
		}

	} // end of action_update




	/*
		action_delete

		Deletes a credit transaction

		Results
		0	failure
		1	success
	*/
	function action_delete()
	{
		log_debug("credit", "Executing action_delete()");


		/*
			Start Transaction
		*/

		$sql_obj = New sql_query;
		$sql_obj->trans_begin();


		/*
			Delete credit transaction
		*/
			
		$sql_obj->string	= "DELETE FROM account_credit WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();


		/*
			Commit
		*/
		
		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "credit", "An error occured whilst trying to delete the credit transaction");

			return 0;
		}
		else
		{
			$sql_obj->trans_commit();

			log_write("notification", "credit", "Credit transaction has been successfully deleted.");

			return 1;
		}
	}



} // end of class_credit




?>
