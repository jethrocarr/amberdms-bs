<?php
/*
	customers/add.php
	
	access: customers_write

	Form to add a new customer to the database.

*/

class page_output
{
	var $obj_form;	// page form
	var $num_contacts;
	var $tax_array = array();

	function page_output()
	{
		// required pages
		$this->requires["javascript"][]		= "include/customers/javascript/addedit_customers.js";
		$this->requires["javascript"][]		= "include/customers/javascript/addedit_customer_contacts.js";
		$this->requires["css"][]		= "include/customers/css/addedit_customer.css";
	}
	
	function check_permissions()
	{
		return user_permissions_get('customers_write');
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
		$this->obj_form->formname = "customer_add";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "customers/edit-process.php";
		$this->obj_form->method = "post";
		

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "code_customer";
		$structure["type"]		= "input";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "name_customer";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]		= "date_start";
		$structure["type"]		= "date";
		$structure["defaultvalue"]	= date("Y-m-d");
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] = "date_end";
		$structure["type"]	= "date";
		$this->obj_form->add_input($structure);

		
		//contacts
		if (!empty($_SESSION["error"]["num_contacts"]))
		{
			$this->num_contacts = stripslashes($_SESSION["error"]["num_contacts"]);
		}
		else
		{
			$this->num_contacts = 1;
		}
	
		$structure = NULL;
		$structure["fieldname"]		= "num_contacts";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->num_contacts;
		$this->obj_form->add_input($structure);
		
		
		for ($i=0; $i<$this->num_contacts; $i++)
		{
			$structure = NULL;
			$structure["fieldname"]		= "contact_id_" .$i;
			$structure["type"]		= "hidden";
			$this->obj_form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"]		= "delete_contact_" .$i;
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= "false";
			$this->obj_form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"]		= "contact_" .$i;
			$structure["type"]		= "input";
			if (isset($_SESSION["error"]["contact_" .$i. "-error"]))
			{
				$structure["options"]["css_field_class"]	= "hidden_form_field_error";
			}
			else if (empty($_SESSION["error"]) && $i == 0)
			{
				$structure["options"]["css_field_class"]	= "hidden_form_field_error";
			}
			else
			{
				$structure["options"]["css_field_class"]	= "hidden_form_field";
			}
			$structure["options"]["width"]			= "200";
			$this->obj_form->add_input($structure);
			
			
			$structure = NULL;
			$structure["fieldname"]		= "role_" .$i;
			$structure["type"]		= "dropdown";
			$structure["options"]["width"]	= "205";
			if ($i == 0)
			{
				$structure["values"]			= array("accounts");
				$structure["options"]["autoselect"]	= "yes";
				$structure["options"]["disabled"]	= "yes";
			}
			else
			{
				$structure["values"]			= array("other");
				$structure["options"]["autoselect"]	= "yes";
			}
			if (isset($_SESSION["error"]["contact_" .$i. "-error"]))
			{
				$structure["options"]["css_field_class"]	= "hidden_form_field_error";
			}
			else if (empty($_SESSION["error"]) && $i == 0)
			{
				$structure["options"]["css_field_class"]	= "hidden_form_field_error";
			}
			else
			{
				$structure["options"]["css_field_class"]	= "hidden_form_field";
			}			
			$this->obj_form->add_input($structure);
			
			
			
			$structure = NULL;
			$structure["fieldname"]		= "description_" .$i;
			$structure["type"]		= "textarea";
			if (isset($_SESSION["error"]["contact_" .$i. "-error"]))
			{
				$structure["options"]["css_field_class"]	= "hidden_form_field_error";
			}
			else if (empty($_SESSION["error"]) && $i == 0)
			{
				$structure["options"]["css_field_class"]	= "hidden_form_field_error";
			}
			else
			{
				$structure["options"]["css_field_class"]	= "hidden_form_field";
			}
			$structure["options"]["width"]			= "205";
			$structure["options"]["height"]			= "";
			$this->obj_form->add_input($structure);
			
			//contact records
			if (!empty($_SESSION["error"]["num_records_$i"]))
			{
				$num_records = stripslashes($_SESSION["error"]["num_records_$i"]);
			}
			else
			{
				$num_records = 0;
			}
			
			$structure = NULL;
			$structure["fieldname"]		= "num_records_" .$i;
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= $num_records;
			$this->obj_form->add_input($structure);
			
			if ($num_records > 0)
			{
				for ($j=0; $j<$num_records; $j++)
				{
					$structure = NULL;
					$structure["fieldname"]		= "contact_" .$i. "_record_id_" .$j;
					$structure["type"]		= "hidden";
					$this->obj_form->add_input($structure);
					
					$structure = NULL;
					$structure["fieldname"]		= "contact_" .$i. "_delete_" .$j;
					$structure["type"]		= "hidden";
					$structure["defaultvalue"]	= "false";
					$this->obj_form->add_input($structure);
					
					$structure = NULL;
					$structure["fieldname"]		= "contact_" .$i. "_type_" .$j;
					$structure["type"]		= "hidden";
					$this->obj_form->add_input($structure);
					
					$structure = NULL;
					$structure["fieldname"]		= "contact_" .$i. "_label_" .$j;
					$structure["type"]		= "hidden";
					$this->obj_form->add_input($structure);
					
					$structure = NULL;
					$structure["fieldname"]		= "contact_" .$i. "_detail_" .$j;
					$structure["type"]		= "input";
					$structure["options"]["width"]  = "";
					$this->obj_form->add_input($structure);
				}
			}
		}

		// taxes
		$structure = NULL;
		$structure["fieldname"] = "tax_number";
		$structure["type"]	= "input";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure = form_helper_prepare_dropdownfromdb("tax_default", "SELECT id, name_tax as label FROM account_taxes");
		$this->obj_form->add_input($structure);


		// list all the taxes so the user can enable or disable the taxes
		$sql_tax_obj		= New sql_query;
		$sql_tax_obj->string	= "SELECT id, name_tax, description, default_customers FROM account_taxes ORDER BY name_tax";
		$sql_tax_obj->execute();

		if ($sql_tax_obj->num_rows())
		{
			// user note
			$structure = NULL;
			$structure["fieldname"] 		= "tax_message";
			$structure["type"]			= "message";
			$structure["defaultvalue"]		= "<p>Select all the taxes below which apply to this customer. Any taxes not selected, will not be added to invoices for this customer.</p>";
			$this->obj_form->add_input($structure);
			

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
				
				//check if tax is set as a default
				if ($data_tax["default_customers"])
				{
					$structure["defaultvalue"]	= "on";
				}

				// add to form
				$this->obj_form->add_input($structure);
				$this->tax_array[] = "tax_". $data_tax["id"];
			}
		}
		else
		{
			$structure = NULL;
			$structure["fieldname"] 		= "tax_message";
			$structure["type"]			= "message";
			$structure["defaultvalue"]		= "<p>No taxes can be selected for this customer, as there have been no taxes configured yet.</p>";
			$this->obj_form->add_input($structure);
		}




		// purchase options
		$structure = NULL;
		$structure["fieldname"] 		= "discount";
		$structure["type"]			= "input";
		$structure["options"]["width"]		= 50;
		$structure["options"]["label"]		= " %";
		$structure["options"]["max_length"]	= "2";
		$this->obj_form->add_input($structure);

		$this->obj_form->subforms["customer_purchase"] = array("discount");



		// billing address
		$structure = NULL;
		$structure["fieldname"] = "address1_street";
		$structure["type"]	= "textarea";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] = "address1_city";
		$structure["type"]	= "input";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] = "address1_state";
		$structure["type"]	= "input";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] = "address1_country";
		$structure["type"]	= "input";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] = "address1_zipcode";
		$structure["type"]	= "input";
		$this->obj_form->add_input($structure);


		// shipping address
		$structure = NULL;
		$structure["fieldname"]			= "address1_same_as_2";
		$structure["type"]			= "checkbox";
		$structure["options"]["label"]		= " ". lang_trans("address1_same_as_2_help");
		$structure["options"]["css_row_class"]	= "shipping_same_address";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] = "address2_street";
		$structure["type"]	= "textarea";
		$structure["options"]["css_row_class"]	= "shipping_address";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] = "address2_city";
		$structure["type"]	= "input";
		$structure["options"]["css_row_class"]	= "shipping_address";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] = "address2_state";
		$structure["type"]	= "input";
		$structure["options"]["css_row_class"]	= "shipping_address";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] = "address2_country";
		$structure["type"]	= "input";
		$structure["options"]["css_row_class"]	= "shipping_address";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] = "address2_zipcode";
		$structure["type"]	= "input";
		$structure["options"]["css_row_class"]	= "shipping_address";
		$this->obj_form->add_input($structure);

		// reseller options
		$structure = NULL;
		$structure["fieldname"]			= "reseller_customer";
		$structure["type"]			= "radio";
		$structure["values"]			= array("standalone", "reseller", "customer_of_reseller");
		$structure["defaultvalue"]		= "standalone";
		$structure["options"]["css_row_class"]	= "reseller_customer";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure = form_helper_prepare_dropdownfromdb("reseller_id", "SELECT id, code_customer as label, name_customer as label1 FROM customers WHERE reseller_customer = 'reseller'");
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 		= "reseller_message";
		$structure["type"]			= "message";
		$structure["defaultvalue"]		= "<p>Each customer in the system can either be classified for the Reseller Options as either a standalone customer, a reseller to other customers, or as a customer of a reseller. By default new customers are standalone.</p>";
		$this->obj_form->add_input($structure);
		
		
		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Create Customer";
		$this->obj_form->add_input($structure);

		
		// load any data returned due to errors
		$this->obj_form->load_data_error();
	}



	function render_html()
	{
		// title and summary
		print "<h3>ADD CUSTOMER RECORD</h3><br>";
		print "<p>This page allows you to add a new customer.</p>";

		print "<form class=\"form_standard\" action=\"" .$this->obj_form->action. "\" method=\"" .$this->obj_form->method. "\" enctype=\"multipart/form-data\">";
		print "<table class=\"form_table\" width=\"100%\">";

		//customer details
		print "<tr class=\"header\">";
			print "<td colspan=\"2\"><b>" .lang_trans("customer_view"). "</b></td>";
		print "</tr>";
		
		$this->obj_form->render_row("code_customer");
		$this->obj_form->render_row("name_customer");
		$this->obj_form->render_row("date_start");
		$this->obj_form->render_row("date_end");
		
		print "</table>";
		print "<br />";
		
		//customer contacts
		print "<table id=\"customer_contacts_table\" class=\"form_table\" width=\"100%\">";
		
		print "<tr class=\"header\">";
			print "<td colspan=\"2\"><b>" .lang_trans("customer_contacts"). "</b></td>";
		print "</tr>";
		
		for ($i = 0; $i < $this->num_contacts; $i++)
		{
			print "<tr >";
				print "<td>";
				if (isset($_SESSION["error"]["contact_" .$i. "-error"]))
				{
					print "<table id=\"contact_box_$i\" class=\"error_box contact_box\">";
				}
				else
				{
					print "<table id=\"contact_box_$i\" class=\"contact_box\">";
				}
				
					print "<tr>";
					//contact name
					print "<td width=\"25%\">";
						$this->obj_form->render_field("contact_id_$i");
						
						if (empty($_SESSION["error"]) && $i == 0)
						{
							print "<span>";
						}
						else if ($_SESSION["error"]["contact_" .$i. "-error"])
						{
							print "<span>";
						}
						else
						{
							print "<span class=\"hidden_text\">";
						}
						print "<label for=\"contact_$i\" >Name: </label><br />";
						print "</span>";
						$this->obj_form->render_field("contact_$i");
						
						if (empty($_SESSION["error"]) && $i == 0)
						{
							print "<span>";
						}
						else if ($_SESSION["error"]["contact_" .$i. "-error"])
						{
							print "<span>";
						}
						else
						{
							print "<span class=\"hidden_text\">";
						}
						print "<label for=\"role_$i\">Role: </label><br />";
						print "</span>";
						$this->obj_form->render_field("role_$i");
						
						$this->obj_form->render_field("num_records_$i");
						print "<div ";
							if($_SESSION["error"]["contact_" .$i. "-error"])
							{
								print "class=\"hidden_text\" ";
							}
							else if (empty($_SESSION["error"]) && $i == 0)
							{
								print "class=\"hidden_text\" ";
							}
							print "id=\"contact_text_$i\"><b>" .lang_trans($this->obj_form->structure["contact_$i"]["defaultvalue"]). "</b></div>";
					print "</td>";
					
					//delete contact link
					print "<td width=\"75%\" class=\"delete_contact_cell\">";
						$this->obj_form->render_field("delete_contact_$i");
				
						if ($i == 0)
						{
							print "&nbsp;";
						}
						else
						{
							print "<a id=\"delete_contact_$i\" href=\"\">delete contact...</a>";
						}
					print "</td>";
					print "</tr>";
					
					print "<tr>";
					//contact description
					print "<td width=\"25%\" class=\"description_cell\">";
					
					if (empty($_SESSION["error"]) && $i == 0)
					{
						print "<span>";
					}
					else if ($_SESSION["error"]["contact_" .$i. "-error"])
					{
						print "<span>";
					}
					else
					{
						print "<span class=\"hidden_text\">";
					}
						print "<label for=\"description_$i\">Description: </label><br />";
						print "</span>";
						$this->obj_form->render_field("description_$i");
						print "<p class=\"";
							if (isset($_SESSION["error"]["contact_" .$i. "-error"]))
							{
								print "hidden_text ";
							}
							else if (empty($_SESSION["error"]) && $i == 0)
							{
								print "hidden_text ";
							}
							print "contact_description\" id=\"description_text_$i\">" .$this->obj_form->structure["description_$i"]["defaultvalue"]. "</p>";


						
						if (isset($_SESSION["error"]["contact_" .$i. "-error"]))
						{
							print "<p class=\"change_contact\"><a id=\"change_contact_$i\" href=\"\" >done</a></p>";
						}
						else if (empty($_SESSION["error"]) && $i == 0)
						{
							print "<p class=\"change_contact\"><a id=\"change_contact_$i\" href=\"\" >done</a></p>";
						}
						else
						{
							print "<p class=\"change_contact\"><a id=\"change_contact_$i\" href=\"\" >change...</a></p>";
						}
												
						print "<input type=\"hidden\" name=\"change_contact_$i\" value=\"";
							if($_SESSION["error"]["contact_" .$i. "-error"])
							{
								print "open\" />";
							}
							else if(empty($_SESSION["error"]) && $i == 0)
							{
								print "open\" />";
							}
							else
							{
								print "closed\" />";
							}
					print "</td>";
					
					//contact records table
					print "<td width=\"75%\"  align=\"right\">";
						print "<table id=\"records_table_$i\" class=\"records_table\">";
						
						if ($this->obj_form->structure["num_records_$i"]["defaultvalue"] > 0)
						{
							for ($j = 0; $j < $this->obj_form->structure["num_records_$i"]["defaultvalue"]; $j++)
							{
								print "<tr id=\"contact_" .$i. "_record_row_" .$j. "\">";
								
								//record type
								print "<td>";
									$this->obj_form->render_field("contact_" .$i. "_record_id_" .$j);
									$this->obj_form->render_field("contact_" .$i. "_type_" .$j);
									
									$type = $this->obj_form->structure["contact_" .$i. "_type_" .$j]["defaultvalue"];
									if ($type == "phone")
									{
										print "<b>P</b>";
									}
									elseif ($type == "fax")
									{
										print "<b>F</b>";
									}
									elseif ($type == "mobile")
									{
										print "<b>M</b>";
									}
									elseif ($type == "email")
									{
										print "<b>E</b>";
									}
								print "</td>";
								
								//record label
								print "<td>";
									$this->obj_form->render_field("contact_" .$i. "_label_" .$j);
									print $this->obj_form->structure["contact_" .$i. "_label_" .$j]["defaultvalue"];
								print "</td>";
								
								//record detail
								print "<td>";
									$this->obj_form->render_field("contact_" .$i. "_detail_" .$j);
								print "</td>";
								
								//delete record link
								print "<td class=\"delete_record\">";
									$this->obj_form->render_field("contact_" .$i. "_delete_" .$j);
									print "<a id=\"contact_" .$i. "_delete_" .$j. "\" href=\"\">delete</a>";
									
								print "</td>";							
								print "</tr>";
							}
						}
						
						print "</table>";
						print "<br />";
						
						//add record form
						if (empty($_SESSION["error"]) && $i == 0)
						{
							print "<div class=\"add_record\" id=\"add_record_new_customer\">";
						}
						else
						{
							print "<div class=\"add_record\">";
						}
						print "<div id=\"add_record_link_$i\"><a id=\"add_record_$i\" href=\"\">Add Record</a></div>";
						print "<div class=\"add_record_form\" id=\"add_record_form_$i\">";
							print "<table class=\"add_record_table\">";
							print "<tr>";
								print "<td colspan=2><b>Add Record</b></td>";
							print "</tr><tr>";
								print "<td>Record Type</td>";
								print "<td><select name=\"new_record_type_$i\">";
									print "<option value=\"phone\">Phone</option>";
									print "<option value=\"fax\">Fax</option>";
									print "<option value=\"mobile\">Mobile</option>";
									print "<option value=\"email\">Email</option>";
								print "</select></td>";	
							print "</tr><tr>";
								print "<td>Label</td>";
								print "<td><input name=\"new_record_label_$i\" /></td>";
							print "</tr><tr>";
								print "<td>Detail</td>";
								print "<td><input name=\"new_record_detail_$i\" /></td>";
							print "</tr><tr>";
								print "<td colspan=2 class=\"insert_new_record\"><a href=\"\" class=\"disabled_link button_small\" id=\"insert_record_$i\">Insert</a></td></tr>";
							print "</table>";
						print "</div>";
						print "</div>";
					print "</td>";
						
					print "</tr>";
				print "</table>";
				print "</td>";
			print "</tr>";
		}
		print "<tr id=\"add_new_contact_row\">";
		print "<td>";
			print "<div class=\"add_contact_div\"><a href=\"\" id=\"add_new_contact\">Add New Contact</a></div>";
		print "</td>";
		print "</tr>";
		print "</table>";
		
		print "<br />";
		
		//customer taxes
		print "<table class=\"form_table\" width=\"100%\">";
		print "<tr class=\"header\">";
			print "<td colspan=\"2\"><b>" .lang_trans("customer_taxes"). "</b></td>";
		print "</tr>";
		
		$this->obj_form->render_row("tax_number");
		$this->obj_form->render_row("tax_default");
		$this->obj_form->render_row("tax_message");
		
		for ($i= 0; $i < count($this->tax_array); $i++)
		{
			$this->obj_form->render_row($this->tax_array[$i]);
		}
		
		print "</table>";
		
		print "<br />";
		
		
		//customer purchase
		print "<table class=\"form_table\" width=\"100%\">";
		print "<tr class=\"header\">";
			print "<td colspan=\"2\"><b>" .lang_trans("customer_purchase"). "</b></td>";
		print "</tr>";
		
		$this->obj_form->render_row("discount");
		
		print "</table>";
		
		
		print "<br />";
		
		
		//billing address
		print "<table class=\"form_table\" width=\"100%\">";
		print "<tr class=\"header\">";
			print "<td colspan=\"2\"><b>" .lang_trans("address_billing"). "</b></td>";
		print "</tr>";
		
		$this->obj_form->render_row("address1_street");
		$this->obj_form->render_row("address1_city");
		$this->obj_form->render_row("address1_state");
		$this->obj_form->render_row("address1_country");
		$this->obj_form->render_row("address1_zipcode");
		
		print "</table>";
		
		print "<br />";
		
		
		//shipping address
		print "<table class=\"form_table\" width=\"100%\">";
		print "<tr class=\"header\">";
			print "<td colspan=\"2\"><b>" .lang_trans("address_shipping"). "</b></td>";
		print "</tr>";
		
		$this->obj_form->render_row("address1_same_as_2");
		$this->obj_form->render_row("address2_street");
		$this->obj_form->render_row("address2_city");
		$this->obj_form->render_row("address2_state");
		$this->obj_form->render_row("address2_country");
		$this->obj_form->render_row("address2_zipcode");
		
		print "</table>";
		
		print "<br />";

		//reseller options
		print "<table class=\"form_table\" width=\"100%\">";
		print "<tr class=\"header\">";
			print "<td colspan=\"2\"><b>" .lang_trans("reseller_options"). "</b></td>";
		print "</tr>";
		
		$this->obj_form->render_row("reseller_customer");
		$this->obj_form->render_row("reseller_id");
		$this->obj_form->render_row("reseller_message");
		
		print "</table>";
		
		print "<br />";
		
		print "<table class=\"form_table\" width=\"100%\">";
		print "<tr class=\"header\">";
			print "<td colspan=\"2\"><b>" .lang_trans("submit"). "</b></td>";
		print "</tr>";
		
		$this->obj_form->render_row("submit");
		print "</table>";
		
		$this->obj_form->render_field("num_contacts");
		
		print "</form>";
		
	}


} // end page_output class

?>
