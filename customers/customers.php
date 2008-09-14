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
		$customer_list->tablename	= "customer_list";
		$customer_list->sql_table	= "customers";

		// define all the columns and structure
		$customer_list->add_column("standard", "id_customer", "id");
		$customer_list->add_column("standard", "name_customer", "");
		$customer_list->add_column("standard", "name_contact", "");
		$customer_list->add_column("standard", "contact_phone", "");
		$customer_list->add_column("standard", "contact_email", "");
		$customer_list->add_column("standard", "contact_fax", "");
		$customer_list->add_column("date", "date_start", "");
		$customer_list->add_column("date", "date_end", "");
		$customer_list->add_column("standard", "tax_number", "");
		$customer_list->add_column("standard", "address1_city", "");
		$customer_list->add_column("standard", "address1_state", "");
		$customer_list->add_column("standard", "address1_country", "");

		// defaults
		$customer_list->columns		= array("name_customer", "name_contact", "contact_phone", "contact_email");
		$customer_list->columns_order	= array("name_customer");

		// custom SQL stuff
		$customer_list->prepare_sql_addfield("id", "");


		// heading
		print "<h3>CUSTOMER LIST</h3><br><br>";


		// options form
		$customer_list->load_options_form();
		$customer_list->render_options_form();


		// fetch all the customer information
		$customer_list->generate_sql();
		$customer_list->load_data_sql();

		if (!count($customer_list->columns))
		{
			print "<p><b>Please select some valid options to display.</b></p>";
		}
		elseif (!$customer_list->data_num_rows)
		{
			print "<p><b>You currently have no customers in your database.</b></p>";
		}
		else
		{
			// view link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$customer_list->add_link("view", "customers/view.php", $structure);

			// display the table
			$customer_list->render_table();

			// TODO: display CSV download link
		}

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
