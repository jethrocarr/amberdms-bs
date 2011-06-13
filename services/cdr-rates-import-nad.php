<?php
/*
	services/cdr-rates-import-nad.php

	access:	services_write

	Takes the New Zealand NAD formatted CSV files and reads the data into a form that can be processed
	and adjusted before finally being imported.

	NOTE: NZ NAD IS A CUSTOM FORMAT ADDED FOR NEW ZEALAND CUSTOMERS, ALTHOUGH IT MAY ALSO MATCH THE FORMATTING
	OF OTHER COUNTRIES/COMPANIES/ORGANISATIONS.

*/

require("include/services/inc_services.php");
require("include/services/inc_services_cdr.php");


class page_output
{
	var $obj_form;
	var $num_col;
	var $example_array;
	

	function page_output()
	{
		$this->obj_rate_table	= New cdr_rate_table;


		// fetch variables
		$this->obj_rate_table->id = @security_script_input('/^[0-9]*$/', $_GET["id"]);


		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Rate Table Details", "page=services/cdr-rates-view.php&id=". $this->obj_rate_table->id ."");
		$this->obj_menu_nav->add_item("Rate Table Items", "page=services/cdr-rates-items.php&id=". $this->obj_rate_table->id ."");
		$this->obj_menu_nav->add_item("Rate Table Import", "page=services/cdr-rates-import.php&id=". $this->obj_rate_table->id ."", TRUE);
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
		/*
			Define fields and column examples
		*/
		
		$this->obj_form			= New form_input;
		$this->obj_form->formname	= "cdr_rate_table_import_nad";

		$this->obj_form->method		= "post";
		$this->obj_form->action		= "services/cdr-rates-import-nad-process.php";
		


		// basic settings
		$structure				= NULL;
		$structure["fieldname"]			= "nad_country_prefix";
		$structure["type"]			= "input";
		$structure["defaultvalue"]		= "64";
		$this->obj_form->add_input($structure);

		$structure				= NULL;
		$structure["fieldname"]			= "nad_default_destination";
		$structure["type"]			= "input";
		$structure["defaultvalue"]		= "Unknown NZ Region";
		$this->obj_form->add_input($structure);


		// call pricing structure
		$structure				= NULL;
		$structure["fieldname"]			= "nad_price_info_national";
		$structure["type"]			= "text";
		$structure["defaultvalue"]		= "National Pricing";
		$this->obj_form->add_input($structure);

		$structure				= NULL;
		$structure["fieldname"]			= "nad_price_cost_national";
		$structure["type"]			= "money";
		$structure["options"]["prelabel"]	= "Cost Price: ";
		$this->obj_form->add_input($structure);

		$structure				= NULL;
		$structure["fieldname"]			= "nad_price_sale_national";
		$structure["type"]			= "money";
		$structure["options"]["prelabel"]	= "Sale Price: ";
		$this->obj_form->add_input($structure);


		$structure				= NULL;
		$structure["fieldname"]			= "nad_price_info_mobile";
		$structure["type"]			= "text";
		$structure["defaultvalue"]		= "Mobile Pricing";
		$this->obj_form->add_input($structure);

		$structure				= NULL;
		$structure["fieldname"]			= "nad_price_cost_mobile";
		$structure["type"]			= "money";
		$structure["options"]["prelabel"]	= "Cost Price: ";
		$this->obj_form->add_input($structure);

		$structure				= NULL;
		$structure["fieldname"]			= "nad_price_sale_mobile";
		$structure["type"]			= "money";
		$structure["options"]["prelabel"]	= "Sale Price: ";
		$this->obj_form->add_input($structure);


		$structure				= NULL;
		$structure["fieldname"]			= "nad_price_info_directory_national";
		$structure["type"]			= "text";
		$structure["defaultvalue"]		= "National Directory Pricing";
		$this->obj_form->add_input($structure);

		$structure				= NULL;
		$structure["fieldname"]			= "nad_price_cost_directory_national";
		$structure["type"]			= "money";
		$structure["options"]["prelabel"]	= "Cost Price: ";
		$this->obj_form->add_input($structure);

		$structure				= NULL;
		$structure["fieldname"]			= "nad_price_sale_directory_national";
		$structure["type"]			= "money";
		$structure["options"]["prelabel"]	= "Sale Price: ";
		$this->obj_form->add_input($structure);




		$structure				= NULL;
		$structure["fieldname"]			= "nad_price_info_directory_international";
		$structure["type"]			= "text";
		$structure["defaultvalue"]		= "International Directory Pricing";
		$this->obj_form->add_input($structure);

		$structure				= NULL;
		$structure["fieldname"]			= "nad_price_cost_directory_international";
		$structure["type"]			= "money";
		$structure["options"]["prelabel"]	= "Cost Price: ";
		$this->obj_form->add_input($structure);

		$structure				= NULL;
		$structure["fieldname"]			= "nad_price_sale_directory_international";
		$structure["type"]			= "money";
		$structure["options"]["prelabel"]	= "Sale Price: ";
		$this->obj_form->add_input($structure);


		$structure				= NULL;
		$structure["fieldname"]			= "nad_price_info_tollfree";
		$structure["type"]			= "text";
		$structure["defaultvalue"]		= "Toll-Free Call Pricing";
		$this->obj_form->add_input($structure);

		$structure				= NULL;
		$structure["fieldname"]			= "nad_price_cost_tollfree";
		$structure["type"]			= "money";
		$structure["options"]["prelabel"]	= "Cost Price: ";
		$this->obj_form->add_input($structure);

		$structure				= NULL;
		$structure["fieldname"]			= "nad_price_sale_tollfree";
		$structure["type"]			= "money";
		$structure["options"]["prelabel"]	= "Sale Price: ";
		$this->obj_form->add_input($structure);


		$structure				= NULL;
		$structure["fieldname"]			= "nad_price_info_special";
		$structure["type"]			= "text";
		$structure["defaultvalue"]		= "Special Call Pricing";
		$this->obj_form->add_input($structure);

		$structure				= NULL;
		$structure["fieldname"]			= "nad_price_cost_special";
		$structure["type"]			= "money";
		$structure["options"]["prelabel"]	= "Cost Price: ";
		$this->obj_form->add_input($structure);

		$structure				= NULL;
		$structure["fieldname"]			= "nad_price_sale_special";
		$structure["type"]			= "money";
		$structure["options"]["prelabel"]	= "Sale Price: ";
		$this->obj_form->add_input($structure);


		$this->obj_form->subforms_grouped["nad_import_prices"]["nad_price_national"] 			= array("nad_price_info_national", "nad_price_cost_national", "nad_price_sale_national");
		$this->obj_form->subforms_grouped["nad_import_prices"]["nad_price_mobile"] 			= array("nad_price_info_mobile", "nad_price_cost_mobile", "nad_price_sale_mobile");
		$this->obj_form->subforms_grouped["nad_import_prices"]["nad_price_directory_national"]		= array("nad_price_info_directory_national", "nad_price_cost_directory_national", "nad_price_sale_directory_national");
		$this->obj_form->subforms_grouped["nad_import_prices"]["nad_price_directory_international"]	= array("nad_price_info_directory_international", "nad_price_cost_directory_international", "nad_price_sale_directory_international");
		$this->obj_form->subforms_grouped["nad_import_prices"]["nad_price_tollfree"] 			= array("nad_price_info_tollfree", "nad_price_cost_tollfree", "nad_price_sale_tollfree");
		$this->obj_form->subforms_grouped["nad_import_prices"]["nad_price_special"] 			= array("nad_price_info_special", "nad_price_cost_special", "nad_price_sale_special");


		// import options
		$structure 				= NULL;
		$structure["fieldname"]			= "cdr_rate_import_mode";
		$structure["type"]			= "radio";
		$structure["values"]			= array("cdr_import_update_existing", "cdr_import_delete_existing");
		$structure["defaultvalue"]		= "cdr_import_update_existing";
		$this->obj_form->add_input($structure);


		// hidden fields
		$structure 				= NULL;
		$structure["fieldname"]			= "id_rate_table";
		$structure["type"]			= "hidden";
		$structure["defaultvalue"]		= $this->obj_rate_table->id;
		$this->obj_form->add_input($structure);


		// submit
		$structure 				= NULL;
		$structure["fieldname"]			= "submit";
		$structure["type"]			= "submit";
		$structure["defaultvalue"]		= "submit";
		$this->obj_form->add_input($structure);
	

		// subforms
		$this->obj_form->subforms["nad_import_details"]	= array("nad_country_prefix", "nad_default_destination");
		$this->obj_form->subforms["nad_import_prices"]	= array("nad_price_national", "nad_price_mobile", "nad_price_directory_national", "nad_price_directory_international", "nad_price_tollfree", "nad_price_special");
		$this->obj_form->subforms["nad_import_options"]	= array("cdr_rate_import_mode");
		$this->obj_form->subforms["hidden"]		= array("id_rate_table");
		$this->obj_form->subforms["submit"]		= array("submit");



		/*
			Load error data (if any)
		*/
		if (error_check())
		{
			$this->obj_form->load_data_error();
		}
		
	} 


	function render_html()
	{
		// Title + Summary
		print "<h3>CDR NAD IMPORT</h3><br>";
		print "<p>This interface allows the import of New Zealand-style NAD CSV files to populate the rate table with all the regions/prefixes.</p>";
	
		// display the form
		$this->obj_form->render_form();
	}
		

} // end class page_output

?>
