<?php
/*
	include/accounts/inc_charts.php

	Contains various functions for easing working with charts, as well as the main class for managing the charts
	themselves.
*/


/*
	FUNCTIONS
*/


/*
	charts_form_prepare_acccountdropdown($fieldname, $menuname)

	Returns a structure for creating a form drop down of charts with suitable menu configurations.

	Values
	fieldname		Name of the form dropdown
	menuid/menuname		Either the ID or name (value) of the menu item. It is recommended
				to use the name for clarity of code and the ID will probably be phased out eventually.
*/
function charts_form_prepare_acccountdropdown($fieldname, $menu_name)
{
	log_debug("inc_charts", "Executing charts_form_prepare_accountdropdown($fieldname, $menu_name)");


	// see if we need to fetch the ID for the name
	// (see function comments - this will be phased out eventually)
	if (is_int($menu_name))
	{
		log_debug("inc_charts", "Obsolete: Use of menu ID rather than menu name");

		$menuid = $menu_name;
	}
	else
	{
		$menuid = sql_get_singlevalue("SELECT id as value FROM account_chart_menu WHERE value='$menu_name'");
	}


	// fetch list of suitable charts belonging to the menu requested.
	$sql_query	= "SELECT "
			."account_charts.id as id, "
			."account_charts.code_chart as label, "
			."account_charts.description as label1 "
			."FROM account_charts "
			."LEFT JOIN account_charts_menus ON account_charts_menus.chartid = account_charts.id "
			."WHERE account_charts_menus.menuid='$menuid' "
			."ORDER BY account_charts.code_chart";
											
	$return = form_helper_prepare_dropdownfromdb($fieldname, $sql_query);

	// if we don't get any form data returned this means no charts with the required
	// permissions exist in the database, so we need to return a graceful error.
	if (!$return)
	{
		$structure = NULL;
		$structure["fieldname"]			= $fieldname;
		$structure["type"]			= "text";
		$structure["defaultvalue"]		= "No suitable charts avaliable";

		return $structure;
	}
	else
	{
		return $return;
	}
	
}



/*
	CLASSES
*/

/*
	CLASS: chart

	Provides functions for managing charts.
*/

class chart
{
	var $id;		// holds chart ID
	var $data;		// holds values of record fields



	/*
		verify_id

		Checks that the provided ID is a valid chart

		Results
		0	Failure to find the ID
		1	Success - chart exists
	*/

	function verify_id()
	{
		log_debug("inc_charts", "Executing verify_id()");

		if ($this->id)
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM `account_charts` WHERE id='". $this->id ."' LIMIT 1";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				return 1;
			}
		}

		return 0;

	} // end of verify_id



	/*
		verify_code_chart

		Checks that the code_chart value supplied has not already been taken.

		Results
		0	Failure - name in use
		1	Success - name is available
	*/

	function verify_code_chart()
	{
		log_debug("inc_charts", "Executing verify_code_chart()");

		$sql_obj			= New sql_query;
		$sql_obj->string		= "SELECT id FROM `account_charts` WHERE code_chart='". $this->data["code_chart"] ."' ";

		if ($this->id)
			$sql_obj->string	.= " AND id!='". $this->id ."'";

		$sql_obj->string		.= " LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			return 0;
		}
		
		return 1;

	} // end of verify_code_chart



	/*
		check_delete_lock

		Checks if the chart is safe to delete or not

		Results
		0	Unlocked
		1	Locked
	*/

	function check_delete_lock()
	{
		log_debug("inc_charts", "Executing check_delete_lock()");


		// make sure chart has no transactions in it
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_trans WHERE chartid='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			return 1;
		}

		unset($sql_obj);


		// make sure chart has no items belonging to it - this will catch quotes which
		// won't have any entry in the ledger table, but do have an entry in the items table
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_items WHERE chartid='". $this->id ."' LIMIT 1";
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

		Load the chart's information into the $this->data array.

		Returns
		0	failure
		1	success
	*/
	function load_data()
	{
		log_debug("inc_charts", "Executing load_data()");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT * FROM account_charts WHERE id='". $this->id ."' LIMIT 1";
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

		Create a new chart based on the data in $this->data

		Results
		0	Failure
		#	Success - return ID
	*/
	function action_create()
	{
		log_debug("inc_charts", "Executing action_create()");

		// create a new chart
		$sql_obj		= New sql_query;
		$sql_obj->string	= "INSERT INTO `account_charts` (chart_type) VALUES ('". $this->data["chart_type"]. "')";
		$sql_obj->execute();

		$this->id = $sql_obj->fetch_insert_id();

		return $this->id;

	} // end of action_create



	/*
		action_update

		Wrapper function that executes both action_update_details and action_update_menu

		Returns
		0	failure
		#	success - returns the ID
	*/
	function action_update()
	{
		log_debug("inc_charts", "Executing action_update()");


		/*
			Start Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();



		/*
			If no ID exists, create a new account first
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
			log_write("error", "inc_charts", "An error occured when updating account details.");
		}


		// update menu options
		if (!$this->action_update_menu())
		{
			log_write("error", "inc_charts", "An error occured when updating account menu options.");
		}

		
		/*
			Commit
		*/

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "inc_charts", "An error occured whilst attempting to update account. No changes have been made.");
		}
		else
		{
			$sql_obj->trans_commit();

			if ($mode == "update")
			{
				log_write("notification", "inc_charts", "Account details successfully updated.");
			}
			else
			{
				log_write("notification", "inc_charts", "Account successfully created.");
			}
		}


		// success
		return $this->id;

	} // end of action_update



	/*
		action_update_details

		Update a chart's details based on the data in $this->data. If no ID is provided,
		it will first call the action_create function.

		Returns
		0	failure
		#	success - returns the ID
	*/
	function action_update_details()
	{
		log_debug("inc_charts", "Executing action_update()");


		/*
			Start Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();


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
			$this->data["code_chart"] = config_generate_uniqueid("CODE_ACCOUNT", "SELECT id FROM account_charts WHERE code_chart='VALUE'");
		}



		/*
			Update chart details
		*/

		$sql_obj->string	= "UPDATE `account_charts` SET "
						."code_chart='". $this->data["code_chart"] ."', "
						."description='". $this->data["description"] ."' "
						."WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();



		/*
			Commit
		*/
		if (error_check())
		{
			$sql_obj->trans_rollback();
			
			return 0;
		}
		else
		{
			$sql_obj->trans_commit();

			return $this->id;
		}

	} // end of action_update_details




	/*
		action_update_menu

		A chart will have a number of different menu options selected - we need to run through
		all the options provided and process them accordingly.

		This takes quite a few SQL calls, as we need to remove old permissions
		and add new ones on a one-by-one basis.

		TODO: take a look at performance optimisation of this code

		All menu options must be set by:
		$this->data["menuoptions"]["name"]	= on/off

		Returns
		0	failure
		1	success
	*/

	function action_update_menu()
	{
		log_debug("inc_charts", "Executing action_update_menu()");

		// start transaction
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();


		// fetch all the menu options
		$sql_obj_menu		= New sql_query;
		$sql_obj_menu->string	= "SELECT id, value FROM `account_chart_menu`";
		$sql_obj_menu->execute();
		$sql_obj_menu->fetch_array();

		foreach ($sql_obj_menu->data as $data_menu)
		{
			// check if any current settings exist
			$sql_obj->string	= "SELECT id FROM account_charts_menus WHERE chartid='". $this->id ."' AND menuid='". $data_menu["id"] ."' LIMIT 1";
			$sql_obj->execute();

			
			if ($sql_obj->num_rows())
			{
				// chart has this menu option set

				// if the new setting is "off", delete the current setting.
				if ($this->data["menuoptions"][ $data_menu["value"] ] != "on")
				{
					$sql_obj->string	= "DELETE FROM account_charts_menus WHERE chartid='". $this->id ."' AND menuid='". $data_menu["id"] ."' LIMIT 1";
					$sql_obj->execute();
				}

				// if new setting is "on", we don't need todo anything.
			}
			else
			{	// no current option exists

				// if the new option is "on", insert a new entry
				if ($this->data["menuoptions"][ $data_menu["value"] ] == "on")
				{
					$sql_obj->string	= "INSERT INTO account_charts_menus (chartid, menuid) VALUES ('". $this->id ."', '". $data_menu["id"] ."')";
					$sql_obj->execute();
				}

				// if new option is "off", we don't need todo anything.
			}
			
		} // end of loop through menu items


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

	} // end of action_update_menu



	/*
		action_update_menu_singleoption

		This function updates a single menu option for the selected chart
		based on the values supplied to the function.

		This function is intended for use by the SOAP API which can only
		handle a single option per query.

		When wanting to change a number of options at once, use the
		action_update_menu function above.

		Values
		name		Name of the menu option
		status		Status: "on" or "off"

		Returns
		0		failure
		1		success
	*/
	function action_update_menu_singleoption($name, $status)
	{
		log_debug("inc_charts", "Execting action_update_menu_singleoption($name, $status)");


		// fetch ID of the menu option
		$menuid	= sql_get_singlevalue("SELECT id as value FROM account_chart_menu WHERE value='". $name ."'");

		if (!$menuid)
		{
			// invalid ID
			log_write("error", "inc_charts", "Requested menu option \"$name\" does not exist");
			return 0;
		}


		// fetch the current status
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_charts_menus WHERE chartid='". $this->id ."' AND menuid='". $menuid ."'";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			// menu option is currently on for this chart

			// if the new setting is "off", delete the current setting.
			if ($status == "off")
			{
				$sql_obj		= New sql_query;
				$sql_obj->string	= "DELETE FROM account_charts_menus WHERE chartid='". $this->id ."' AND menuid='". $menuid ."' LIMIT 1";

				if (!$sql_obj->execute())
				{
					return 0;
				}
			}

			// if new setting is "on", we don't need todo anything.
		}
		else
		{
			// no current option exists

			// if the new option is "on", insert a new entry
			if ($status == "on")
			{
				$sql_obj = New sql_query;
				$sql_obj->string = "INSERT INTO account_charts_menus (chartid, menuid) VALUES ('". $this->id ."', '". $menuid ."') LIMIT 1";
				
				if (!$sql_obj->execute())
				{
					return 0;
				}
			}

			// if new option is "off", we don't need todo anything.
		}


		return 1;

	} // end of action_update_menu_singleoption



	/*
		action_delete

		Deletes a chart.

		Note: the check_delete_lock function should be executed before calling
		this function to ensure database integrity.

		Results
		0	failure
		1	success
	*/
	function action_delete()
	{
		log_debug("inc_charts", "Executing action_delete()");

		/*
			Start Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();


		/*
			Delete chart
		*/
			
		$sql_obj->string	= "DELETE FROM account_charts WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();



		/*
			Delete all chart menu options
		*/

		$sql_obj->string	= "DELETE FROM account_charts_menus WHERE chartid='". $this->id ."'";
		$sql_obj->execute();



		/*
			Commit
		*/

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "inc_charts", "An error occured whilst attempting to delete account. No changes have been made.");

			return 0;
		}
		else
		{
			$sql_obj->trans_commit();

			log_write("notification", "inc_charts", "Account has been successfully deleted.");

			return 1;
		}

	} // end of action_delete


} // end of class:charts







?>
