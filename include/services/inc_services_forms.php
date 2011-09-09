<?php
/*
	include/services/inc_services_forms.php

	Provides forms for service management
*/


require("include/accounts/inc_charts.php");



/*
	service_render_summarybox($id_service)

	Displays a summary box of the service information and whether the service is enabled or disabled.

	Values
	id_service	ID of the service

	Return Codes
	0	failure
	1	sucess
*/
function service_render_summarybox($id_service)
{
	log_debug("inc_service", "service_render_summarybox($id_service)");


	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT name_service,
						active,
						service_types.name as type_name,
						service_groups.group_name as group_name,
						price,
						discount
					FROM services
					LEFT JOIN service_types ON service_types.id = services.typeid
					LEFT JOIN service_groups ON service_groups.id = services.id_service_group
					WHERE services.id='$id_service' LIMIT 1";
	$sql_obj->execute();

	if ($sql_obj->num_rows())
	{
		$sql_obj->fetch_array();

		if ($sql_obj->data[0]["active"])
		{
			// service is enabled
			print "<table width=\"100%\" class=\"table_highlight_open\">";
			print "<tr>";
				print "<td>";
				print "<b>Service ". $sql_obj->data[0]["name_service"] ." is enabled.</b>";
	
				print "<table cellpadding=\"4\">";
					
					print "<tr>";
						print "<td>Service Type:</td>";
						print "<td>". $sql_obj->data[0]["type_name"] ."</td>";
					print "</tr>";
					
					print "<tr>";
						print "<td>Service Group:</td>";
						print "<td>". $sql_obj->data[0]["group_name"] ."</td>";
					print "</tr>";

					if ($sql_obj->data[0]["discount"])
					{
						// work out the price after discount
						$discount_calc	= $sql_obj->data[0]["discount"] / 100;
						$discount_calc	= $sql_obj->data[0]["price"] * $discount_calc;


						print "<tr>";
							print "<td>Base Plan Price:</td>";
							print "<td>". format_money($sql_obj->data[0]["price"] - $discount_calc) ." (discount of ". format_money($discount_calc) ." included)</td>";
						print "</tr>";

					}
					else
					{
						print "<tr>";
							print "<td>Base Plan Price:</td>";
							print "<td>". format_money($sql_obj->data[0]["price"]) ."</td>";
						print "</tr>";
					}
					
				print "</table>";

				print "</td>";

			print "</tr>";
			print "</table>";
		}
		else
		{
			// service is not yet enabled
			print "<table width=\"100%\" class=\"table_highlight_important\">";
			print "<tr>";
				print "<td>";
				print "<b>Service ". $sql_obj->data[0]["name_service"] ." is inactive.</b>";
				print "<p>This service is currently unconfigured, you need to setup the service plan before it will be activated.</p>";
				print "</td>";
			print "</tr>";
			print "</table>";
		}


		print "<br>";
	}
}






/*
	class: services_form_details

	Generates forms for processing service details
*/
class services_form_details
{
	var $serviceid;			// ID of the service entry
	var $mode;			// Mode: "add" or "edit"

	var $obj_form;


	function execute()
	{
		log_debug("services_form_details", "Executing execute()");

		/*
			Define form structure
		*/
		$this->obj_form = New form_input;
		$this->obj_form->formname = "service_". $this->mode;
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "services/edit-process.php";
		$this->obj_form->method = "post";

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "name_service";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = charts_form_prepare_acccountdropdown("chartid", "ar_income");
		$structure["options"]["search_filter"]	= "yes";
		$structure["options"]["width"]		= "400";
		$structure["options"]["req"]		= "yes";
		$this->obj_form->add_input($structure);	

		$structure = NULL;
		$structure["fieldname"] 		= "description";
		$structure["type"]			= "textarea";
		$structure["options"]["width"]		= "600";
		$structure["options"]["height"]		= "100";
		$this->obj_form->add_input($structure);


		// the service type can only be set at creation time.
		if ($this->mode == "add")
		{
			$structure = form_helper_prepare_radiofromdb("typeid", "SELECT id, name as label, description as label1 FROM service_types WHERE active='1' ORDER BY name");
			$structure["options"]["req"]	= "yes";

			// replace all the -- joiners with <br> for clarity
			for ($i = 0; $i < count($structure["values"]); $i++)
			{
				$structure["translations"][ $structure["values"][$i] ] = str_replace("--", "<br><i>", $structure["translations"][ $structure["values"][$i] ]);
				$structure["translations"][ $structure["values"][$i] ] .= "</i><br>";
			}

			// handle misconfiguration gracefully
			if (empty($this->obj_form->structure["typeid"]["values"]))
			{
				$this->obj_form->structure["typeid"]["type"]			= "text";
				$this->obj_form->structure["typeid"]["defaultvalue"]		= "error_no_types_available";
			}

			$this->obj_form->add_input($structure);
		}
		else
		{
			$structure			= NULL;
			$structure["fieldname"]		= "typeid";
			$structure["type"]		= "text";
			$this->obj_form->add_input($structure);
		}



		// service grouping
		$structure = form_helper_prepare_dropdownfromgroup("id_service_group", "SELECT id as value_id, group_name as value_key, id_parent as value_parent FROM service_groups");
		$structure["options"]["req"]		= "yes";
		$structure["options"]["search_filter"]	= "yes";
		$structure["options"]["width"]		= "400";
		$this->obj_form->add_input($structure);

		$structure = form_helper_prepare_dropdownfromgroup("id_service_group_usage", "SELECT id as value_id, group_name as value_key, id_parent as value_parent FROM service_groups");
		$structure["options"]["req"]		= "yes";
		$structure["options"]["search_filter"]	= "yes";
		$structure["options"]["width"]		= "400";
		$this->obj_form->add_input($structure);


		// write service usage grouping javascript UI logic - we need to get all the options
		// and write actions for each ID

		$this->obj_form->add_action("typeid", "default", "id_service_group_usage", "hide");


		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id, name as label FROM service_types";
		$sql_obj->execute();
		$sql_obj->fetch_array();

		foreach ($sql_obj->data as $data_row)
		{
			switch ($data_row["label"])
			{
				case "data_traffic":
				case "generic_with_usage":
				case "phone_single":
				case "phone_trunk":
				case "phone_tollfree":
				case "time":
					$this->obj_form->add_action("typeid", $data_row["id"] , "id_service_group_usage", "show");		// for add mode
					$this->obj_form->add_action("typeid", $data_row["label"] , "id_service_group_usage", "show");		// for view mode
				break;

				case "bundle":
				case "generic_no_usage":
				case "licenses":
					$this->obj_form->add_action("typeid", $data_row["id"] , "id_service_group_usage", "hide");		// for add mode
					$this->obj_form->add_action("typeid", $data_row["label"] , "id_service_group_usage", "hide");		// for view mode
				break;
			}
		}

		// define service_details subform
		$this->obj_form->subforms["service_details"]	= array("name_service", "chartid", "typeid", "id_service_group", "id_service_group_usage", "description");



		/*
			List all the taxes, so that the user can select the tax(es) that apply to the service
		*/

		$sql_tax_obj		= New sql_query;
		$sql_tax_obj->string	= "SELECT id, name_tax, description, default_services FROM account_taxes ORDER BY name_tax";
		$sql_tax_obj->execute();

		if ($sql_tax_obj->num_rows())
		{
			// user note
			$structure = NULL;
			$structure["fieldname"] 		= "tax_message";
			$structure["type"]			= "message";
			$structure["defaultvalue"]		= "<p>Check all taxes that apply to this service below.</p>";
			$this->obj_form->add_input($structure);
		
			$this->obj_form->subforms["service_tax"][] = "tax_message";


			// run through all the taxes
			$sql_tax_obj->fetch_array();

			foreach ($sql_tax_obj->data as $data_tax)
			{
				// define tax checkbox
				$structure = NULL;
				$structure["fieldname"] 		= "tax_". $data_tax["id"];
				$structure["type"]			= "checkbox";
				$structure["options"]["label"]		= $data_tax["name_tax"] ." -- ". $data_tax["description"];
				$structure["options"]["no_fieldname"]	= "enable";

				if ($this->serviceid)
				{
					// see if this tax is currently enabled for this service
					$sql_obj		= New sql_query;
					$sql_obj->string	= "SELECT id FROM services_taxes WHERE serviceid='". $this->serviceid ."' AND taxid='". $data_tax["id"] ."' LIMIT 1";
					$sql_obj->execute();

					if ($sql_obj->num_rows())
					{
						$structure["defaultvalue"] = "on";
					}
				}
				else
				{
					if ($data_tax["default_services"])
					{
						$structure["defaultvalue"] = "on";
					}
				}

				// add to form
				$this->obj_form->add_input($structure);
				$this->obj_form->subforms["service_tax"][] = "tax_". $data_tax["id"];
			}
		}



		/*
			Upstream Vendor Information

			These fields are purely for users notes/record keeping purposes.
		*/

		$structure = NULL;
		$structure["fieldname"]			= "upstream_help_message";
		$structure["type"]			= "message";
		$structure["defaultvalue"]		= "<p>". lang_trans("upstream_help_message") ."</p>";
		$this->obj_form->add_input($structure);

		$sql_struct_obj	= New sql_query;
		$sql_struct_obj->prepare_sql_settable("vendors");
		$sql_struct_obj->prepare_sql_addfield("id", "vendors.id");
		$sql_struct_obj->prepare_sql_addfield("label", "vendors.code_vendor");
		$sql_struct_obj->prepare_sql_addfield("label1", "vendors.name_vendor");
		$sql_struct_obj->prepare_sql_addorderby("code_vendor");
		$sql_struct_obj->prepare_sql_addwhere("id = 'CURRENTID' OR date_end = '0000-00-00'");
			
		$structure = form_helper_prepare_dropdownfromobj("upstream_id", $sql_struct_obj);
		$structure["options"]["search_filter"]	= "yes";
		$structure["options"]["width"]		= "400";
		$this->obj_form->add_input($structure);	

		$structure = NULL;
		$structure["fieldname"] 		= "upstream_notes";
		$structure["type"]			= "textarea";
		$structure["options"]["width"]		= "600";
		$structure["options"]["height"]		= "100";
		$this->obj_form->add_input($structure);

		$this->obj_form->subforms["service_upstream"]	= array("upstream_help_message", "upstream_id", "upstream_notes");



		// define subforms	
		if (user_permissions_get("services_write"))
		{
			$this->obj_form->subforms["submit"]	= array("submit");
		}
		else
		{
			$this->obj_form->subforms["submit"]	= array("");
		}


		/*
			Mode dependent options
		*/
		
		if ($this->mode == "add")
		{
			// submit button
			$structure = NULL;
			$structure["fieldname"] 	= "submit";
			$structure["type"]		= "submit";
			$structure["defaultvalue"]	= "Create Service";
			$this->obj_form->add_input($structure);
		}
		else
		{
			// submit button
			$structure = NULL;
			$structure["fieldname"] 	= "submit";
			$structure["type"]		= "submit";
			$structure["defaultvalue"]	= "Save Changes";
			$this->obj_form->add_input($structure);

			// hidden data
			$structure = NULL;
			$structure["fieldname"] 	= "id_service";
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= $this->serviceid;
			$this->obj_form->add_input($structure);
				

			$this->obj_form->subforms["hidden"]	= array("id_service");
		}


		/*
			Load Data
		*/
		if ($this->mode == "add")
		{
			$this->obj_form->load_data_error();
		}
		else
		{
			// load details data
			$this->obj_form->sql_query	= "SELECT 
								services.name_service, 
								services.chartid, 
								services.id_service_group,
								services.id_service_group_usage,
								services.description, 
								service_types.name as typeid,
								services.upstream_id as upstream_id,
								services.upstream_notes as upstream_notes
							FROM `services`
							LEFT JOIN service_types ON service_types.id = services.typeid
							WHERE services.id='". $this->serviceid ."' LIMIT 1";
			$this->obj_form->load_data();
		}
	}


	function render_html()
	{
		log_debug("services_form_details", "Executing execute()");
		
		// display form
		$this->obj_form->render_form();

		
		if (!user_permissions_get("services_write"))
		{
			format_msgbox("locked", "<p>Sorry, you do not have permissions to adjust this service.</p>");
		}

	}
}


/*
	class: services_form_plan

	Generates forms for configuring the service plan information.
*/
class services_form_plan
{
	var $serviceid;			// ID of the service entry

	var $obj_form;


	function execute()
	{
		log_debug("services_form_plan", "Executing execute()");

		/*
			Fetch plan type information
		*/
		$sql_plan_obj		 = New sql_query;
		$sql_plan_obj->string	 = "SELECT services.typeid, service_types.name FROM services LEFT JOIN service_types ON service_types.id = services.typeid WHERE services.id='". $this->serviceid ."' LIMIT 1";
		$sql_plan_obj->execute();
		$sql_plan_obj->fetch_array();



		/*
			Define form structure
		*/
		$this->obj_form = New form_input;
		$this->obj_form->formname = "service_plan";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "services/plan-edit-process.php";
		$this->obj_form->method = "post";


		// general details
		$structure = NULL;
		$structure["fieldname"] 	= "name_service";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);


		$structure = form_helper_prepare_radiofromdb("billing_cycle", "SELECT id, name as label, description as label1 FROM billing_cycles WHERE active='1' ORDER BY priority");
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "price";
		$structure["type"]		= "money";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "price_setup";
		$structure["type"]		= "money";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 		= "discount";
		$structure["type"]			= "input";
		$structure["options"]["width"]		= 50;
		$structure["options"]["label"]		= " %";
		$structure["options"]["max_length"]	= "6";
		$this->obj_form->add_input($structure);


		/*
			Type-specific Form Options
		*/

		switch ($sql_plan_obj->data[0]["name"])
		{
			case "generic_with_usage":
				/*
					GENERIC_WITH_USAGE

					This service is to be used for any non-traffic, non-time accounting service that needs to track usage. Examples of this
					could be counting the number of API requests, size of disk usage on a vhost, etc.
				*/
				

				// custom
				$structure = NULL;
				$structure["fieldname"]		= "plan_information";
				$structure["type"]		= "message";
				$structure["defaultvalue"]	= "<i>This section is where you define what units you wish to bill in, along with the cost of excess units. It is acceptable to leave the price for extra units set to 0.00 if you have some other method of handling excess usage (eg: rate shaping rather than billing). If you wish to create an uncapped/unlimited usage service, set both the price for extra units and the included units to 0.</i>";
				$this->obj_form->add_input($structure);

				$structure = NULL;
				$structure["fieldname"]			= "units";
				$structure["type"]			= "input";
				$structure["options"]["req"]		= "yes";
				$structure["options"]["autoselect"]	= "yes";	
				$this->obj_form->add_input($structure);
		
				$structure = NULL;
				$structure["fieldname"] 	= "included_units";
				$structure["type"]		= "input";
				$structure["options"]["req"]	= "yes";
				$this->obj_form->add_input($structure);
				
				$structure = NULL;
				$structure["fieldname"] 	= "price_extraunits";
				$structure["type"]		= "money";
				$structure["options"]["req"]	= "yes";
				$this->obj_form->add_input($structure);

				$structure = form_helper_prepare_radiofromdb("usage_mode", "SELECT id, description as label FROM service_usage_modes");
				$structure["options"]["req"]		= "yes";
				$this->obj_form->add_input($structure);


				// general
				$structure = form_helper_prepare_radiofromdb("billing_mode", "SELECT id, name as label, description as label1 FROM billing_modes WHERE active='1' AND name NOT LIKE '%advance%'");
				$structure["options"]["req"]		= "yes";

				// replace all the -- joiners with <br> for clarity
				for ($i = 0; $i < count($structure["values"]); $i++)
				{
					$structure["translations"][ $structure["values"][$i] ] = str_replace("--", "<br><i>", $structure["translations"][ $structure["values"][$i] ]);
					$structure["translations"][ $structure["values"][$i] ] .= "</i>";
				}
				
				$this->obj_form->add_input($structure);


				// usage alerts
				$structure = NULL;
				$structure["fieldname"] 		= "alert_80pc";
				$structure["type"]			= "checkbox";
				$structure["options"]["no_fieldname"]	= "yes";
				$structure["options"]["label"]		= "Send customers email warnings when they hit 80% of their usage";
				$this->obj_form->add_input($structure);

				$structure = NULL;
				$structure["fieldname"] 		= "alert_100pc";
				$structure["type"]			= "checkbox";
				$structure["options"]["no_fieldname"]	= "yes";
				$structure["options"]["label"]		= "Send customers email warnings when they hit 100% of their usage";
				$this->obj_form->add_input($structure);

				$structure = NULL;
				$structure["fieldname"] 		= "alert_extraunits";
				$structure["type"]			= "input";
				$this->obj_form->add_input($structure);
					


				// subforms
				$this->obj_form->subforms["service_plan"]		= array("name_service", "price", "price_setup", "discount", "billing_cycle", "billing_mode");
				$this->obj_form->subforms["service_plan_custom"]	= array("plan_information", "units", "included_units", "price_extraunits", "usage_mode");
				$this->obj_form->subforms["service_plan_alerts"] 	= array("alert_80pc", "alert_100pc", "alert_extraunits");
		
			break;
			
			case "licenses":
				/*
					LICENSES

				*/
				$structure = NULL;
				$structure["fieldname"]		= "plan_information";
				$structure["type"]		= "message";
				$structure["defaultvalue"]	= "<i>For licenses services, the price field and included units specify how much for the number of base licenses. The extra units price field specifies how much for additional licenses.</i>";
				$this->obj_form->add_input($structure);

				$structure = NULL;
				$structure["fieldname"]			= "units";
				$structure["type"]			= "input";
				$structure["options"]["req"]		= "yes";
				$structure["options"]["autoselect"]	= "yes";
				$this->obj_form->add_input($structure);
		
				$structure = NULL;
				$structure["fieldname"] 	= "included_units";
				$structure["type"]		= "input";
				$structure["options"]["req"]	= "yes";
				$this->obj_form->add_input($structure);
				
				$structure = NULL;
				$structure["fieldname"] 	= "price_extraunits";
				$structure["type"]		= "money";
				$structure["options"]["req"]	= "yes";
				$this->obj_form->add_input($structure);

				
				// general
				$structure = form_helper_prepare_radiofromdb("billing_mode", "SELECT id, name as label, description as label1 FROM billing_modes WHERE active='1' AND name NOT LIKE '%telco%'");
				$structure["options"]["req"]		= "yes";
				
				// replace all the -- joiners with <br> for clarity
				for ($i = 0; $i < count($structure["values"]); $i++)
				{
					$structure["translations"][ $structure["values"][$i] ] = str_replace("--", "<br><i>", $structure["translations"][ $structure["values"][$i] ]);
					$structure["translations"][ $structure["values"][$i] ] .= "</i>";
				}

				$this->obj_form->add_input($structure);
		

				
				// subforms
				$this->obj_form->subforms["service_plan"]		= array("name_service", "price", "price_setup", "discount", "billing_cycle", "billing_mode");
				$this->obj_form->subforms["service_plan_custom"]	= array("plan_information", "units", "included_units", "price_extraunits");
			break;


			case "time":
				/*
					TIME

					Incrementing usage counters.
				*/
				$structure = NULL;
				$structure["fieldname"]		= "plan_information";
				$structure["type"]		= "message";
				$structure["defaultvalue"]	= "<i>This section is where you define what units you wish to bill in, along with the cost of excess units. It is acceptable to leave the price for extra units set to 0.00 if you have some other method of handling excess usage (eg: rate shaping rather than billing). If you wish to create an uncapped/unlimited usage service, set both the price for extra units and the included units to 0.</i>";
				$this->obj_form->add_input($structure);

				$structure = form_helper_prepare_radiofromdb("units", "SELECT id, name as label, description as label1 FROM service_units WHERE typeid='". $sql_plan_obj->data[0]["typeid"] ."' AND active='1' ORDER BY name");
				$structure["options"]["req"]		= "yes";
				$structure["options"]["autoselect"]	= "yes";
				$this->obj_form->add_input($structure);
		
				$structure = NULL;
				$structure["fieldname"] 	= "included_units";
				$structure["type"]		= "input";
				$structure["options"]["req"]	= "yes";
				$this->obj_form->add_input($structure);
				
				$structure = NULL;
				$structure["fieldname"] 	= "price_extraunits";
				$structure["type"]		= "money";
				$structure["options"]["req"]	= "yes";
				$this->obj_form->add_input($structure);
				
				// general
				$structure = form_helper_prepare_radiofromdb("billing_mode", "SELECT id, name as label, description as label1 FROM billing_modes WHERE active='1' AND name NOT LIKE '%advance%'");
				$structure["options"]["req"]		= "yes";

				// replace all the -- joiners with <br> for clarity
				for ($i = 0; $i < count($structure["values"]); $i++)
				{
					$structure["translations"][ $structure["values"][$i] ] = str_replace("--", "<br><i>", $structure["translations"][ $structure["values"][$i] ]);
					$structure["translations"][ $structure["values"][$i] ] .= "</i>";
				}
		
				$this->obj_form->add_input($structure);


				// usage alerts
				$structure = NULL;
				$structure["fieldname"] 		= "alert_80pc";
				$structure["type"]			= "checkbox";
				$structure["options"]["no_fieldname"]	= "yes";
				$structure["options"]["label"]		= "Send customers email warnings when they hit 80% of their usage";
				$this->obj_form->add_input($structure);

				$structure = NULL;
				$structure["fieldname"] 		= "alert_100pc";
				$structure["type"]			= "checkbox";
				$structure["options"]["no_fieldname"]	= "yes";
				$structure["options"]["label"]		= "Send customers email warnings when they hit 100% of their usage";
				$this->obj_form->add_input($structure);

				$structure = NULL;
				$structure["fieldname"] 		= "alert_extraunits";
				$structure["type"]			= "input";
				$this->obj_form->add_input($structure);
													
				
				// subforms
				$this->obj_form->subforms["service_plan"]		= array("name_service", "price", "price_setup", "discount", "billing_cycle", "billing_mode");
				$this->obj_form->subforms["service_plan_custom"] 	= array("plan_information", "units", "included_units", "price_extraunits");
				$this->obj_form->subforms["service_plan_alerts"] 	= array("alert_80pc", "alert_100pc", "alert_extraunits");


				// handle misconfiguration
				if (empty($this->obj_form->structure["units"]["values"]))
				{
					$this->obj_form->structure["units"]["type"]			= "text";
					$this->obj_form->structure["units"]["defaultvalue"]		= "error_no_units_available";
				}

			break;


			case "data_traffic":
				/*
					DATA_TRAFFIC
		
					Data traffic service types are reasonably complex and allow multiple data caps to be assigned, based
					on the selected traffic types.
				*/



				/*
					data_traffic: General service optionsd

				*/

				// general
				$structure = form_helper_prepare_radiofromdb("billing_mode", "SELECT id, name as label, description as label1 FROM billing_modes WHERE active='1' AND name NOT LIKE '%advance%'");
				$structure["options"]["req"]		= "yes";

				// replace all the -- joiners with <br> for clarity
				for ($i = 0; $i < count($structure["values"]); $i++)
				{
					$structure["translations"][ $structure["values"][$i] ] = str_replace("--", "<br><i>", $structure["translations"][ $structure["values"][$i] ]);
					$structure["translations"][ $structure["values"][$i] ] .= "</i>";
				}
		
				$this->obj_form->add_input($structure);

				// subforms
				$this->obj_form->subforms["service_plan"]		= array("name_service", "price", "price_setup", "discount", "billing_cycle", "billing_mode");




				/*
					data_traffic: Service Plan Options
				*/

				$structure = NULL;
				$structure["fieldname"]		= "plan_information";
				$structure["type"]		= "message";
				$structure["defaultvalue"]	= "<i>This section is where you define what units you wish to bill in, along with the cost of excess units. It is acceptable to leave the price for extra units set to 0.00 if you have some other method of handling excess usage (eg: rate shaping rather than billing). If you wish to create an uncapped/unlimited usage service, set both the price for extra units and the included units to 0.</i>";
				$this->obj_form->add_input($structure);

				$structure = form_helper_prepare_radiofromdb("units", "SELECT id, name as label, description as label1 FROM service_units WHERE typeid='". $sql_plan_obj->data[0]["typeid"] ."' AND active='1' ORDER BY name");
				$structure["options"]["req"]		= "yes";
				$structure["options"]["autoselect"]	= "yes";
				$this->obj_form->add_input($structure);
		
				// handle misconfiguration
				if (empty($this->obj_form->structure["units"]["values"]))
				{
					$this->obj_form->structure["units"]["type"]			= "text";
					$this->obj_form->structure["units"]["defaultvalue"]		= "error_no_units_available";
				}

				// subforms
				$this->obj_form->subforms["service_plan_custom"] 	= array("plan_information", "units");




				/*
					data_traffic: Service Plan Datacaps
				*/

				// help info
				$structure = NULL;
				$structure["fieldname"]		= "traffic_cap_help";
				$structure["type"]		= "message";
				$structure["defaultvalue"]	= "<p>Define the traffic cap(s) for the selected service below - note that the \"Any\" type will always exist and applies to any traffic that doesn't match any of the other types matched to the customer on this service.</p>";
				$this->obj_form->add_input($structure);

				$this->obj_form->subforms["traffic_caps"][] = "traffic_cap_help";


				// header
				$structure = NULL;
				$structure["fieldname"]		= "traffic_cap_header_active";
				$structure["type"]		= "text";
				$structure["defaultvalue"]	= lang_trans("header_traffic_cap_active");
				$this->obj_form->add_input($structure);
				
				$structure = NULL;
				$structure["fieldname"]		= "traffic_cap_header_name";
				$structure["type"]		= "text";
				$structure["defaultvalue"]	= lang_trans("header_traffic_cap_name");
				$this->obj_form->add_input($structure);
				
				$structure = NULL;
				$structure["fieldname"]		= "traffic_cap_header_mode";
				$structure["type"]		= "text";
				$structure["defaultvalue"]	= lang_trans("header_traffic_cap_mode");
				$this->obj_form->add_input($structure);

				$structure = NULL;
				$structure["fieldname"]		= "traffic_cap_header_units_included";
				$structure["type"]		= "text";
				$structure["defaultvalue"]	= lang_trans("header_traffic_units_included");
				$this->obj_form->add_input($structure);

				$structure = NULL;
				$structure["fieldname"]		= "traffic_cap_header_units_price";
				$structure["type"]		= "text";
				$structure["defaultvalue"]	= lang_trans("header_traffic_units_price");
				$this->obj_form->add_input($structure);


				$this->obj_form->subforms_grouped["traffic_caps"]["traffic_cap_header"][]		= "traffic_cap_header_active";
				$this->obj_form->subforms_grouped["traffic_caps"]["traffic_cap_header"][]		= "traffic_cap_header_name";
				$this->obj_form->subforms_grouped["traffic_caps"]["traffic_cap_header"][]		= "traffic_cap_header_mode";
				$this->obj_form->subforms_grouped["traffic_caps"]["traffic_cap_header"][]		= "traffic_cap_header_units_included";
				$this->obj_form->subforms_grouped["traffic_caps"]["traffic_cap_header"][]		= "traffic_cap_header_units_price";

				$this->obj_form->subforms["traffic_caps"][] = "traffic_cap_header";


				// loop through all the traffic types
				$obj_sql_traffic_types		= New sql_query;
				$obj_sql_traffic_types->string	= "SELECT id, type_name FROM traffic_types ORDER BY id='1', type_name DESC";
				$obj_sql_traffic_types->execute();
				
				$obj_sql_traffic_types->num_rows();	// will always be at least one, need for loop
				$obj_sql_traffic_types->fetch_array();


				for ($i=0; $i < $obj_sql_traffic_types->data_num_rows; $i++)
				{


					// define form fields
					$structure = NULL;
					$structure["fieldname"]		= "traffic_cap_". $i ."_active";
					$structure["type"]		= "checkbox";
					$structure["options"]["label"]	= "Apply to Service";

					if ($obj_sql_traffic_types->data[$i]["id"] == 1)
					{
						$structure["options"]["disabled"]	= "yes";
						$structure["defaultvalue"]		= "on";
					}

					$this->obj_form->add_input($structure);

					$structure = NULL;
					$structure["fieldname"]		= "traffic_cap_". $i ."_id";
					$structure["type"]		= "hidden";
					$structure["defaultvalue"]	= $obj_sql_traffic_types->data[$i]["id"];
					$this->obj_form->add_input($structure);

					$structure = NULL;
					$structure["fieldname"]		= "traffic_cap_". $i ."_name";
					$structure["type"]		= "text";
					$structure["defaultvalue"]	= $obj_sql_traffic_types->data[$i]["type_name"];
					$this->obj_form->add_input($structure);
					
					$structure = NULL;
					$structure["fieldname"]		= "traffic_cap_". $i ."_mode";
					$structure["type"]		= "dropdown";

					$structure["values"][0]		= "unlimited";
					$structure["values"][1]		= "capped";

					$structure["defaultvalue"]	= "unlimited";
					$structure["options"]["width"]	= "100";
					$this->obj_form->add_input($structure);

					$structure = NULL;
					$structure["fieldname"]		= "traffic_cap_". $i ."_units_included";
					$structure["type"]		= "input";
					$structure["options"]["width"]	= "100";
					$structure["options"]["label"]	= "_units_"; // javascript replace with unit name
					$this->obj_form->add_input($structure);

					$structure = NULL;
					$structure["fieldname"]		= "traffic_cap_". $i ."_units_price";
					$structure["type"]		= "money";
					$structure["options"]["label"]	= "_units_"; // javascript replace with unit name
					$this->obj_form->add_input($structure);


					$this->obj_form->subforms_grouped["traffic_caps"]["traffic_cap_". $i][]		= "traffic_cap_". $i ."_active";
					$this->obj_form->subforms_grouped["traffic_caps"]["traffic_cap_". $i][]		= "traffic_cap_". $i ."_name";
					$this->obj_form->subforms_grouped["traffic_caps"]["traffic_cap_". $i][]		= "traffic_cap_". $i ."_mode";
					$this->obj_form->subforms_grouped["traffic_caps"]["traffic_cap_". $i][]		= "traffic_cap_". $i ."_units_included";
					$this->obj_form->subforms_grouped["traffic_caps"]["traffic_cap_". $i][]		= "traffic_cap_". $i ."_units_price";
					$this->obj_form->subforms_grouped["traffic_caps"]["traffic_cap_". $i][]		= "traffic_cap_". $i ."_id";

					$this->obj_form->subforms["traffic_caps"][] = "traffic_cap_". $i;


					// fetch data caps for this service, if any
					$obj_sql_traffic_cap 		= New sql_query;
					$obj_sql_traffic_cap->string	= "SELECT mode, units_price, units_included FROM `traffic_caps` WHERE id_service='". $this->serviceid ."' AND id_traffic_type='". $obj_sql_traffic_types->data[$i]["id"] ."' LIMIT 1";
					$obj_sql_traffic_cap->execute();

					if ($obj_sql_traffic_cap->num_rows())
					{
						$obj_sql_traffic_cap->fetch_array();

						$this->obj_form->structure["traffic_cap_". $i ."_active"]["defaultvalue"]		= "on";
						$this->obj_form->structure["traffic_cap_". $i ."_mode"]["defaultvalue"]			= $obj_sql_traffic_cap->data[0]["mode"];
						$this->obj_form->structure["traffic_cap_". $i ."_units_included"]["defaultvalue"]	= $obj_sql_traffic_cap->data[0]["units_included"];
						$this->obj_form->structure["traffic_cap_". $i ."_units_price"]["defaultvalue"]		= $obj_sql_traffic_cap->data[0]["units_price"];
					}

					unset($obj_sql_traffic_cap);


				}





				/*
					data_traffic: Service Usage Alerts
				*/


				// usage alerts
				$structure = NULL;
				$structure["fieldname"] 		= "alert_80pc";
				$structure["type"]			= "checkbox";
				$structure["options"]["no_fieldname"]	= "yes";
				$structure["options"]["label"]		= "Send customers email warnings when they hit 80% of their usage";
				$this->obj_form->add_input($structure);

				$structure = NULL;
				$structure["fieldname"] 		= "alert_100pc";
				$structure["type"]			= "checkbox";
				$structure["options"]["no_fieldname"]	= "yes";
				$structure["options"]["label"]		= "Send customers email warnings when they hit 100% of their usage";
				$this->obj_form->add_input($structure);

				$structure = NULL;
				$structure["fieldname"] 		= "alert_extraunits";
				$structure["type"]			= "input";
				$this->obj_form->add_input($structure);
													
				
				// subforms
				$this->obj_form->subforms["service_plan_alerts"] 	= array("alert_80pc", "alert_100pc", "alert_extraunits");



			break;


		
			case "bundle":
				// do not offer any advance billing methods	

				// general
				$structure = form_helper_prepare_radiofromdb("billing_mode", "SELECT id, name as label, description as label1 FROM billing_modes WHERE active='1' AND name NOT LIKE '%advance%'");
				$structure["options"]["req"]		= "yes";
				
				// replace all the -- joiners with <br> for clarity
				for ($i = 0; $i < count($structure["values"]); $i++)
				{
					$structure["translations"][ $structure["values"][$i] ] = str_replace("--", "<br><i>", $structure["translations"][ $structure["values"][$i] ]);
					$structure["translations"][ $structure["values"][$i] ] .= "</i>";
				}

				$this->obj_form->add_input($structure);


			
				// subforms
				$this->obj_form->subforms["service_plan"]		= array("name_service", "price", "price_setup", "discount", "billing_cycle", "billing_mode");
			break;


			case "phone_single":
			case "phone_tollfree":
			case "phone_trunk":
				/*
					Phones services are plans that get call cost values from rate tables.
				*/
				

				// general
				$structure = form_helper_prepare_radiofromdb("billing_mode", "SELECT id, name as label, description as label1 FROM billing_modes WHERE active='1' AND name NOT LIKE '%advance%'");
				$structure["options"]["req"]		= "yes";
				
				// replace all the -- joiners with <br> for clarity
				for ($i = 0; $i < count($structure["values"]); $i++)
				{
					$structure["translations"][ $structure["values"][$i] ] = str_replace("--", "<br><i>", $structure["translations"][ $structure["values"][$i] ]);
					$structure["translations"][ $structure["values"][$i] ] .= "</i>";
				}

				$this->obj_form->add_input($structure);



				// CDR info
				$structure = NULL;
				$structure["fieldname"]		= "cdr_information";
				$structure["type"]		= "message";
				$structure["defaultvalue"]	= "<i>For phone services, call charges are defined in rate tables - you should setup general rate tables using the \"<a href=\"index.php?page=services/cdr-rates.php\">CDR Rate Tables</a>\" page. You can over-ride certain rates using the Rate Override page in the menu above.</i>";
				$this->obj_form->add_input($structure);

				$structure = form_helper_prepare_dropdownfromdb("id_rate_table", "SELECT id, rate_table_name as label FROM cdr_rate_tables");
				$structure["options"]["req"]		= "yes";
				$this->obj_form->add_input($structure);


				// DDI options
				if ($sql_plan_obj->data[0]["name"] == "phone_trunk")
				{
					$structure = NULL;
					$structure["fieldname"]		= "phone_ddi_info";
					$structure["type"]		= "message";
					$structure["defaultvalue"]	= "<i>Use these fields to define the number of DDIs included in the plan as well as the cost of each DDI that a customer may want in addition of what is included with the plan.</i>";
					$this->obj_form->add_input($structure);

					$structure = NULL;
					$structure["fieldname"]		= "phone_ddi_included_units";
					$structure["type"]		= "input";
					$structure["options"]["req"]	= "yes";
					$structure["defaultvalue"]	= 1;
					$this->obj_form->add_input($structure);

					$structure = NULL;
					$structure["fieldname"]		= "phone_ddi_price_extra_units";
					$structure["type"]		= "money";
					$this->obj_form->add_input($structure);
				}


				// trunk options
				if ($sql_plan_obj->data[0]["name"] == "phone_trunk" || $sql_plan_obj->data[0]["name"] == "phone_tollfree")
				{
					$structure = NULL;
					$structure["fieldname"]		= "phone_trunk_info";
					$structure["type"]		= "message";
					$structure["defaultvalue"]	= "<i>Define the number of trunks (concurrent calls) that are included in the service, as well as the cost of each additional trunk that a customer may have.</i>";
					$this->obj_form->add_input($structure);

					$structure = NULL;
					$structure["fieldname"]		= "phone_trunk_included_units";
					$structure["type"]		= "input";
					$structure["options"]["req"]	= "yes";
					$structure["defaultvalue"]	= 1;
					$this->obj_form->add_input($structure);

					$structure = NULL;
					$structure["fieldname"]		= "phone_trunk_price_extra_units";
					$structure["type"]		= "money";
					$this->obj_form->add_input($structure);
				}


				// subforms
				$this->obj_form->subforms["service_plan"]		= array("name_service", "price", "price_setup", "discount", "billing_cycle", "billing_mode");
				$this->obj_form->subforms["service_plan_cdr"]		= array("cdr_information", "id_rate_table");
				
				if ($sql_plan_obj->data[0]["name"] == "phone_trunk")
				{
					$this->obj_form->subforms["service_plan_ddi"]	= array("phone_ddi_info", "phone_ddi_included_units", "phone_ddi_price_extra_units");
				}

				if ($sql_plan_obj->data[0]["name"] == "phone_trunk" || $sql_plan_obj->data[0]["name"] == "phone_tollfree")
				{
					$this->obj_form->subforms["service_plan_trunks"]	= array("phone_trunk_info", "phone_trunk_included_units", "phone_trunk_price_extra_units");
				}
		
			break;

			
			case "generic_no_usage":
			default:
				// no extra fields to display

				// general
				$structure = form_helper_prepare_radiofromdb("billing_mode", "SELECT id, name as label, description as label1 FROM billing_modes WHERE active='1' AND name NOT LIKE '%telco%'");
				$structure["options"]["req"]		= "yes";
				
				// replace all the -- joiners with <br> for clarity
				for ($i = 0; $i < count($structure["values"]); $i++)
				{
					$structure["translations"][ $structure["values"][$i] ] = str_replace("--", "<br><i>", $structure["translations"][ $structure["values"][$i] ]);
					$structure["translations"][ $structure["values"][$i] ] .= "</i>";
				}

				$this->obj_form->add_input($structure);


			
				// subforms
				$this->obj_form->subforms["service_plan"]		= array("name_service", "price", "price_setup", "discount", "billing_cycle", "billing_mode");
			break;
		}



		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Save Changes";
		$this->obj_form->add_input($structure);


		// hidden data
		$structure = NULL;
		$structure["fieldname"] 	= "id_service";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->serviceid;
		$this->obj_form->add_input($structure);
	

		// define subforms
		$this->obj_form->subforms["hidden"]		= array("id_service");
		
		if (user_permissions_get("services_write"))
		{
			$this->obj_form->subforms["submit"]	= array("submit");
		}
		else
		{
			$this->obj_form->subforms["submit"]	= array();
		}


		/*
			Load Data
		*/

		// load service details
		$this->obj_form->sql_query = "SELECT * FROM `services` WHERE id='". $this->serviceid ."' LIMIT 1";
		$this->obj_form->load_data();

		// handle misconfiguration gracefully
		if (empty($this->obj_form->structure["billing_cycle"]["values"]))
		{
			$this->obj_form->structure["billing_cycle"]["type"]			= "text";
			$this->obj_form->structure["billing_cycle"]["defaultvalue"]		= "error_no_billing_cycles_available";
		}


		// load options data
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT option_name, option_value FROM services_options WHERE option_type='service' AND option_type_id='". $this->serviceid ."'";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$sql_obj->fetch_array();

			foreach ($sql_obj->data as $data_options)
			{
				if (isset($this->obj_form->structure[ $data_options["option_name"] ]))
				{
					$this->obj_form->structure[ $data_options["option_name"] ]["defaultvalue"] = $data_options["option_value"];
				}
			}
		}


	}


	function render_html()
	{
		// display form
		$this->obj_form->render_form();
	
		if (!user_permissions_get("services_write"))
		{
			format_msgbox("locked", "<p>Sorry, you do not have permissions to adjust this service.</p>");
		}
		
	}

} // end of services_form_plan







/*
	class: services_form_delete

	Generates forms for deleting unwanted services
*/
class services_form_delete
{
	var $serviceid;			// ID of the service entry

	var $obj_form;
	var $locked;


	function execute()
	{
		log_debug("services_form_delete", "Executing execute()");

		/*
			Check if service is in use/locked
		*/

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM services_customers WHERE serviceid='". $this->serviceid ."' LIMIT 1";
		$sql_obj->execute();
		
		if ($sql_obj->num_rows())
		{
			$this->locked = 1;
		}


		/*
			Define form structure
		*/
		$this->obj_form = New form_input;
		$this->obj_form->formname	= "service_delete";
		$this->obj_form->language	= $_SESSION["user"]["lang"];

		$this->obj_form->action		= "services/delete-process.php";
		$this->obj_form->method		= "POST";

		
		// basic details
		$structure = NULL;
		$structure["fieldname"] 	= "name_service";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "delete_confirm";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "Yes, I wish to delete this service and realise that once deleted the data can not be recovered.";
		$this->obj_form->add_input($structure);


		// ID
		$structure = NULL;
		$structure["fieldname"]		= "id_service";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->serviceid;
		$this->obj_form->add_input($structure);	


		// submit
		$structure = NULL;
		$structure["fieldname"]		= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Delete Service";
		$this->obj_form->add_input($structure);


		// load data
		$this->obj_form->sql_query = "SELECT name_service FROM services WHERE id='". $this->serviceid ."'";
		$this->obj_form->load_data();


		$this->obj_form->subforms["service_delete"]	= array("name_service");
		$this->obj_form->subforms["hidden"]		= array("id_service");

		if ($this->locked)
		{
			$this->obj_form->subforms["submit"] 	= array();
		}
		else
		{
			$this->obj_form->subforms["submit"] 	= array("delete_confirm", "submit");
		}
	}


	function render_html()
	{
		log_debug("services_form_delete", "Executing execute()");
		
		// display form
		$this->obj_form->render_form();

		if ($this->locked)
		{
			format_msgbox("locked", "<p>This service can not be deleted because it is currently active by various customers.</p>");
		}
	}
}







?>
