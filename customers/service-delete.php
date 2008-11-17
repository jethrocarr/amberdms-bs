<?php
/*
	customers/service-delete.php
	
	access: customers_write

	Form to delete a customer service.
*/

if (user_permissions_get('customers_write'))
{
	$customerid = $_GET["customerid"];
	
	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;

	$_SESSION["nav"]["title"][]	= "Customer's Details";
	$_SESSION["nav"]["query"][]	= "page=customers/view.php&id=$customerid";

	$_SESSION["nav"]["title"][]	= "Customer's Journal";
	$_SESSION["nav"]["query"][]	= "page=customers/journal.php&id=$customerid";

	$_SESSION["nav"]["title"][]	= "Customer's Services";
	$_SESSION["nav"]["query"][]	= "page=customers/services.php&id=$customerid";
	$_SESSION["nav"]["current"]	= "page=customers/services.php&id=$customerid";

	$_SESSION["nav"]["title"][]	= "Delete Customer";
	$_SESSION["nav"]["query"][]	= "page=customers/delete.php&id=$customerid";



	function page_render()
	{
		$customerid		= security_script_input('/^[0-9]*$/', $_GET["customerid"]);
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
			
			print "<h3>DELETE SERVICE</h3><br>";
			print "<p>This page allows you to delete a service from a customer's account.</p>";
			

			/*
				Define form structure
			*/
			$form = New form_input;
			$form->formname = "service_delete";
			$form->language = $_SESSION["user"]["lang"];

			$form->action = "customers/service-delete-process.php";
			$form->method = "post";
		
		
			// general
			$structure = NULL;
			$structure["fieldname"] 	= "name_service";
			$structure["type"]		= "text";
			$form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"] 	= "description";
			$structure["type"]		= "text";
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
			

			// confirm delete
			$structure = NULL;
			$structure["fieldname"] 	= "delete_confirm";
			$structure["type"]		= "checkbox";
			$structure["options"]["label"]	= "Yes, I wish to delete this service and realise that once deleted the data can not be recovered.";
			$form->add_input($structure);


			// submit button
			$structure = NULL;
			$structure["fieldname"] 	= "submit";
			$structure["type"]		= "submit";
			$structure["defaultvalue"]	= "Delete Service";
			$form->add_input($structure);


			// define subforms
			$form->subforms["service_delete"]	= array("name_service", "description");
			$form->subforms["hidden"]		= array("customerid", "services_customers_id");
			$form->subforms["submit"]		= array("delete_confirm", "submit");
	

			// fetch the form data
			$form->sql_query = "SELECT services_customers.description, services.name_service FROM services_customers LEFT JOIN services ON services.id = services_customers.serviceid WHERE services_customers.id='$services_customers_id' LIMIT 1";
			$form->load_data();


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
