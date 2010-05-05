<?php
/*
	customers/credit-edit.php

	access: customers_credit

	Allows a credit entry to be made against the customer.
*/


require("include/customers/inc_customers.php");
require("include/accounts/inc_credit.php");
require("include/accounts/inc_charts.php");


class page_output
{
	var $obj_customer;
	var $obj_credit;

	var $obj_menu_nav;
	var $obj_form;

	

	function page_output()
	{
		$this->obj_customer				= New customer;
		$this->obj_credit				= New credit;



		// fetch variables
		$this->obj_customer->id				= @security_script_input('/^[0-9]*$/', $_GET["id_customer"]);

		$this->obj_credit->id				= @security_script_input('/^[0-9]*$/', $_GET["id_credit"]);
		$this->obj_credit->id_organisation		= $this->obj_customer->id;
		$this->obj_credit->id_organisation_type		= "customer";


		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Customer's Details", "page=customers/view.php&id=". $this->obj_customer->id ."");

		if (sql_get_singlevalue("SELECT value FROM config WHERE name='MODULE_CUSTOMER_PORTAL' LIMIT 1") == "enabled")
		{
			$this->obj_menu_nav->add_item("Portal Options", "page=customers/portal.php&id=". $this->obj_customer->id ."");
		}

		$this->obj_menu_nav->add_item("Customer's Journal", "page=customers/journal.php&id=". $this->obj_customer->id ."");
		$this->obj_menu_nav->add_item("Customer's Invoices", "page=customers/invoices.php&id=". $this->obj_customer->id ."");
		$this->obj_menu_nav->add_item("Customer's Credit", "page=customers/credit.php&id_customer=". $this->obj_customer->id ."", TRUE);
		$this->obj_menu_nav->add_item("Customer's Services", "page=customers/services.php&id=". $this->obj_customer->id ."");

		if (user_permissions_get("customers_write"))
		{
			$this->obj_menu_nav->add_item("Delete Customer", "page=customers/delete.php&id=". $this->obj_customer->id ."");
		}
	}



	function check_permissions()
	{
		return user_permissions_get("customers_credit");
	}



	function check_requirements()
	{
		// verify that customer exists
		if (!$this->obj_customer->verify_id())
		{
			log_write("error", "page_output", "The requested customer (". $this->obj_customer->id .") does not exist - possibly the customer has been deleted.");
			return 0;
		}

		// verify that the credit exists (if being adjusted)
		if ($this->obj_credit->id)
		{
			if (!$this->obj_credit->verify_id())
			{
				log_write("error", "page_output", "The requested credit transaction is not valid");
				return 0;
			}
		}


		return 1;
	}





	function execute()
	{
		// load basic data
		$this->obj_customer->load_data();


		// define basic form details
		$this->obj_form = New form_input;
		$this->obj_form->formname = "customer_credit_edit";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "customers/credit-edit-process.php";
		$this->obj_form->method = "post";


		// customer details
		$structure = NULL;
		$structure["fieldname"] 		= "name_customer";
		$structure["type"]			= "text";
		$structure["defaultvalue"]		= $this->obj_customer->data["code_customer"] ." -- ". $this->obj_customer->data["name_customer"];
		$this->obj_form->add_input($structure);

		// current balance
		$structure = NULL;
		$structure["fieldname"] 		= "credit_balance";
		$structure["type"]			= "text";
		$structure["defaultvalue"]		= format_money($this->obj_credit->get_org_balance());
		$this->obj_form->add_input($structure);

		$this->obj_form->subforms["customer_details"]	= array("name_customer", "credit_balance");


		// credit details
		$structure = NULL;
		$structure["fieldname"]			= "code_credit";
		$structure["type"]			= "input";
		$structure["options"]["req"]		= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]			= "date_trans";
		$structure["type"]			= "date";
		$structure["options"]["req"]		= "yes";
		$this->obj_form->add_input($structure);

		$structure = form_helper_prepare_dropdownfromdb("id_employee", "SELECT id, staff_code as label, name_staff as label1 FROM staff ORDER BY name_staff");
		$structure["options"]["req"]		= "yes";
		$structure["options"]["autoselect"]	= "yes";
		$structure["options"]["width"]		= "600";
		$structure["defaultvalue"]		= @$_SESSION["user"]["default_employeeid"];
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]			= "description";
		$structure["type"]			= "textarea";
		$this->obj_form->add_input($structure);

		$this->obj_form->subforms["credit_note_details"]	= array("code_credit", "date_trans", "id_employee", "description");



		// amount/location
		$structure = charts_form_prepare_acccountdropdown("id_chart_account", "ar_income");
		$structure["options"]["req"]		= "yes";
		$structure["options"]["width"]		= "600";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]			= "amount";
		$structure["type"]			= "money";
		$structure["options"]["req"]		= "yes";
		$this->obj_form->add_input($structure);


		$this->obj_form->subforms["credit_note_amount"]		= array("id_chart_account", "amount");



		// taxes
		$sql_tax_obj		= New sql_query;
		$sql_tax_obj->string	= "SELECT id, name_tax, description FROM account_taxes ORDER BY name_tax";
		$sql_tax_obj->execute();

		if ($sql_tax_obj->num_rows())
		{
			// user note
			$structure = NULL;
			$structure["fieldname"] 		= "tax_message";
			$structure["type"]			= "message";
			$structure["defaultvalue"]		= "<p>If you are issuing this credit note as a refund for a customer and need to refund the tax component as well, select the relevent taxes below.</p>";
			$this->obj_form->add_input($structure);
				
			$this->obj_form->subforms["credit_note_taxes"][] = "tax_message";


			// fetch customer's current tax status, we use this to select the taxes that
			// would most-likely apply to that customer.
			if (!isset($_SESSION["error"]["message"]))
			{
				$sql_customer_taxes_obj		= New sql_query;
				$sql_customer_taxes_obj->string	= "SELECT taxid FROM customers_taxes WHERE customerid='". $this->id ."'";

				$sql_customer_taxes_obj->execute();

				if ($sql_customer_taxes_obj->num_rows())
				{
					$sql_customer_taxes_obj->fetch_array();
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
				
				if ($sql_customer_taxes_obj->data_num_rows)
				{
					foreach ($sql_customer_taxes_obj->data as $data)
					{
						if ($data["taxid"] == $data_tax["id"])
						{
							$structure["defaultvalue"] = "on";
						}
					}
				}

				// add to form
				$this->obj_form->add_input($structure);
				$this->obj_form->subforms["credit_note_taxes"][] = "tax_". $data_tax["id"];
			}
		}





		// hidden
		$structure = NULL;
		$structure["fieldname"] 		= "id_customer";
		$structure["type"]			= "hidden";
		$structure["defaultvalue"]		= $this->obj_customer->id;
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 		= "id_credit";
		$structure["type"]			= "hidden";
		$structure["defaultvalue"]		= $this->obj_credit->id;
		$this->obj_form->add_input($structure);

		// submit button
		$structure = NULL;
		$structure["fieldname"] 		= "submit";
		$structure["type"]			= "submit";
		$structure["defaultvalue"]		= "submit";
		$this->obj_form->add_input($structure);
		

		// define subforms
		$this->obj_form->subforms["hidden"]		= array("id_customer", "id_credit");
		$this->obj_form->subforms["submit"]		= array("submit");
		

		// load any data returned due to errors
		if (error_check())
		{
			$this->obj_form->load_data_error();
		}
		else
		{
			// load DDI
			if ($this->obj_credit->id)
			{
				$this->obj_credit->load_data();

				$this->obj_form->structure["code_credit"]["defaultvalue"]		= $this->obj_credit->data["code_credit"];
				$this->obj_form->structure["date_trans"]["defaultvalue"]		= $this->obj_credit->data["date_trans"];
				$this->obj_form->structure["id_employee"]["defaultvalue"]		= $this->obj_credit->data["id_employee"];
				$this->obj_form->structure["id_chart_account"]["defaultvalue"]		= $this->obj_credit->data["id_chart_account"];
				$this->obj_form->structure["amount"]["defaultvalue"]			= $this->obj_credit->data["amount"];
				$this->obj_form->structure["description"]["defaultvalue"]		= $this->obj_credit->data["description"];
			}
		}
	}



	function render_html()
	{
		// title and summary
		if ($this->obj_credit->id)
		{
			print "<h3>ADJUST CREDIT NOTE</h3><br>";
			print "<p>Use the form below to adjust the credit note.</p>";
		}
		else
		{
			print "<h3>ADD NEW CREDIT NOTE</h3>";
			print "<p>Use the form below to add a new credit note to the selected customer.</p>";
		}

		// display the form
		$this->obj_form->render_form();
	}



}


?>
