<?php
/*
	customers.php
	
	access: "customers_view" group members

	Displays a list of all the customers on the system.
*/

class page_output
{
	var $obj_table_list;


	function check_permissions()
	{
		if (user_permissions_get('customers_view'))
		{
			return 1;
		}
	}

	function check_requirements()
	{
		// nothing todo
		return 1;
	}


	/*
		Define table and load data
	*/
	function execute()
	{
		// define customer list table
		$this->obj_table_list			= New table;
		$this->obj_table_list->language		= $_SESSION["user"]["lang"];
		$this->obj_table_list->tablename	= "customer_list";

		// define all the columns and structure
		$this->obj_table_list->add_column("standard", "code_customer", "");
		$this->obj_table_list->add_column("standard", "name_customer", "");
		$this->obj_table_list->add_column("standard", "name_contact", "");
		$this->obj_table_list->add_column("standard", "contact_phone", "");
		$this->obj_table_list->add_column("standard", "contact_email", "");
		$this->obj_table_list->add_column("standard", "contact_fax", "");
		$this->obj_table_list->add_column("date", "date_start", "");
		$this->obj_table_list->add_column("date", "date_end", "");
		$this->obj_table_list->add_column("standard", "tax_number", "");
		$this->obj_table_list->add_column("standard", "address1_city", "");
		$this->obj_table_list->add_column("standard", "address1_state", "");
		$this->obj_table_list->add_column("standard", "address1_country", "");

		// defaults
		$this->obj_table_list->columns			= array("code_customer", "name_customer", "name_contact", "contact_phone", "contact_email");
		$this->obj_table_list->columns_order		= array("name_customer");
		$this->obj_table_list->columns_order_options	= array("code_customer", "name_customer", "name_contact", "contact_phone", "contact_email", "contact_fax", "date_start", "date_end", "tax_number", "address1_city", "address1_state", "address1_country");

		// define SQL structure
		$this->obj_table_list->sql_obj->prepare_sql_settable("customers");
		$this->obj_table_list->sql_obj->prepare_sql_addfield("id", "");

		// acceptable filter options
		$structure = NULL;
		$structure["fieldname"] = "date_start";
		$structure["type"]	= "date";
		$structure["sql"]	= "date_start >= 'value'";
		$this->obj_table_list->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] = "date_end";
		$structure["type"]	= "date";
		$structure["sql"]	= "date_end <= 'value' AND date_end != '0000-00-00'";
		$this->obj_table_list->add_filter($structure);
		
		$structure = NULL;
		$structure["fieldname"] = "searchbox";
		$structure["type"]	= "input";
		$structure["sql"]	= "code_customer LIKE '%value%' OR name_customer LIKE '%value%' OR name_contact LIKE '%value%' OR contact_email LIKE '%value%' OR contact_phone LIKE '%value%' OR contact_fax LIKE '%fax%'";
		$this->obj_table_list->add_filter($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "hide_ex_customers";
		$structure["type"]		= "checkbox";
		$structure["sql"]		= "date_end='0000-00-00'";
		$structure["defaultvalue"]	= "on";
		$structure["options"]["label"]	= "Hide any customers who are no longer active";
		$this->obj_table_list->add_filter($structure);
		

		// load settings from options form
		$this->obj_table_list->load_options_form();

		// fetch all the customer information
		$this->obj_table_list->generate_sql();
		$this->obj_table_list->load_data_sql();

	} // end of load_data()



	/*
		Output: HTML format
	*/
	function render_html()
	{
		// heading
		print "<h3>CUSTOMER LIST</h3><br><br>";

		// load options form
		$this->obj_table_list->render_options_form();


		// display results
		if (!count($this->obj_table_list->columns))
		{
			format_msgbox("important", "<p>Please select some valid options to display.</p>");
		}
		elseif (!$this->obj_table_list->data_num_rows)
		{
			format_msgbox("info", "<p>You currently have no customers in your database.</p>");
		}
		else
		{
			// details link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table_list->add_link("details", "customers/view.php", $structure);

			// invoices link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table_list->add_link("invoices", "customers/invoices.php", $structure);

			// services link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table_list->add_link("services", "customers/services.php", $structure);


			// display the table
			$this->obj_table_list->render_table_html();

			// display CSV download link
			print "<p align=\"right\"><a href=\"index-export.php?mode=csv&page=customers/customers.php\">Export as CSV</a></p>";
		}
	}


	/*
		Output: CSV text file
	*/
	function render_csv()
	{
		$this->obj_table_list->render_table_csv();
		
	} // end of render_csv


	function render_pdf()
	{
		// TODO: write me
		
	} // end of render_pdf
	

} // end class page_output


?>
