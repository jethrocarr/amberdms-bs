<?php
/*
	timekeeping/timekeeping.php

	Summary page for booked time for either the currently viewing employee, or all employees
	if the user has the correct permissions level.
*/

class page_output
{
	var $access_staff_ids;


	function check_permissions()
	{
		if (user_permissions_get("timekeeping"))
		{
			// accept user if they have access to all staff
			if (user_permissions_get("timekeeping_all_view"))
			{
				return 1;
			}

			// select the IDs that the user does have access to
			if ($this->access_staff_ids = user_permissions_staff_getarray("timereg_view"))
			{
				return 1;
			}
			else
			{
				log_write("error", "page", "Before you can view or enter values into timesheets, your administrator must configure the staff accounts you may access, or set the timekeeping_all_view permission.");
				log_write("error", "page", "Until access has been configured, the timesheet functions will be unavailable.");
				return 1;
			}
		}
	}

	function check_requirements()
	{
		// nothing todo
		return 1;
	}

	function execute()
	{
		// nothing todo
		return 1;
	}

	function render_html()
	{
		print "<h3>TIME KEEPING</h3>";
		print "<br>";

		


		/*
			Time Booked
		*/

		// fetch amount of time booked for today
		$sql_obj = New sql_query;
		$sql_obj->prepare_sql_settable("timereg");
		$sql_obj->prepare_sql_addfield("timebooked", "SUM(timereg.time_booked)");
		$sql_obj->prepare_sql_addwhere("date='". date("Y-m-d") ."'");

		if ($this->access_staff_ids)
		{
			$sql_obj->prepare_sql_addwhere("employeeid IN (". format_arraytocommastring($this->access_staff_ids) .")");
		}

		$sql_obj->generate_sql();

		$sql_obj->execute();
		$sql_obj->fetch_array();


		list($booked_time_hours, $booked_time_mins) = explode(":", time_format_hourmins($sql_obj->data[0]["timebooked"]));

		if ($booked_time_hours > 0 && $booked_time_mins > 0)
		{
			$message = "Time booked for today: $booked_time_hours hours and $booked_time_mins minutes.";
		}
		elseif ($booked_time_hours > 0)
		{
			$message = "Time booked for today: $booked_time_hours hours.";
		}
		elseif ($booked_time_mins > 0)
		{
			$message = "Time booked for today: $booked_time_mins minutes.";
		}
		else
		{
			$message = "<b>No time has been booked for today - click to add time.</b>";
		}

		// display
		print "<br>";
		format_linkbox("default", "index.php?page=timekeeping/timereg-day.php&date=". date("Y-m-d"), "<p><b>TIME BOOKED</b></p><p>$message</p>");



		/*
			Unbilled Time
		*/
		if (user_permissions_get("projects_timegroup"))
		{
			/*
				Create an array of all unbilled time records. We need to do the following to create this list:
				1. Exclude any internal_only projects.
				2. Include time which belongs to a time_group, but ONLY if the time group has not been added to an invoice.
			*/

			$unbilled_ids = array();


			// select non-internal projects
			$sql_projects_obj		= New sql_query;
			$sql_projects_obj->string	= "SELECT projects.id as projectid, project_phases.id as phaseid FROM project_phases LEFT JOIN projects ON projects.id = project_phases.projectid WHERE projects.internal_only='0'";
			$sql_projects_obj->execute();

			if ($sql_projects_obj->num_rows())
			{
				$sql_projects_obj->fetch_array();

				foreach ($sql_projects_obj->data as $project_data)
				{
					// select non-group time records
					$sql_obj		= New sql_query;
					$sql_obj->string	= "SELECT id FROM timereg WHERE groupid='0' AND phaseid='". $project_data["phaseid"] ."'";
					$sql_obj->execute();

					if ($sql_obj->num_rows())
					{
						$sql_obj->fetch_array();

						foreach ($sql_obj->data as $data_tmp)
						{
							// we store the ID inside an array key, since they are unique
							// and this will prevent us needed to check for the existance of
							// the ID already.
							$unbilled_ids[ $data_tmp["id"] ] = "on";
						}
					}

					unset($sql_obj);


					// select unpaid group IDs
					$sql_obj		= New sql_query;
					$sql_obj->string	= "SELECT id FROM time_groups WHERE projectid='". $project_data["projectid"] ."' AND invoiceid='0'";
					$sql_obj->execute();

					if ($sql_obj->num_rows())
					{
						$sql_obj->fetch_array();

						foreach ($sql_obj->data as $data_group)
						{
							// fetch all the time reg IDs belonging this group, but only select time entries marked as billable - we
							// don't want to report a timegroup with unbillable time as being billed!
							$sql_reg_obj		= New sql_query;
							$sql_reg_obj->string	= "SELECT id FROM timereg WHERE groupid='". $data_group["id"] ."' AND billable='1'";
							$sql_reg_obj->execute();

							if ($sql_reg_obj->num_rows())
							{
								$sql_reg_obj->fetch_array();

								foreach ($sql_reg_obj->data as $data_tmp)
								{
									// we store the ID inside an array key, since they are unique
									// and this will prevent us needed to check for the existance of
									// the ID already.
									$unbilled_ids[ $data_tmp["id"] ] = "on";
								}
							}

							unset($sql_reg_obj);
						}
					}

					unset($sql_obj);
				}
			}




			/*
				Fetch amount of unbilled time
			*/

			// fetch amount of unbilled time
			$sql_obj = New sql_query;
			$sql_obj->prepare_sql_settable("timereg");
			$sql_obj->prepare_sql_addfield("timebooked", "SUM(timereg.time_booked)");
		
			if ($this->access_staff_ids)
			{
				$sql_obj->prepare_sql_addwhere("employeeid IN (". format_arraytocommastring($this->access_staff_ids) .")");
			}

			$sql_obj->prepare_sql_addjoin("LEFT JOIN time_groups ON timereg.groupid = time_groups.id");

			// provide list of valid IDs
			$unbilled_ids_keys	= array_keys($unbilled_ids);
			$unbilled_ids_count	= count($unbilled_ids_keys);
			$unbilled_ids_sql	= "";

			if ($unbilled_ids_count)
			{
				$i = 0;
				foreach ($unbilled_ids_keys as $id)
				{
					$i++;

					if ($i == $unbilled_ids_count)
					{
						$unbilled_ids_sql .= "timereg.id='$id' ";
					}
					else
					{
						$unbilled_ids_sql .= "timereg.id='$id' OR ";
					}
				}
						
				$sql_obj->prepare_sql_addwhere("($unbilled_ids_sql)");
				

				$sql_obj->generate_sql();

				$sql_obj->execute();
				$sql_obj->fetch_array();

				list($unbilled_time_hours, $unbilled_time_mins) = explode(":", time_format_hourmins($sql_obj->data[0]["timebooked"]));


				if ($unbilled_time_hours > 0 && $unbilled_time_mins > 0)
				{
					$message = "There is currently $unbilled_time_hours hours and $unbilled_time_mins minutes of unbilled time to be processed.";
				}
				elseif ($unbilled_time_hours > 0)
				{
					$message = "There is currently $unbilled_time_hours hours of unbilled time to be processed.";
				}
				elseif ($unbilled_time_mins > 0)
				{
					$message = "There is currently $unbilled_time_mins minutes of unbilled time to be processed.";
				}

			}
			else
			{
				$message = "There is no unbilled time to be processed.";
			}


			// display
			print "<br>";
			format_linkbox("default", "index.php?page=timekeeping/unbilled.php", "<p><b>UNBILLED TIME</b></p><p>$message</p>");
		
		}
	}
}

?>	
