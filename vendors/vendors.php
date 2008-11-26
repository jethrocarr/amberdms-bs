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


		// define all the columns and structure
		$vendor_list->add_column("standard", "code_vendor", "");
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
		$vendor_list->columns		= array("code_vendor", "name_vendor", "name_contact", "contact_phone", "contact_email");
		$vendor_list->columns_order	= array("name_vendor");

		// define SQL structure
		$vendor_list->sql_obj->prepare_sql_settable("vendors");
		$vendor_list->sql_obj->prepare_sql_addfield("id", "");

		// acceptable filter options
		$structure = NULL;
		$structure["fieldname"] = "date_start";
		$structure["type"]	= "date";
		$structure["sql"]	= "date_start >= 'value'";
		$vendor_list->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] = "date_end";
		$structure["type"]	= "date";
		$structure["sql"]	= "date_end <= 'value' AND date_end != '0000-00-00'";
		$vendor_list->add_filter($structure);
		
		$structure = NULL;
		$structure["fieldname"] = "searchbox";
		$structure["type"]	= "input";
		$structure["sql"]	= "code_vendor LIKE '%value%' OR name_vendor LIKE '%value%' OR name_contact LIKE '%value%' OR contact_email LIKE '%value%' OR contact_phone LIKE '%value%' OR contact_fax LIKE '%fax%'";
		$vendor_list->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "hide_ex_vendors";
		$structure["type"]		= "checkbox";
		$structure["sql"]		= "date_end='0000-00-00'";
		$structure["defaultvalue"]	= "on";
		$structure["options"]["label"]	= "Hide any vendors who are no longer active";
		$vendor_list->add_filter($structure);
		

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
			// view link
			$structure = NULL;
			$structure["id"]["column"] = "id";
			$vendor_list->add_link("view", "vendors/view.php", $structure);

			// display the table
			$vendor_list->render_table();

			// TODO: display CSV download link
		}

		
	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
