<?php
/*
	customers/service-edit.php
	
	access: customers_view
		customers_write

	Form to add or edit a customer service.
*/


require("include/services/inc_services.php");
require("include/customers/inc_customers.php");


class page_output
{
	var $obj_service;
	var $obj_customer;
	
	var $obj_menu_nav;
	var $obj_form;

	

	function page_output()
	{
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
			$this->obj_menu_nav->add_item("Customer's Invoices", "page=customers/invoices.php&id=". $this->obj_customer->id ."");
			$this->obj_menu_nav->add_item("Customer's Services", "page=customers/services.php&id=". $this->obj_customer->id ."", TRUE);

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
			$structure["fieldname"]		= "name_service";
			$structure["type"]		= "input";
			$structure["options"]["req"]	= "yes";
			$this->obj_form->add_input($structure);
	
			$structure = NULL;
			$structure["fieldname"] 	= "description";
			$structure["type"]		= "textarea";
			$this->obj_form->add_input($structure);

			$this->obj_form->subforms["service_edit"]	= array("id_service_customer", "name_service", "description");



			// service controls
			$structure = NULL;
			$structure["fieldname"] 	= "active";
			$structure["type"]		= "checkbox";
			$structure["options"]["label"]	= "Service is enabled";
			$this->obj_form->add_input($structure);

			$this->obj_form->subforms["service_controls"]	= array("active");



			// billing
			$structure = NULL;
			$structure["fieldname"]		= "billing_cycle_string";
			$structure["type"]		= "text";
			$this->obj_form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"] 	= "date_period_first";
			$structure["type"]		= "text";
			$this->obj_form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"] 	= "date_period_next";
			$structure["type"]		= "text";
			$this->obj_form->add_input($structure);


			$this->obj_form->subforms["service_billing"]	= array("billing_cycle_string", "date_period_first", "date_period_next");



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
											."<td><a class=\"button_small\" href=\"index.php?page=customers/service-edit.php&customerid=". $this->obj_customer->id ."&serviceid=". $obj_component->option_type_id ."\">View Service</a></td>"
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
					$this->obj_form->add_input($structure);

					$this->obj_form->subforms["service_options_ddi"]	= array("phone_ddi_info", "phone_ddi_single");

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
					$structure["type"]		= "text";
					$this->obj_form->add_input($structure);

					$structure = NULL;
					$structure["fieldname"]		= "phone_trunk_quantity";
					$structure["type"]		= "input";
					$structure["options"]["req"]	= "yes";
					$this->obj_form->add_input($structure);

					$this->obj_form->subforms["service_options_trunks"]	= array("phone_trunk_info", "phone_trunk_included_units", "phone_trunk_quantity");

				break;



				case "phone_trunk":

					// DDI options
					$structure = NULL;
					$structure["fieldname"]		= "phone_ddi_info";
					$structure["type"]		= "message";
					$structure["defaultvalue"]	= "<p>This is a phone trunk service - with this service you are able to have multiple individual DDIs and DDI ranges. Note that it is important to define all the DDIs belonging to this customer, otherwise they may be able to make calls without being charged.<br><br><a class=\"button_small\" href=\"index.php?page=customers/service-ddi.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $this->obj_customer->id_service_customer ."\">Configure Customer's DDIs</a></p>";
					$this->obj_form->add_input($structure);

					// trunk options
					$structure = NULL;
					$structure["fieldname"]		= "phone_trunk_info";
					$structure["type"]		= "message";
					$structure["defaultvalue"]	= "<p>Define the number of trunks (concurrent calls) that are included in the service, depending on the service plan, there may be additional charges concurred.</p>";
					$this->obj_form->add_input($structure);

					$structure = NULL;
					$structure["fieldname"]		= "phone_trunk_included_units";
					$structure["type"]		= "text";
					$this->obj_form->add_input($structure);

					$structure = NULL;
					$structure["fieldname"]		= "phone_trunk_quantity";
					$structure["type"]		= "input";
					$structure["options"]["req"]	= "yes";
					$this->obj_form->add_input($structure);

					$this->obj_form->subforms["service_options_ddi"]	= array("phone_ddi_info");
					$this->obj_form->subforms["service_options_trunks"]	= array("phone_trunk_info", "phone_trunk_included_units", "phone_trunk_quantity");

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
								."<td><a class=\"button_small\" href=\"index.php?page=customers/service-edit.php&customerid=". $this->obj_customer->id ."&serviceid=". $obj_component->option_type_id ."\">View Service</a></td>"
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
		$this->obj_form->subforms["hidden"] = array("id_customer");


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
			$this->obj_form->sql_query = "SELECT active, date_period_first, date_period_next, quantity FROM `services_customers` WHERE id='". $this->obj_customer->id_service_customer ."' LIMIT 1";
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
