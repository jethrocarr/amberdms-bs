<?php
/*
	vendors/view.php

	access: vendors_view (read-only)
		vendors_write (write access)

	Displays the selected vendor and if the user has correct permissions
	allows the vendor to be updated.
*/

if (user_permissions_get('vendors_view'))
{
	$id = $_GET["id"];
	
	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "Vendor's Details";
	$_SESSION["nav"]["query"][]	= "page=vendors/view.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=vendors/view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Vendors's Journal";
	$_SESSION["nav"]["query"][]	= "page=vendors/journal.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Vendor's Invoices";
	$_SESSION["nav"]["query"][]	= "page=vendors/invoices.php&id=$id";

	if (user_permissions_get('vendors_write'))
	{
		$_SESSION["nav"]["title"][]	= "Delete Vendor";
		$_SESSION["nav"]["query"][]	= "page=vendors/delete.php&id=$id";
	}


	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		print "<h3>VENDOR DETAILS</h3><br>";
		print "<p>This page allows you to view and adjust the vendor's records.</p>";

		$mysql_string	= "SELECT id FROM `vendors` WHERE id='$id'";
		$mysql_result	= mysql_query($mysql_string);
		$mysql_num_rows	= mysql_num_rows($mysql_result);

		if (!$mysql_num_rows)
		{
			print "<p><b>Error: The requested vendor does not exist. <a href=\"index.php?page=vendors/vendors.php\">Try looking for your vendor on the vendor list page.</a></b></p>";
		}
		else
		{
			/*
				Define form structure
			*/
			$form = New form_input;
			$form->formname = "vendor_view";
			$form->language = $_SESSION["user"]["lang"];

			$form->action = "vendors/edit-process.php";
			$form->method = "post";
			

			// general
			$structure = NULL;
			$structure["fieldname"] 	= "id_vendor";
			$structure["type"]		= "text";
			$structure["defaultvalue"]	= "$id";
			$form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"] 	= "name_vendor";
			$structure["type"]		= "input";
			$structure["options"]["req"]	= "yes";
			$form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"] = "name_contact";
			$structure["type"]	= "input";
			$form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"] = "name_contact";
			$structure["type"]	= "input";
			$form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"] = "contact_email";
			$structure["type"]	= "input";
			$form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"] = "contact_phone";
			$structure["type"]	= "input";
			$form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"] = "contact_fax";
			$structure["type"]	= "input";
			$form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"] = "date_start";
			$structure["type"]	= "date";
			$form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"] = "date_end";
			$structure["type"]	= "date";
			$form->add_input($structure);


			// tax options
			$structure = NULL;
			$structure["fieldname"] = "tax_included";
			$structure["type"]	= "input";
			$form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"] = "tax_number";
			$structure["type"]	= "input";
			$form->add_input($structure);


			// billing address
			$structure = NULL;
			$structure["fieldname"] = "address1_street";
			$structure["type"]	= "textarea";
			$form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"] = "address1_city";
			$structure["type"]	= "input";
			$form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"] = "address1_state";
			$structure["type"]	= "input";
			$form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"] = "address1_country";
			$structure["type"]	= "input";
			$form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"] = "address1_zipcode";
			$structure["type"]	= "input";
			$form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"] = "pobox";
			$structure["type"]	= "textarea";
			$form->add_input($structure);


			// shipping address
			$structure = NULL;
			$structure["fieldname"] = "address2_street";
			$structure["type"]	= "textarea";
			$form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"] = "address2_city";
			$structure["type"]	= "input";
			$form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"] = "address2_state";
			$structure["type"]	= "input";
			$form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"] = "address2_country";
			$structure["type"]	= "input";
			$form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"] = "address2_zipcode";
			$structure["type"]	= "input";
			$form->add_input($structure);
			
			// submit section
			if (user_permissions_get("vendors_write"))
			{
				$structure = NULL;
				$structure["fieldname"] 	= "submit";
				$structure["type"]		= "submit";
				$structure["defaultvalue"]	= "Save Changes";
				$form->add_input($structure);
			
			}
			else
			{
				$structure = NULL;
				$structure["fieldname"] 	= "submit";
				$structure["type"]		= "message";
				$structure["defaultvalue"]	= "<p><i>Sorry, you don't have permissions to make changes to vendor records.</i></p>";
				$form->add_input($structure);
			}
			
			
			// define subforms
			$form->subforms["vendor_view"]	= array("id_vendor", "name_vendor", "name_contact", "contact_phone", "contact_fax", "contact_email", "date_start", "date_end", "tax_included", "tax_number");
			$form->subforms["address_billing"]	= array("address1_street", "address1_city", "address1_state", "address1_country", "address1_zipcode", "pobox");
			$form->subforms["address_shipping"]	= array("address2_street", "address2_city", "address2_state", "address2_country", "address2_zipcode");
			$form->subforms["submit"]	= array("submit");

			
			// fetch the form data
			$form->sql_query = "SELECT * FROM `vendors` WHERE id='$id' LIMIT 1";		
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
