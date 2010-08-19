<?php
/*
	admin/config_locale.php
	
	access: admin users only

	Configuration of date/language options (most of these are default for when adding new users) as well as currency settings.
*/

class page_output
{
	var $obj_form;


	function check_permissions()
	{
		return user_permissions_get("admin");
	}

	function check_requirements()
	{
		// nothing to do
		return 1;
	}


	function execute()
	{
		/*
			Define form structure
		*/
		
		$this->obj_form = New form_input;
		$this->obj_form->formname = "config_locale";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "admin/config_locale-process.php";
		$this->obj_form->method = "post";


		// language options
		$structure = form_helper_prepare_radiofromdb("LANGUAGE_DEFAULT", "SELECT name as id, name as label FROM language_avaliable ORDER BY name");
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);

		// appearance options
		$structure = form_helper_prepare_dropdownfromdb("THEME_DEFAULT", "SELECT id, theme_name as label FROM themes ORDER BY theme_name");
		$structure["options"]["autoselect"]	= "yes";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);
		
		// time options
		$structure = form_helper_prepare_timezonedropdown("TIMEZONE_DEFAULT");
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$structure["options"]["search_filter"]		= "yes";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"]				= "DATEFORMAT";
		$structure["type"]				= "radio";
		$structure["values"]				= array("yyyy-mm-dd", "mm-dd-yyyy", "dd-mm-yyyy");
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);

	

		// currency options
		$structure = NULL;
		$structure["fieldname"]				= "CURRENCY_DEFAULT_NAME";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);
				
		$structure = NULL;
		$structure["fieldname"]				= "CURRENCY_DEFAULT_SYMBOL";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]				= "CURRENCY_DEFAULT_SYMBOL_POSITION";
		$structure["type"]				= "radio";
		$structure["values"]				= array("before", "after");
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$structure["translations"]["before"]		= "Before the currency value (eg: $20)";
		$structure["translations"]["after"]		= "After the currency value (eg: 20 RSD)";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"]				= "CURRENCY_DEFAULT_THOUSANDS_SEPARATOR";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]				= "CURRENCY_DEFAULT_DECIMAL_SEPARATOR";
		$structure["type"]				= "input";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);

		// submit section
		$structure = NULL;
		$structure["fieldname"]				= "submit";
		$structure["type"]				= "submit";
		$structure["defaultvalue"]			= "Save Changes";
		$this->obj_form->add_input($structure);
		
		
		// define subforms
		$this->obj_form->subforms["config_language"]		= array("LANGUAGE_DEFAULT");
		$this->obj_form->subforms["config_appearance"]		= array("THEME_DEFAULT");
		$this->obj_form->subforms["config_date"]		= array("DATEFORMAT", "TIMEZONE_DEFAULT");
		$this->obj_form->subforms["config_currency"]		= array("CURRENCY_DEFAULT_NAME", "CURRENCY_DEFAULT_SYMBOL", "CURRENCY_DEFAULT_SYMBOL_POSITION", "CURRENCY_DEFAULT_THOUSANDS_SEPARATOR", "CURRENCY_DEFAULT_DECIMAL_SEPARATOR");
		$this->obj_form->subforms["submit"]			= array("submit");

		if (error_check())
		{
			// load error datas
			$this->obj_form->load_data_error();
		}
		else
		{
			// fetch all the values from the database
			$sql_config_obj		= New sql_query;
			$sql_config_obj->string	= "SELECT name, value FROM config ORDER BY name";
			$sql_config_obj->execute();
			$sql_config_obj->fetch_array();

			foreach ($sql_config_obj->data as $data_config)
			{
				$this->obj_form->structure[ $data_config["name"] ]["defaultvalue"] = $data_config["value"];
			}

			unset($sql_config_obj);
		}
	}



	function render_html()
	{
		// Title + Summary
		print "<h3>LOCALE CONFIGURATION</h3><br>";
		print "<p>Configure the default timezone, date formatting and currency settings here. Apart from currency, most of these settings can be overridden if desired by users on their preference pages.</p>";
	
		// display the form
		$this->obj_form->render_form();
	}

	
}

?>
