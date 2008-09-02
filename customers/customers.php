<?php
/*
	customers.php
	
	access: "customers_view" group members

	Displays a list of all the customers on the system.
*/

if (user_permissions_get('customers_view'))
{
	function page_render()
	{
		// establish a new table object
		$customer_list = New table;

		$customer_list->language	= $_SESSION["user"]["lang"];
		$customer_list->sql_table	= "customers";
		$customer_list->columns		= array("name_customer", "name_contact", "contact_phone", "contact_email");
		$customer_list->columns_order	= array("name_customer");

		// heading
		print "<h3>CUSTOMER MANAGEMENT</h3><br><br>";

		// fetch all the customer information
		$customer_list->generate_sql();
		$customer_list->generate_data();

		if (!$customer_list->data_num_rows)
		{
			print "<p><b>You currently have no customers in your database.</b></p>";
		}
		else
		{
			// translate the column labels
			$customer_list->render_column_names();
		
			// display header row
			print "<table class=\"table_content\" width=\"100%\">";
			print "<tr>";
			
				foreach ($customer_list->render_columns as $columns)
				{
					print "<td class=\"header\"><b>". $columns ."</b></td>";
				}
				
				print "<td class=\"header\"></td>";	// filler for link column
				
			print "</tr>";
		
			// display data
			for ($i=0; $i < $customer_list->data_num_rows; $i++)
			{
				print "<tr>";

				foreach ($customer_list->columns as $columns)
				{
					print "<td>". $customer_list->data[$i]["$columns"] ."</td>";
				}
				print "<td><a href=\"index.php?page=customers/view.php&id=". $customer_list->data[$i]["id"] ."\">view</td>";
				
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
