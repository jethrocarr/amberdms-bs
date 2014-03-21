<?php
/*
	service-history-cdr-export.php

	access: "customers_view"

	Export mode functionality for exporting service CDR history as per the configured format set with
	SERVICE_CDR_EXPORT_FORMAT.

*/

require("include/services/inc_services.php");
require("include/services/inc_services_cdr.php");
require("include/customers/inc_customers.php");




class page_output
{
	var $obj_customer;	
	var $obj_menu_nav;
	var $obj_table;

	var $id_service_period;

	var $output;


	function page_output()
	{
		$this->obj_customer				= New customer_services;


		// fetch variables
		$this->obj_customer->id				= @security_script_input('/^[0-9]*$/', $_GET["id_customer"]);
		$this->obj_customer->id_service_customer	= @security_script_input('/^[0-9]*$/', $_GET["id_service_customer"]);

		$this->id_service_period			= @security_script_input('/^[0-9]*$/', $_GET["id_service_period"]);


		// load service data
		$this->obj_customer->load_data_service();
	}



	function check_permissions()
	{
		return user_permissions_get("customers_view");
	}



	function check_requirements()
	{
		// verify that customer exists
		if (!$this->obj_customer->verify_id())
		{
			log_write("error", "page_output", "The requested customer (". $this->customerid .") does not exist - possibly the customer has been deleted.");
			return 0;
		}


		// verify that service-customer mapping is valid
		if (!$this->obj_customer->verify_id_service_customer())
		{
			log_write("error", "page_output", "The requested service (". $this->obj_customer->id_service_customer .") was not found and/or does not match the selected customer");
			return 0;
		}

		// verify that the selected period is valid
		if (!$this->id_service_period)
		{
			log_write("error", "page_output", "A valid service period must be supplied");
			return 0;
		}

		return 1;
	}


				
	
	function execute()
	{
		/*
			Fetch period data
		*/

		$sql_period_obj			= New sql_query;
		$sql_period_obj->string		= "SELECT date_start, date_end FROM services_customers_periods WHERE id='". $this->id_service_period ."' LIMIT 1";
		$sql_period_obj->execute();
		$sql_period_obj->fetch_array();


		/*
			Generate CSV CDR Output
		*/

		$options = array(
			'id_customer'		=> $this->obj_customer->id,
			'id_service_customer'	=> $this->obj_customer->id_service_customer,
			'period_start'		=> $sql_period_obj->data[0]["date_start"],
			'period_end'		=> $sql_period_obj->data[0]["date_end"],
		);

		$csv = new cdr_csv($options);

		if (!$this->output = $csv->getCSV())
		{
			log_write("error", "page_output", "Unable to generate CSV ouput for the configured range");
			return 0;
		}

		return 1;
	}


	function render_html()
	{
		// no HTML exports supported
	}


	function render_csv()
	{
		// render output
		print $this->output;
	}


	function render_pdf()
	{
		// no PDF exports supported
	}


} // end page_output


?>
