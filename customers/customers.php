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

		// define all the columns and structure
		$customer_list->add_column("standard", "code_customer", "");
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
		$customer_list->columns		= array("code_customer", "name_customer", "name_contact", "contact_phone", "contact_email");
		$customer_list->columns_order	= array("name_customer");

		// define SQL structure
		$customer_list->sql_obj->prepare_sql_settable("customers");
		$customer_list->sql_obj->prepare_sql_addfield("id", "");

		// acceptable filter options
		$structure = NULL;
		$structure["fieldname"] = "date_start";
		$structure["type"]	= "date";
		$structure["sql"]	= "date_start >= 'value'";
		$customer_list->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] = "date_end";
		$structure["type"]	= "date";
		$structure["sql"]	= "date_end <= 'value' AND date_end != '0000-00-00'";
		$customer_list->add_filter($structure);
		
		$structure = NULL;
		$structure["fieldname"] = "searchbox";
		$structure["type"]	= "input";
		$structure["sql"]	= "code_customer LIKE '%value%' OR name_customer LIKE '%value%' OR name_contact LIKE '%value%' OR contact_email LIKE '%value%' OR contact_phone LIKE '%value%' OR contact_fax LIKE '%fax%'";
		$customer_list->add_filter($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "hide_ex_customers";
		$structure["type"]		= "checkbox";
		$structure["sql"]		= "date_end='0000-00-00'";
		$structure["defaultvalue"]	= "on";
		$structure["options"]["label"]	= "Hide any customers who are no longer active";
		$customer_list->add_filter($structure);
		


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
			// details link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$customer_list->add_link("details", "customers/view.php", $structure);

			// invoices link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$customer_list->add_link("invoices", "customers/invoices.php", $structure);

			// services link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$customer_list->add_link("services", "customers/services.php", $structure);



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
