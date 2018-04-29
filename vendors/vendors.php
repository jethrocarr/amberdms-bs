<?php
/*
	vendors.php
	
	access: "vendors_view" group members

	Displays a list of all the vendors on the system.
*/


class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_table;


	function check_permissions()
	{
		return user_permissions_get("vendors_view");
	}



	function check_requirements()
	{
		// nothing todo
		return 1;
	}


	function execute()
	{
		// establish a new table object
		$this->obj_table = New table;

		$this->obj_table->language	= $_SESSION["user"]["lang"];
		$this->obj_table->tablename	= "vendor_list";


		// define all the columns and structure
		$this->obj_table->add_column("standard", "code_vendor", "");
		$this->obj_table->add_column("standard", "name_vendor", "");
		$this->obj_table->add_column("standard", "name_contact", "NONE");
		$this->obj_table->add_column("standard", "contact_phone", "NONE");
		$this->obj_table->add_column("standard", "contact_mobile", "NONE");
		$this->obj_table->add_column("standard", "contact_email", "NONE");
		$this->obj_table->add_column("standard", "contact_fax", "NONE");
		$this->obj_table->add_column("date", "date_start", "");
		$this->obj_table->add_column("date", "date_end", "");
		$this->obj_table->add_column("standard", "tax_number", "");
		$this->obj_table->add_column("standard", "address1_city", "");
		$this->obj_table->add_column("standard", "address1_state", "");
		$this->obj_table->add_column("standard", "address1_country", "");

		// defaults
		$this->obj_table->columns		= array("code_vendor", "name_vendor", "name_contact", "contact_phone", "contact_email");
		$this->obj_table->columns_order		= array("name_vendor");
		$this->obj_table->columns_order_options	= array("code_vendor", "name_vendor", "name_contact", "contact_phone", "contact_email", "contact_mobile", "contact_fax", "date_start", "date_end", "tax_number", "address1_city", "address1_state", "address1_country");


		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("vendors");
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "");

		// acceptable filter options
		$structure = NULL;
		$structure["fieldname"] = "date_start";
		$structure["type"]	= "date";
		$structure["sql"]	= "date_start >= 'value'";
		$this->obj_table->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] = "date_end";
		$structure["type"]	= "date";
		$structure["sql"]	= "date_end <= 'value' AND date_end != '0000-00-00'";
		$this->obj_table->add_filter($structure);
		
		$structure = NULL;
		$structure["fieldname"] = "searchbox";
		$structure["type"]	= "input";
		$structure["sql"]	= "(code_vendor LIKE '%value%' OR name_vendor LIKE '%value%' OR name_contact LIKE '%value%' OR contact_email LIKE '%value%' OR contact_phone LIKE '%value%' OR contact_mobile LIKE '%value%' OR contact_fax LIKE '%fax%')";
		$this->obj_table->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "hide_ex_vendors";
		$structure["type"]		= "checkbox";
		$structure["sql"]		= "date_end='0000-00-00'";
		$structure["defaultvalue"]	= "on";
		$structure["options"]["label"]	= "Hide any vendors who are no longer active";
		$this->obj_table->add_filter($structure);
		
		
		// load options
		$this->obj_table->load_options_form();
		
		// fetch all the vendor information
		$this->obj_table->generate_sql();
		$this->obj_table->load_data_sql();
	}



	function render_html()
	{
		// heading
		print "<h3>VENDORS/SUPPLIERS LIST</h3><br><br>";

		// display options form
		$this->obj_table->render_options_form();

		// display data
		if (!count($this->obj_table->columns))
		{
			format_msbbox("important", "<p>Please select some valid options to display.</p>");
		}
		elseif (!$this->obj_table->data_num_rows)
		{
			format_msgbox("info", "<p>You currently have no vendors in your database.</p>");
		}
		else
		{
			// calculate all the totals and prepare processed values
			//$this->obj_table->render_table_prepare();
                    
      			// view link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("view", "vendors/view.php", $structure);

                        // invoices link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("invoices", "vendors/invoices.php", $structure);

                        // credits link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("credits", "vendors/credit.php", $structure);

                        $this->obj_table->render_table_html();

//			 display CSV & PDF download links
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=csv&page=vendors/vendors.php\">Export as CSV</a></p>";
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=pdf&page=vendors/vendors.php\">Export as PDF</a></p>";
		}

	}


	function render_csv()
	{
		// display table
		$this->obj_table->render_table_csv();
	}

	function render_pdf()
	{
		// display table
		$this->obj_table->render_table_pdf();
	}


} // end of page_output

?>
