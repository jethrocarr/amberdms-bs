<?php
/*
	hr/inc_staff.php

	Provides classes for managing employees.
*/




/*
	CLASS: hr_staff

	Provides functions for managing employees.
*/

class hr_staff
{
	var $id;		// holds employee ID
	var $data;		// holds values of record fields



	/*
		verify_id

		Checks that the provided ID is a valid employee.

		Results
		0	Failure to find the ID
		1	Success - employee exists
	*/

	function verify_id()
	{
		log_debug("inc_staff", "Executing verify_id()");

		if ($this->id)
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM `staff` WHERE id='". $this->id ."' LIMIT 1";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				return 1;
			}
		}

		return 0;

	} // end of verify_id




	/*
		verify_name_staff

		Checks that the name_staff supplied has not been taken by another employee already.

		Results
		0	Failure - name in use
		1	Success - name is available
	*/

	function verify_name_staff()
	{
		log_debug("inc_staff", "Executing verify_name_staff()");

		$sql_obj			= New sql_query;
		$sql_obj->string		= "SELECT id FROM `staff` WHERE name_staff='". $this->data["name_staff"] ."' ";

		if ($this->id)
			$sql_obj->string	.= " AND id!='". $this->id ."'";

		$sql_obj->string		.= " LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			return 0;
		}
		
		return 1;

	} // end of verify_name_staff



	/*
		check_lock

		Checks if the employee is locked or not.

		Results
		0	Unlocked
		1	Locked
	*/

	function check_lock()
	{
		log_debug("inc_staff", "Executing check_lock()");

		// check if employee belongs to any AR invoices
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_ar WHERE employeeid='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			return 1;
		}


		// check if employee belongs to any AP invoices
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_ap WHERE employeeid='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			return 1;
		}


		// check if employee belongs to any quotes
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_quotes WHERE employeeid='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			return 1;
		}

		// check if employee has booked time
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM timereg WHERE employeeid='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			return 1;
		}


		// unlocked
		return 0;

	}  // end of check_lock



	/*
		load_data

		Load the employee's information into the $this->data array.

		Returns
		0	failure
		1	success
	*/
	function load_data()
	{
		log_debug("inc_staff", "Executing load_data()");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT * FROM staff WHERE id='". $this->id ."' LIMIT 1";
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

		Create a new employee based on the data in $this->data

		Results
		0	Failure
		#	Success - return ID of new employee
	*/
	function action_create()
	{
		log_debug("inc_staff", "Executing action_create()");

		// create a new employee record
		$sql_obj		= New sql_query;
		$sql_obj->string	= "INSERT INTO `staff` (name_staff) VALUES ('". $this->data["name_staff"]. "')";
		$sql_obj->execute();

		$this->id = $sql_obj->fetch_insert_id();

		if ($this->id)
		{
			// call update function to load all the fields
			if ($this->action_update())
			{
				// post to journal
				journal_quickadd_event("staff", $this->id, "Employee successfully created.");

				// success
				log_write("notification", "inc_staff", "Employee successfully created.");
				return $this->id;
			}
		}

		// failure
		return 0;

	} // end of action_create




	/*
		action_update

		Update an employee's details based on the data in $this->data

		Returns
		0	failure
		1	success
	*/
	function action_update()
	{
		log_debug("inc_staff", "Executing action_update()");

		// update the employee
		$sql_obj		= New sql_query;
		$sql_obj->string	= "UPDATE `staff` SET "
						."name_staff='". $this->data["name_staff"] ."', "
						."staff_code='". $this->data["staff_code"] ."', "
						."staff_position='". $this->data["staff_position"] ."', "
						."contact_phone='". $this->data["contact_phone"] ."', "
						."contact_email='". $this->data["contact_email"] ."', "
						."contact_fax='". $this->data["contact_fax"] ."', "
						."date_start='". $this->data["date_start"] ."', "
						."date_end='". $this->data["date_end"] ."' "
						."WHERE id='". $this->id ."'";
		if (!$sql_obj->execute())
		{
			return 0;
		}

		unset($sql_obj);


		// add journal entry
		//
		// note that we only want to do this if updating an existing employee, but
		// if the action_update function has been called by the action_create function this should not be posted.
		//
		// We check this by seeing if any journal entries currently exist for the employee. If none do, then must be
		// a new employee.
		//

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM journal WHERE journalname='staff' AND customid='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			// employee already exists, so post update message to journal
			journal_quickadd_event("staff", $this->id, "Employee successfully adjusted.");

			// UI notification
			log_write("notification", "inc_staff", "Employee successfully adjusted.");
		}

		unset($sql_obj);


		// success
		return 1;

	} // end of action_update



	/*
		action_delete

		Deletes an employee.

		Note: the check_lock function should be executed before calling this function to ensure database integrity


		Results
		0	failure
		1	success
	*/
	function action_delete()
	{
		log_debug("inc_staff", "Executing action_delete()");

		/*
			Delete Employee
		*/
			
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM staff WHERE id='". $this->id ."'";
			
		if (!$sql_obj->execute())
		{
			log_write("error", "inc_staff", "A fatal SQL error occured whilst trying to delete the employee");
			return 0;
		}


		/*
			Delete User <-> Employee permissions mappings
		*/

		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM users_permissions_staff WHERE staffid='$this->id'";
			
		if (!$sql_obj->execute())
		{
			log_write("error", "inc_staff", "A fatal SQL error occured whilst trying to delete the user-employee permissions mappings");
			return 0;
		}	



		/*
			Delete Journal
		*/
		journal_delete_entire("staff", $this->id);


		log_write("notification", "inc_staff", "Employee has been successfully deleted.");

		return 1;
	}


} // end of class:hr_staff


?>
