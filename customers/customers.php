<?php
/*
	customers.php
	
	access: "customers_view" group members

	Displays a list of all the customers on the system.
*/

include("include/services/inc_services.php");

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
                $this->obj_table_list->add_column("money", "service_price_monthly", "NONE");
		$this->obj_table_list->add_column("money", "service_price_yearly", "NONE");

		// defaults
		$this->obj_table_list->columns			= array("code_customer", "name_customer", "name_contact", "contact_phone", "contact_email");
		$this->obj_table_list->columns_order		= array("name_customer");
		$this->obj_table_list->columns_order_options	= array("code_customer", "name_customer", "name_contact", "contact_phone", "contact_mobile", "contact_email", "contact_fax", "date_start", "date_end", "tax_number", "address1_city", "address1_state", "address1_country");

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
		$structure["sql"]	= "(code_customer LIKE '%value%' OR name_customer LIKE '%value%')";
		$this->obj_table_list->add_filter($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "hide_ex_customers";
		$structure["type"]		= "checkbox";
		$structure["sql"]		= "date_end='0000-00-00'";
		$structure["defaultvalue"]	= "on";
		$structure["options"]["label"]	= "Hide any customers who are no longer active";
		$this->obj_table_list->add_filter($structure);
 
 		$structure = NULL;
		$structure["fieldname"] 	= "show_prices_with_discount";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "Display service prices with discounts applied";
		$structure["defaultvalue"]	= "1";
		$structure["sql"]		= "";
		$this->obj_table_list->add_filter($structure);


               		

		// load settings from options form
		$this->obj_table_list->load_options_form();

		// fetch all the customer information
		$this->obj_table_list->generate_sql();
		$this->obj_table_list->load_data_sql();


		// handle services, if columns selected
                if (in_array('service_price_yearly', $this->obj_table_list->columns)
                        || in_array('service_price_monthly', $this->obj_table_list->columns))
		{
			/*
				Foreach customer, we need to fetch all their service details and then determine the 
				cost of those services.

				Unfortunatly we can't just do a table query, since we need to load the service details to
				check for stuff such as price overrides. :'(
			*/
	
			// fetch service billing cycle information
			$obj_cycles_sql		= New sql_query;
			$obj_cycles_sql->string	= "SELECT id, name, priority FROM billing_cycles";
			$obj_cycles_sql->execute();
			$obj_cycles_sql->fetch_array();


			// run through all returned customers
			for ($i=0; $i < $this->obj_table_list->data_num_rows; $i++)
			{
				// fetch all services for the customer (if they have any)
				$obj_services_sql		= New sql_query;
				$obj_services_sql->string	= "SELECT id as id_service_customer, serviceid as id_service FROM services_customers WHERE customerid='". $this->obj_table_list->data[$i]["id"] ."'";
				$obj_services_sql->execute();

				if ($obj_services_sql->num_rows())
				{
					$obj_services_sql->fetch_array();


					foreach ($obj_services_sql->data as $data_service_list)
					{
						// query service details for each service
						$obj_service			= New service;

						$obj_service->option_type	= "customer";
						$obj_service->option_type_id	= $data_service_list["id_service_customer"];
						$obj_service->id		= $data_service_list["id_service"];

						$obj_service->load_data();
						$obj_service->load_data_options();

						// counting totals
						$service_price_monthly	= 0;
						$service_price_yearly	= 0;

						// calculate pricing
						foreach ($obj_cycles_sql->data as $data_cycles)
						{
							if ($obj_service->data["billing_cycle"] == $data_cycles["id"])
							{
								if ($data_cycles["priority"] < 32)
								{
									// monthly or less
									if ($data_cycles["name"] == "monthly")
									{
										// monthly billed service
										$service_price_monthly = $obj_service->data["price"];
									}
									else
									{
										// less than a month, calculate a month's amount
										$ratio = 28 / $data_cycles["priority"];

										$service_price_monthly = $obj_service->data["price"] * $ratio;
									}
								}
								else
								{
									if ($data_cycles["name"] == "yearly")
									{
										// yearly billed service
										$service_price_yearly = $obj_service->data["price"];
									}
									else
									{
										// more than a month, less than a year, calcuate a year's amount
										$ratio = 365 / $data_cycles["priority"];

										$service_price_yearly = $obj_service->data["price"] * $ratio;
									}
								}
							}

						} // end of calculate pricing


						// apply discount if enabled
						if ($_SESSION["form"]["customer_list"]["filters"]["filter_show_prices_with_discount"])
						{
							if (!empty($service_price_monthly))
							{
								$service_price_monthly = $service_price_monthly - ($service_price_monthly * ($obj_service->data["discount"] /100));
							}

							if (!empty($service_price_yearly))
							{
								$service_price_yearly = $service_price_yearly - ($service_price_yearly * ($obj_service->data["discount"] /100));
							}
						}

						// save totals for this customer
						$this->obj_table_list->data[$i]["service_price_monthly"]	= $this->obj_table_list->data[$i]["service_price_monthly"] + $service_price_monthly;
						$this->obj_table_list->data[$i]["service_price_yearly"]		= $this->obj_table_list->data[$i]["service_price_yearly"] + $service_price_yearly;

						unset($obj_service);

					} // end of service loop
				

				unset($obj_services_sql);

				} // end if services exist

			} // end of table loop

			unset($obj_cycles_sql);

		} // end if service columns enabled


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
		else if (!$this->obj_table_list->data_num_rows)
		{
			format_msgbox("info", "<p>You currently have no customers in your database.</p>");
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
						print "<a class=\"button_small\" href=\"index.php?page=customers/attributes.php&id_customer=" .$this->obj_table_list->data[$i]["id"]. "\">" .lang_trans("tbl_lnk_attributes"). "</a> ";
						print "<a class=\"button_small\" href=\"index.php?page=customers/orders.php&id_customer=" .$this->obj_table_list->data[$i]["id"]. "\">" .lang_trans("orders"). "</a> ";
						print "<a class=\"button_small\" href=\"index.php?page=customers/invoices.php&id=" .$this->obj_table_list->data[$i]["id"]. "\">" .lang_trans("invoices"). "</a> ";
						print "<a class=\"button_small\" href=\"index.php?page=customers/services.php&id=" .$this->obj_table_list->data[$i]["id"]. "\">" .lang_trans("services"). "</a> ";						
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
