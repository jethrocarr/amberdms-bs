<?php
/*
	admin/config_integration.php
	
	access: admin users only

	Options and configuration for service billing.
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
		$this->obj_form->formname = "config_services";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "admin/config_services-process.php";
		$this->obj_form->method = "post";



		/*
			Configure Service Types & Labels
		*/

		$structure = NULL;
		$structure["fieldname"]					= "service_types_enabled";
		$structure["type"]					= "text";
		$structure["defaultvalue"]				= "<p>". lang_trans("service_types_selection_help") ."</p>";
		$this->obj_form->add_input($structure);

		$this->obj_form->subforms["config_service_types"]	= array("service_types_enabled");


		$obj_sql		= New sql_query;
		$obj_sql->string	= "SELECT id, name, description, active FROM service_types ORDER BY name";
		$obj_sql->execute();

		if ($obj_sql->num_rows())
		{
			$obj_sql->fetch_array();

			foreach ($obj_sql->data as $data_row)
			{
				// enable/disable
				$structure = NULL;
				$structure["fieldname"]				= "service_type_". $data_row["id"] ."_enable";
				$structure["type"]				= "checkbox";
				$structure["options"]["label"]			= $data_row["name"];

				if ($data_row["active"])
				{
					$structure["defaultvalue"]		= 1;
				}

				$this->obj_form->add_input($structure);


				// description
				$structure = NULL;
				$structure["fieldname"]				= "service_type_". $data_row["id"] ."_description";
				$structure["type"]				= "input";
				$structure["defaultvalue"]			= $data_row["description"];
				$structure["options"]["width"]			= "800";

				$this->obj_form->add_input($structure);




				// grouping
				$this->obj_form->subforms["config_service_types"][] = "service_type_". $data_row["id"];
		
				$this->obj_form->subforms_grouped["config_service_types"][ "service_type_". $data_row["id"] ][]	= "service_type_". $data_row["id"] ."_enable";
				$this->obj_form->subforms_grouped["config_service_types"][ "service_type_". $data_row["id"] ][]	= "service_type_". $data_row["id"] ."_description";
			}
		}

		unset($obj_sql);


		
		

		/*
			Configure service billing cycles

			We provide the ability to enable/disable service billing cycles in the UI to make things easier
			for the users.
		*/

		$structure = NULL;
		$structure["fieldname"]					= "billing_cycle_enabled";
		$structure["type"]					= "text";
		$structure["defaultvalue"]				= "<p>". lang_trans("billing_cycle_selection_help") ."</p>";
		$this->obj_form->add_input($structure);

		$this->obj_form->subforms["config_billing_cycle"]	= array("billing_cycle_enabled");


		$obj_sql		= New sql_query;
		$obj_sql->string	= "SELECT id, name, description, active FROM billing_cycles ORDER BY priority";
		$obj_sql->execute();

		if ($obj_sql->num_rows())
		{
			$obj_sql->fetch_array();

			foreach ($obj_sql->data as $data_row)
			{
				$structure = NULL;
				$structure["fieldname"]				= "billing_cycle_". $data_row["id"];
				$structure["type"]				= "checkbox";
				$structure["options"]["label"]			= $data_row["name"] ." -- ". $data_row["description"];
				$structure["options"]["no_fieldname"]		= 1;
				$structure["options"]["no_shift"]		= 1;

				if ($data_row["active"])
				{
					$structure["defaultvalue"]		= 1;
				}

				$this->obj_form->add_input($structure);

				$this->obj_form->subforms["config_billing_cycle"][] = "billing_cycle_". $data_row["id"];
			}
		}

		unset($obj_sql);



		/*
			Configure service billing modes

			We provide the ability to enable/disable service billing modes in the UI to make things easier
			for the users.
		*/

		$structure = NULL;
		$structure["fieldname"]					= "billing_mode_enabled";
		$structure["type"]					= "text";
		$structure["defaultvalue"]				= "<p>". lang_trans("billing_mode_selection_help") ."</p>";
		$this->obj_form->add_input($structure);

		$this->obj_form->subforms["config_billing_mode"]	= array("billing_mode_enabled");


		$obj_sql		= New sql_query;
		$obj_sql->string	= "SELECT id, name, description, active FROM billing_modes ORDER BY id";
		$obj_sql->execute();

		if ($obj_sql->num_rows())
		{
			$obj_sql->fetch_array();

			foreach ($obj_sql->data as $data_row)
			{
				$structure = NULL;
				$structure["fieldname"]				= "billing_mode_". $data_row["id"];
				$structure["type"]				= "checkbox";
				$structure["options"]["label"]			= $data_row["name"] ." -- ". $data_row["description"];
				$structure["options"]["no_fieldname"]		= 1;
				$structure["options"]["no_shift"]		= 1;

				if ($data_row["active"])
				{
					$structure["defaultvalue"]		= 1;
				}

				$this->obj_form->add_input($structure);

				$this->obj_form->subforms["config_billing_mode"][] = "billing_mode_". $data_row["id"];
			}
		}

		unset($obj_sql);




		/*
			Configure service usage unit options

			We allow the administor to enable/disable service units from being displayed as options (primarily 
			to prevent staff confusion.
		*/

		$structure = NULL;
		$structure["fieldname"]					= "service_units_enabled";
		$structure["type"]					= "text";
		$structure["defaultvalue"]				= "<p>". lang_trans("service_unit_selection_help") ."</p>";
		$this->obj_form->add_input($structure);

		$this->obj_form->subforms["config_usage_units"]		= array("service_units_enabled");


		$obj_sql		= New sql_query;
		$obj_sql->string	= "SELECT id, name, description, active FROM service_units ORDER BY typeid, name";
		$obj_sql->execute();

		if ($obj_sql->num_rows())
		{
			$obj_sql->fetch_array();

			foreach ($obj_sql->data as $data_row)
			{
				$structure = NULL;
				$structure["fieldname"]				= "service_unit_". $data_row["id"];
				$structure["type"]				= "checkbox";
				$structure["options"]["label"]			= $data_row["name"] ." -- ". $data_row["description"];
				$structure["options"]["no_fieldname"]		= 1;
				$structure["options"]["no_shift"]		= 1;

				if ($data_row["active"])
				{
					$structure["defaultvalue"]		= 1;
				}

				$this->obj_form->add_input($structure);

				$this->obj_form->subforms["config_usage_units"][] = "service_unit_". $data_row["id"];
			}
		}

		unset($obj_sql);



		/*
			usage services data source configuration
		
			We limit this to enabled servers, since it could be misused to try breaking into SQL databases
			if run by untrusted administrators.
		*/		
		if ($GLOBALS["config"]["dangerous_conf_options"] == "enabled")
		{
			$structure = NULL;
			$structure["fieldname"]					= "SERVICE_TRAFFIC_MODE";
			$structure["type"]					= "radio";
			$structure["values"]					= array("internal", "external");
			$structure["translations"]["internal"]			= "Use an internal database for usage records, uploaded via the SOAP API";
			$structure["translations"]["external"]			= "Use an external SQL database for fetching usage records (such as a netflow DB)";
			$structure["options"]["no_translate_fieldname"]		= "yes";
			$this->obj_form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"]					= "SERVICE_TRAFFIC_DB_TYPE";
			$structure["type"]					= "radio";
			$structure["values"]					= array("mysql_netflow_daily", "mysql_traffic_summary");
			$structure["translations"]["mysql_netflow_daily"]	= "MySQL Netflow Daily Tables (traffic_YYYYMMDD)";
			$structure["translations"]["mysql_traffic_summary"]	= "MySQL Traffic Summary Tables (traffic_summary)";
			$structure["options"]["autoselect"]			= "yes";
			$structure["options"]["no_translate_fieldname"]		= "yes";
			$this->obj_form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"]					= "SERVICE_TRAFFIC_DB_HOST";
			$structure["type"]					= "input";
			$structure["options"]["no_translate_fieldname"]		= "yes";
			$this->obj_form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"]					= "SERVICE_TRAFFIC_DB_NAME";
			$structure["type"]					= "input";
			$structure["options"]["no_translate_fieldname"]		= "yes";
			$this->obj_form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"]					= "SERVICE_TRAFFIC_DB_USERNAME";
			$structure["type"]					= "input";
			$structure["options"]["no_translate_fieldname"]		= "yes";
			$this->obj_form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"]					= "SERVICE_TRAFFIC_DB_PASSWORD";
			$structure["type"]					= "input";
			$structure["options"]["no_translate_fieldname"]		= "yes";
			$this->obj_form->add_input($structure);


			// set javascript actions
			$this->obj_form->add_action("SERVICE_TRAFFIC_MODE", "default", "SERVICE_TRAFFIC_DB_TYPE", "hide");
			$this->obj_form->add_action("SERVICE_TRAFFIC_MODE", "default", "SERVICE_TRAFFIC_DB_HOST", "hide");
			$this->obj_form->add_action("SERVICE_TRAFFIC_MODE", "default", "SERVICE_TRAFFIC_DB_NAME", "hide");
			$this->obj_form->add_action("SERVICE_TRAFFIC_MODE", "default", "SERVICE_TRAFFIC_DB_USERNAME", "hide");
			$this->obj_form->add_action("SERVICE_TRAFFIC_MODE", "default", "SERVICE_TRAFFIC_DB_PASSWORD", "hide");

			$this->obj_form->add_action("SERVICE_TRAFFIC_MODE", "external", "SERVICE_TRAFFIC_DB_TYPE", "show");
			$this->obj_form->add_action("SERVICE_TRAFFIC_MODE", "external", "SERVICE_TRAFFIC_DB_HOST", "show");
			$this->obj_form->add_action("SERVICE_TRAFFIC_MODE", "external", "SERVICE_TRAFFIC_DB_NAME", "show");
			$this->obj_form->add_action("SERVICE_TRAFFIC_MODE", "external", "SERVICE_TRAFFIC_DB_USERNAME", "show");
			$this->obj_form->add_action("SERVICE_TRAFFIC_MODE", "external", "SERVICE_TRAFFIC_DB_PASSWORD", "show");
	

			// add subform
			$this->obj_form->subforms["config_usage_traffic"]	= array("SERVICE_TRAFFIC_MODE", "SERVICE_TRAFFIC_DB_TYPE", "SERVICE_TRAFFIC_DB_HOST", "SERVICE_TRAFFIC_DB_NAME", "SERVICE_TRAFFIC_DB_USERNAME", "SERVICE_TRAFFIC_DB_PASSWORD");
		}
		else
		{
			//
			// explain that the configuration is locked and tell the user the current source of records.
			//
			$structure = NULL;
			$structure["fieldname"]					= "SERVICE_TRAFFIC_MSG";
			$structure["type"]					= "message";

			if (sql_get_singlevalue("SELECT value FROM config WHERE name='SERVICE_TRAFFIC_MODE' LIMIT 1") == "internal")
			{
				$structure["defaultvalue"]			= "<p>Using internal database for usage records (this configuration is locked by the system administrator)</p>";
			}
			else
			{
				$structure["defaultvalue"]			= "<p>Use external database for usage records (this configuration is locked by the system administrator)</p>";
			}

			$structure["options"]["css_row_class"]			= "table_highlight_info";
			$structure["options"]["no_translate_fieldname"]		= "yes";
			$this->obj_form->add_input($structure);


			$this->obj_form->subforms["config_usage_traffic"]	= array("SERVICE_TRAFFIC_MSG");
		}





		/*
			cdr services data source configuration
		
			We limit this to enabled servers, since it could be misused to try breaking into SQL databases
			if run by untrusted administrators.
		*/		
		if ($GLOBALS["config"]["dangerous_conf_options"] == "enabled")
		{
			$structure = NULL;
			$structure["fieldname"]					= "SERVICE_CDR_MODE";
			$structure["type"]					= "radio";
			$structure["values"]					= array("internal", "external");
			$structure["translations"]["internal"]			= "Use an internal database for usage records, uploaded via the SOAP API";
			$structure["translations"]["external"]			= "Use an external SQL database for fetching usage records.";
			$structure["options"]["no_translate_fieldname"]		= "yes";
			$this->obj_form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"]					= "SERVICE_CDR_DB_TYPE";
			$structure["type"]					= "radio";
			$structure["values"]					= array("mysql_asterisk");
			$structure["translations"]["mysql_asterisk"]		= "MySQL-based Asterisk CDR Database";
			$structure["options"]["autoselect"]			= "yes";
			$structure["options"]["no_translate_fieldname"]		= "yes";
			$this->obj_form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"]					= "SERVICE_CDR_DB_HOST";
			$structure["type"]					= "input";
			$structure["options"]["no_translate_fieldname"]		= "yes";
			$this->obj_form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"]					= "SERVICE_CDR_DB_NAME";
			$structure["type"]					= "input";
			$structure["options"]["no_translate_fieldname"]		= "yes";
			$this->obj_form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"]					= "SERVICE_CDR_DB_USERNAME";
			$structure["type"]					= "input";
			$structure["options"]["no_translate_fieldname"]		= "yes";
			$this->obj_form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"]					= "SERVICE_CDR_DB_PASSWORD";
			$structure["type"]					= "input";
			$structure["options"]["no_translate_fieldname"]		= "yes";
			$this->obj_form->add_input($structure);


			// set javascript actions
			$this->obj_form->add_action("SERVICE_CDR_MODE", "default", "SERVICE_CDR_DB_TYPE", "hide");
			$this->obj_form->add_action("SERVICE_CDR_MODE", "default", "SERVICE_CDR_DB_HOST", "hide");
			$this->obj_form->add_action("SERVICE_CDR_MODE", "default", "SERVICE_CDR_DB_NAME", "hide");
			$this->obj_form->add_action("SERVICE_CDR_MODE", "default", "SERVICE_CDR_DB_USERNAME", "hide");
			$this->obj_form->add_action("SERVICE_CDR_MODE", "default", "SERVICE_CDR_DB_PASSWORD", "hide");

			$this->obj_form->add_action("SERVICE_CDR_MODE", "external", "SERVICE_CDR_DB_TYPE", "show");
			$this->obj_form->add_action("SERVICE_CDR_MODE", "external", "SERVICE_CDR_DB_HOST", "show");
			$this->obj_form->add_action("SERVICE_CDR_MODE", "external", "SERVICE_CDR_DB_NAME", "show");
			$this->obj_form->add_action("SERVICE_CDR_MODE", "external", "SERVICE_CDR_DB_USERNAME", "show");
			$this->obj_form->add_action("SERVICE_CDR_MODE", "external", "SERVICE_CDR_DB_PASSWORD", "show");
		

			// add subform
			$this->obj_form->subforms["config_usage_cdr"]	= array("SERVICE_CDR_MODE", "SERVICE_CDR_DB_TYPE", "SERVICE_CDR_DB_HOST", "SERVICE_CDR_DB_NAME", "SERVICE_CDR_DB_USERNAME", "SERVICE_CDR_DB_PASSWORD");
		}
		else
		{
			//
			// explain that the configuration is locked and tell the user the current source of records.
			//
			$structure = NULL;
			$structure["fieldname"]					= "SERVICE_CDR_MSG";
			$structure["type"]					= "message";

			if (sql_get_singlevalue("SELECT value FROM config WHERE name='SERVICE_CDR_MODE' LIMIT 1") == "internal")
			{
				$structure["defaultvalue"]			= "<p>Using internal database for usage records (this configuration is locked by the system administrator)</p>";
			}
			else
			{
				$structure["defaultvalue"]			= "<p>Use external database for usage records (this configuration is locked by the system administrator)</p>";
			}

			$structure["options"]["css_row_class"]			= "table_highlight_info";
			$structure["options"]["no_translate_fieldname"]		= "yes";
			$this->obj_form->add_input($structure);


			$this->obj_form->subforms["config_usage_cdr"]		= array("SERVICE_CDR_MSG");
		}




		// CDR optons
		$structure = NULL;
		$structure["fieldname"]				= "SERVICE_CDR_LOCAL";
		$structure["type"]				= "radio";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$structure["values"]				= array("prefix", "destination");
		$structure["translations"]["prefix"]		= "Define local customer region by prefix (eg: \"64123\")";
		$structure["translations"]["destination"]	= "Define local customer region by destination string (eg: \"Wellington\")";
		$this->obj_form->add_input($structure);


		$structure = NULL;
		$structure["fieldname"]				= "SERVICE_CDR_BILLSELF";
		$structure["type"]				= "radio";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$structure["values"]				= array("free", "local", "regular");
		$structure["translations"]["free"]		= "Free calling between a customer's own DDIs";
		$structure["translations"]["local"]		= "\"LOCAL\" calling rate for calls between a customer's own DDI";
		$structure["translations"]["regular"]		= "Regular calling rates for calls between a customer's own DDI";
		$this->obj_form->add_input($structure);

		$this->obj_form->subforms["config_options_cdr"]	= array("SERVICE_CDR_LOCAL", "SERVICE_CDR_BILLSELF");


		// migration mode options
		$structure = NULL;
		$structure["fieldname"]				= "SERVICE_MIGRATION_MODE";
		$structure["type"]				= "checkbox";
		$structure["options"]["label"]			= "When enabled, provides additional options to service creation to create a part usage period.";
		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);

		
		// misc
		$structure = NULL;
		$structure["fieldname"]				= "SERVICE_PARTPERIOD_MODE";
		$structure["type"]				= "radio";
		$structure["values"]				= array("seporate", "merge");

		$structure["translations"]["seporate"]		= "Invoice a partial period (eg new customer signup) in a seporate invoice.";
		$structure["translations"]["merge"]		= "Add the additional period to next month's invoice.";

		$structure["options"]["no_translate_fieldname"]	= "yes";
		$this->obj_form->add_input($structure);



		// submit section
		$structure = NULL;
		$structure["fieldname"]				= "submit";
		$structure["type"]				= "submit";
		$structure["defaultvalue"]			= "Save Changes";
		$this->obj_form->add_input($structure);
		
		
		// define subforms
		$this->obj_form->subforms["config_migration"]		= array("SERVICE_MIGRATION_MODE");
		$this->obj_form->subforms["config_misc"]		= array("SERVICE_PARTPERIOD_MODE");
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
		print "<h3>SERVICE CONFIGURATION</h3><br>";
		print "<p>Options and configuration for services and billing.</p>";

		// display the form
		$this->obj_form->render_form();
	}

	
}

?>
