<?php
/*
	SOAP SERVICE -> HR_STAFF_MANAGE

	access:		staff_view
			staff_write


	This service provides APIs for creating, updating and deleting staff employee records.

	Refer to the Developer API documentation for information on using this service
	as well as sample code.
*/


// include libraries
include("../../include/config.php");
include("../../include/amberphplib/main.php");

// custom includes
include("../../include/hr/inc_staff.php");



class hr_staff_manage_soap
{

	/*
		get_employee_details

		Fetch all the details for the requested employee
	*/
	function get_employee_details($id)
	{
		log_debug("hr_staff_manager_soap", "Executing get_employee_details($id)");

		if (user_permissions_get("staff_view"))
		{
			$obj_employee = New hr_staff;


			// sanatise input
			$obj_employee->id = security_script_input_predefined("int", $id);

			if (!$obj_employee->id || $obj_employee->id == "error")
			{
				throw new SoapFault("Sender", "INVALID_INPUT");
			}


			// verify that the ID is valid
			if (!$obj_employee->verify_id())
			{
				throw new SoapFault("Sender", "INVALID_ID");
			}


			// load data from DB for this employee
			if (!$obj_employee->load_data())
			{
				throw new SoapFault("Sender", "UNEXPECTED_ACTION_ERROR");
			}

			// return data
			$return = array($obj_employee->data["name_staff"], 
					$obj_employee->data["staff_code"], 
					$obj_employee->data["staff_position"], 
					$obj_employee->data["contact_phone"],
					$obj_employee->data["contact_fax"], 
					$obj_employee->data["contact_email"], 
					$obj_employee->data["date_start"], 
					$obj_employee->data["date_end"]);

			return $return;
		}
		else
		{
			throw new SoapFault("Sender", "ACCESS_DENIED");
		}

	} // end of get_employee_details




	/*
		set_employee_details

		Creates/Updates an employee record.

		Returns
		0	failure
		#	ID of the employee
	*/
	function set_employee_details($id, $name_staff, $staff_code, $staff_position, $contact_phone, $contact_fax, $contact_email, $date_start, $date_end)
	{
		log_debug("hr_staff_manager", "Executing set_employee_details($id, values...)");

		if (user_permissions_get("staff_write"))
		{
			$obj_employee = New hr_staff;

			
			/*
				Load POST Data
			*/
			$obj_employee->id				= security_script_input_predefined("int", $id);
			$obj_employee->data["name_staff"]		= security_script_input_predefined("any", $name_staff);
			$obj_employee->data["staff_code"]		= security_script_input_predefined("any", $staff_code);
			$obj_employee->data["staff_position"]		= security_script_input_predefined("any", $staff_position);
			$obj_employee->data["contact_phone"]		= security_script_input_predefined("any", $contact_phone);
			$obj_employee->data["contact_fax"]		= security_script_input_predefined("any", $contact_fax);
			$obj_employee->data["contact_email"]		= security_script_input_predefined("email", $contact_email);
			$obj_employee->data["date_start"]		= security_script_input_predefined("date", $date_start);
			$obj_employee->data["date_end"]			= security_script_input_predefined("date", $date_end);

			foreach (array_keys($obj_employee->data) as $key)
			{
				if ($obj_employee->data[$key] == "error")
				{
					throw new SoapFault("Sender", "INVALID_INPUT");
				}
			}



			/*
				Error Handling
			*/

			// verify employee ID (if editing an existing employee)
			if ($obj_employee->id)
			{
				if (!$obj_employee->verify_id())
				{
					throw new SoapFault("Sender", "INVALID_ID");
				}
			}

			// make sure we don't choose a staff name that has already been taken
			if (!$obj_employee->verify_name_staff())
			{
				throw new SoapFault("Sender", "DUPLICATE_NAME_STAFF");
			}


			// make sure we don't choose a staff code that has already been taken
			if ($obj_employee->data["staff_code"])
			{
				if (!$obj_employee->verify_code_staff())
				{
					throw new SoapFault("Sender", "DUPLICATE_CODE_STAFF");
				}
			}



			/*
				Perform Changes
			*/
			
			if ($obj_employee->action_update())
			{
				return $obj_employee->id;
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

	} // end of set_employee_details




	/*
		delete_employee

		Deletes an employee, provided that the employee is not locked.

		Returns
		0	failure
		1	success
	*/
	function delete_employee($id)
	{
		log_debug("hr_staff_manager", "Executing delete_employee_details($id, values...)");

		if (user_permissions_get("staff_write"))
		{
			$obj_employee = New hr_staff;

			
			/*
				Load POST Data
			*/
			$obj_employee->id				= security_script_input_predefined("int", $id);

			if (!$obj_employee || $obj_employee == "error")
			{
				throw new SoapFault("Sender", "INVALID_INPUT");
			}


			/*
				Error Handling
			*/

			// verify employee ID (if editing an existing employee)
			if (!$obj_employee->verify_id())
			{
				throw new SoapFault("Sender", "INVALID_ID");
			}


			// make sure employee is not locked
			if ($obj_employee->check_lock())
			{
				throw new SoapFault("Sender", "LOCKED");
			}



			/*
				Perform Changes
			*/
			if ($obj_employee->action_delete())
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

	} // end of delete_employee



} // end of hr_staff_manage_soap class



// define server
$server = new SoapServer("staff_manage.wsdl");
$server->setClass("hr_staff_manage_soap");
$server->handle();



?>

