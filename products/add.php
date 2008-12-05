<?php
/*
	products/add.php
	
	access: products_write

	Form to add a new product to the database.
*/

// include form functions
require("include/products/inc_product_forms.php");


class page_output
{
	var $obj_productform;

	function page_output()
	{
		$this->obj_productform			= New products_form_details;
		$this->obj_productform->productid	= 0;
		$this->obj_productform->mode		= "add";
	}


	function check_permissions()
	{
		return user_permissions_get("products_write");
	}

	function check_requirements()
	{
		// nothing todo
		return 1;
	}


	function execute()
	{
		return $this->obj_productform->execute();
	}

	function render_html()
	{
		// Title + Summary
		print "<h3>ADD PRODUCT</h3><br>";
		print "<p>This page allows you to add a new product.</p>";


		// render form
		return $this->obj_productform->render_html();
	}

}

?>
