<?php
/*
	products/view.php

	access: products_view (read-only)
		products_write (write access)

	Displays the selected product and if the user has correct permissions
	allows the product to be updated.
*/


// include form functions
require("include/products/inc_product_forms.php");


class page_output
{
	var $productid;

	var $obj_form;


	function page_output()
	{
		$this->productid		= security_script_input('/^[0-9]*$/', $_GET["id"]);
		$this->itemid			= security_script_input('/^[0-9]*$/', $_GET["itemid"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Product Details", "page=products/view.php&id=". $this->productid ."");
		$this->obj_menu_nav->add_item("Product Taxes", "page=products/taxes.php&id=". $this->productid ."", TRUE);
		$this->obj_menu_nav->add_item("Product Journal", "page=products/journal.php&id=". $this->productid ."");
		$this->obj_menu_nav->add_item("Delete Product", "page=products/delete.php&id=". $this->productid ."");
	}


	function check_permissions()
	{
		return user_permissions_get("products_write");
	}

	function check_requirements()
	{
		// verify that the product exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM products WHERE id='". $this->productid ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested product (". $this->productid .") does not exist - possibly the product has been deleted.");
			return 0;
		}

		unset($sql_obj);

		return 1;
	}


	function execute()
	{
		/*
			Start Form
		*/
		$this->obj_form = New form_input;
		$this->obj_form->formname		= "products_taxes_edit";
		$this->obj_form->language		= $_SESSION["user"]["lang"];

		$this->obj_form->action		= "products/taxes-edit-process.php";
		$this->obj_form->method		= "POST";
		

		/*
			Define form structure
		*/

		// tax selection
		$structure = form_helper_prepare_dropdownfromdb("taxid", "SELECT id, name_tax as label FROM account_taxes");
		$structure["options"]["autoselect"] = "yes";
		$this->obj_form->add_input($structure);
		

		// description
		$structure = NULL;
		$structure["fieldname"] 	= "description";
		$structure["type"]		= "input";
		$this->obj_form->add_input($structure);


		// auto or manual
		$structure = NULL;
		$structure["fieldname"] 	= "manual_option";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "Do not auto-calculate this tax, instead use the specified manual value.";
		$this->obj_form->add_input($structure);

		// manual value input field
		$structure = NULL;
		$structure["fieldname"] 	= "manual_amount";
		$structure["type"]		= "input";
		$this->obj_form->add_input($structure);


		$this->obj_form->add_action("manual_option", "default", "manual_amount", "hide");
		$this->obj_form->add_action("manual_option", "1", "manual_amount", "show");


		// IDs
		$structure = NULL;
		$structure["fieldname"]		= "id_product";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->productid;
		$this->obj_form->add_input($structure);	
		
		$structure = NULL;
		$structure["fieldname"]		= "id_item";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->itemid;
		$this->obj_form->add_input($structure);	
		

		// submit
		$structure = NULL;
		$structure["fieldname"]		= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Save Changes";
		$this->obj_form->add_input($structure);


		// define form layout
		$this->obj_form->subforms["tax_details"]		= array("taxid", "description", "manual_option", "manual_amount");
		$this->obj_form->subforms["hidden"]			= array("id_product", "id_item");
		$this->obj_form->subforms["submit"]			= array("submit");


		// SQL query
		$this->obj_form->sql_query = "SELECT taxid, description, manual_amount, manual_option FROM products_taxes WHERE id='". $this->itemid ."'";

		// load data
		$this->obj_form->load_data();


		return 1;
	}

	function render_html()
	{
		// Title + Summary
		if ($this->itemid)
		{
			print "<h3>ADJUST TAX</h3><br>";
			print "<p>Use this form to adjust the tax item on the selected product.</p>";
		}
		else
		{
			print "<h3>ADD TAX</h3><br>";
			print "<p>Use this form to add a new tax to the selected product.</p>";
		}



		/*
			Display Form
		*/
		
		$this->obj_form->render_form();


		return 1;
	}

}


?>
