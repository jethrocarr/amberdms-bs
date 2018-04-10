<?php
/*
	customers/credit-refund.php
	
	access: customers_credit

	Form to make a credit refund payment - a refund is essentially an item in the credits pool for a customer
	as well as an associated GL transaction crediting the asset account.
*/


require("include/customers/inc_customers.php");
require("include/accounts/inc_credits.php");
require("include/accounts/inc_charts.php");


class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_table;

	var $obj_customer;
	var $obj_refund;

	
	function __construct()
	{
		// fetch variables
		$this->obj_customer		= New customer_credits;
		$this->obj_customer->id		= @security_script_input('/^[0-9]*$/', $_GET["id_customer"]);

		$this->obj_refund		= New credit_refund;
		$this->obj_refund->type		= "customer";
		$this->obj_refund->id		= @security_script_input('/^[0-9]*$/', $_GET["id_refund"]);


		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Customer's Details", "page=customers/view.php&id=". $this->obj_customer->id ."");

		if (sql_get_singlevalue("SELECT value FROM config WHERE name='MODULE_CUSTOMER_PORTAL' LIMIT 1") == "enabled")
		{
			$this->obj_menu_nav->add_item("Portal Options", "page=customers/portal.php&id=". $this->obj_customer->id ."");
		}

		$this->obj_menu_nav->add_item("Customer's Journal", "page=customers/journal.php&id=". $this->obj_customer->id ."");
		$this->obj_menu_nav->add_item("Customer's Attributes", "page=customers/attributes.php&id_customer=". $this->obj_customer->id ."");
		$this->obj_menu_nav->add_item("Customer's Orders", "page=customers/orders.php&id_customer=". $this->obj_customer->id ."");
		$this->obj_menu_nav->add_item("Customer's Invoices", "page=customers/invoices.php&id=". $this->obj_customer->id ."");
		$this->obj_menu_nav->add_item("Customer's Credit", "page=customers/credit.php&id_customer=". $this->obj_customer->id ."", TRUE);
		$this->obj_menu_nav->add_item("Customer's Services", "page=customers/services.php&id=". $this->obj_customer->id ."");

		if ($this->obj_customer->verify_reseller() == 1)
		{
	               $this->obj_menu_nav->add_item("Reseller's Customers", "page=customers/reseller.php&id_customer=". $this->obj_customer->id ."");
		}

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

		// verify the refund (if specified)
		if ($this->obj_refund->id)
		{
			if (!$this->obj_refund->verify_id())
			{
				log_write("error", "page_output", "The selected refund item does not exist!");
			}
		}

		return 1;
	}



	function execute()
	{
		/*
			Define form structure
		*/
		$this->obj_form			= New form_input;
		$this->obj_form->formname	= "credit_refund";
		$this->obj_form->language	= $_SESSION["user"]["lang"];

		$this->obj_form->action		= "customers/credit-refund-edit-process.php";
		$this->obj_form->method		= "post";

	

		// basic details
		$structure = NULL;
		$structure["fieldname"]			= "date_trans";
		$structure["type"]			= "date";
		$structure["defaultvalue"]		= date("Y-m-d");
		$structure["options"]["req"]		= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]			= "type";
		$structure["type"]			= "text";
		$structure["defaultvalue"]		= "refund";
		$this->obj_form->add_input($structure);

		$sql_struct_obj	= New sql_query;
		$sql_struct_obj->prepare_sql_settable("staff");
		$sql_struct_obj->prepare_sql_addfield("id", "staff.id");
		$sql_struct_obj->prepare_sql_addfield("label", "staff.staff_code");
		$sql_struct_obj->prepare_sql_addfield("label1", "staff.name_staff");
		$sql_struct_obj->prepare_sql_addorderby("staff_code");
		$sql_struct_obj->prepare_sql_addwhere("id = 'CURRENTID' OR date_end = '0000-00-00'");
		
		$structure = form_helper_prepare_dropdownfromobj("id_employee", $sql_struct_obj);
		$structure["options"]["req"]		= "yes";
		$structure["options"]["autoselect"]	= "yes";
		$structure["options"]["width"]		= "600";
		$structure["options"]["search_filter"]	= "enabled";
		$structure["defaultvalue"]		= @$_SESSION["user"]["default_employeeid"];
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]			= "description";
		$structure["type"]			= "textarea";
		$structure["defaultvalue"]		= "";
		$structure["options"]["req"]		= "yes";
		$structure["options"]["width"]		= "600";
		$this->obj_form->add_input($structure);



		// amount
		$structure = NULL;
		$structure["fieldname"] 		= "amount";
		$structure["type"]			= "money";
		$structure["options"]["req"]		= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure = charts_form_prepare_acccountdropdown("account_asset", "ap_payment");
		$structure["options"]["search_filter"]	= "enabled";
		$structure["options"]["autoselect"]	= "enabled";
		$structure["options"]["width"]		= "600";
		$structure["options"]["req"]		= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure = charts_form_prepare_acccountdropdown("account_dest", "ar_summary_account");
		$structure["options"]["search_filter"]	= "enabled";
		$structure["options"]["autoselect"]	= "enabled";
		$structure["options"]["width"]		= "600";
		$structure["options"]["req"]		= "yes";
		$this->obj_form->add_input($structure);




		// hidden values
		$structure = NULL;
		$structure["fieldname"]		= "id_customer";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->obj_customer->id;
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]		= "id_refund";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->obj_refund->id;
		$this->obj_form->add_input($structure);
		


		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Save Changes";
		$this->obj_form->add_input($structure);



		// define base subforms	
		$this->obj_form->subforms["credit_refund_details"]	= array("date_trans", "type", "id_employee", "description");
		$this->obj_form->subforms["credit_refund_amount"]	= array("amount", "account_asset", "account_dest");
		$this->obj_form->subforms["hidden"]			= array("id_customer", "id_refund");
		$this->obj_form->subforms["submit"] 			= array("submit");


		// fetch the form data if editing
		if ($this->obj_refund->id)
		{
			// load existing information
			$this->obj_refund->load_data();

			$this->obj_form->structure["date_trans"]["defaultvalue"]	= $this->obj_refund->data["date_trans"];
			$this->obj_form->structure["amount"]["defaultvalue"]		= $this->obj_refund->data["amount_total"];
			$this->obj_form->structure["id_employee"]["defaultvalue"]	= $this->obj_refund->data["id_employee"];
			$this->obj_form->structure["description"]["defaultvalue"]	= $this->obj_refund->data["description"];
		}
		else
		{
			// set defaults
			$this->obj_form->structure["date_trans"]["defaultvalue"]	= date("Y-m-d");
			$this->obj_form->structure["amount"]["defaultvalue"]		= sql_get_singlevalue("SELECT SUM(amount_total) as value FROM customers_credits WHERE id_customer='". $this->obj_customer->id ."' AND id!='". $this->obj_refund->id ."'");
		}

			
		if (error_check())
		{
			// load any data returned due to errors
			$this->obj_form->load_data_error();
		}


	}


	function render_html()
	{
		// heading
		print "<h3>CREDIT REFUND</h3>";
		print "<p>If a customer has requested a refund of their credit, rather than applying it to their next invoice, this page enables a refund to be made.</p>";

		$this->obj_customer->credit_render_summarybox();

		$this->obj_form->render_form();
	}


} // end of page_output

?>
