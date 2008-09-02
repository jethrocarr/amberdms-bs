<?php
/*
	view.php

	access: customers_view (read-only)
		customers_write (write access)

	Depending on the way called, either allows the creation of a new route or the
	adjustment of an existing one.
*/

if (user_permissions_get('customers_view'))
{
	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);
	
		// heading
		print "<h2>VIEW CUSTOMER</h2><br><br>";
	
		/*
			Define form structure
		*/
		$form = New form_input;
		$form->formname = "customer_view";
		$form->language = $_SESSION["user"]["lang"];

		$form->action = "customers/edit-process.php";
		$form->method = "POST";
		

		// general
		$structure = NULL;
		$structure["fieldname"] = "name_customer";
		$structure["type"]	= "input";
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
		$structure["type"]	= "input";
		$form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] = "date_end";
		$structure["type"]	= "input";
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
		$structure["type"]	= "input";
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
		$structure["type"]	= "input";
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
		


		// define subforms
		$form->subforms["customer_view"]	= array("name_customer", "name_contact", "contact_phone", "contact_fax", "contact_email", "date_start", "date_end", "tax_included", "tax_number");
		$form->subforms["address_billing"]	= array("address1_street", "address1_city", "address1_state", "address1_country", "address1_zipcode", "pobox");
		$form->subforms["address_shipping"]	= array("address2_street", "address2_city", "address2_state", "address2_country", "address2_zipcode");

		
		// fetch the form data
		$form->sql_query = "SELECT * FROM `customers` WHERE id='$id' LIMIT 1";
		
		$form->load_data_sql();

		// display the form
		$form->render_form();
		
	/*

		// general form
		$form_general = array("name_customer", "name_contact", "contact_phone", "contact_fax");
		
		print "<td valign=\"top\" width=\"50%\"><table class=\"form_table\" width=\"100%\">";

		// form header
		$numcols = count($form_general);
		print "<tr class=\"header\">";
		print "<td colspan=\"$numcols\"><b>". language_translate_string($_SESSION["user"]["lang"], "General Details") ."</b></td>";
		print "</tr>";

		// display all the rows
		foreach ($form_general as $fieldname)
		{
			$form->render_row($fieldname);
		}

		// end form table
		print "</table></td>";



		// shipping address form
		$form_general = array("name_customer", "name_contact", "contact_phone", "contact_fax");
		$form_shipping = array("address2_street", "address2_city", "address2_state", "address2_country", "address2_zipcode");
		
		print "<td valign=\"top\" width=\"50%\"><table class=\"form_table\" width=\"100%\">";

		// form header
		$numcols = count($form_shipping);
		print "<tr class=\"header\">";
		print "<td colspan=\"$numcols\"><b>". language_translate_string($_SESSION["user"]["lang"], "Shipping Details") ."</b></td>";
		print "</tr>";

		// display all the rows
		foreach ($form_shipping as $fieldname)
		{
			$form->render_row($fieldname);
		}

		// end form table
		print "</table></td>";

		
		print "</tr></table>";
		print "</form>";

		*/

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
