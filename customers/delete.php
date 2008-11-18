<?php
/*
	customers/delete.php
	
	access:	customers_write

	Allows an unwanted customer to be deleted.
*/

if (user_permissions_get('customers_write'))
{
	$id = $_GET["id"];
	
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

	$_SESSION["nav"]["title"][]	= "Delete Customer";
	$_SESSION["nav"]["query"][]	= "page=customers/delete.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=customers/delete.php&id=$id";


	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		print "<h3>DELETE CUSTOMER</h3><br>";
		print "<p>This page allows you to delete an unwanted customers. Note that it is only possible to delete a customer if they do not belong to any
		invoices or time groups. If they do, you can not delete the customer, but instead you can disable the customer by setting the date_end field.</p>";

		$mysql_string	= "SELECT id FROM `customers` WHERE id='$id'";
		$mysql_result	= mysql_query($mysql_string);
		$mysql_num_rows	= mysql_num_rows($mysql_result);

		if (!$mysql_num_rows)
		{
			print "<p><b>Error: The requested customer does not exist. <a href=\"index.php?page=customers/customers.php\">Try looking for your customer on the customer list page.</a></b></p>";
		}
		else
		{
			/*
				Define form structure
			*/
			$form = New form_input;
			$form->formname = "customer_delete";
			$form->language = $_SESSION["user"]["lang"];

			$form->action = "customers/delete-process.php";
			$form->method = "post";
			

			// general
			$structure = NULL;
			$structure["fieldname"] 	= "name_customer";
			$structure["type"]		= "text";
			$form->add_input($structure);


			// hidden
			$structure = NULL;
			$structure["fieldname"] 	= "id_customer";
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= "$id";
			$form->add_input($structure);
			
			
			// confirm delete
			$structure = NULL;
			$structure["fieldname"] 	= "delete_confirm";
			$structure["type"]		= "checkbox";
			$structure["options"]["label"]	= "Yes, I wish to delete this customer and realise that once deleted the data can not be recovered.";
			$form->add_input($structure);



			/*
				Check that the customer can be deleted
			*/

			$locked = 0;
			

			// make sure customer does not belong to any invoices
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM account_ar WHERE customerid='$id'";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				$locked = 1;
			}

			// make sure customer has no time groups assigned to it
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM time_groups WHERE customerid='$id'";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				$locked = 1;
			}

	
			// define submit field
			$structure = NULL;
			$structure["fieldname"] = "submit";

			if ($locked)
			{
				$structure["type"]		= "message";
				$structure["defaultvalue"]	= "<i>This customer can not be deleted because it belongs to an invoice or time group.</i>";
			}
			else
			{
				$structure["type"]		= "submit";
				$structure["defaultvalue"]	= "delete";
			}
					
			$form->add_input($structure);


			
			// define subforms
			$form->subforms["customer_delete"]	= array("name_customer");
			$form->subforms["hidden"]		= array("id_customer");
			$form->subforms["submit"]		= array("delete_confirm", "submit");

			
			// fetch the form data
			$form->sql_query = "SELECT name_customer FROM `customers` WHERE id='$id' LIMIT 1";		
			$form->load_data();

			// display the form
			$form->render_form();

		}

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
