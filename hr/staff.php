<?php
/*
	staff.php
	
	access: "staff_view" group members

	Displays a list of all the staff on the system.
*/

if (user_permissions_get('staff_view'))
{
	function page_render()
	{
		// establish a new table object
		$staff_list = New table;

		$staff_list->language	= $_SESSION["user"]["lang"];
		$staff_list->tablename	= "staff_list";
		$staff_list->sql_table	= "staff";

		// define all the columns and structure
		$staff_list->add_column("standard", "id_staff", "id");
		$staff_list->add_column("standard", "staff_code", "staff_code");
		$staff_list->add_column("fullname", "name_staff", "name_staff");
		$staff_list->add_column("standard", "staff_position", "staff_position");
		$staff_list->add_column("standard", "contact_phone", "contact_phone");
		$staff_list->add_column("standard", "contact_email", "contact_email");
		$staff_list->add_column("standard", "contact_fax", "contact_fax");
		$staff_list->add_column("date", "date_start", "date_start");
		$staff_list->add_column("date", "date_end", "date_end");


		// defaults
		$staff_list->columns		= array("name_staff", "staff_code", "staff_position", "contact_phone", "contact_email");
		$staff_list->columns_order	= array("name_staff");


		// heading
		print "<h3>STAFF LIST</h3><br><br>";


		// options form
		$staff_list->load_options_form();
		$staff_list->render_options_form();


		// fetch all the staff information
		$staff_list->generate_sql();
		$staff_list->load_data_sql();

		if (!count($staff_list->columns))
		{
			print "<p><b>Please select some valid options to display.</b></p>";
		}
		elseif (!$staff_list->data_num_rows)
		{
			print "<p><b>You currently have no staff in your database.</b></p>";
		}
		else
		{
			// translate the column labels
			$staff_list->render_column_names();
		
			// display header row
			print "<table class=\"table_content\" width=\"100%\">";
			print "<tr>";
			
				foreach ($staff_list->render_columns as $columns)
				{
					print "<td class=\"header\"><b>". $columns ."</b></td>";
				}
				
				print "<td class=\"header\"></td>";	// filler for link column
				
			print "</tr>";
		
			// display data
			for ($i=0; $i < $staff_list->data_num_rows; $i++)
			{
				print "<tr>";

				foreach ($staff_list->columns as $columns)
				{
					print "<td>". $staff_list->data[$i]["$columns"] ."</td>";
				}
				print "<td><a href=\"index.php?page=hr/staff-view.php&id=". $staff_list->data[$i]["id"] ."\">view</td>";
				
				print "</tr>";
			}

			print "</table>";

			// TODO: display CSV download link

		}

		
	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
