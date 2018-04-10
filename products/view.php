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
	var $obj_productform;

	function __construct()
	{
		$this->obj_productform			= New products_form_details;
		$this->obj_productform->productid	= @security_script_input('/^[0-9]*$/', $_GET["id"]);
		$this->obj_productform->mode		= "edit";


		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Product Details", "page=products/view.php&id=". $this->obj_productform->productid ."", TRUE);
		$this->obj_menu_nav->add_item("Product Journal", "page=products/journal.php&id=". $this->obj_productform->productid ."");

		if (user_permissions_get("products_write"))
		{
			$this->obj_menu_nav->add_item("Delete Product", "page=products/delete.php&id=". $this->obj_productform->productid ."");
		}
	}


	function check_permissions()
	{
		return user_permissions_get("products_view");
	}

	function check_requirements()
	{
		// verify that the product exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM products WHERE id='". $this->obj_productform->productid ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested product (". $this->obj_productform->productid .") does not exist - possibly the product has been deleted.");
			return 0;
		}

		unset($sql_obj);

		return 1;
	}


	function execute()
	{
		return $this->obj_productform->execute();
	}

	function render_html()
	{
		// Title + Summary
		print "<h3>PRODUCT DETAILS</h3><br>";
		print "<p>This page allows you to view and adjust the product's records.</p>";

		// render form
		return $this->obj_productform->render_html();
	}

}


?>
