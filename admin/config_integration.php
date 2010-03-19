<?php
/*
	admin/config_integration.php
	
	access: admin users only

	
	Options and configuration for enabling/disabling/configuring ABS options for interacting with other
	applications and modules.
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
		$this->obj_form->formname = "config_integration";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "admin/config_integration-process.php";
		$this->obj_form->method = "post";


		// customer portal stuff
		$structure = NULL;
		$structure["fieldname"]				= "MODULE_CUSTOMER_PORTAL";
		$structure["type"]				= "checkbox";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$structure["options"]["label"]			= "Enable/disable the customer portal intergration.";
		$this->obj_form->add_input($structure);
				

		// submit section
		$structure = NULL;
		$structure["fieldname"]				= "submit";
		$structure["type"]				= "submit";
		$structure["defaultvalue"]			= "Save Changes";
		$this->obj_form->add_input($structure);
		
		
		// define subforms
		$this->obj_form->subforms["config_integration"]		= array("MODULE_CUSTOMER_PORTAL");
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
		print "<h3>ABS INTEGRATION CONFIGURATION</h3><br>";
		print "<p>Options and configuration for enabling/disabling/configuring ABS options for interacting with other applications and modules.</p>";

		// display the form
		$this->obj_form->render_form();
	}

	
}

?>
