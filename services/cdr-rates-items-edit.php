<?php
/*
	services/cdr-rates-items-edit.php

	access: services_write

	Form to add or edit CDR table rate items
*/


require("include/services/inc_services_cdr.php");


class page_output
{
	var $obj_rate_table;
	var $obj_menu_nav;
	var $obj_form;



	function page_output()
	{
		$this->obj_rate_table		= New cdr_rate_table_rates;


		// fetch variables
		$this->obj_rate_table->id	= @security_script_input('/^[0-9]*$/', $_GET["id"]);
		$this->obj_rate_table->id_rate	= @security_script_input('/^[0-9]*$/', $_GET["id_rate"]);


		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Rate Table Details", "page=services/cdr-rates-view.php&id=". $this->obj_rate_table->id ."");
		$this->obj_menu_nav->add_item("Rate Table Items", "page=services/cdr-rates-items.php&id=". $this->obj_rate_table->id ."", TRUE);
		$this->obj_menu_nav->add_item("Delete Rate Table", "page=services/cdr-rates-delete.php&id=". $this->obj_rate_table->id ."");
	}



	function check_permissions()
	{
		return user_permissions_get("services_write");
	}


	function check_requirements()
	{
		if (!$this->obj_rate_table->verify_id())
		{
			log_write("error", "page_output", "The supplied rate table ID ". $this->obj_rate_table->id ." does not exist");
			return 0;
		}

		return 1;
	}


	function execute()
	{
		// define basic form details
		$this->obj_form = New form_input;
		$this->obj_form->formname = "cdr_rate_table_item_edit";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "services/cdr-rates-items-edit-process.php";
		$this->obj_form->method = "post";
		

		// rate table details
		$structure = NULL;
		$structure["fieldname"] 	= "rate_table_name";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"]		= "rate_table_description";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);


		// item options
		$structure = NULL;
		$structure["fieldname"]		= "rate_prefix";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]		= "rate_description";
		$structure["type"]		= "input";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]		= "rate_price_sale";
		$structure["type"]		= "money";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]		= "rate_price_cost";
		$structure["type"]		= "money";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);



		// hidden
		$structure = NULL;
		$structure["fieldname"] 	= "id";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->obj_rate_table->id;
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "id_rate";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->obj_rate_table->id_rate;
		$this->obj_form->add_input($structure);

		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "submit";
		$this->obj_form->add_input($structure);
		

		// define subforms
		$this->obj_form->subforms["rate_table_details"]	= array("rate_table_name", "rate_table_description");
		$this->obj_form->subforms["rate_table_items"]	= array("rate_prefix", "rate_description", "rate_price_sale", "rate_price_cost");
		$this->obj_form->subforms["hidden"]		= array("id", "id_rate");
		$this->obj_form->subforms["submit"]		= array("submit");
		

		// load any data returned due to errors
		if (error_check())
		{
			$this->obj_form->load_data_error();
		}
		else
		{
			$this->obj_rate_table->load_data();
			$this->obj_rate_table->load_data_rate();

			$this->obj_form->structure["rate_table_name"]["defaultvalue"]		= $this->obj_rate_table->data["rate_table_name"];
			$this->obj_form->structure["rate_table_description"]["defaultvalue"]	= $this->obj_rate_table->data["rate_table_description"];

			$this->obj_form->structure["rate_prefix"]["defaultvalue"]		= $this->obj_rate_table->data_rate["rate_prefix"];
			$this->obj_form->structure["rate_description"]["defaultvalue"]		= $this->obj_rate_table->data_rate["rate_description"];
			$this->obj_form->structure["rate_price_sale"]["defaultvalue"]		= $this->obj_rate_table->data_rate["rate_price_sale"];
			$this->obj_form->structure["rate_price_cost"]["defaultvalue"]		= $this->obj_rate_table->data_rate["rate_price_cost"];
		}
	}



	function render_html()
	{
		// title and summary
		print "<h3>RATE TABLE ITEMS</h3><br>";
		print "<p>View/Adjust the basic details of the selected rate item below.</p>";

		// display the form
		$this->obj_form->render_form();

	}


}


?>
