<?php
/*
	products/groups-delete.php

	access:	products_write

	Allows an unwanted and unused product group to be deleted.
*/


require("include/products/inc_products_groups.php");

class page_output
{
	var $obj_product_group;
	var $obj_menu_nav;
	var $obj_form;


	function __construct()
	{
		// init
		$this->obj_product_group	= New product_groups;

		// fetch variables
		$this->obj_product_group->id	= @security_script_input('/^[0-9]*$/', $_GET["id"]);


		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Product Group Details", "page=products/groups-view.php&id=". $this->obj_product_group->id ."");
		$this->obj_menu_nav->add_item("Delete Product Group", "page=products/groups-delete.php&id=". $this->obj_product_group->id ."", TRUE);
	}



	function check_permissions()
	{
		return user_permissions_get("products_write");
	}



	function check_requirements()
	{
		// verify that product group exists
		if (!$this->obj_product_group->verify_id())
		{
			log_write("error", "page_output", "The requested product group (". $this->id .") does not exist - possibly the product group has already been deleted?");
			return 0;
		}

		unset($sql_obj);


		// check if the product group can be deleted
		$this->locked = $this->obj_product_group->check_delete_lock();

		return 1;
	}


	function execute()
	{
		/*
			Define form structure
		*/
		$this->obj_form			= New form_input;
		$this->obj_form->formname	= "product_group_delete";
		$this->obj_form->language	= $_SESSION["user"]["lang"];

		$this->obj_form->action		= "products/groups-delete-process.php";
		$this->obj_form->method		= "post";
		

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "group_name";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "group_description";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);


		// hidden
		$structure = NULL;
		$structure["fieldname"] 	= "id_product_group";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->obj_product_group->id;
		$this->obj_form->add_input($structure);
		
		
		// confirm delete
		$structure = NULL;
		$structure["fieldname"] 	= "delete_confirm";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "Yes, I wish to delete this product group and realise that once deleted the data can not be recovered.";
		$this->obj_form->add_input($structure);



		// define submit field
		$structure = NULL;
		$structure["fieldname"]		= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "delete";
				
		$this->obj_form->add_input($structure);


		
		// define subforms
		$this->obj_form->subforms["product_group_delete"]	= array("group_name", "group_description");
		$this->obj_form->subforms["hidden"]			= array("id_product_group");

		if ($this->locked)
		{
			$this->obj_form->subforms["submit"]	= array();
		}
		else
		{
			$this->obj_form->subforms["submit"]	= array("delete_confirm", "submit");
		}
		
		// fetch the form data
		$this->obj_form->sql_query = "SELECT group_name, group_description FROM `product_groups` WHERE id='". $this->obj_product_group->id ."' LIMIT 1";
		$this->obj_form->load_data();
		
	}
	


	function render_html()
	{

		// title/summary
		print "<h3>DELETE PRODUCT GROUP</h3><br>";
		print "<p>This page allows you to delete any unwanted, empty, product groups.</p>";

		// display the form
		$this->obj_form->render_form();
		
		if ($this->locked)
		{
			format_msgbox("locked", "<p>This product group can not be deleted as products currently belong to it.</p>");
		}
	}


} // end page_output class


?>
