<?php
/*
	admin/config_company.php
	
	access: admin users only

	Allows an administrator to change the company information and details.
*/

class page_output
{
	var $obj_form;


	function check_permissions()
	{
		return user_permissions_get("admin");
	}

	function check_requirements()
	{
		// nothing to do
		return 1;
	}


	function execute()
	{
		/*
			Define form structure
		*/
		
		$this->obj_form = New form_input;
		$this->obj_form->formname = "config_company";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "admin/config_company-process.php";
		$this->obj_form->method = "post";



		$structure = NULL;
		$structure["fieldname"]				= "COMPANY_NAME";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]				= "COMPANY_REG_NUMBER";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"] = "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]				= "COMPANY_TAX_NUMBER";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"] = "yes";
		$this->obj_form->add_input($structure);
	
		$structure = NULL;
		$structure["fieldname"]				= "COMPANY_CONTACT_EMAIL";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);
	
		$structure = NULL;
		$structure["fieldname"]				= "COMPANY_CONTACT_PHONE";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);
	
		$structure = NULL;
		$structure["fieldname"]				= "COMPANY_CONTACT_FAX";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);
	
		$structure = NULL;
		$structure["fieldname"]				= "COMPANY_ADDRESS1_STREET";
		$structure["type"]				= "textarea";
		$structure["options"]["width"]			= "300";
		$structure["options"]["height"]			= "60";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);
	
		$structure = NULL;
		$structure["fieldname"]				= "COMPANY_ADDRESS1_CITY";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);
	
		$structure = NULL;
		$structure["fieldname"]				= "COMPANY_ADDRESS1_STATE";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]				= "COMPANY_ADDRESS1_COUNTRY";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]				= "COMPANY_ADDRESS1_ZIPCODE";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]				= "COMPANY_PAYMENT_DETAILS";
		$structure["type"]				= "textarea";
		$structure["options"]["width"]			= "600";
		$structure["options"]["height"]			= "60";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]				= "COMPANY_ADDRESS2_STREET";
		$structure["type"]				= "textarea";
		$structure["options"]["width"]			= "300";
		$structure["options"]["height"]			= "60";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);
	
		$structure = NULL;
		$structure["fieldname"]				= "COMPANY_ADDRESS2_CITY";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);
	
		$structure = NULL;
		$structure["fieldname"]				= "COMPANY_ADDRESS2_STATE";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]				= "COMPANY_ADDRESS2_COUNTRY";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]				= "COMPANY_ADDRESS2_ZIPCODE";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 			= "COMPANY_ADDRESS_MSG";
		$structure["type"]				= "message";
		$structure["defaultvalue"]			= "Enter the registered address of the company below, if different to the contact address.";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]				= "COMPANY_B2C_TERMS";
		$structure["type"]				= "tinymce";
		$structure["options"]["width"]			= 500;
		$structure["options"]["height"]			= 100;
		$structure["options"]["no_translate_fieldname"] = "yes";
		$structure["options"]["css_field_class"]	= "tinymce";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]				= "COMPANY_B2B_TERMS";
		$structure["type"]				= "tinymce";
		$structure["options"]["width"]			= 500;
		$structure["options"]["height"]			= 100;
		$structure["options"]["no_translate_fieldname"] = "yes";
		$structure["options"]["css_field_class"]	= "tinymce";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 			= "COMPANY_LOGO";
		$structure["type"]				= "file";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);
	
		$structure = NULL;
		$structure["fieldname"] 			= "COMPANY_LOGO_MSG";
		$structure["type"]				= "message";
		$structure["defaultvalue"]			= "Note: You only need to upload a logo once or when you want to replace it with a new logo. The logo will be used on PDF files generated by the billing system such as invoices.";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);

		// submit section
		$structure = NULL;
		$structure["fieldname"]			= "submit";
		$structure["type"]			= "submit";
		$structure["defaultvalue"]		= "Save Changes";
		$this->obj_form->add_input($structure);
		
		
		// define subforms
		$this->obj_form->subforms["config_company_details"]		= array("COMPANY_NAME", "COMPANY_LOGO", "COMPANY_LOGO_MSG");
		$this->obj_form->subforms["config_company_contact"]		= array("COMPANY_CONTACT_EMAIL", "COMPANY_CONTACT_PHONE", "COMPANY_CONTACT_FAX", "COMPANY_ADDRESS1_STREET", "COMPANY_ADDRESS1_CITY","COMPANY_ADDRESS1_STATE","COMPANY_ADDRESS1_COUNTRY", "COMPANY_ADDRESS1_ZIPCODE");
		$this->obj_form->subforms["config_company_registration"]		= array("COMPANY_TAX_NUMBER","COMPANY_REG_NUMBER","COMPANY_ADDRESS_MSG","COMPANY_ADDRESS2_STREET","COMPANY_ADDRESS2_CITY","COMPANY_ADDRESS2_STATE","COMPANY_ADDRESS2_COUNTRY","COMPANY_ADDRESS2_ZIPCODE");
		$this->obj_form->subforms["config_company_terms"] 		= array("COMPANY_PAYMENT_DETAILS","COMPANY_B2C_TERMS","COMPANY_B2B_TERMS");
		$this->obj_form->subforms["submit"]			= array("submit");

		if (error_check())
		{
			// load error datas
			$this->obj_form->load_data_error();
		}
		else
		{
			// fetch all the values from the database
			$sql_config_obj		= New sql_query;
			$sql_config_obj->string	= "SELECT name, value FROM config ORDER BY name";
			$sql_config_obj->execute();
			$sql_config_obj->fetch_array();

			foreach ($sql_config_obj->data as $data_config)
			{
				$this->obj_form->structure[ $data_config["name"] ]["defaultvalue"] = $data_config["value"];
			}

			unset($sql_config_obj);
		}
	}



	function render_html()
	{
		// Title + Summary
		print "<h3>COMPANY CONFIGURATION</h3><br>";
		print "<p>This page allows you to configure your company details and contact information for use in invoices and reports.</p>";
	
		// display the form
		$this->obj_form->render_form();
	}

	
}

?>
