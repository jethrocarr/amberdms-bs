<?php
/*
	taxes/view.php
	
	access: accounts_taxes_view
		accounts_taxes_write

	Displays all the details for the tax and if the user has correct
	permissions allows the tax to be updated.
*/


// custom includes
require("include/accounts/inc_charts.php");


class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_form;


	function page_output()
	{
		// fetch variables
		$this->id = @security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Tax Details", "page=accounts/taxes/view.php&id=". $this->id ."", TRUE);
		$this->obj_menu_nav->add_item("Tax Ledger", "page=accounts/taxes/ledger.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Tax Collected", "page=accounts/taxes/tax_collected.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Tax Paid", "page=accounts/taxes/tax_paid.php&id=". $this->id ."");


		if (user_permissions_get("accounts_taxes_write"))
		{
			$this->obj_menu_nav->add_item("Delete Tax", "page=accounts/taxes/delete.php&id=". $this->id ."");
		}
	}



	function check_permissions()
	{
		return user_permissions_get("accounts_taxes_view");
	}



	function check_requirements()
	{
		// verify that the tax exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_taxes WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested account (". $this->id .") does not exist - possibly the account has been deleted.");
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
		$this->obj_form->formname = "tax_view";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "accounts/taxes/edit-process.php";
		$this->obj_form->method = "post";
		

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "name_tax";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 		= "taxrate";
		$structure["type"]			= "input";
		$structure["options"]["req"]		= "yes";
		$structure["options"]["width"]		= 50;
		$structure["options"]["label"]		= " %";
		$structure["options"]["max_length"]	= "6";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "taxnumber";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);


		$structure = NULL;
		$structure["fieldname"] 	= "description";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);

		// tax account selection
		$structure = charts_form_prepare_acccountdropdown("chartid", "tax_summary_account");
		$structure["options"]["req"]	= "yes";

		if (!$structure["values"])
		{
			$structure["type"]		= "text";
			$structure["defaultvalue"]	= "<b>You need to add some tax accounts for this tax to belong to, before you can use this tax</b>";
		}
		$this->obj_form->add_input($structure);
		
		
		//set defaults
		$structure = NULL;
		$structure["fieldname"] 		= "default_customers";
		$structure["type"]			= "checkbox";
		$structure["options"]["no_fieldname"]	= "yes";
		$structure["options"]["label"]		= "Enable this tax for all customers by default";
		$structure["defaultvalue"]		= "on";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 		= "default_vendors";
		$structure["type"]			= "checkbox";
		$structure["options"]["no_fieldname"]	= "yes";
		$structure["options"]["label"]		= "Enable this tax for all vendors by default";
		$structure["defaultvalue"]		= "on";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 		= "default_products";
		$structure["type"]			= "checkbox";
		$structure["options"]["no_fieldname"]	= "yes";
		$structure["options"]["label"]		= "Enable this tax for all products by default";
		$structure["defaultvalue"]		= "on";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 		= "default_services";
		$structure["type"]			= "checkbox";
		$structure["options"]["no_fieldname"]	= "yes";
		$structure["options"]["label"]		= "Enable this tax for all services by default";
		$structure["defaultvalue"]		= "on";
		$this->obj_form->add_input($structure);


		// hidden
		$structure = NULL;
		$structure["fieldname"] 	= "id_tax";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->id;
		$this->obj_form->add_input($structure);


		// submit section
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Save Changes";
		$this->obj_form->add_input($structure);
		
		
		// define subforms
		$this->obj_form->subforms["tax_details"]	= array("name_tax", "chartid", "taxrate", "taxnumber", "description");
		$this->obj_form->subforms["tax_set_default"]	= array("default_customers", "default_vendors", "default_products", "default_services");
		$this->obj_form->subforms["hidden"]		= array("id_tax");
		
		if (user_permissions_get("accounts_taxes_write"))
		{
			$this->obj_form->subforms["submit"]	= array("submit");
		}
		else
		{
			$this->obj_form->subforms["submit"]	= array("");
		}

		
		// fetch the form data
		$this->obj_form->sql_query = "SELECT * FROM `account_taxes` WHERE id='". $this->id ."' LIMIT 1";
		$this->obj_form->load_data();

	}


	function render_html()
	{

		// Title + Summary
		print "<h3>TAX DETAILS</h3><br>";
		print "<p>This page allows you to view and adjust the selected tax. Note that any changes to the tax rate will not affect any existing invoices.</p>";

		// display the form
		$this->obj_form->render_form();
		
		if (!user_permissions_get("accounts_taxes_write"))
		{
			format_msgbox("locked", "Sorry, you do not have permission to make changes to taxes.");
		}
	}
}


?>
