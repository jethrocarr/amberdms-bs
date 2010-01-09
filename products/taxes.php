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

	var $obj_table;


	function page_output()
	{
		$this->productid		= @security_script_input('/^[0-9]*$/', $_GET["id"]);


		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Product Details", "page=products/view.php&id=". $this->productid ."", TRUE);
		$this->obj_menu_nav->add_item("Product Taxes", "page=products/taxes.php&id=". $this->productid ."", TRUE);
		$this->obj_menu_nav->add_item("Product Journal", "page=products/journal.php&id=". $this->productid ."");

		if (user_permissions_get("products_write"))
		{
			$this->obj_menu_nav->add_item("Delete Product", "page=products/delete.php&id=". $this->productid ."");
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
		// establish a new table object
		$this->obj_table = New table;

		$this->obj_table->language	= $_SESSION["user"]["lang"];
		$this->obj_table->tablename	= "product_taxes";

		// define all the columns and structure
		$this->obj_table->add_column("standard", "name_tax", "CONCAT_WS(' -- ',account_taxes.name_tax,account_taxes.description)");
		$this->obj_table->add_column("text", "description", "products_taxes.description");

		// defaults
		$this->obj_table->columns	= array("name_tax", "description");
		$this->obj_table->columns_order	= array("name_tax");

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("products_taxes");
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "products_taxes.id");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN account_taxes ON account_taxes.id = products_taxes.taxid");
		$this->obj_table->sql_obj->prepare_sql_addwhere("productid='". $this->productid ."'");
		

		// run SQL query
		$this->obj_table->generate_sql();
		$this->obj_table->load_data_sql();

	}

	function render_html()
	{
		// Title + Summary
		print "<h3>PRODUCT TAXES</h3><br>";
		print "<p>This page is where you can set the different taxes that apply to this product.</p>";

		if (!$this->obj_table->data_num_rows)
		{
			format_msgbox("info", "<p>This product currently has no taxes assigned to it.</p>");
		}
		else
		{
			if (user_permissions_get("products_write"))
			{
				// edit link
				$structure = NULL;
				$structure["id"]["value"]	= $this->productid;
				$structure["itemid"]["column"]	= "id";
			
				$this->obj_table->add_link("edit", "products/taxes-edit.php", $structure);
			
				// delete link
				$structure = NULL;
				$structure["id"]["value"]	= $this->productid;
				$structure["itemid"]["column"]	= "id";
				$structure["full_link"]		= "yes";

				$this->obj_table->add_link("delete", "products/taxes-delete-process.php", $structure);
			}

		
			// display the table
			$this->obj_table->render_table_html();
	
		}
		
		if (user_permissions_get("products_write"))
		{
			print "<p><a class=\"button\" href=\"index.php?page=products/taxes-edit.php&id=". $this->productid ."\">Add Tax</a></p>";
		}

		return 1;


	}

}


?>
