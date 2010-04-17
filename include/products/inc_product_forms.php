<?php
/*
	include/products/inc_products_forms.php

	Provides various forms used for managing product entries.
*/

require("include/accounts/inc_charts.php");



/*
	class: products_form_details

	Generates forms for processing product details
*/
class products_form_details
{
	var $productid;			// ID of the product entry
	var $mode;			// Mode: "add" or "edit"

	var $obj_form;


	function execute()
	{
		log_debug("products_form_details", "Executing execute()");


		/*
			Define form structure
		*/
		$this->obj_form = New form_input;
		$this->obj_form->formname = "product_". $this->mode;
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "products/edit-process.php";
		$this->obj_form->method = "post";

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "code_product";
		$structure["type"]		= "input";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "name_product";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 		= "units";
		$structure["type"]			= "input";
		$structure["options"]["width"]		= 50;
		$structure["options"]["max_length"]	= 10;
		$structure["options"]["req"]		= "yes";
		$this->obj_form->add_input($structure);


		$structure = charts_form_prepare_acccountdropdown("account_sales", "ar_income");
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = charts_form_prepare_acccountdropdown("account_purchase", "ap_expense");
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);


		$structure = NULL;
		$structure["fieldname"] 	= "details";
		$structure["type"]		= "textarea";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]		= "date_start";
		$structure["type"]		= "date";
		$structure["options"]["req"]	= "yes";
		$structure["defaultvalue"]	= date("Y-m-d");
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]		= "date_end";
		$structure["type"]		= "date";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]		= "date_current";
		$structure["type"]		= "date";
		$this->obj_form->add_input($structure);

		$this->obj_form->subforms["product_view"]	= array("code_product", "name_product", "units", "account_sales", "account_purchase", "date_start", "date_end", "date_current", "details");

		
		// pricing			
		$structure = NULL;
		$structure["fieldname"]		= "price_cost";
		$structure["type"]		= "money";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]		= "price_sale";
		$structure["type"]		= "money";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 		= "discount";
		$structure["type"]			= "input";
		$structure["options"]["width"]		= 50;
		$structure["options"]["label"]		= " %";
		$structure["options"]["max_length"]	= "6";
		$this->obj_form->add_input($structure);

		$this->obj_form->subforms["product_pricing"]	= array("price_cost", "price_sale", "discount");



		/*
			List all the taxes, so that the user can select the tax(es) that apply to the product
		*/

		$sql_tax_obj		= New sql_query;
		$sql_tax_obj->string	= "SELECT id, name_tax, description FROM account_taxes ORDER BY name_tax";
		$sql_tax_obj->execute();

		if ($sql_tax_obj->num_rows())
		{
			// user note
			$structure = NULL;
			$structure["fieldname"] 		= "tax_message";
			$structure["type"]			= "message";
			$structure["defaultvalue"]		= "<p>Check all taxes that apply to this product below.</p>";
			$this->obj_form->add_input($structure);
		
			$this->obj_form->subforms["product_tax"][] = "tax_message";


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

				// see if this tax is currently enabled for this product
				if ($this->productid)
				{
					$sql_obj		= New sql_query;
					$sql_obj->string	= "SELECT id FROM products_taxes WHERE productid='". $this->productid ."' AND taxid='". $data_tax["id"] ."' LIMIT 1";
					$sql_obj->execute();

					if ($sql_obj->num_rows())
					{
						$structure["defaultvalue"] = "on";
					}
				}

				// add to form
				$this->obj_form->add_input($structure);
				$this->obj_form->subforms["product_tax"][] = "tax_". $data_tax["id"];
			}
		}


		// quantity
		$structure = NULL;
		$structure["fieldname"]		= "quantity_instock";
		$structure["type"]		= "input";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]		= "quantity_vendor";
		$structure["type"]		= "input";
		$this->obj_form->add_input($structure);

		$this->obj_form->subforms["product_quantity"]	= array("quantity_instock", "quantity_vendor");


		// supplier details
		$structure = form_helper_prepare_dropdownfromdb("vendorid", "SELECT id, name_vendor as label FROM vendors");
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "code_product_vendor";
		$structure["type"]		= "input";
		$this->obj_form->add_input($structure);

		$this->obj_form->subforms["product_supplier"]	= array("vendorid", "code_product_vendor");


		// define remaining subforms	
		if (user_permissions_get("products_write"))
		{
			$this->obj_form->subforms["submit"]		= array("submit");
		}
		else
		{
			$this->obj_form->subforms["submit"]		= array();
		}


		/*
			Mode dependent options
		*/
		
		if ($this->mode == "add")
		{
			// submit button
			$structure = NULL;
			$structure["fieldname"] 	= "submit";
			$structure["type"]		= "submit";
			$structure["defaultvalue"]	= "Create Product";
			$this->obj_form->add_input($structure);
		}
		else
		{
			// submit button
			$structure = NULL;
			$structure["fieldname"] 	= "submit";
			$structure["type"]		= "submit";
			$structure["defaultvalue"]	= "Save Changes";
			$this->obj_form->add_input($structure);


			// hidden data
			$structure = NULL;
			$structure["fieldname"] 	= "id_product";
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= $this->productid;
			$this->obj_form->add_input($structure);
				

			$this->obj_form->subforms["hidden"]	= array("id_product");
		}


		/*
			Load Data
		*/
		if ($this->mode == "add")
		{
			$this->obj_form->load_data_error();
		}
		else
		{
			$this->obj_form->sql_query = "SELECT * FROM `products` WHERE id='". $this->productid ."' LIMIT 1";		
			$this->obj_form->load_data();
		}
	}


	function render_html()
	{
		log_debug("products_form_details", "Executing render_html()");
		
		// display the form
		$this->obj_form->render_form();

		if (!user_permissions_get("products_write"))
		{
			format_msgbox("locked", "<p>Sorry, you do not have permissions to make modifications to this product.</p>");
		}
	}
	
} // end of products_form_details





/*
	class: products_form_delete

	Generates forms for deleting an unwanted product
*/
class products_form_delete
{
	var $productid;			// ID of the product entry
	
	var $obj_form;
	var $locked;


	function execute()
	{
		/*
			Check if product can be deleted
		*/
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_items WHERE (type='product' OR type='time') AND customid='". $this->productid ."'";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$this->locked = 1;
		}



		/*
			Define form structure
		*/
		$this->obj_form = New form_input;
		$this->obj_form->formname = "product_delete";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "products/delete-process.php";
		$this->obj_form->method = "post";

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "code_product";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "name_product";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);


		// hidden data
		$structure = NULL;
		$structure["fieldname"] 	= "id_product";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->productid;
		$this->obj_form->add_input($structure);


		// confirm delete
		$structure = NULL;
		$structure["fieldname"] 	= "delete_confirm";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "Yes, I wish to delete this product and realise that once deleted the data can not be recovered.";
		$this->obj_form->add_input($structure);


		// submit button
		//
		// We check if the product has been added to any invoices, and then either define
		// a delete button or a message
		
		$structure = NULL;
		$structure["fieldname"] = "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "delete";
		$this->obj_form->add_input($structure);

			


		// define subforms
		$this->obj_form->subforms["product_delete"]	= array("code_product", "name_product");
		$this->obj_form->subforms["hidden"]		= array("id_product");

		if ($this->locked)
		{
			$this->obj_form->subforms["submit"]	= array();
		}
		else
		{
			$this->obj_form->subforms["submit"]	= array("delete_confirm", "submit");
		}


		/*
			Load Data
		*/
		$this->obj_form->sql_query = "SELECT * FROM `products` WHERE id='". $this->productid ."' LIMIT 1";
		$this->obj_form->load_data();

		return 1;
	}


	function render_html()
	{
		// Display Form Information
		$this->obj_form->render_form();

		if ($this->locked)
		{
			format_msgbox("locked", "<p>This product can no longer be deleted since it has been used in invoices.</p>");
		}

		return 1;
	}
	
} // end of products_form_delete


?>
