<?php
/*
	customers/orders-view.php
	
	access: customers_view		(read-only)
		customers_orders	(read-write)

	Form to add or adjust customer orders.
*/


require("include/customers/inc_customers.php");


class page_output
{
	var $obj_customer;
	
	var $obj_menu_nav;
	var $obj_form;
	

	

	function __construct()
	{
		// javascript: AJAX call to load product information
		$this->requires["javascript"][]			= "include/accounts/javascript/invoice-items-edit_ar.js";

		// customer object
		$this->obj_customer				= New customer_orders;


		// fetch variables
		$this->obj_customer->id				= @security_script_input('/^[0-9]*$/', $_GET["id_customer"]);
		$this->obj_customer->id_order			= @security_script_input('/^[0-9]*$/', $_GET["id_order"]);


		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;
	
		if ($this->obj_customer->id_order)
		{
			// load order details
			$this->obj_customer->load_data();
			$this->obj_customer->load_data_order();


			// Nav Menu
			$this->obj_menu_nav->add_item("Return to Customer Orders Page", "page=customers/orders.php&id_customer=". $this->obj_customer->id ."");
			$this->obj_menu_nav->add_item("Order Details", "page=customers/orders-view.php&id_customer=". $this->obj_customer->id ."&id_order=". $this->obj_customer->id_order ."", TRUE);
		}
		else
		{
			// Nav Menu
			$this->obj_menu_nav->add_item("Return to Customer Orders Page", "page=customers/orders.php&id_customer=". $this->obj_customer->id ."");
			$this->obj_menu_nav->add_item("New Order", "page=customers/orders-view.php&id_customer=". $this->obj_customer->id ."&id_order=". $this->obj_customer->id_order ."", TRUE);


			// define some defaults
			if (empty($this->obj_customer->data_orders["type"]))
			{
				$this->obj_customer->data_orders["type"] = "product";
			}

		}
	}



	function check_permissions()
	{
		return user_permissions_get("customers_view");
	}



	function check_requirements()
	{
		// verify that customer exists
		if (!$this->obj_customer->verify_id())
		{
			log_write("error", "page_output", "The requested customer (". $this->obj_customer->id .") does not exist - possibly the customer has been deleted.");
			return 0;
		}


		// verify that the order entry exists
		if (!empty($this->obj_customer->id_order))
		{
			if (!$this->obj_customer->verify_id_order())
			{
				log_write("error", "page_output", "The requested order (". $this->obj_customer->id_order .") was not found and/or does not match the selected customer");
				return 0;
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
		$this->obj_form->formname	= "orders_view";
		$this->obj_form->language	= $_SESSION["user"]["lang"];

		$this->obj_form->action		= "customers/orders-edit-process.php";
		$this->obj_form->method		= "post";

	

		/*
			Define Orders (products-style)
		*/


		// basic details
		$structure = NULL;
		$structure["fieldname"]		= "date_ordered";
		$structure["type"]		= "date";
		$structure["defaultvalue"]	= date("Y-m-d");
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]		= "type";
		$structure["type"]		= "text";
		$structure["defaultvalue"]	= $this->obj_customer->data_orders["type"];
		$this->obj_form->add_input($structure);

		$this->obj_form->subforms["order_basic"]	= array("date_ordered", "type");




		/*
			Item Specifics
		*/

		switch ($this->obj_customer->data_orders["type"])
		{
			case "product":
			
				// price
				$structure = null;
				$structure["fieldname"] 	= "price";
				$structure["type"]		= "money";
				$this->obj_form->add_input($structure);

				// quantity
				$structure = null;
				$structure["fieldname"] 	= "quantity";
				$structure["type"]		= "input";
				$structure["options"]["width"]	= 50;
				$this->obj_form->add_input($structure);


				// units
				$structure = null;
				$structure["fieldname"] 		= "units";
				$structure["type"]			= "input";
				$structure["options"]["width"]		= 50;
				$structure["options"]["max_length"]	= 10;
				$this->obj_form->add_input($structure);



				// product id
				$sql_struct_obj	= new sql_query;
				$sql_struct_obj->prepare_sql_settable("products");
				$sql_struct_obj->prepare_sql_addfield("id", "products.id");
				$sql_struct_obj->prepare_sql_addfield("label", "products.code_product");
				$sql_struct_obj->prepare_sql_addfield("label1", "products.name_product");
				$sql_struct_obj->prepare_sql_addorderby("code_product");
				$sql_struct_obj->prepare_sql_addwhere("id = 'currentid' or date_end = '0000-00-00'");
				
				$structure = form_helper_prepare_dropdownfromobj("productid", $sql_struct_obj);
				$structure["options"]["search_filter"]	= "enabled";
				$structure["options"]["width"]		= "600";
				$this->obj_form->add_input($structure);


				// description
				$structure = null;
				$structure["fieldname"] 		= "description";
				$structure["type"]			= "textarea";
				$structure["options"]["height"]		= "50";
				$structure["options"]["width"]		= 500;
				$this->obj_form->add_input($structure);

				// discount
				$structure = null;
				$structure["fieldname"] 		= "discount";
				$structure["type"]			= "input";
				$structure["options"]["width"]		= 50;
				$structure["options"]["label"]		= " %";
				$structure["options"]["max_length"]	= "2";
				$this->obj_form->add_input($structure);


				// subform
				$this->obj_form->subforms["order_product"]	= array("productid", "price", "quantity", "units", "description", "discount");

			break;



			case "service":

				// price
				$structure = null;
				$structure["fieldname"] 	= "price";
				$structure["type"]		= "money";
				$this->obj_form->add_input($structure);

				// discount
				$structure = null;
				$structure["fieldname"] 		= "discount";
				$structure["type"]			= "input";
				$structure["options"]["width"]		= 50;
				$structure["options"]["label"]		= " %";
				$structure["options"]["max_length"]	= "2";
				$this->obj_form->add_input($structure);


				// service id
				$sql_struct_obj	= new sql_query;
				$sql_struct_obj->prepare_sql_settable("services");
				$sql_struct_obj->prepare_sql_addfield("id", "services.id");
				$sql_struct_obj->prepare_sql_addfield("label", "services.name_service");
				$sql_struct_obj->prepare_sql_addorderby("name_service");
				
				$structure = form_helper_prepare_dropdownfromobj("serviceid", $sql_struct_obj);
				$structure["options"]["search_filter"]	= "enabled";
				$structure["options"]["width"]		= "600";
				$this->obj_form->add_input($structure);


				// description
				$structure = null;
				$structure["fieldname"] 		= "description";
				$structure["type"]			= "textarea";
				$structure["options"]["height"]		= "50";
				$structure["options"]["width"]		= 500;
				$this->obj_form->add_input($structure);

				// subform
				$this->obj_form->subforms["order_serice"]	= array("serviceid", "price", "discount", "description");


			break;

		} // end of type



		// hidden values
		$structure = NULL;
		$structure["fieldname"]		= "id_customer";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->obj_customer->id;
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]		= "id_order";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->obj_customer->id_order;
		$this->obj_form->add_input($structure);
		


		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Save Changes";
		$this->obj_form->add_input($structure);



		// define base subforms	
		$this->obj_form->subforms["hidden"]		= array("id_customer", "id_order");


		if (user_permissions_get("customers_orders"))
		{
			$this->obj_form->subforms["submit"] = array("submit");
		}
		else
		{
			$this->obj_form->subforms["submit"] = array();
		}


		// fetch the form data if editing
		if ($this->obj_customer->id_order)
		{
			// data already loaded with $this->obj_customers->load_data_order(), now
			// we need to fetch each order item detail.

			$this->obj_customer->data_orders["productid"] = $this->obj_customer->data_orders["customid"];
			$this->obj_customer->data_orders["serviceid"] = $this->obj_customer->data_orders["customid"];

			$this->obj_form->load_data_object($this->obj_customer->data_orders);

		}
		else
		{
			// set defaults
			$this->obj_form->structure["quantity"]["defaultvalue"] = 1;
		}

			
		if (error_check())
		{
			// load any data returned due to errors
			$this->obj_form->load_data_error();
		}

	}


	function render_html()
	{
		// title/summary
		if ($this->obj_customer->id_order)
		{
			print "<h3>ADJUST ORDER</h3><br>";
			print "<p>This page allows you to adjust an existing order item.</p>";
		
//			// orders summary
//			$this->obj_customer->orders_render_summarybox();
		}
		else
		{
			print "<h3>ADD ORDER ITEM</h3><br>";
			print "<p>This page allows you to add an order item to the customer.</p>";
		}


		// display the form
		$this->obj_form->render_form();

		
		if (!user_permissions_get("customers_orders"))
		{
			format_msgbox("locked", "<p>Sorry, you do not have permissions to make changes to customer orders.</p>");
		}
	}

} // end page_output



?>
