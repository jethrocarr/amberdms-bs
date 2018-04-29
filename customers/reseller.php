<?php
/*
	reseller.php
	
	access: "customers_view" group members

	Displays a list of all the customers on the system belonging to the customer that is a reseller.
*/

require("include/customers/inc_customers.php");

class page_output
{
	var $obj_table_list;

        function __construct()
        {
                // customer object
                $this->obj_customer             = New customer;
                $this->obj_customer->id         = @security_script_input('/^[0-9]*$/', $_GET["id_customer"]);

                // define the navigiation menu
                $this->obj_menu_nav = New menu_nav;

                $this->obj_menu_nav->add_item("Customer's Details", "page=customers/view.php&id=". $this->obj_customer->id ."");

                if ($GLOBALS["config"]["MODULE_CUSTOMER_PORTAL"] == "enabled")
                {
                        $this->obj_menu_nav->add_item("Portal Options", "page=customers/portal.php&id=". $this->obj_customer->id ."");
                }

                $this->obj_menu_nav->add_item("Customer's Journal", "page=customers/journal.php&id=". $this->obj_customer->id ."");
                $this->obj_menu_nav->add_item("Customer's Attributes", "page=customers/attributes.php&id_customer=". $this->obj_customer->id ."");
                $this->obj_menu_nav->add_item("Customer's Orders", "page=customers/orders.php&id_customer=". $this->obj_customer->id ."");
                $this->obj_menu_nav->add_item("Customer's Invoices", "page=customers/invoices.php&id=". $this->obj_customer->id ."");
                $this->obj_menu_nav->add_item("Customer's Credit", "page=customers/credit.php&id=". $this->obj_customer->id ."");
                $this->obj_menu_nav->add_item("Customer's Services", "page=customers/services.php&id=". $this->obj_customer->id ."");


		if ($this->obj_customer->verify_reseller() == 1)
		{
	                $this->obj_menu_nav->add_item("Reseller's Customers", "page=customers/reseller.php&id_customer=". $this->obj_customer->id ."", TRUE);
		}

                if (user_permissions_get("customers_write"))
                {
                        $this->obj_menu_nav->add_item("Delete Customer", "page=customers/delete.php&id=". $this->obj_customer->id ."");
                }
        }


	function check_permissions()
	{
		if (user_permissions_get('customers_view'))
		{
			return 1;
		}
	}

	function check_requirements()
	{
		// customer must be a reseller
		if ($this->obj_customer->verify_reseller() != 1)
		{
			log_write("error", "page", "This customer is not a reseller.");
			return 0;
		}

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
		$this->obj_table_list->add_column("standard", "name_contact", "NONE");
		$this->obj_table_list->add_column("standard", "contact_phone", "NONE");
		$this->obj_table_list->add_column("standard", "contact_mobile", "NONE");
		$this->obj_table_list->add_column("standard", "contact_email", "NONE");
		$this->obj_table_list->add_column("standard", "contact_fax", "NONE");
		$this->obj_table_list->add_column("date", "date_start", "");
		$this->obj_table_list->add_column("date", "date_end", "");
		$this->obj_table_list->add_column("standard", "tax_number", "");
		$this->obj_table_list->add_column("standard", "address1_city", "");
		$this->obj_table_list->add_column("standard", "address1_state", "");
		$this->obj_table_list->add_column("standard", "address1_country", "");

		// defaults
		$this->obj_table_list->columns			= array("code_customer", "name_customer", "name_contact", "contact_phone", "contact_email");
		$this->obj_table_list->columns_order		= array("name_customer");
		$this->obj_table_list->columns_order_options	= array("code_customer", "name_customer", "name_contact", "contact_phone", "contact_mobile", "contact_email", "contact_fax", "date_start", "date_end", "tax_number", "address1_city", "address1_state", "address1_country");

		// define SQL structure
		$this->obj_table_list->sql_obj->prepare_sql_settable("customers");
		$this->obj_table_list->sql_obj->prepare_sql_addfield("id", "");


		// permanently filter on the customer id
		$this->obj_table_list->sql_obj->prepare_sql_addwhere("reseller_id = '". $this->obj_customer->id ."'");
		$this->obj_table_list->add_fixed_option("id_customer", $this->obj_customer->id);

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
		$structure["sql"]	= "(code_customer LIKE '%value%' OR name_customer LIKE '%value%')";
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
		print "<h3>RESELLER CUSTOMERS LIST</h3><br>";
		print "<p>The following list of customers is made up of all customers who belong to this reseller. To add a customer to this reseller, adjust the customer's details and select the reseller they should belong to.</p>";


		/*
			TODO: this code is a big copy and paste job from customers/customers.php, we should
			turn it into a function so that we don't keep cloning features between the applications.

			DEVELOPER IS VERY NAUGHTY AND MUST BE PUNISHED.
		*/


		// load options form
		$this->obj_table_list->render_options_form();


		// display results
		if (!count($this->obj_table_list->columns))
		{
			format_msgbox("important", "<p>Please select some valid options to display.</p>");
		}
		else if (!$this->obj_table_list->data_num_rows)
		{
			format_msgbox("info", "<p>There are currently no customers associated to this reseller.</p>");
		}
		else
		{
			// calculate all the totals and prepare processed values
			$this->obj_table_list->render_table_prepare();

			// display header row
			print "<table class=\"table_content\" cellspacing=\"0\" width=\"100%\">";	
					
			print "<tr>";
				foreach ($this->obj_table_list->columns as $column)
				{
					print "<td class=\"header\"><b>". $this->obj_table_list->render_columns[$column] ."</b></td>";
				}
				
				//placeholder for links
				print "<td class=\"header\">&nbsp;</td>";				
				
			print "</tr>";
			
			// display data
			for ($i=0; $i < $this->obj_table_list->data_num_rows; $i++)
			{
				$customer_id = $this->obj_table_list->data[$i]["id"];
				$contact_id = sql_get_singlevalue("SELECT id AS value FROM customer_contacts WHERE customer_id = '" .$customer_id. "' AND role = 'accounts' LIMIT 1");
				print "<tr>";
				foreach ($this->obj_table_list->columns as $columns)
				{
					print "<td valign=\"top\">";						
						//contact name
						if ($columns == "name_contact")
						{
							$value = sql_get_singlevalue("SELECT contact AS value FROM customer_contacts WHERE id = '" .$contact_id. "' LIMIT 1");
							if ($value)
							{
								print $value;
							}
						}
						
						//contact phone
						else if ($columns == "contact_phone")
						{
							$value = sql_get_singlevalue("SELECT detail AS value FROM customer_contact_records WHERE contact_id = '" .$contact_id. "' AND type = 'phone' LIMIT 1");
							if ($value)
							{
								print $value;
							}
						}
						
						//contact mobile
						else if ($columns == "contact_mobile")
						{
							$value = sql_get_singlevalue("SELECT detail AS value FROM customer_contact_records WHERE contact_id = '" .$contact_id. "' AND type= 'mobile' LIMIT 1");
							if ($value)
							{
								print $value;
							}
						}
						
						//contact email
						else if ($columns == "contact_email")
						{
							$value = sql_get_singlevalue("SELECT detail AS value FROM customer_contact_records WHERE contact_id = '" .$contact_id. "' AND type= 'email' LIMIT 1");
							if ($value)
							{
								print $value;
							}
						}
						
						//contact fax
						else if ($columns == "contact_fax")
						{
							$value = sql_get_singlevalue("SELECT detail AS value FROM customer_contact_records WHERE contact_id = '" .$contact_id. "' AND type= 'fax' LIMIT 1");
							if ($value)
							{
								print $value;
							}
						}
						
						//all other columns
						else
						{
							if ($this->obj_table_list->data_render[$i][$columns])
							{
//								print $columns;
								print $this->obj_table_list->data_render[$i][$columns];
							}
							else
							{
								print "&nbsp;";
							}
						}
					print "</td>";
				}
				
					//links
					print "<td align=\"right\" nowrap >";
						print "<a class=\"button_small\" href=\"index.php?page=customers/view.php&id=" .$this->obj_table_list->data[$i]["id"]. "\">" .lang_trans("details"). "</a> ";
					print "</td>";
				print "</tr>";
			}
			print "</table>";
			print "<br />";

			// display CSV/PDF download link
			print "<p align=\"right\"><a class=\"button_export\" style=\"font-weight: normal;\"  href=\"index-export.php?mode=csv&page=customers/customers.php\">Export as CSV</a></p>";
			print "<p align=\"right\"><a class=\"button_export\" style=\"font-weight: normal;\" href=\"index-export.php?mode=pdf&page=customers/customers.php\">Export as PDF</a></p>";
		}
	}


	function render_csv()
	{
		$this->obj_table_list->render_table_csv();
	}
	
	
	function render_pdf()
	{
		$this->obj_table_list->render_table_pdf();
	}
	

} // end class page_output


?>
