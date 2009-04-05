<?php
/*
	vendors/view.php

	access: vendors_view (read-only)
		vendors_write (write access)

	Displays the selected vendor and if the user has correct permissions
	allows the vendor to be updated.
*/


class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_form;


	function page_output()
	{
		// fetch variables
		$this->id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Vendor's Details", "page=vendors/view.php&id=". $this->id ."", TRUE);
		$this->obj_menu_nav->add_item("Vendor's Journal", "page=vendors/journal.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Vendor's Invoices", "page=vendors/invoices.php&id=". $this->id ."");

		if (user_permissions_get("vendors_write"))
		{
			$this->obj_menu_nav->add_item("Delete Vendor", "page=vendors/delete.php&id=". $this->id ."");
		}
	}



	function check_permissions()
	{
		return user_permissions_get("vendors_view");
	}



	function check_requirements()
	{
		// verify that vendor exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM vendors WHERE id='". $this->id ."'";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested vendor (". $this->id .") does not exist - possibly the vendor has been deleted.");
			return 0;
		}

		unset($sql_obj);


		return 1;
	}



	function execute()
	{
		/*
			Define form structure
		*/
		$this->obj_form = New form_input;
		$this->obj_form->formname = "vendor_view";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "vendors/edit-process.php";
		$this->obj_form->method = "post";
		

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "code_vendor";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "name_vendor";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] = "name_contact";
		$structure["type"]	= "input";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] = "name_contact";
		$structure["type"]	= "input";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] = "contact_email";
		$structure["type"]	= "input";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] = "contact_phone";
		$structure["type"]	= "input";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] = "contact_fax";
		$structure["type"]	= "input";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "date_start";
		$structure["type"]		= "date";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] = "date_end";
		$structure["type"]	= "date";
		$this->obj_form->add_input($structure);

		$this->obj_form->subforms["vendor_view"]		= array("code_vendor", "name_vendor", "name_contact", "contact_phone", "contact_fax", "contact_email", "date_start", "date_end");



		// taxes
		$structure = NULL;
		$structure["fieldname"] = "tax_number";
		$structure["type"]	= "input";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure = form_helper_prepare_dropdownfromdb("tax_default", "SELECT id, name_tax as label FROM account_taxes");
		$this->obj_form->add_input($structure);

		$this->obj_form->subforms["vendor_taxes"]	= array("tax_number", "tax_default");



		// list all the taxes so the user can enable or disable the taxes
		$sql_tax_obj		= New sql_query;
		$sql_tax_obj->string	= "SELECT id, name_tax, description FROM account_taxes ORDER BY name_tax";
		$sql_tax_obj->execute();

		if ($sql_tax_obj->num_rows())
		{
			// user note
			$structure = NULL;
			$structure["fieldname"] 		= "tax_message";
			$structure["type"]			= "message";
			$structure["defaultvalue"]		= "<p>Select all the taxes below which apply to this vendor. Any taxes not selected, will not be added to invoices from this vendor.</p>";
			$this->obj_form->add_input($structure);
				
			$this->obj_form->subforms["vendor_taxes"][] = "tax_message";


			// fetch vendor's current tax status
			if (!$_SESSION["error"]["message"])
			{
				$sql_vendor_taxes_obj		= New sql_query;
				$sql_vendor_taxes_obj->string	= "SELECT taxid FROM vendors_taxes WHERE vendorid='". $this->id ."'";

				$sql_vendor_taxes_obj->execute();

				if ($sql_vendor_taxes_obj->num_rows())
				{
					$sql_vendor_taxes_obj->fetch_array();
				}
			}

			// run through all the taxes
			$sql_tax_obj->fetch_array();

			foreach ($sql_tax_obj->data as $data_tax)
			{
				// define tax checkbox
				$structure = NULL;
				$structure["fieldname"] 		= "tax_". $data_tax["id"];
				$structure["type"]			= "checkbox";
				$structure["options"]["label"]		= $data_tax["name_tax"] ." -- ". $data_tax["description"];
				$structure["options"]["no_fieldname"]	= "enable";

				// check if this tax is currently checked
				if ($sql_vendor_taxes_obj->data_num_rows)
				{
					foreach ($sql_vendor_taxes_obj->data as $data)
					{
						if ($data["taxid"] == $data_tax["id"])
						{
							$structure["defaultvalue"] = "on";
						}
					}
				}

				// add to form
				$this->obj_form->add_input($structure);
				$this->obj_form->subforms["vendor_taxes"][] = "tax_". $data_tax["id"];
			}
		}


		// purchase options
		$structure = NULL;
		$structure["fieldname"] 		= "discount";
		$structure["type"]			= "input";
		$structure["options"]["width"]		= 50;
		$structure["options"]["label"]		= " %";
		$structure["options"]["max_length"]	= "6";
		$this->obj_form->add_input($structure);

		$this->obj_form->subforms["vendor_purchase"] = array("discount");




		// billing address
		$structure = NULL;
		$structure["fieldname"] = "address1_street";
		$structure["type"]	= "textarea";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] = "address1_city";
		$structure["type"]	= "input";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] = "address1_state";
		$structure["type"]	= "input";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] = "address1_country";
		$structure["type"]	= "input";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] = "address1_zipcode";
		$structure["type"]	= "input";
		$this->obj_form->add_input($structure);
		
		$this->obj_form->subforms["address_billing"]		= array("address1_street", "address1_city", "address1_state", "address1_country", "address1_zipcode");



		// shipping address
		$structure = NULL;
		$structure["fieldname"] = "address2_street";
		$structure["type"]	= "textarea";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] = "address2_city";
		$structure["type"]	= "input";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] = "address2_state";
		$structure["type"]	= "input";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] = "address2_country";
		$structure["type"]	= "input";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] = "address2_zipcode";
		$structure["type"]	= "input";
		$this->obj_form->add_input($structure);
	
		$this->obj_form->subforms["address_shipping"]		= array("address2_street", "address2_city", "address2_state", "address2_country", "address2_zipcode");



		// submit section
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Save Changes";
		$this->obj_form->add_input($structure);
			
		// hidden
		$structure = NULL;
		$structure["fieldname"] 	= "id_vendor";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->id;
		$this->obj_form->add_input($structure);
		


		// define subforms
		$this->obj_form->subforms["hidden"]			= array("id_vendor");

		if (user_permissions_get("vendors_write"))
		{
			$this->obj_form->subforms["submit"]			= array("submit");
		}
		else
		{
			$this->obj_form->subforms["submit"]			= array();
		}

		
		// fetch the form data
		$this->obj_form->sql_query = "SELECT * FROM `vendors` WHERE id='". $this->id ."' LIMIT 1";
		$this->obj_form->load_data();

	}


	function render_html()
	{
		// title + summary
		print "<h3>VENDOR DETAILS</h3><br>";
		print "<p>This page allows you to view and adjust the vendor's records.</p>";

		// display the form
		$this->obj_form->render_form();

		if (!user_permissions_get("vendors_write"))
		{
			format_msgbox("locked", "<p>Sorry, you do not have permissions to adjust this vendor.</p>");
		}
	}


} // end of page_output

?>
