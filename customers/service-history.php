<?php
/*
	service-history.php

	Displays all the periods and invoices relating to this service
	
	access: "customers_view"

*/


class page_output
{
	var $customerid;
	var $services_customers_id;
	
	var $obj_menu_nav;
	var $obj_table;
	

	function page_output()
	{
		// fetch variables
		$this->customerid		= @security_script_input('/^[0-9]*$/', $_GET["customerid"]);
		$this->services_customers_id	= @security_script_input('/^[0-9]*$/', $_GET["serviceid"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Customer's Details", "page=customers/view.php&id=". $this->customerid ."");
		$this->obj_menu_nav->add_item("Customer's Journal", "page=customers/journal.php&id=". $this->customerid ."");
		$this->obj_menu_nav->add_item("Customer's Invoices", "page=customers/invoices.php&id=". $this->customerid ."");
		$this->obj_menu_nav->add_item("Customer's Services", "page=customers/services.php&id=". $this->customerid ."", TRUE);

		if (user_permissions_get("customers_write"))
		{
			$this->obj_menu_nav->add_item("Delete Customer", "page=customers/delete.php&id=". $this->customerid ."");
		}
	}



	function check_permissions()
	{
		return user_permissions_get("customers_view");
	}



	function check_requirements()
	{
		// verify that customer exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM customers WHERE id='". $this->customerid ."'";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested customer (". $this->customerid .") does not exist - possibly the customer has been deleted.");
			return 0;
		}

		unset($sql_obj);


		// verify that the customer_service mapping exists and belongs to the correct customer
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT customerid FROM `services_customers` WHERE id='". $this->services_customers_id ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested service does not exist.");
			return 0;
		}
		else
		{
			$sql_obj->fetch_array();

			if ($sql_obj->data[0]["customerid"] != $this->customerid)
			{
				log_write("error", "page_output", "The requested service does not match the provided customer ID. Potential application bug?");
				return 0;
			}
		}

		unset($sql_obj);

		return 1;
	}


				
	
	function execute()
	{
		// establish a new table object
		$this->obj_table = New table;

		$this->obj_table->language		= $_SESSION["user"]["lang"];
		$this->obj_table->tablename		= "service_history";

		// define all the columns and structure
		$this->obj_table->add_column("date", "date_start", "");
		$this->obj_table->add_column("date", "date_end", "");
		$this->obj_table->add_column("date", "invoice_gen_date", "date_billed");
		$this->obj_table->add_column("standard", "usage_summary", "");
		$this->obj_table->add_column("bool_tick", "invoiced", "invoiceid");
		$this->obj_table->add_column("bool_tick", "paid", "NONE");
		$this->obj_table->add_column("standard", "code_invoice", "account_ar.code_invoice");

		// defaults
		$this->obj_table->columns		= array("date_start", "date_end", "invoice_gen_date", "usage_summary", "invoiced", "paid", "code_invoice");
		$this->obj_table->columns_order		= array("date_start");

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("services_customers_periods");
		
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN account_ar ON account_ar.id = services_customers_periods.invoiceid");
		
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "services_customers_periods.id");
		$this->obj_table->sql_obj->prepare_sql_addfield("amount_total", "account_ar.amount_total");
		$this->obj_table->sql_obj->prepare_sql_addfield("amount_paid", "account_ar.amount_paid");
		$this->obj_table->sql_obj->prepare_sql_addwhere("services_customers_id = '". $this->services_customers_id ."'");

		// run SQL query
		$this->obj_table->generate_sql();
		$this->obj_table->load_data_sql();

	}


	function render_html()
	{
		// heading
		print "<h3>CUSTOMER SERVICE HISTORY</h3>";
		print "<p>This page displays all the periods of this service, showing when the service was active and when it has been billed.</p>";

	
		if (!$this->obj_table->data_num_rows)
		{
			format_msgbox("info", "<p>This service does not have any history - this is normal when the service has just recently been added.</p>");
		}
		else
		{
			// run through all the data rows to make custom changes
			for ($i=0; $i < $this->obj_table->data_num_rows; $i++)
			{
				// make the invoice number a hyperlink
				if ($this->obj_table->data[$i]["code_invoice"] && user_permissions_get("accounts_ar_view"))
				{
					$this->obj_table->data[$i]["code_invoice"] = "<a href=\"index.php?page=accounts/ar/invoice-view.php&id=". $this->obj_table->data[$i]["invoiced"] ."\">AR ". $this->obj_table->data[$i]["code_invoice"] ."</a>";
				}

				// tick the paid column if the invoice has been paid off completely
				if ($this->obj_table->data[$i]["invoiced"])
				{
					if ($this->obj_table->data[$i]["amount_total"] == $this->obj_table->data[$i]["amount_paid"])
					{
						$this->obj_table->data[$i]["paid"] = 1;
					}
				}

				// if the usage is 0, just blank it, as it might not be a usage service
				if ($this->obj_table->data[$i]["usage_summary"] == 0)
				{
					$this->obj_table->data[$i]["usage_summary"] = "";
				}
				else
				{
					// if this is the most recent period, then add a check link next to the usage amount
					if ($i == ($this->obj_table->data_num_rows - 1))
					{
						$this->obj_table->data[$i]["usage_summary"] = $this->obj_table->data[$i]["usage_summary"] ." <a href=\"customers/services-checkusage-process.php?customerid=". $this->customerid ."&serviceid=". $this->services_customers_id ."\">(get latest)</a>";
					}

				}

			}
			

			// display the table
			$this->obj_table->render_table_html();
			
			// display CSV/PDF download link
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=csv&page=customers/service-history.php&customerid=". $this->customerid ."&serviceid=". $this->services_customers_id ."\">Export as CSV</a></p>";
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=pdf&page=customers/service-history.php&customerid=". $this->customerid ."&serviceid=". $this->services_customers_id ."\">Export as PDF</a></p>";
		}
	}


	function render_csv()
	{
		// display the table
		$this->obj_table->render_table_csv();
	}


	function render_pdf()
	{
		// display the table
		$this->obj_table->render_table_pdf();
	}


} // end page_output


?>
