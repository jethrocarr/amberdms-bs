<?php
/*
	customers/service-edit.php
	
	access: customers_write

	Form to add or edit a customer service.
*/

if (user_permissions_get('customers_write'))
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
			/*
				Title + Summary
			*/
			if ($services_customers_id)
			{
				print "<h3>EDIT SERVICE</h3><br>";
				print "<p>This page allows you to modifiy a customer service.</p>";
			}
			else
			{
				print "<h3>ADD CUSTOMER TO SERVICE</h3><br>";
				print "<p>This page allows you to subscribe a customer to a new service.</p>";
			}
			

			/*
				Define form structure
			*/
			$form = New form_input;
			$form->formname = "service_view";
			$form->language = $_SESSION["user"]["lang"];

			$form->action = "customers/service-edit-process.php";
			$form->method = "post";

				
		
			// general

			if ($services_customers_id)
			{
				// fetch service details
				$sql_service_obj		= New sql_query;
				$sql_service_obj->string	= "SELECT name_service, typeid, billing_cycle FROM services WHERE id='$serviceid' LIMIT 1";
				$sql_service_obj->execute();
				$sql_service_obj->fetch_array();

				// fetch service type
				$service_type = sql_get_singlevalue("SELECT name as value FROM service_types WHERE id='". $sql_service_obj->data[0]["typeid"] ."' LIMIT 1");


				// general
				$structure = NULL;
				$structure["fieldname"]		= "serviceid";
				$structure["type"]		= "text";
				$structure["defaultvalue"]	= $sql_service_obj->data[0]["name_service"];
				$form->add_input($structure);

				$structure = NULL;
				$structure["fieldname"] 	= "active";
				$structure["type"]		= "checkbox";
				$structure["options"]["label"]	= "Service is enabled";
				$form->add_input($structure);
		
	
				// quantity field - licenses only
				if ($service_type == "licenses")
				{
					$structure = NULL;
					$structure["fieldname"] 	= "quantity_msg";
					$structure["type"]		= "message";
					$structure["defaultvalue"]	= "<i>Because this is a license service, you need to specifiy how many license in the box below. Note that this will only affect billing from the next invoice. If you wish to charge for usage between now and the next invoice, you will need to generate a manual invoice.</i>";
					$form->add_input($structure);
					
					$structure = NULL;
					$structure["fieldname"] 	= "quantity";
					$structure["type"]		= "input";
					$structure["options"]["req"]	= "yes";
					$form->add_input($structure);
				}


				
				// billing
				$structure = NULL;
				$structure["fieldname"]		= "billing_cycle";
				$structure["type"]		= "text";
				$structure["defaultvalue"]	= sql_get_singlevalue("SELECT name as value FROM billing_cycles WHERE id='". $sql_service_obj->data[0]["billing_cycle"] ."' LIMIT 1");
				$form->add_input($structure);
				
				$structure = NULL;
				$structure["fieldname"] 	= "date_period_first";
				$structure["type"]		= "text";
				$form->add_input($structure);
				
				$structure = NULL;
				$structure["fieldname"] 	= "date_period_next";
				$structure["type"]		= "text";
				$form->add_input($structure);
			}
			else
			{
				$structure = form_helper_prepare_dropdownfromdb("serviceid", "SELECT id, name_service as label FROM services ORDER BY name_service");
				$structure["options"]["req"] = "yes";
				$form->add_input($structure);
			
				$structure = NULL;
				$structure["fieldname"] 	= "date_period_first";
				$structure["type"]		= "date";
				$structure["options"]["req"]	= "yes";
				$structure["defaultvalue"]	= date("Y-m-d");
				$form->add_input($structure);
			}


			$structure = NULL;
			$structure["fieldname"] 	= "description";
			$structure["type"]		= "textarea";
			$form->add_input($structure);
			

			// hidden values
			$structure = NULL;
			$structure["fieldname"]		= "customerid";
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= $customerid;
			$form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"]		= "services_customers_id";
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= $services_customers_id;
			$form->add_input($structure);
			


			// submit button
			$structure = NULL;
			$structure["fieldname"] 	= "submit";
			$structure["type"]		= "submit";
			if ($services_customers_id)
			{
				$structure["defaultvalue"]	= "Save Changes";
			}
			else
			{
				$structure["defaultvalue"]	= "Add Service";
			}
			$form->add_input($structure);


			// define subforms
			if ($services_customers_id)
			{
				$form->subforms["service_edit"]		= array("serviceid", "active", "description");
				$form->subforms["service_billing"]	= array("billing_cycle", "date_period_first", "date_period_next");


				if ($service_type == "licenses")
				{
					$form->subforms["service_options_licenses"]	= array("quantity_msg", "quantity");
				}
			}
			else
			{
				$form->subforms["service_add"]		= array("serviceid", "date_period_first", "description");
			}
			
			$form->subforms["hidden"]		= array("customerid", "services_customers_id");
			$form->subforms["submit"]		= array("submit");
	

			// fetch the form data if editing
			if ($services_customers_id)
			{
				$form->sql_query = "SELECT active, date_period_first, date_period_next, quantity, description FROM `services_customers` WHERE id='$services_customers_id' LIMIT 1";
				$form->load_data();
			}
			else
			{
				// load any data returned due to errors
				$form->load_data_error();
			}


			// display the form
			$form->render_form();

		} // end if valid options

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
