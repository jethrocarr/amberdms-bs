<?php
/*
	services.php
	
	access: "customers_view"	(read-only)
		"customers_write"

	Displays all the services currently assigned to the user's account, and allows the customer
	to have new services added/removed.
*/

if (user_permissions_get('customers_view'))
{
	$customerid = $_GET["id"];
	
	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "Customer's Details";
	$_SESSION["nav"]["query"][]	= "page=customers/view.php&id=$customerid";

	$_SESSION["nav"]["title"][]	= "Customer's Journal";
	$_SESSION["nav"]["query"][]	= "page=customers/journal.php&id=$customerid";

	$_SESSION["nav"]["title"][]	= "Customer's Services";
	$_SESSION["nav"]["query"][]	= "page=customers/services.php&id=$customerid";
	$_SESSION["nav"]["current"]	= "page=customers/services.php&id=$customerid";

	if (user_permissions_get('customers_write'))
	{
		$_SESSION["nav"]["title"][]	= "Delete Customer";
		$_SESSION["nav"]["query"][]	= "page=customers/delete.php&id=$customerid";
	}



	function page_render()
	{
		$customerid = security_script_input('/^[0-9]*$/', $_GET["id"]);

		// check that the specified customer actually exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM `customers` WHERE id='$customerid'";
		$sql_obj->execute();
		
		if (!$sql_obj->num_rows())
		{
			print "<p><b>Error: The requested customer does not exist. <a href=\"index.php?page=customers/customers.php\">Try looking for your customer on the customer list page.</a></b></p>";
		}
		else
		{
			// heading
			print "<h3>CUSTOMER SERVICES</h3>";

			print "<p>This page allows you to manage all the services that the customer is assigned to.</p>";
		
		
			// establish a new table object
			$service_list = New table;

			$service_list->language		= $_SESSION["user"]["lang"];
			$service_list->tablename	= "service_list";

			// define all the columns and structure
			$service_list->add_column("standard", "name_service", "services.name_service");
			$service_list->add_column("bool_tick", "active", "services_customers.active");
			$service_list->add_column("standard", "typeid", "service_types.name");
			$service_list->add_column("standard", "billing_cycles", "billing_cycles.name");
			$service_list->add_column("date", "date_billed_next", "");
			$service_list->add_column("date", "date_billed_last", "");
			$service_list->add_column("standard", "description", "services_customers.description");

			// defaults
			$service_list->columns		= array("name_service", "active", "typeid", "date_billed_next", "date_billed_last", "description");
			$service_list->columns_order	= array("name_service");

			// define SQL structure
			$service_list->sql_obj->prepare_sql_settable("services_customers");
			
			$service_list->sql_obj->prepare_sql_addjoin("LEFT JOIN services ON services.id = services_customers.serviceid");
			$service_list->sql_obj->prepare_sql_addjoin("LEFT JOIN billing_cycles ON billing_cycles.id = services.billing_cycle");
			$service_list->sql_obj->prepare_sql_addjoin("LEFT JOIN service_types ON service_types.id = services.typeid");
			
			$service_list->sql_obj->prepare_sql_addfield("id", "services_customers.id");
			$service_list->sql_obj->prepare_sql_addwhere("services_customers.customerid = '$customerid'");

			// run SQL query
			$service_list->generate_sql();
			$service_list->load_data_sql();

			if (!$service_list->data_num_rows)
			{
				print "<p><b>This customer is not subscribed to any services. <a href=\"index.php?page=customers/service-edit.php&customerid=$customerid\">Click here to add this customer to a service</a>.</b></p>";
			}
			else
			{
				// edit link
				$structure = NULL;
				$structure["customerid"]["value"]	= $customerid;
				$structure["serviceid"]["column"]	= "id";
				$service_list->add_link("edit", "customers/service-edit.php", $structure);
				
				// delete link
				$structure = NULL;
				$structure["customerid"]["value"]	= $customerid;
				$structure["serviceid"]["column"]	= "id";
				$service_list->add_link("delete", "customers/service-delete.php", $structure);


				// display the table
				$service_list->render_table();

				
				print "<p><b><a href=\"index.php?page=customers/service-edit.php&customerid=$customerid\">Click here to add a new service to your customer</a>.</b></p>";
			}

		} // end if customer exists
		
	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
