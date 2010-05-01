<?php
/*
	products/groups-add.php

	access: products_write

	Form to add a new product group to the system.
*/

class page_output
{
	var $obj_form;	// page form


	function check_permissions()
	{
		return user_permissions_get('products_write');
	}

	function check_requirements()
	{
		// nothing todo
		return 1;
	}


	function execute()
	{
		// define basic form details
		$this->obj_form = New form_input;
		$this->obj_form->formname = "product_group_add";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "products/groups-edit-process.php";
		$this->obj_form->method = "post";
		

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "group_name";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"]		= "group_description";
		$structure["type"]		= "input";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]		= "id_parent";
		$structure["type"]		= "input";
		$structure = form_helper_prepare_dropdownfromdb("id_parent", "SELECT id, group_name as label, id_parent FROM product_groups");
		//echo "<pre>".print_r($structure, true)."<pre>";
		$this->obj_form->add_input($structure); 
		
		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "submit";
		$this->obj_form->add_input($structure);
		

		// define subforms
		$this->obj_form->subforms["product_group_add"]		= array("group_name", "group_description", "id_parent");
		$this->obj_form->subforms["submit"]			= array("submit");
		
		// load any data returned due to errors
		$this->obj_form->load_data_error();
	}



	function render_html()
	{
		// title and summary
		print "<h3>ADD PRODUCT GROUP</h3><br>";
		print "<p>This page allows you to add a new product group which allows products to be grouped together for better display on invoices.</p>";

		// display the form
		$this->obj_form->render_form();
	}


} // end page_output class

?>
