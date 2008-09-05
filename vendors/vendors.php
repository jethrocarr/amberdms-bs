<?php
/*
	vendors.php
	
	access: "vendors_view" group members

	Displays a list of all the vendors on the system.
*/

if (user_permissions_get('vendors_view'))
{
	function page_render()
	{
		// establish a new table object
		$vendor_list = New table;

		$vendor_list->language	= $_SESSION["user"]["lang"];
		$vendor_list->tablename	= "vendor_list";
		$vendor_list->sql_table	= "vendors";


		// define all the columns and structure
		$vendor_list->add_column("standard", "id_vendor", "id");
		$vendor_list->add_column("standard", "name_vendor", "");
		$vendor_list->add_column("standard", "name_contact", "");
		$vendor_list->add_column("standard", "contact_phone", "");
		$vendor_list->add_column("standard", "contact_email", "");
		$vendor_list->add_column("standard", "contact_fax", "");
		$vendor_list->add_column("date", "date_start", "");
		$vendor_list->add_column("date", "date_end", "");
		$vendor_list->add_column("standard", "tax_number", "");
		$vendor_list->add_column("standard", "address1_city", "");
		$vendor_list->add_column("standard", "address1_state", "");
		$vendor_list->add_column("standard", "address1_country", "");

		// defaults
		$vendor_list->columns		= array("name_vendor", "name_contact", "contact_phone", "contact_email");
		$vendor_list->columns_order	= array("name_vendor");


		// heading
		print "<h3>VENDORS/SUPPLIERS LIST</h3><br><br>";


		// options form
		$vendor_list->load_options_form();
		$vendor_list->render_options_form();


		// fetch all the vendor information
		$vendor_list->generate_sql();
		$vendor_list->load_data_sql();

		if (!count($vendor_list->columns))
		{
			print "<p><b>Please select some valid options to display.</b></p>";
		}
		elseif (!$vendor_list->data_num_rows)
		{
			print "<p><b>You currently have no vendors in your database.</b></p>";
		}
		else
		{
			// translate the column labels
			$vendor_list->render_column_names();
		
			// display header row
			print "<table class=\"table_content\" width=\"100%\">";
			print "<tr>";
			
				foreach ($vendor_list->render_columns as $columns)
				{
					print "<td class=\"header\"><b>". $columns ."</b></td>";
				}
				
				print "<td class=\"header\"></td>";	// filler for link column
				
			print "</tr>";
		
			// display data
			for ($i=0; $i < $vendor_list->data_num_rows; $i++)
			{
				print "<tr>";

				foreach ($vendor_list->columns as $columns)
				{
					print "<td>". $vendor_list->data[$i]["$columns"] ."</td>";
				}
				print "<td><a href=\"index.php?page=vendors/view.php&id=". $vendor_list->data[$i]["id"] ."\">view</td>";
				
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
