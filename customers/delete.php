<?php
/*
	customers/delete.php
	
	access:	customers_write

	Allows an unwanted customer to be deleted.
*/


// custom includes
require("include/customers/inc_customers.php");


class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_form;
	var $obj_customer;

	var $locked;		// hold locked status of customer

	
	function page_output()
	{
		// fetch variables
		$this->id = @security_script_input('/^[0-9]*$/', $_GET["id"]);

		// create customer object
		$this->obj_customer		= New customer;
		$this->obj_customer->id		= $this->id;


		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Customer's Details", "page=customers/view.php&id=". $this->id ."");

		if (sql_get_singlevalue("SELECT value FROM config WHERE name='MODULE_CUSTOMER_PORTAL' LIMIT 1") == "enabled")
		{
			$this->obj_menu_nav->add_item("Portal Options", "page=customers/portal.php&id=". $this->id ."");
		}

		$this->obj_menu_nav->add_item("Customer's Journal", "page=customers/journal.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Customer's Attributes", "page=customers/attributes.php&id_customer=". $this->id ."");
		$this->obj_menu_nav->add_item("Customer's Orders", "page=customers/orders.php&id_customer=". $this->id ."");
		$this->obj_menu_nav->add_item("Customer's Invoices", "page=customers/invoices.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Customer's Credit", "page=customers/credit.php&id_customer=". $this->obj_customer->id ."");
		$this->obj_menu_nav->add_item("Customer's Services", "page=customers/services.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Delete Customer", "page=customers/delete.php&id=". $this->id ."", TRUE);
	}


	function check_permissions()
	{
		return user_permissions_get('customers_write');
	}
	

	function check_requirements()
	{
		// check if the customer exists
		if (!$this->obj_customer->verify_id())
		{
			return 0;
		}

		// check if the customer can be deleted
		$this->locked = $this->obj_customer->check_delete_lock();


		return 1;
	}



	function execute()
	{
		/*
			Define form structure
		*/
		$this->obj_form = New form_input;
		$this->obj_form->formname = "customer_delete";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "customers/delete-process.php";
		$this->obj_form->method = "post";
		

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "name_customer";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);


		// hidden
		$structure = NULL;
		$structure["fieldname"] 	= "id_customer";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->id;
		$this->obj_form->add_input($structure);
		
		
		// confirm delete
		$structure = NULL;
		$structure["fieldname"] 	= "delete_confirm";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "Yes, I wish to delete this customer and realise that once deleted the data can not be recovered.";
		$this->obj_form->add_input($structure);



		// define submit field
		$structure = NULL;
		$structure["fieldname"] = "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "delete";
				
		$this->obj_form->add_input($structure);


		
		// define subforms
		$this->obj_form->subforms["customer_delete"]	= array("name_customer");
		$this->obj_form->subforms["hidden"]		= array("id_customer");

		if ($this->locked)
		{
			$this->obj_form->subforms["submit"]	= array();
		}
		else
		{
			$this->obj_form->subforms["submit"]	= array("delete_confirm", "submit");
		}
		
		// fetch the form data
		$this->obj_form->sql_query = "SELECT name_customer FROM `customers` WHERE id='". $this->id ."' LIMIT 1";
		$this->obj_form->load_data();
		
	}
	


	function render_html()
	{

		// title/summary
		print "<h3>DELETE CUSTOMER</h3><br>";
		print "<p>This page allows you to delete an unwanted customers. Note that it is only possible to delete a customer if they do not belong to any invoices or time groups. If they do, you can not delete the customer, but instead you can disable the customer by setting the date_end field.</p>";


		// display the form
		$this->obj_form->render_form();
		
		if ($this->locked)
		{
			format_msgbox("locked", "<p>This customer can not be removed because their account has either subscribed services, orders, invoices or time groups belonging to it.</p><p>If you wish to close this customer's account, use the Customer Details page and set the End Date field or remove the records preventing deletion.</p>");
		}
	}


} // end page_output class


?>
