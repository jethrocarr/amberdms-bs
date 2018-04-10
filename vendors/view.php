<?php
/*
	vendors/view.php

	access: vendors_view (read-only)
		vendors_write (write access)

	Displays the selected vendor and if the user has correct permissions
	allows the vendor to be updated.
*/


class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_form;
	var $num_contacts;
	var $tax_array = array();


	function __construct()
	{
		// fetch variables
		$this->id = @security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Vendor's Details", "page=vendors/view.php&id=". $this->id ."", TRUE);
		$this->obj_menu_nav->add_item("Vendor's Journal", "page=vendors/journal.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Vendor's Invoices", "page=vendors/invoices.php&id=". $this->id ."");

		if (user_permissions_get("vendors_write"))
		{
			$this->obj_menu_nav->add_item("Delete Vendor", "page=vendors/delete.php&id=". $this->id ."");
		}


		// required pages
		$this->requires["javascript"][]		= "include/vendors/javascript/addedit_vendors.js";
		$this->requires["javascript"][]		= "include/vendors/javascript/addedit_vendor_contacts.js";
		$this->requires["css"][]		= "include/vendors/css/addedit_vendors.css";
	}



	function check_permissions()
	{
		return user_permissions_get("vendors_view");
	}



	function check_requirements()
	{
		// verify that vendor exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM vendors WHERE id='". $this->id ."'";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested vendor (". $this->id .") does not exist - possibly the vendor has been deleted.");
			return 0;
		}

		unset($sql_obj);


		return 1;
	}



	function execute()
	{
		/*
			Define form structure
		*/
		$this->obj_form = New form_input;
		$this->obj_form->formname = "vendor_view";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "vendors/edit-process.php";
		$this->obj_form->method = "post";
		

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "code_vendor";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "name_vendor";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);
		

		$structure = NULL;
		$structure["fieldname"] 	= "date_start";
		$structure["type"]		= "date";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] = "date_end";
		$structure["type"]	= "date";
		$this->obj_form->add_input($structure);

		
		//contacts

		//
		// TODO: this should be replaced with load_data_contacts in some point in the future
		//

		$sql_contacts_obj		= New sql_query;
		$sql_contacts_obj->string	= "SELECT id, role, contact, description FROM vendor_contacts WHERE vendor_id = " .$this->id;
		$sql_contacts_obj->execute();
		
		$sql_contacts_obj->fetch_array();
		
		if (!empty($_SESSION["error"]["num_contacts"]))
		{
			$this->num_contacts = stripslashes($_SESSION["error"]["num_contacts"]);
		}
		else if ($sql_contacts_obj->num_rows())
		{
			$this->num_contacts = $sql_contacts_obj->num_rows();
		}
		else
		{
			$this->num_contacts = 0;
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
			$structure["defaultvalue"]	= $sql_contacts_obj->data[$i]["id"];
			$this->obj_form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"]		= "delete_contact_" .$i;
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= "false";
			$this->obj_form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"]		= "contact_" .$i;
			$structure["type"]		= "input";
			$structure["defaultvalue"]	= $sql_contacts_obj->data[$i]["contact"];
			if (isset($_SESSION["error"]["contact_" .$i. "-error"]))
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
			$structure["defaultvalue"]	= $sql_contacts_obj->data[$i]["role"];
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
			else
			{
				$structure["options"]["css_field_class"]	= "hidden_form_field";
			}			
			$this->obj_form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"]		= "description_" .$i;
			$structure["type"]		= "textarea";
			$structure["defaultvalue"]	= $sql_contacts_obj->data[$i]["description"];
			if (isset($_SESSION["error"]["contact_" .$i. "-error"]))
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
			$sql_records_obj		= New sql_query;			
			if (!empty($sql_contacts_obj->data[$i]["id"]))
			{
				$sql_records_obj->string	= "SELECT id, type, label, detail FROM vendor_contact_records WHERE contact_id= " .$sql_contacts_obj->data[$i]["id"]. " ORDER BY type";
				$sql_records_obj->execute();				
				$sql_records_obj->fetch_array();
			}

			if (!empty($_SESSION["error"]["num_records_$i"]))
			{
				$num_records = stripslashes($_SESSION["error"]["num_records_$i"]);
			}
			else if ($sql_records_obj->num_rows())
			{
				$num_records = $sql_records_obj->num_rows();
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
					$structure["defaultvalue"]	= $sql_records_obj->data[$j]["id"];
					$this->obj_form->add_input($structure);
					
					$structure = NULL;
					$structure["fieldname"]		= "contact_" .$i. "_delete_" .$j;
					$structure["type"]		= "hidden";
					$structure["defaultvalue"]	= "false";
					$this->obj_form->add_input($structure);
					
					$structure = NULL;
					$structure["fieldname"]		= "contact_" .$i. "_type_" .$j;
					$structure["type"]		= "hidden";
					$structure["defaultvalue"]	= $sql_records_obj->data[$j]["type"];
					$this->obj_form->add_input($structure);
					
					$structure = NULL;
					$structure["fieldname"]		= "contact_" .$i. "_label_" .$j;
					$structure["type"]		= "hidden";
					$structure["defaultvalue"]	= $sql_records_obj->data[$j]["label"];
					$this->obj_form->add_input($structure);
					
					$structure = NULL;
					$structure["fieldname"]		= "contact_" .$i. "_detail_" .$j;
					$structure["type"]		= "input";
					$structure["defaultvalue"]	= $sql_records_obj->data[$j]["detail"];
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
		$sql_tax_obj->string	= "SELECT id, name_tax, description FROM account_taxes ORDER BY name_tax";
		$sql_tax_obj->execute();

		if ($sql_tax_obj->num_rows())
		{
			// user note
			$structure = NULL;
			$structure["fieldname"] 		= "tax_message";
			$structure["type"]			= "message";
			$structure["defaultvalue"]		= "<p>Select all the taxes below which apply to this vendor. Any taxes not selected, will not be added to invoices from this vendor.</p>";
			$this->obj_form->add_input($structure);
				


			// fetch vendor's current tax status
			if (empty($_SESSION["error"]["message"]))
			{
				$sql_vendor_taxes_obj		= New sql_query;
				$sql_vendor_taxes_obj->string	= "SELECT taxid FROM vendors_taxes WHERE vendorid='". $this->id ."'";

				$sql_vendor_taxes_obj->execute();

				if ($sql_vendor_taxes_obj->num_rows())
				{
					$sql_vendor_taxes_obj->fetch_array();
				}
			}

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

				// check if this tax is currently checked
				if ($sql_vendor_taxes_obj->data_num_rows)
				{
					foreach ($sql_vendor_taxes_obj->data as $data)
					{
						if ($data["taxid"] == $data_tax["id"])
						{
							$structure["defaultvalue"] = "on";
						}
					}
				}

				// add to form
				$this->obj_form->add_input($structure);
				$this->tax_array[] = "tax_". $data_tax["id"];
			}
		}
		else
		{
			$this->tax_array = array();

			$structure = NULL;
			$structure["fieldname"] 		= "tax_message";
			$structure["type"]			= "message";
			$structure["defaultvalue"]		= "<p>No taxes can be selected for this vendor, as there have been no taxes configured yet.</p>";
			$this->obj_form->add_input($structure);
		}



		// purchase options
		$structure = NULL;
		$structure["fieldname"] 		= "discount";
		$structure["type"]			= "input";
		$structure["options"]["width"]		= 50;
		$structure["options"]["label"]		= " %";
		$structure["options"]["max_length"]	= "6";
		$this->obj_form->add_input($structure);



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
	


		//submit section
		if (user_permissions_get("vendors_write"))
		{
			$structure = NULL;
			$structure["fieldname"] 	= "submit";
			$structure["type"]		= "submit";
			$structure["defaultvalue"]	= "Save Changes";
			$this->obj_form->add_input($structure);
		
		}
			
		// hidden
		$structure = NULL;
		$structure["fieldname"] 	= "id_vendor";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->id;
		$this->obj_form->add_input($structure);
		
		
		// fetch the form data
		$this->obj_form->sql_query = "SELECT * FROM `vendors` WHERE id='". $this->id ."' LIMIT 1";
		$this->obj_form->load_data();

	}


	function render_html()
	{
		// title + summary
		print "<h3>VENDOR DETAILS</h3><br>";
		print "<p>This page allows you to view and adjust the vendor's records.</p>";

		print "<form class=\"form_standard\" action=\"" .$this->obj_form->action. "\" method=\"" .$this->obj_form->method. "\" enctype=\"multipart/form-data\">";
		print "<table class=\"form_table\" width=\"100%\">";

		//vendor details
		print "<tr class=\"header\">";
			print "<td colspan=\"2\"><b>" .lang_trans("vendor_view"). "</b></td>";
		print "</tr>";
		
		$this->obj_form->render_row("code_vendor");
		$this->obj_form->render_row("name_vendor");
		$this->obj_form->render_row("date_start");
		$this->obj_form->render_row("date_end");
		
		print "</table>";
		print "<br />";
		
		
		//vendor contacts
		print "<table id=\"vendor_contacts_table\" class=\"form_table\" width=\"100%\">";
		
		print "<tr class=\"header\">";
			print "<td colspan=\"2\"><b>" .lang_trans("vendor_contacts"). "</b></td>";
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
						
						if (isset($_SESSION["error"]["contact_" .$i. "-error"]))
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
						
						
						if (isset($_SESSION["error"]["contact_" .$i. "-error"]))
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
							print "id=\"contact_text_$i\"><b>" .lang_trans($this->obj_form->structure["contact_$i"]["defaultvalue"]). "</b>";
							print "<br />(" .lang_trans($this->obj_form->structure["role_$i"]["defaultvalue"]). ")</div>";
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
					
					
						if (isset($_SESSION["error"]["contact_" .$i. "-error"]))
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
							if($_SESSION["error"]["contact_" .$i. "-error"])
							{
								print "hidden_text ";
							}
							print "contact_description\" id=\"description_text_$i\">" .$this->obj_form->structure["description_$i"]["defaultvalue"]. "</p>";


						if (isset($_SESSION["error"]["contact_" .$i. "-error"]))
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
						print "<div class=\"add_record\">";
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
								print "<td colspan=2 class=\"insert_new_record\"><a class=\"disabled_link button_small\" id=\"insert_record_$i\" href=\"\">Insert</a></td></tr>";
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
		
		
		//vendor taxes
		print "<table class=\"form_table\" width=\"100%\">";
		print "<tr class=\"header\">";
			print "<td colspan=\"2\"><b>" .lang_trans("vendor_taxes"). "</b></td>";
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
		
		
		//vendor purchase
		print "<table class=\"form_table\" width=\"100%\">";
		print "<tr class=\"header\">";
			print "<td colspan=\"2\"><b>" .lang_trans("vendor_purchase"). "</b></td>";
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
		
		if (user_permissions_get("vendors_write"))
		{
			print "<table class=\"form_table\" width=\"100%\">";
			print "<tr class=\"header\">";
				print "<td colspan=\"2\"><b>" .lang_trans("submit"). "</b></td>";
			print "</tr>";
			
			$this->obj_form->render_row("submit");
			print "</table>";
		}		
		else
		{
			format_msgbox("locked", "<p>Sorry, you do not have permission to edit this vendor</p>");
		}
		
		$this->obj_form->render_field("id_vendor");
		$this->obj_form->render_field("num_contacts");
		
		print "</form>";

	}

		
	

} // end of page_output

?>
