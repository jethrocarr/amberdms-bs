<?php
/*
	service-history.php

	Displays all the periods and invoices relating to this service
	
	access: "customers_view"

*/

if (user_permissions_get('customers_view'))
{
	$id = $_GET["customerid"];
	
	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;

	$_SESSION["nav"]["title"][]	= "Customer's Details";
	$_SESSION["nav"]["query"][]	= "page=customers/view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Customer's Journal";
	$_SESSION["nav"]["query"][]	= "page=customers/journal.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Customer's Invoices";
	$_SESSION["nav"]["query"][]	= "page=customers/invoices.php&id=$id";
	
	$_SESSION["nav"]["title"][]	= "Customer's Services";
	$_SESSION["nav"]["query"][]	= "page=customers/services.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=customers/services.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Delete Customer";
	$_SESSION["nav"]["query"][]	= "page=customers/delete.php&id=$id";





	function page_render()
	{
		$customerid	= security_script_input('/^[0-9]*$/', $_GET["customerid"]);
		$services_customers_id	= security_script_input('/^[0-9]*$/', $_GET["serviceid"]);


		/*
			Perform verification tasks
		*/
		$error = 0;
		
		// check that the specified customer actually exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM `customers` WHERE id='$customerid' LIMIT 1";
		$sql_obj->execute();
			
		if (!$sql_obj->num_rows())
		{
			print "<p><b>Error: The requested customer does not exist. <a href=\"index.php?page=customers/customers.php\">Try looking for your customer on the customer list page.</a></b></p>";
			$error = 1;
		}
		else
		{
			if ($services_customers_id)
			{
				// are we editing an existing service? make sure it exists and belongs to this customer
				$sql_obj		= New sql_query;
				$sql_obj->string	= "SELECT serviceid, customerid FROM `services_customers` WHERE id='$services_customers_id' LIMIT 1";
				$sql_obj->execute();

				if (!$sql_obj->num_rows())
				{
					print "<p><b>Error: The requested service does not exist.</b></p>";
					$error = 1;
				}
				else
				{
					$sql_obj->fetch_array();

					$serviceid = $sql_obj->data[0]["serviceid"];

					if ($sql_obj->data[0]["customerid"] != $customerid)
					{
						print "<p><b>Error: The requested service does not match the provided customer ID. Potential application bug?</b></p>";
						$error = 1;
					}
					
				}
			}
		}

	
		/*
			Display Form
		*/
		if (!$error)
		{
			// heading
			print "<h3>CUSTOMER SERVICE HISTORY</h3>";

			print "<p>This page displays all the periods of this service, showing when the service was active and when it has been billed.</p>";
		
		
			// establish a new table object
			$service_list = New table;

			$service_list->language		= $_SESSION["user"]["lang"];
			$service_list->tablename	= "service_history";

			// define all the columns and structure
			$service_list->add_column("date", "date_start", "");
			$service_list->add_column("date", "date_end", "");
			$service_list->add_column("bool_tick", "invoiced", "invoiceid");
			$service_list->add_column("standard", "code_invoice", "account_ar.code_invoice");

			// defaults
			$service_list->columns		= array("date_start", "date_end", "invoiced", "code_invoice");
			$service_list->columns_order	= array("date_start");

			// define SQL structure
			$service_list->sql_obj->prepare_sql_settable("services_customers_periods");
			
			$service_list->sql_obj->prepare_sql_addjoin("LEFT JOIN account_ar ON account_ar.id = services_customers_periods.invoiceid");
			
			$service_list->sql_obj->prepare_sql_addfield("id", "services_customers_periods.id");
			$service_list->sql_obj->prepare_sql_addwhere("services_customers_id = '$services_customers_id'");

			// run SQL query
			$service_list->generate_sql();
			$service_list->load_data_sql();

			if (!$service_list->data_num_rows)
			{
				print "<p><b>This service does not have any history - if the service has just been added, then this is normal.</b></p>";
			}
			else
			{
				// run through all the data rows to make custom changes
				for ($i=0; $i < $service_list->data_num_rows; $i++)
				{
					if ($service_list->data[$i]["code_invoice"])
					{
						$service_list->data[$i]["code_invoice"] = "<a href=\"index.php?page=accounts/ar/invoice-view.php&id=". $service_list->data[$i]["invoiced"] ."\">AR ". $service_list->data[$i]["code_invoice"] ."</a>";
					}
				}
				

				// display the table
				$service_list->render_table();
			}


		} // end if customer exists
		
	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
