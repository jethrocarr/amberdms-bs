<?php
/*
	customers/service-edit.php
	
	access: customers_view
		customers_write

	Form to add or edit a customer service.
*/


require("include/services/inc_services.php");
require("include/services/inc_services_cdr.php");
require("include/services/inc_services_traffic.php");
require("include/customers/inc_customers.php");


class page_output
{
	var $obj_service;
	var $obj_customer;
	
	var $obj_menu_nav;
	var $obj_form;
	
	var $num_ddi_rows;
	var $locked_datechange;

	

	function page_output()
	{
		// define page dependencies
		$this->requires["javascript"][]		= "include/javascript/services.js";
		$this->requires["javascript"][]		= "include/customers/javascript/service-edit.js";
		$this->requires["css"][]		= "include/customers/css/service-edit.css";
		
		
		$this->obj_customer				= New customer_services;


		// fetch variables
		$this->obj_customer->id				= @security_script_input('/^[0-9]*$/', $_GET["id_customer"]);
		$this->obj_customer->id_service_customer	= @security_script_input('/^[0-9]*$/', $_GET["id_service_customer"]);


		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;
	
		if ($this->obj_customer->id_service_customer)
		{
			// load service data
			$this->obj_customer->load_data_service();


			// edit existing service
			$this->obj_menu_nav->add_item("Return to Customer Services Page", "page=customers/services.php&id=". $this->obj_customer->id ."");
			$this->obj_menu_nav->add_item("Service Details", "page=customers/service-edit.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $this->obj_customer->id_service_customer ."", TRUE);
			$this->obj_menu_nav->add_item("Service History", "page=customers/service-history.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $this->obj_customer->id_service_customer ."");

			if (in_array($this->obj_customer->obj_service->data["typeid_string"], array("phone_single", "phone_tollfree", "phone_trunk")))
			{
				$this->obj_menu_nav->add_item("CDR Override", "page=customers/service-cdr-override.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $this->obj_customer->id_service_customer ."");
			}

			if ($this->obj_customer->obj_service->data["typeid_string"] == "phone_trunk")
			{
				$this->obj_menu_nav->add_item("DDI Configuration", "page=customers/service-ddi.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $this->obj_customer->id_service_customer ."");
			}

			if ($this->obj_customer->obj_service->data["typeid_string"] == "data_traffic")
			{
				$this->obj_menu_nav->add_item("IPv4 Addresses", "page=customers/service-ipv4.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $this->obj_customer->id_service_customer ."");
			}

			if (user_permissions_get("customers_write"))
			{
				$this->obj_menu_nav->add_item("Service Delete", "page=customers/service-delete.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $this->obj_customer->id_service_customer ."");
			}
		}
		else
		{
			// new service
			$this->obj_menu_nav->add_item("Customer's Details", "page=customers/view.php&id=". $this->obj_customer->id ."");

			if (sql_get_singlevalue("SELECT value FROM config WHERE name='MODULE_CUSTOMER_PORTAL' LIMIT 1") == "enabled")
			{
				$this->obj_menu_nav->add_item("Portal Options", "page=customers/portal.php&id=". $this->obj_customer->id ."");
			}

			$this->obj_menu_nav->add_item("Customer's Journal", "page=customers/journal.php&id=". $this->obj_customer->id ."");
			$this->obj_menu_nav->add_item("Customer's Attributes", "page=customers/attributes.php&id_customer=". $this->obj_customer->id ."");
			$this->obj_menu_nav->add_item("Customer's Orders", "page=customers/orders.php&id_customer=". $this->obj_customer->id ."");
			$this->obj_menu_nav->add_item("Customer's Invoices", "page=customers/invoices.php&id=". $this->obj_customer->id ."");
			$this->obj_menu_nav->add_item("Customer's Credit", "page=customers/credit.php&id_customer=". $this->obj_customer->id ."");
			$this->obj_menu_nav->add_item("Customer's Services", "page=customers/services.php&id=". $this->obj_customer->id ."", TRUE);
			$this->obj_menu_nav->add_item("Resellers Customers", "page=customers/reseller.php&id_customer=". $this->obj_customer->id ."");

			if (user_permissions_get("customers_write"))
			{
				$this->obj_menu_nav->add_item("Delete Customer", "page=customers/delete.php&id=". $this->obj_customer->id ."");
			}

		}
	}



	function check_permissions()
	{
		return user_permissions_get("customers_view");
	}



	function check_requirements()
	{
		// verify that customer exists
		if (!$this->obj_customer->verify_id())
		{
			log_write("error", "page_output", "The requested customer (". $this->obj_customer->id .") does not exist - possibly the customer has been deleted.");
			return 0;
		}


		// verify that the service-customer entry exists
		if (isset($this->ob_customer->id_service_customer))
		{
			if (!$this->obj_customer->verify_id_service_customer())
			{
				log_write("error", "page_output", "The requested service (". $this->obj_customer->id_service_customer .") was not found and/or does not match the selected customer");
				return 0;
			}
		}


		// obtain edit status
		$this->locked_datechange = $this->obj_customer->service_check_datechangesafe();

		return 1;
	}



	function execute()
	{
		/*
			Define form structure
		*/
		$this->obj_form = New form_input;
		$this->obj_form->formname = "service_view";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "customers/service-edit-process.php";
		$this->obj_form->method = "post";

	
		// general
		if ($this->obj_customer->id_service_customer)
		{
			/*
				An existing service is being adjusted
			*/

			// general
			$structure = NULL;
			$structure["fieldname"]		= "id_service_customer";
			$structure["type"]		= "text";
			$structure["defaultvalue"]	= $this->obj_customer->id_service_customer;
			$this->obj_form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"]		= "service_parent";
			$structure["type"]		= "text";
			$structure["options"]["nohidden"] = 1;
			$structure["defaultvalue"]	= "<a href=\"index.php?page=services/view.php&id=". $this->obj_customer->obj_service->id ."\">". sql_get_singlevalue("SELECT name_service as value FROM services WHERE id='". $this->obj_customer->obj_service->id ."' LIMIT 1") ."</a>";
			$this->obj_form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"]		= "name_service";
			$structure["type"]		= "input";
			$structure["options"]["req"]	= "yes";
			$this->obj_form->add_input($structure);
	
			$structure = NULL;
			$structure["fieldname"] 	= "description";
			$structure["type"]		= "textarea";
			$this->obj_form->add_input($structure);

			$this->obj_form->subforms["service_edit"]	= array("id_service_customer", "service_parent", "name_service", "description");



			// service controls
			$structure = NULL;
			$structure["fieldname"] 	= "control_help";
			$structure["type"]		= "message";
			$structure["defaultvalue"]	= "When disabling services, the best approach is to set the last period date, and ABS will correctly bill to that final date, handle usage/partial periods and issue a final invoice, before disabling the service automatically - however it is possible to disable a service immediently if so desired by using the active checkbox.";
			$this->obj_form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"] 	= "active";
			$structure["type"]		= "checkbox";
			$structure["options"]["label"]	= "Service is enabled";
			$this->obj_form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"] 		= "date_period_last";
			$structure["type"]			= "date";
			$structure["options"]["label"]		= " Earliest termination date is: ". time_format_humandate( sql_get_singlevalue("SELECT date_end as value FROM services_customers_periods WHERE id_service_customer='". $this->obj_customer->id_service_customer ."' ORDER BY date_end DESC LIMIT 1") );
			$this->obj_form->add_input($structure);

			$this->obj_form->subforms["service_controls"]	= array("control_help", "active", "date_period_last");



			// billing
			$structure = NULL;
			$structure["fieldname"]		= "billing_cycle_string";
			$structure["type"]		= "text";
			$this->obj_form->add_input($structure);


			if ($this->locked_datechange)
			{
				// the service has been billed, the start date is fixed.
				$structure = NULL;
				$structure["fieldname"] 	= "date_period_first";
				$structure["type"]		= "text";
				$this->obj_form->add_input($structure);
			
				$structure = NULL;
				$structure["fieldname"] 	= "date_period_next";
				$structure["type"]		= "text";
				$this->obj_form->add_input($structure);
			}
			else
			{
				// service has not yet been billed, so the dates can still be adjusted
				$structure = NULL;
				$structure["fieldname"] 	= "date_period_first";
				$structure["type"]		= "date";
				$this->obj_form->add_input($structure);
			
				$structure = NULL;
				$structure["fieldname"] 	= "date_period_next";
				$structure["type"]		= "text";
				$this->obj_form->add_input($structure);
			}

			$this->obj_form->subforms["service_billing"]	= array("billing_cycle_string", "date_period_first", "date_period_next");

			$pos = stristr($this->obj_customer->obj_service->data["typeid_string"], "phone_");
			if($pos !== FALSE && $pos == 0) {

				$structure = NULL;
				$structure["fieldname"]         = "billing_cdr_csv_output";
				$structure["options"]["label"]  = " ". lang_trans("billing_cdr_csv_output_help");
				$structure["type"]              = "checkbox";
				$this->obj_form->add_input($structure);

				$this->obj_form->subforms["service_billing"][] = "billing_cdr_csv_output";

			}

			if (!$this->obj_customer->service_get_is_bundle_item())
			{
				// price customisation
				$structure = NULL;
				$structure["fieldname"]		 	= "price";
				$structure["type"]			= "money";
				$structure["options"]["req"]		= "yes";
				$this->obj_form->add_input($structure);
			
				$structure = NULL;
				$structure["fieldname"] 		= "discount";
				$structure["type"]			= "input";
				$structure["options"]["width"]		= 50;
				$structure["options"]["label"]		= " %";
				$structure["options"]["max_length"]	= "6";
				$this->obj_form->add_input($structure);

				$this->obj_form->subforms["service_price"]	= array("price", "discount");


				// setup charges - only display if the service is inactive
				if (!sql_get_singlevalue("SELECT active as value FROM services_customers WHERE id='". $this->obj_customer->id_service_customer ."' LIMIT 1"))
				{
					$structure = NULL;
					$structure["fieldname"]			= "info_setup_help";
					$structure["type"]			= "message";
					$structure["defaultvalue"]		= "<p>". lang_trans("info_setup_help") ."</p>";
					$this->obj_form->add_input($structure);

					$structure = NULL;
					$structure["fieldname"] 		= "price_setup";
					$structure["type"]			= "money";
					$structure["options"]["req"]		= "yes";
					$this->obj_form->add_input($structure);

					$structure = NULL;
					$structure["fieldname"] 		= "discount_setup";
					$structure["type"]			= "input";
					$structure["options"]["width"]		= 50;
					$structure["options"]["label"]		= " %";
					$structure["options"]["max_length"]	= "6";
					$structure["defaultvalue"]		= $this->obj_customer->obj_service->data["discount"];
					$this->obj_form->add_input($structure);

					$this->obj_form->subforms["service_setup"]	= array("info_setup_help", "price_setup", "discount_setup");
				}
				else
				{
					$structure = NULL;
					$structure["fieldname"]			= "info_setup_help";
					$structure["type"]			= "message";
					$structure["defaultvalue"]		= "<p>A setup fee of ". format_money($this->obj_customer->obj_service->data["price_setup"]) ." was charged for this service.</p>";
					$this->obj_form->add_input($structure);
					
					$this->obj_form->subforms["service_setup"]	= array("info_setup_help");
				}

			} // end if not bundle


			// service-type specific sections
			switch ($this->obj_customer->obj_service->data["typeid_string"])
			{
				case "bundle":
					/*
						Bundle Service

						Display a hyperlinked list of all the component services belonging to
						the bundle.
					*/
					
					$structure = NULL;
					$structure["fieldname"]		= "bundle_msg";
					$structure["type"]		= "message";
					$structure["defaultvalue"]	= "<p>This service is a bundle, containing a number of other services. Note that enabling/disabling or deleting this bundle service will affect all the component services below.</p>";
					$this->obj_form->add_input($structure);


					// fetch all the items for the bundle that have been setup for this customer and
					// display some details in a table inside of a form field. (kinda ugly rendering hack, but works OK)

					$structure = NULL;
					$structure["fieldname"]		= "bundle_components";
					$structure["type"]		= "message";
					$structure["defaultvalue"]	= "<table class=\"table_highlight\">";


					$sql_obj		= New sql_query;
					$sql_obj->string	= "SELECT id, serviceid as id_service FROM services_customers WHERE bundleid='". $this->obj_customer->id_service_customer ."'";
					$sql_obj->execute();

					if ($sql_obj->num_rows())
					{
						$sql_obj->fetch_array();

						foreach ($sql_obj->data as $data_component)
						{
							$obj_component			= New service;
							$obj_component->id		= $data_component["id_service"];
							$obj_component->option_type	= "customer";
							$obj_component->option_type_id	= $data_component["id"];

							$obj_component->load_data();
							$obj_component->load_data_options();

							if (sql_get_singlevalue("SELECT active as value FROM services_customers WHERE id='". $data_component["id"] ."' LIMIT 1"))
							{
								$obj_component->active_status_string	= "<td class=\"table_highlight_info\">active</td>";
							}
							else
							{
								$obj_component->active_status_string	= "<td class=\"table_highlight_important\">disabled</td>";
							}

							$structure["defaultvalue"]	.= "<tr>"
											."<td>Bundle Component: <b>". $obj_component->data["name_service"] ."</b></td>"
											. $obj_component->active_status_string
											."<td>". $obj_component->data["description"] ."</td>"
											."<td><a class=\"button_small\" href=\"index.php?page=customers/service-edit.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $obj_component->option_type_id ."\">View Service</a></td>"
											."</tr>";
						}
					}

					$structure["defaultvalue"] .= "</table>";
					$this->obj_form->add_input($structure);


					$this->obj_form->subforms["service_bundle"]	= array("bundle_msg", "bundle_components");		

				break;


				case "license":

					$structure = NULL;
					$structure["fieldname"] 	= "quantity_msg";
					$structure["type"]		= "message";
					$structure["defaultvalue"]	= "<i>Because this is a license service, you need to specifiy how many license in the box below. Note that this will only affect billing from the next invoice. If you wish to charge for usage between now and the next invoice, you will need to generate a manual invoice.</i>";
					$this->obj_form->add_input($structure);
				
					$structure = NULL;
					$structure["fieldname"] 	= "quantity";
					$structure["type"]		= "input";
					$structure["options"]["req"]	= "yes";
					$this->obj_form->add_input($structure);

					$this->obj_form->subforms["service_options_licenses"]	= array("quantity_msg", "quantity");
				break;


				case "data_traffic":

					// help info
					$structure = NULL;
					$structure["fieldname"]		= "traffic_cap_help";
					$structure["type"]		= "message";
					$structure["defaultvalue"]	= "<p>If desired, traffic types, data caps and overage changes can be overridden here to customise a service for a particular customer.</p>";
					$this->obj_form->add_input($structure);

					$this->obj_form->subforms["traffic_caps"][] = "traffic_cap_help";


					// header
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


					$this->obj_form->subforms_grouped["traffic_caps"]["traffic_cap_header"][]		= "traffic_cap_header_name";
					$this->obj_form->subforms_grouped["traffic_caps"]["traffic_cap_header"][]		= "traffic_cap_header_mode";
					$this->obj_form->subforms_grouped["traffic_caps"]["traffic_cap_header"][]		= "traffic_cap_header_units_included";
					$this->obj_form->subforms_grouped["traffic_caps"]["traffic_cap_header"][]		= "traffic_cap_header_units_price";

					$this->obj_form->subforms["traffic_caps"][] = "traffic_cap_header";

					
					// fetch service unitname
					$unitname = sql_get_singlevalue("SELECT name as value FROM service_units WHERE id='". $this->obj_customer->obj_service->data["units"] ."'");



					// manual load of override values for data cap services
					$data_traffic_overrides = New traffic_caps;

					$data_traffic_overrides->id_service		= $this->obj_customer->obj_service->id;
					$data_traffic_overrides->id_service_customer	= $this->obj_customer->id_service_customer;

					$data_traffic_overrides->load_data_traffic_caps();
					$data_traffic_overrides->load_data_override_caps();


					for ($i=0; $i < $data_traffic_overrides->data_num_rows; $i++)
					{
						// define form fields
						$structure = NULL;
						$structure["fieldname"]		= "traffic_cap_". $i ."_id";
						$structure["type"]		= "hidden";
						$structure["defaultvalue"]	= $data_traffic_overrides->data[$i]["id_type"];
						$this->obj_form->add_input($structure);

						$structure = NULL;
						$structure["fieldname"]		= "traffic_cap_". $i ."_name";
						$structure["type"]		= "text";
						$structure["defaultvalue"]	= $data_traffic_overrides->data[$i]["type_name"];
						$this->obj_form->add_input($structure);
						
						$structure = NULL;
						$structure["fieldname"]		= "traffic_cap_". $i ."_mode";
						$structure["type"]		= "dropdown";

						$structure["values"][0]		= "unlimited";
						$structure["values"][1]		= "capped";

						$structure["defaultvalue"]	= $data_traffic_overrides->data[$i]["cap_mode"];
						$structure["options"]["width"]	= "100";
						$this->obj_form->add_input($structure);

						$structure = NULL;
						$structure["fieldname"]		= "traffic_cap_". $i ."_units_included";
						$structure["type"]		= "input";
						$structure["options"]["width"]	= "100";
						$structure["options"]["label"]	= " $unitname";
						$structure["defaultvalue"]	= $data_traffic_overrides->data[$i]["cap_units_included"];
						$this->obj_form->add_input($structure);

						$structure = NULL;
						$structure["fieldname"]		= "traffic_cap_". $i ."_units_price";
						$structure["type"]		= "money";
						$structure["options"]["label"]	= " per $unitname additional usage.";
						$structure["defaultvalue"]	= $data_traffic_overrides->data[$i]["cap_units_price"];
						$this->obj_form->add_input($structure);

						$structure = NULL;
						$structure["fieldname"]		= "traffic_cap_". $i ."_override";
						$structure["type"]		= "text";
						$structure["options"]["nohidden"] = 1;

						if (!empty($data_traffic_overrides->data[$i]["override"]))
						{
							$structure["defaultvalue"] = "<span class=\"table_highlight_important\">SERVICE OVERRIDE</span>";
						}

						$this->obj_form->add_input($structure);


						$this->obj_form->subforms_grouped["traffic_caps"]["traffic_cap_". $i][]		= "traffic_cap_". $i ."_name";
						$this->obj_form->subforms_grouped["traffic_caps"]["traffic_cap_". $i][]		= "traffic_cap_". $i ."_mode";
						$this->obj_form->subforms_grouped["traffic_caps"]["traffic_cap_". $i][]		= "traffic_cap_". $i ."_units_included";
						$this->obj_form->subforms_grouped["traffic_caps"]["traffic_cap_". $i][]		= "traffic_cap_". $i ."_units_price";
						$this->obj_form->subforms_grouped["traffic_caps"]["traffic_cap_". $i][]		= "traffic_cap_". $i ."_override";
						$this->obj_form->subforms_grouped["traffic_caps"]["traffic_cap_". $i][]		= "traffic_cap_". $i ."_id";

						$this->obj_form->subforms["traffic_caps"][] = "traffic_cap_". $i;
					}

					unset($data_traffic_overrides);


				break;	


				case "phone_single":

					// single DDI
					$structure = NULL;
					$structure["fieldname"]		= "phone_ddi_info";
					$structure["type"]		= "message";
					$structure["defaultvalue"]	= "<i>You must set the DDI of the phone here for billing purposes</i>";
					$this->obj_form->add_input($structure);

					$structure = NULL;
					$structure["fieldname"]		= "phone_ddi_single";
					$structure["type"]		= "input";
					$structure["options"]["req"]	= "yes";
					$structure["options"]["help"]	= "eg: 6412345678";
					$this->obj_form->add_input($structure);


					if ($GLOBALS["config"]["SERVICE_CDR_LOCAL"] == "prefix")
					{
						/*
							Prefix-based local rates are easy, we define the prefix number and
							from that we match when doing the rate billing.
						*/
						$structure = NULL;
						$structure["fieldname"]		= "phone_local_prefix";
						$structure["type"]		= "input";
						$structure["options"]["req"]	= "yes";
						$structure["options"]["help"]	= "eg: 64123";
						$structure["options"]["label"]	= " Any calls to numbers matching this prefix will be charged at LOCAL rate.";
						$this->obj_form->add_input($structure);
					}
					else
					{
						/*
							Handling destination based local calling rates is complex, since we need to:
						 	- fetch a list of all destinations
								- include overrides for the service
								- include overrides for the customer
								- include base zones
							- display the label instructing on use
							- handle regions that have no destination/description
						*/


						// fetch all rates, including override rates
						$obj_local_rates				= New cdr_rate_table_rates_override();

						$obj_local_rates->id				= $this->obj_customer->obj_service->data["id_rate_table"];
						$obj_local_rates->option_type			= "customer";
						$obj_local_rates->option_type_id		= $this->obj_customer->id_service_customer;
						$obj_local_rates->option_type_serviceid		= $this->obj_customer->obj_service->id;

						$obj_local_rates->load_data_rate_all();
						$obj_local_rates->load_data_rate_all_override();

						// aggregate the destination
						$cdr_destinations		= array();
						$cdr_destinations["NONE"]	= 1;		// placeholder for no local region

						foreach ($obj_local_rates->data["rates"] as $rate)
						{
							if (!empty($rate["rate_description"]))
							{
								$cdr_destinations[ $rate["rate_description"] ] = 1;
							}
						}

						$cdr_destinations = array_keys($cdr_destinations);
						sort($cdr_destinations);


						// generate dropdown object
						$structure = NULL;
						$structure["fieldname"]		= "phone_local_prefix";
						$structure["type"]		= "dropdown";
						$structure["values"]		= $cdr_destinations;
						$structure["options"]["req"]	= "yes";
						$structure["options"]["label"]	= " Charge calls to any prefix in this region as \"LOCAL\" call rates.";
						$this->obj_form->add_input($structure);
					}


					$this->obj_form->subforms["service_options_ddi"]	= array("phone_ddi_info", "phone_ddi_single", "phone_local_prefix");

				break;



				case "phone_tollfree":

					// single DDI
					$structure = NULL;
					$structure["fieldname"]		= "phone_ddi_info";
					$structure["type"]		= "message";
					$structure["defaultvalue"]	= "<i>You must set the DDI of the tollfree number here for billing purposes.</i>";
					$this->obj_form->add_input($structure);

					$structure = NULL;
					$structure["fieldname"]		= "phone_ddi_single";
					$structure["type"]		= "input";
					$structure["options"]["req"]	= "yes";
					$this->obj_form->add_input($structure);

					$this->obj_form->subforms["service_options_ddi"]	= array("phone_ddi_info", "phone_ddi_single");


					// trunk options
					$structure = NULL;
					$structure["fieldname"]		= "phone_trunk_info";
					$structure["type"]		= "message";
					$structure["defaultvalue"]	= "<i>Define the number of trunks (concurrent calls) that are included in the service, depending on the service plan, there may be additional charges concurred.</i>";
					$this->obj_form->add_input($structure);

					$structure = NULL;
					$structure["fieldname"]		= "phone_trunk_included_units";
					$structure["type"]		= "input";
					$structure["options"]["req"]	= "yes";
					$structure["options"]["width"]	= "100";
					$structure["options"]["label"]	= " trunks included in service base fee.";
					$this->obj_form->add_input($structure);

					$structure = NULL;
					$structure["fieldname"]		= "phone_trunk_quantity";
					$structure["type"]		= "input";
					$structure["options"]["req"]	= "yes";
					$structure["options"]["width"]	= "100";
					$structure["options"]["label"]	= " trunks assigned to customer (any more than included units will be charged at price per additional trunk).";
					$this->obj_form->add_input($structure);

					$structure = NULL;
					$structure["fieldname"]		= "phone_trunk_price_extra_units";
					$structure["type"]		= "money";
					$this->obj_form->add_input($structure);



					$this->obj_form->subforms["service_options_trunks"]	= array("phone_trunk_info", "phone_trunk_included_units", "phone_trunk_quantity", "phone_trunk_price_extra_units");

				break;



				case "phone_trunk":

/*
	
	TODO: Javascript-based DDI Configuration


					//create html string to input into message field to show DDIs
					$html_string = "<div id=\"ddi_form\"><table id=\"ddi_table\"  cellspacing=\"0\"><tr class=\"table_highlight\">
								<td><b>" .lang_trans("ddi_start"). "</b></td>
								<td><b>" .lang_trans("ddi_finish"). "</b></td>
								<td><b>" .lang_trans("description"). "</b></td>
								<td>&nbsp;</td></tr>";
					
					//work out the number of DDI rows needed
					if (!isset($_SESSION["error"]["form"][$this->obj_form->formname]))
					{
						$sql_obj		= New sql_query;
						$sql_obj->string	= "SELECT * FROM services_customers_ddi WHERE id_service_customer = '" .$this->obj_customer->id_service_customer. "'";
						$sql_obj->execute();
				
						if ($sql_obj->num_rows())
						{
							$sql_obj->fetch_array();
					
							if ($sql_obj->data_num_rows < 2)
							{
								$this->num_ddi_rows = 2;
							}
							else
							{
								$this->num_ddi_rows = $sql_obj->data_num_rows+1;
							}
						}
					}
					else
					{
						$this->num_ddi_rows = @security_script_input('/^[0-9]*$/', $_SESSION["error"]["num_ddi_rows"])+1;
					}
					
					$structure = NULL;
					$structure["fieldname"]		= "num_ddi_rows";
					$structure["type"]		= "hidden";
					$structure["defaultvalue"]	= $this->num_ddi_rows;
					$this->obj_form->add_input($structure);
					$this->obj_form->subforms["hidden"][] = "num_ddi_rows";
					
					for ($i= 0; $i < $this->num_ddi_rows; $i++)
					{
						$html_string .= "<tr class=\"table_highlight\">
									<td><input type=\"text\" name=\"ddi_start_$i\" ";
						if (isset($sql_obj->data[$i]["ddi_start"]))
						{
							$html_string .= " value=\"" .$sql_obj->data[$i]["ddi_start"]. "\" /></td>";
						}
						else
						{
							$html_string .= " value=\"\" /></td>";
						}
						
						$html_string .= "<td><input type=\"text\" name=\"ddi_finish_$i\" ";
						if (isset($sql_obj->data[$i]["ddi_finish"]))
						{
							$html_string .= " value=\"" .$sql_obj->data[$i]["ddi_finish"]. "\" /></td>";
						}
						else
						{
							$html_string .= " value=\"\" /></td>";
						}
						
						$html_string .= "<td><textarea name=\"description_$i\">";
						if (isset($sql_obj->data[$i]["description"]))
						{
							$html_string .= $sql_obj->data[$i]["description"]. "</textarea></td>";
						}
						else
						{
							$html_string .= "</textarea></td>";
						}
						
						$html_string .= "<td><input type=\"hidden\" name=\"delete_$i\" ";
						if (isset($_SESSION["error"]["form"][$this->obj_form->formname]))
						{
							$html_string .= " value=\"" .security_script_input_predefined("any",$_SESSION["error"]["delete_$i"]). "\" />";
						}
						else
						{
							$html_string .= " value=\"false\" />";
						}
						$html_string .= "<input type=\"hidden\" name=\"id_$i\" ";
						if (isset($_SESSION["error"]["form"][$this->obj_form->formname]))
						{
							$html_string .= " value=\"" .security_script_input_predefined("any",$_SESSION["error"]["id_$i"]). "\" />";
						}
						else
						{
							$html_string .= " value=\"\" />";
						}
						$html_string .= "<a href=\"\" id=\"delete_link_$i\">delete</a></td></tr>";
					}
					
					$html_string .= "</table></div>";
*/
				
					// DDI options
					$structure = NULL;
					$structure["fieldname"]		= "phone_ddi_info";
					$structure["type"]		= "message";
					$structure["defaultvalue"]	= "<p>This is a phone trunk service - with this service you are able to have multiple individual DDIs and DDI ranges. Note that it is important to define all the DDIs belonging to this customer, otherwise they may be able to make calls without being charged.<br><br><a class=\"button_small\" href=\"index.php?page=customers/service-ddi.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $this->obj_customer->id_service_customer ."\">Configure Customer's DDIs</a></p>";
//					$structure["defaultvalue"]	= "<p>This is a phone trunk service - with this service you are able to have multiple individual DDIs and DDI ranges. Note that it is important to define all the DDIs belonging to this customer, otherwise they may be able to make calls without being charged.<br><br>" .$html_string. "</p>";
					$this->obj_form->add_input($structure);

					$structure = NULL;
					$structure["fieldname"]		= "phone_ddi_included_units";
					$structure["type"]		= "input";
					$structure["options"]["req"]	= "yes";
					$structure["options"]["width"]	= "100";
					$structure["options"]["label"]	= " DDI numbers included in service plan fee";
					$this->obj_form->add_input($structure);

					$structure = NULL;
					$structure["fieldname"]		= "phone_ddi_price_extra_units";
					$structure["type"]		= "money";
					$this->obj_form->add_input($structure);



					// trunk options
					$structure = NULL;
					$structure["fieldname"]		= "phone_trunk_info";
					$structure["type"]		= "message";
					$structure["defaultvalue"]	= "<p>Define the number of trunks (concurrent calls) that are included in the service, depending on the service plan, there may be additional charges concurred.</p>";
					$this->obj_form->add_input($structure);

					$structure = NULL;
					$structure["fieldname"]		= "phone_trunk_included_units";
					$structure["type"]		= "input";
					$structure["options"]["req"]	= "yes";
					$structure["options"]["width"]	= "100";
					$structure["options"]["label"]	= " trunks included in service base fee.";
					$this->obj_form->add_input($structure);

					$structure = NULL;
					$structure["fieldname"]		= "phone_trunk_quantity";
					$structure["type"]		= "input";
					$structure["options"]["req"]	= "yes";
					$structure["options"]["width"]	= "100";
					$structure["options"]["label"]	= " trunks assigned to customer (any more than included units will be charged at price per additional trunk).";
					$this->obj_form->add_input($structure);

					$structure = NULL;
					$structure["fieldname"]		= "phone_trunk_price_extra_units";
					$structure["type"]		= "money";
					$this->obj_form->add_input($structure);


					$this->obj_form->subforms["service_options_ddi"]	= array("phone_ddi_info", "phone_ddi_included_units", "phone_ddi_price_extra_units");
					$this->obj_form->subforms["service_options_trunks"]	= array("phone_trunk_info", "phone_trunk_included_units", "phone_trunk_quantity", "phone_trunk_price_extra_units");

				break;


			}




			/*
				Check if item belongs to a bundle - if it does, display
				additional information fields.
			*/
			if ($parentid = $this->obj_customer->service_get_is_bundle_item())
			{
				// info about bundle
				$structure = NULL;
				$structure["fieldname"]		= "bundle_item_msg";
				$structure["type"]		= "message";
				$structure["defaultvalue"]	= "<p>This service is a part of a bundle assigned to this customer - you can enable/disable this service independently, but the customer will still be billed the same base bundle plan fee.</p>";
				$this->obj_form->add_input($structure);



				// link to parent item
				$obj_component			= New service;
				$obj_component->option_type	= "customer";
				$obj_component->option_type_id	= $parentid;

				$obj_component->verify_id_options();
				$obj_component->load_data();
				$obj_component->load_data_options();

				$structure = NULL;
				$structure["fieldname"]		= "bundle_item_parent";
				$structure["type"]		= "message";
				$structure["defaultvalue"]	= "<table class=\"table_highlight\">"
								."<tr>"
								."<td>Bundle Parent: <b>". $obj_component->data["name_service"] ."</b></td>"
								."<td>". $obj_component->data["description"] ."</td>"
								."<td><a class=\"button_small\" href=\"index.php?page=customers/service-edit.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $obj_component->option_type_id ."\">View Service</a></td>"
								."</tr>"
								."</table>";
				$this->obj_form->add_input($structure);


				$this->obj_form->subforms["service_bundle_item"]	= array("bundle_item_msg", "bundle_item_parent");
			}

		}
		else
		{
			/*
				A new service is being added
			*/


			// basic attributes
			$structure = form_helper_prepare_dropdownfromdb("serviceid", "SELECT id, name_service as label FROM services WHERE active='1' ORDER BY name_service");
			$structure["options"]["req"] = "yes";
			$this->obj_form->add_input($structure);
		
			$structure = NULL;
			$structure["fieldname"] 	= "date_period_first";
			$structure["type"]		= "date";
			$structure["options"]["req"]	= "yes";
			$structure["defaultvalue"]	= date("Y-m-d");
			$this->obj_form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"] 	= "description";
			$structure["type"]		= "textarea";
			$this->obj_form->add_input($structure);

			$this->obj_form->subforms["service_add"]	= array("serviceid", "date_period_first", "description");




			// migration mode options - these allow some nifty tricks like creating
			// a service period in the previous month to be able to bill for past usage
			if ($GLOBALS['config']['SERVICE_MIGRATION_MODE'] == 1)
			{
				$structure = NULL;
				$structure["fieldname"] 		= "migration_date_period_usage_override";
				$structure["type"]			= "radio";
				$structure["values"]			= array("migration_use_period_date", "migration_use_usage_date");
				$structure["defaultvalue"]		= "migration_use_period_date";
				$this->obj_form->add_input($structure);

				$structure = NULL;
				$structure["fieldname"] 		= "migration_date_period_usage_first";
				$structure["type"]			= "date";
				$this->obj_form->add_input($structure);

				$this->obj_form->add_action("migration_date_period_usage_override", "default", "migration_date_period_usage_first", "hide");
				$this->obj_form->add_action("migration_date_period_usage_override", "migration_use_usage_date", "migration_date_period_usage_first", "show");


				$this->obj_form->subforms["service_migration"]	= array("migration_date_period_usage_override", "migration_date_period_usage_first");
			}
		}



		// hidden values
		$structure = NULL;
		$structure["fieldname"]		= "id_customer";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->obj_customer->id;
		$this->obj_form->add_input($structure);
		

		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";

		if ($this->obj_customer->id_service_customer)
		{
			$structure["defaultvalue"]	= "Save Changes";
		}
		else
		{
			$structure["defaultvalue"]	= "Add Service";
		}
		$this->obj_form->add_input($structure);



		// define base subforms	
		$this->obj_form->subforms["hidden"][] = "id_customer";


		if (user_permissions_get("customers_write"))
		{
			$this->obj_form->subforms["submit"] = array("submit");
		}
		else
		{
			$this->obj_form->subforms["submit"] = array();
		}


		// fetch the form data if editing
		if ($this->obj_customer->id_service_customer)
		{
			// fetch service data
			$this->obj_form->structure["description"]["defaultvalue"]	= $this->obj_customer->obj_service->data["description"];
			$this->obj_form->structure["name_service"]["defaultvalue"]	= $this->obj_customer->obj_service->data["name_service"];

			foreach (array_keys($this->obj_customer->obj_service->data) as $option_name)
			{
				if (isset($this->obj_form->structure[ $option_name ]))
				{
					$this->obj_form->structure[ $option_name ]["defaultvalue"] = $this->obj_customer->obj_service->data[ $option_name ];
				}
			}

			// fetch DB data
			$this->obj_form->sql_query = "SELECT active, date_period_first, date_period_next, date_period_last, quantity FROM `services_customers` WHERE id='". $this->obj_customer->id_service_customer ."' LIMIT 1";
			$this->obj_form->load_data();


		}
		
			
		if (error_check())
		{
			// load any data returned due to errors
			$this->obj_form->load_data_error();
		}

	}


	function render_html()
	{
		// title/summary
		if ($this->obj_customer->obj_service->option_type_id)
		{
			print "<h3>EDIT SERVICE</h3><br>";
			print "<p>This page allows you to modifiy a customer service.</p>";
		
			// service summary
			$this->obj_customer->service_render_summarybox();
		}
		else
		{
			print "<h3>ADD SERVICE TO CUSTOMER ACCOUNT</h3><br>";
			print "<p>This page allows you to setup a new service for the selected customer.</p>";
		}


		// display the form
		$this->obj_form->render_form();

		
		if (!user_permissions_get("customers_write"))
		{
			format_msgbox("locked", "<p>Sorry, you do not have permissions to make changes to customer services</p>");
		}
	}

} // end page_output



?>
