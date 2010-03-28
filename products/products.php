<?php
/*
	products.php
	
	access: "products_view" group members

	Displays a list of all the products on the system.
*/

class page_output
{
	var $obj_table;


	function check_permissions()
	{
		if (user_permissions_get("products_view"))
		{
			return 1;
		}
	}

	function check_requirements()
	{
		// nothing todo
		return 1;
	}


	function execute()
	{
		// establish a new table object
		$this->obj_table = New table;

		$this->obj_table->language	= $_SESSION["user"]["lang"];
		$this->obj_table->tablename	= "product_list";


		// define all the columns and structure
		$this->obj_table->add_column("standard", "code_product", "");
		$this->obj_table->add_column("standard", "name_product", "");
		$this->obj_table->add_column("standard", "account_sales", "CONCAT_WS(' -- ',account_charts.code_chart,account_charts.description)");
		$this->obj_table->add_column("price", "price_cost", "");
		$this->obj_table->add_column("price", "price_sale", "");
		$this->obj_table->add_column("date", "date_start", "");
		$this->obj_table->add_column("date", "date_end", "");
		$this->obj_table->add_column("date", "date_current", "");
		$this->obj_table->add_column("standard", "quantity_instock", "");
		$this->obj_table->add_column("standard", "quantity_vendor", "");

		// defaults
		$this->obj_table->columns		= array("code_product", "name_product", "account_sales", "price_cost", "price_sale");
		$this->obj_table->columns_order		= array("code_product");
		$this->obj_table->columns_order_options	= array("code_product", "name_product", "account_sales", "date_current", "quantity_instock", "quantity_vendor");

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("products");
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "products.id");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN account_charts ON account_charts.id = products.account_sales");

		// acceptable filter options
		$structure = NULL;
		$structure["fieldname"] 	= "searchbox";
		$structure["type"]		= "input";
		$structure["sql"]		= "(name_product LIKE '%value%' OR code_product LIKE '%value%')";
		$this->obj_table->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "hide_ex_products";
		$structure["type"]		= "checkbox";
		$structure["sql"]		= "date_end='0000-00-00'";
		$structure["defaultvalue"]	= "on";
		$structure["options"]["label"]	= "Hide any products which are no longer sold";
		$this->obj_table->add_filter($structure);
		
		// load options
		$this->obj_table->load_options_form();

		// fetch all the product information
		$this->obj_table->generate_sql();
		$this->obj_table->load_data_sql();

	}


	function render_html()
	{
		// heading
		print "<h3>PRODUCTS LIST</h3><br><br>";

		// render options form
		$this->obj_table->render_options_form();

		// render table
		if (!count($this->obj_table->columns))
		{
			format_msgbox("important", "<p>Please select some valid options to display.</p>");
		}
		elseif (!$this->obj_table->data_num_rows)
		{
			format_msgbox("info", "<p>You currently have no products in your database.</p>");
		}
		else
		{
			// links
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("tbl_lnk_product_details", "products/view.php", $structure);
			

			// display the table
			$this->obj_table->render_table_html();

			// display CSV/PDF download link
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=csv&page=products/products.php\">Export as CSV</a></p>";
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=pdf&page=products/products.php\">Export as PDF</a></p>";
		}
	}


	function render_csv()
	{
		$this->obj_table->render_table_csv();		
	}


	function render_pdf()
	{
		$this->obj_table->render_table_pdf();
		
	}

	
}

?>
