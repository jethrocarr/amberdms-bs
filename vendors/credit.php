<?php
/*
	vendors/credit.php
	
	access: vendors_view		View Only
		vendors_credit      View and Adjust

	Displays any credit on the vendor's account and allows new credit to be added.
*/


require("include/vendors/inc_vendors.php");


class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_table;

	
	function __construct()
	{
		// fetch variables
		$this->obj_vendor		= New vendor_credits;
		$this->obj_vendor->id		= @security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Vendor's Details", "page=vendors/view.php&id=". $this->obj_vendor->id ."");
		$this->obj_menu_nav->add_item("Vendor's Journal", "page=vendors/journal.php&id=". $this->obj_vendor->id ."");
		$this->obj_menu_nav->add_item("Vendor's Invoices", "page=vendors/invoices.php&id=". $this->obj_vendor->id ."");
                $this->obj_menu_nav->add_item("Vendor's Credits", "page=vendors/credit.php&id=". $this->obj_vendor->id ."", TRUE);
		
                if (user_permissions_get("vendors_write"))
		{
			$this->obj_menu_nav->add_item("Delete Vendor", "page=vendors/delete.php&id=". $this->obj_vendor->id ."");
		}

	}


	function check_permissions()
	{
		if (user_permissions_get("vendors_view") || user_permissions_get("vendors_credit"))
		{
			return 1;
		}
	}
	

	function check_requirements()
	{
		// verify that customer exists
		if (!$this->obj_vendor->verify_id())
		{
			log_write("error", "page_output", "The requested vendor (". $this->obj_vendor->id .") does not exist - possibly the vendor has been deleted.");
			return 0;
		}

		return 1;
	}



	function execute()
	{
		// load basic customer data
		$this->obj_vendor->load_data();

		// establish a new table object
		$this->obj_table = New table;

		$this->obj_table->language	= $_SESSION["user"]["lang"];
		$this->obj_table->tablename	= "vendors_credits";

		// define all the columns and structure
		$this->obj_table->add_column("date", "date_trans", "");
		$this->obj_table->add_column("standard", "type", "");
		$this->obj_table->add_column("standard", "accounts", "NONE");
		$this->obj_table->add_column("standard", "employee", "CONCAT_WS(' -- ', staff_code, name_staff)");
		$this->obj_table->add_column("standard", "description", "");
		$this->obj_table->add_column("money", "amount_total", "");


		// totals
		$this->obj_table->total_columns	= array("amount_total");

		
		// defaults
		$this->obj_table->columns	= array("date_trans", "type", "accounts", "description", "amount_total");
		$this->obj_table->columns_order	= array("date_trans", "type");

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("vendors_credits");
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "vendors_credits.id");
		$this->obj_table->sql_obj->prepare_sql_addfield("id_custom", "vendors_credits.id_custom");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN staff ON staff.id = vendors_credits.id_employee");
		$this->obj_table->sql_obj->prepare_sql_addwhere("vendors_credits.id_vendor='". $this->obj_vendor->id ."'");


		// acceptable filter options
		$this->obj_table->add_fixed_option("id_vendor", $this->obj_vendor->id);
		
		$structure = NULL;
		$structure["fieldname"] = "date_start";
		$structure["type"]	= "date";
		$structure["sql"]	= "date_trans >= 'value'";
		$this->obj_table->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] = "date_end";
		$structure["type"]	= "date";
		$structure["sql"]	= "date_trans <= 'value'";
		$this->obj_table->add_filter($structure);
		
		$structure		= form_helper_prepare_dropdownfromdb("id_employee", "SELECT id, staff_code as label, name_staff as label1 FROM staff ORDER BY name_staff");
		$structure["sql"]	= "vendors_credits.id_employee='value'";
		$this->obj_table->add_filter($structure);


		// load options
		$this->obj_table->load_options_form();

		// fetch all the chart information
		$this->obj_table->generate_sql();
		$this->obj_table->load_data_sql();


		// set customid fields
		for ($i=0; $i < $this->obj_table->data_num_rows; $i++)
		{
			// credit notes
			if ($this->obj_table->data[$i]["type"] == "creditnote")
			{
				$this->obj_table->data[$i]["accounts"] = sql_get_singlevalue("SELECT code_credit as value FROM account_ap_credit WHERE id='". $this->obj_table->data[$i]["id_custom"] ."' LIMIT 1");
			}

			// payments
			if ($this->obj_table->data[$i]["type"] == "payment")
			{
				$this->obj_table->data[$i]["id_custom"]	= sql_get_singlevalue("SELECT invoiceid as value FROM account_items WHERE id='". $this->obj_table->data[$i]["id_custom"] ."' LIMIT 1");
				$this->obj_table->data[$i]["accounts"]	= sql_get_singlevalue("SELECT code_invoice as value FROM account_ap WHERE id='". $this->obj_table->data[$i]["id_custom"] ."' LIMIT 1");
			}

		}
	}


	function render_html()
	{
		// heading
		print "<h3>VENDOR'S CREDIT</h3>";
		print "<p>This page provides a full list of all credit from this vendor as well as providing the option to add additional credits from the vendor.</p>";

		$this->obj_vendor->credit_render_summarybox();


		// display options form	
		$this->obj_table->render_options_form();


		// display data
		if (!count($this->obj_table->columns))
		{
			format_msgbox("important", "<p>Please select some valid options to display.</p>");
		}
		elseif (!$this->obj_table->data_num_rows)
		{
			format_msgbox("info", "<p>This vendor has no credit notes as per the filter options.</p>");
		}
		else
		{
			if (user_permissions_get("accounts_ap_write"))
			{
				// define links
				$structure = NULL;
				$structure["id_vendor"]["value"]			= $this->obj_vendor->id;
				$structure["id_refund"]["column"]			= "id";
				$structure["logic"]["if_not"]["column"]			= "accounts";
				$this->obj_table->add_link("details", "vendors/credit-refund.php", $structure);

				// delete link
				$structure = NULL;
				$structure["id_vendor"]["value"]			= $this->obj_vendor->id;
				$structure["id_refund"]["column"]			= "id";
				$structure["full_link"]					= "yes";
				$structure["logic"]["if_not"]["column"]			= "accounts";
				$this->obj_table->add_link("delete", "vendors/credit-refund-delete-process.php", $structure);
			}


			// set inline hyperlinks
			for ($i=0; $i < $this->obj_table->data_num_rows; $i++)
			{
				// credit notes
				if ($this->obj_table->data[$i]["type"] == "creditnote")
				{
					$this->obj_table->data[$i]["accounts"] = "<a href=\"index.php?page=accounts/ap/credit-view.php&id=". $this->obj_table->data[$i]["id_custom"] ."\">". $this->obj_table->data[$i]["accounts"] ."</a>";
				}

				// invoices/payments
				if ($this->obj_table->data[$i]["type"] == "payment")
				{
					$this->obj_table->data[$i]["accounts"] = "<a href=\"index.php?page=accounts/ap/invoice-view.php&id=". $this->obj_table->data[$i]["id_custom"] ."\">". $this->obj_table->data[$i]["accounts"] ."</a>";
				}
                                
                                // Translate the type field
                                $this->obj_table->data[$i]["type"]=lang_trans($this->obj_table->data[$i]["type"]);
			}

			// display the table
			$this->obj_table->render_table_html();

			// display CSV/PDF download link
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=csv&page=vendors/credit.php&id=". $this->obj_vendor->id ."\">Export as CSV</a></p>";
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=pdf&page=vendors/credit.php&id=". $this->obj_vendor->id ."\">Export as PDF</a></p>";
		}


		// define add credit link
		print "<p>";

		if (user_permissions_get("accounts_ap_write"))
		{
			print "<a class=\"button\" href=\"index.php?page=accounts/ap/credit-add.php&vendorid=". $this->obj_vendor->id ."\">Create Credit Note</a> ";
                        print "<a class=\"button\" href=\"index.php?page=vendors/credit-refund.php&id_vendor=". $this->obj_vendor->id ."\">Make Refund Payment</a>";
		}

		print "</p>";
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
