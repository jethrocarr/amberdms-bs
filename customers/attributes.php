<?php
/*
	customers/attributes.php
	
	access: customers_view (read-only)
		customers_write (write access)

	Provides a list of attriubtes for the selected customer.
*/

require("include/customers/inc_customers.php");
require("include/attributes/inc_attributes.php");


class page_output
{
	var $obj_customer;
	var $obj_attributes;
	var $obj_form;

	var $obj_menu_nav;
	var $obj_journal;


	function page_output()
	{
		// requirements
		$this->requires["css"][]		= "include/attributes/css/attributes.css";
		$this->requires["javascript"][]		= "include/attributes/javascript/attributes.js";
	

		// init objects
		$this->obj_customer		= New customer;
		$this->obj_attributes		= New attributes;
		$this->obj_form			= New form_input;
		

		// fetch variables
		$this->obj_customer->id		= @security_script_input('/^[0-9]*$/', $_GET["id_customer"]);


		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Customer's Details", "page=customers/view.php&id=". $this->obj_customer->id ."");

		if (sql_get_singlevalue("SELECT value FROM config WHERE name='MODULE_CUSTOMER_PORTAL' LIMIT 1") == "enabled")
		{
			$this->obj_menu_nav->add_item("Portal Options", "page=customers/portal.php&id=". $this->obj_customer->id ."");
		}

		$this->obj_menu_nav->add_item("Customer's Journal", "page=customers/journal.php&id=". $this->obj_customer->id ."");
		$this->obj_menu_nav->add_item("Customer's Attributes", "page=customers/attributes.php&id_customer=". $this->obj_customer->id ."", TRUE);
		$this->obj_menu_nav->add_item("Customer's Invoices", "page=customers/invoices.php&id=". $this->obj_customer->id ."");
		$this->obj_menu_nav->add_item("Customer's Services", "page=customers/services.php&id=". $this->obj_customer->id ."");

		if (user_permissions_get("customers_write"))
		{
			$this->obj_menu_nav->add_item("Delete Customer", "page=customers/delete.php&id=". $this->obj_customer->id ."");
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

		unset($sql_obj);


		return 1;
	}


	function execute()
	{
		/*
			Load attribute data
		*/

		$this->obj_attributes->type		= "customer";
		$this->obj_attributes->id_owner		= $this->obj_customer->id;

		$this->obj_attributes->load_data_all();

//print "<pre>"; print_r($this->obj_attributes->data); print "</pre>";

		/*
			Define form structure
		*/

		$this->obj_form->formname		= "attributes_customer";
		$this->obj_form->language		= $_SESSION["user"]["lang"];

		$this->obj_form->action			= "customers/attributes-process.php";
		$this->obj_form->method			= "post";
		
		/*
		 * 	Create variables to track number of attributes and their groups
		 */
		$this->group_arrays			= array();  
		$this->last_row_in_group		= array();
		$this->highest_attr_id			= 0;
		
		/*
		 * 	Assign attributes to correct groups
		 * 	Count highest attribuate	
		 */
		foreach ($this->obj_attributes->data as $attribute)
		{
			$this->group_arrays[$attribute["id_group"]][] = $attribute["id"];
			$this->group_arrays[$attribute["id_group"]]["name"] = $attribute["group_name"];
			if($attribute["id"] > $this->highest_attr_id)
			{
				$this->highest_attr_id = $attribute["id"];
			}
		}
//print "<pre>"; print_r($_SESSION); print "</pre>";
		/*
		 * 	Create blank row for each group
		 */
//		foreach ($this->group_arrays as $group)
//		{	
//			print_r($group);
//			print_r($this->num_values);
//			$this->num_values++;
//			print_r($this->num_values);
//			$group[] = $this->num_values;
//			print_r($group);
//		}
//		for ($i=1; $i<=count($this->group_arrays); $i++)
//		{
//			$this->group_arrays[$i][] = $this->num_values;
//			$this->id_attr_match[$this->num_values] = $this->num_values;
//			$this->num_values++;
//		}
		foreach ($this->group_arrays as $group_id=>$attributes)
		{
			$this->highest_attr_id++;
			$this->group_arrays[$group_id][] = $this->highest_attr_id;
			$this->last_row_in_group[$group_id] = $this->highest_attr_id;
		}		
		
		
		print "<pre>"; print_r($this->group_arrays); print "</pre>";
		//print "<pre>"; print_r($this->id_attr_match); print "</pre>";
		print "<pre>"; print_r($this->highest_attr_id); print "</pre>";
		/*
			Define attribute form rows
		*/

		// unless there has been error data returned, fetch all the records
		// and work out the number of rows - always have one extra
//		if (!isset($_SESSION["error"]["form"][$this->obj_form->formname]))
//		{
//			$this->num_values = 1;
//
//			if ($this->obj_attributes->data)
//			{
//				foreach ($this->obj_attributes->data as $attribute)
//				{
//					$this->num_values++;
//				}
//			}
//		}
//		else
//		{
//			$this->num_values = @security_script_input('/^[0-9]*$/', $_SESSION["error"]["num_values"]);
//		}

		
		// ensure there are at least two rows, if more are needed when entering information,
		// then the javascript functions will provide.
		
//		if ($this->num_values < 2)
//		{
//			$this->num_values = 2;
//		}


		// run through all the existing attributes
//		for ($i = 0; $i < $this->num_values; $i++)
//		{
		foreach	($this->group_arrays as $group_id=>$attributes)
		{	
			foreach	($attributes as $key=>$id)
			{	//print $key;
				if ((string)$key != "name")
				{
				//print "not name";
				// values
				$structure = NULL;
				$structure["fieldname"] 			= "attribute_". $id ."_id";
				$structure["type"]				= "hidden";
				$this->obj_form->add_input($structure);
	
				$structure = NULL;
				$structure["fieldname"]				= "attribute_". $id ."_key";
				$structure["type"]				= "input";
				$structure["options"]["width"]			= "300";
				$structure["options"]["max_length"]		= "80";
				$structure["options"]["autocomplete"]		= "sql";
				$structure["options"]["autocomplete_sql"]	= "SELECT DISTINCT `key` as label FROM attributes";
				$structure["options"]["help"]			= "Key/Label for attribute (with autocomplete)";
				$this->obj_form->add_input($structure);
	
				$structure = NULL;
				$structure["fieldname"] 			= "attribute_". $id ."_value";
				$structure["type"]				= "input";
				$structure["options"]["width"]			= "500";
				$structure["options"]["help"]			= "Text field to store any data";
				$this->obj_form->add_input($structure);
	
				$structure = NULL;
				$structure["fieldname"]				= "attribute_". $id ."_delete_undo";
				$structure["type"]				= "hidden";
				$structure["defaultvalue"]			= "false";
				$this->obj_form->add_input($structure);
				
				$structure = NULL;
				$structure["fieldname"]				= "attribute_". $id ."_group";
				$structure["type"]				= "hidden";
				$structure["defaultvalue"]			= $group_id;
				$this->obj_form->add_input($structure);
				}
			}
		}
		
		foreach ($this->last_row_in_group as $groupid=>$attribute)
		{
			$this->obj_form->structure["attribute_". $attribute ."_key"]["options"]["css_field_class"]	= "last_row";
			$this->obj_form->structure["attribute_". $attribute ."_value"]["options"]["css_field_class"]	= "last_row";
		}


		// load in what data we have
		if (is_array($this->obj_attributes->data))
		{
			foreach ($this->obj_attributes->data as $record)
			{
				// fetch data
				$this->obj_form->structure["attribute_". $record["id"] ."_id"]["defaultvalue"]		= $record["id"];
				$this->obj_form->structure["attribute_". $record["id"] ."_key"]["defaultvalue"]		= $record["key"];
				$this->obj_form->structure["attribute_". $record["id"] ."_value"]["defaultvalue"]	= $record["value"];
			}
		}

		// hidden
		$structure = NULL;
		$structure["fieldname"] 	= "id_customer";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->obj_customer->id;
		$this->obj_form->add_input($structure);

//		$structure = NULL;
//		$structure["fieldname"] 	= "num_values";
//		$structure["type"]		= "hidden";
//		$structure["defaultvalue"]	= "$this->num_values";
//		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "highest_attr_id";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= "$this->highest_attr_id";
		$this->obj_form->add_input($structure);

	
		// submit section
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Save Changes";
		$this->obj_form->add_input($structure);


		// fetch data in event of an error
		if (error_check())
		{
			$this->obj_form->load_data_error();
		}


		return 1;
	}



	function render_html()
	{
/*
		// display header
		print "<h3>CUSTOMER ATTRIBUTES</h3><br>";
		print "<p>You can add customer attributes here</p>";

		if (user_permissions_get("customers_write"))
		{
			print "<div class='add_attribute_box'>";
			print "<h4>Add Record</h4>"; 
			$this->render_new_key_value_form();
			print "</div>";
			//print "<p><b><a class=\"button\" href=\"index.php?page=customers/journal-edit.php&type=text&id=". $this->obj_customer->id ."\">Add new journal entry</a> <a class=\"button\" href=\"index.php?page=customers/journal-edit.php&type=file&id=". $this->obj_customer->id ."\">Upload File</a></b></p>";
		}
*/



		// display header
		print "<h3>CUSTOMER ATTRIBUTES</h3><br>";
		print "<p>Use this page to define attributes such as install dates, model numbers, serial numbers or other values of interest in an easy to search form. For more detailed text or file uploads, use the journal instead.</p>";


		if (!is_array($this->obj_attributes->data))
		{
			format_msgbox("info", "<p>You do not have any attributes currently assigned to this customer, use the fields below to begin entering some.</p>");
			print "<br>";
		}

		print "<p id=\"show_add_group\"><strong><a href=\"\">Create New Group...</a></strong></p>";
		print "<p class=\"add_group\"><strong>Create New Group:</strong></p>";
		print "<p class=\"add_group\">Name: <input type=\"text\" name=\"add_group\" /> &nbsp; <a href=\"\" class=\"button_small\" id=\"add_group\">Add</a> &nbsp; <a href=\"\" class=\"button_small\" id=\"close_add_group\">Cancel</a></p>";
		// start form/table structure
		print "<form method=\"". $this->obj_form->method ."\" action=\"". $this->obj_form->action ."\" class=\"form_standard\">";
		print "<table class=\"form_table\" width=\"100%\">";


//		print "<tr class=\"header\">";
//		print "<td><b>". lang_trans("attribute_key") ."</b></td>";
//		print "<td><b>". lang_trans("attribute_value") ."</b></td>";
//		print "<td><br/></td>";
//		print "<td><br/></td>";
//		print "</tr>";
//
//		print "<tr>";
//		print "<td colspan=\"4\"></td>";
//		print "</tr>";


		// display all the rows
		foreach	($this->group_arrays as $group_id=>$attributes)
		{
		//print "id"; print_r($attributes);
			print "<tr class=\"header show_attributes\" id=\"group_row_". $group_id. "\">";
			print "<td colspan=\"3\"><b>". $attributes["name"] ."</b></td>";
			print "<td class=\"expand_collapse\"><b>v</b></td>";
			print "</tr>";
			
			print "<tr class=\"header group_row_". $group_id ."\">";
			print "<td><b>". lang_trans("attribute_key") ."</b></td>";
			print "<td><b>". lang_trans("attribute_value") ."</b></td>";
//			print "<td><b>". "hi" ."</b></td>";
//			print "<td><b>". "no" ."</b></td>";
			print "<td><br/></td>";
			print "<td><br/></td>";
			print "</tr>";
	
			print "<tr class=\"group_row_". $group_id ."\">";
			print "<td colspan=\"4\"></td>";
			print "</tr>";
			
			//print "count ". count($this->group_arrays[$i]);
			foreach	($attributes as $key=>$id)
			{
			if ((string)$key != "name")
				
				{
//				print "<tr>";
//				print "<td>". $this->group_arrays[$i][$j] ."</td>";
//				print "</tr>";
				

				if (isset($_SESSION["error"]["attribute_". $id ."_value-error"]) || isset($_SESSION["error"]["attribute_". $id ."_key-error"]))
				{
					print "<tr class=\"table_highlight form_error group_row_". $group_id. "\" >";
				}
				else
				{
					print "<tr class=\"table_highlight group_row_". $group_id ."\">";
				}
				
				print "<td width=\"30%\" valign=\"top\">";
				$this->obj_form->render_field("attribute_". $id ."_key");
				$this->obj_form->render_field("attribute_". $id ."_id");
				print "</td>";
	
				print "<td width=\"50%\" valign=\"top\">";
				$this->obj_form->render_field("attribute_". $id ."_value");
				print "</td>";
				
				print "<td width=\"15%\" valign=\"top\">";
				$this->obj_form->render_field("attribute_". $id ."_group");
				print "<strong><a href=\"\" id=\"move_row_" .$id. "\">move...</a></strong></td>";
				
				print "<td width=\"5%\" valign=\"top\">";
				if (user_permissions_get("customers_write"))
				{
					if ($this->obj_form->structure["attribute_". $id ."_delete_undo"]["defaultvalue"] != "disabled")
					{
						$this->obj_form->render_field("attribute_". $id ."_delete_undo");
						print "<strong class=\"delete_undo\"><a href=\"\">delete</a></strong>";
					}
				}
				
				print "</td>";
				print "</tr>";

				
				}
			}	
			print "<tr>";
			print "<td colspan=\"4\"></td>";
			print "</tr>";		
		}
//		for ($i = 0; $i < $this->num_values; $i++)
//		{
//			if (isset($_SESSION["error"]["attribute_". $i ."-error"]))
//			{
//				print "<tr class=\"form_error\">";
//			}
//			else
//			{
//				print "<tr class=\"table_highlight\">";
//			}
//
//			print "<td width=\"25%\" valign=\"top\">";
//			$this->obj_form->render_field("attribute_". $i ."_key");
//			print "</td>";
//
//			print "<td width=\"70%\" valign=\"top\">";
//			$this->obj_form->render_field("attribute_". $i ."_value");
//			print "</td>";
//			
//			print "<td width=\"5%\" valign=\"top\">";
//
//
//			if (user_permissions_get("customers_write"))
//			{
//				if ($this->obj_form->structure["attribute_". $i ."_delete_undo"]["defaultvalue"] != "disabled")
//				{
//					$this->obj_form->render_field("attribute_". $i ."_delete_undo");
//					print "<strong class=\"delete_undo\"><a href=\"\">delete</a></strong>";
//				}
//			}
//
//
//			print "</td>";
//
//			print "</tr>";
//
//			$this->obj_form->render_field("attribute_". $i ."_id");
//		}


		// hidden fields
		$this->obj_form->render_field("id_customer");
		//$this->obj_form->render_field("num_values");
		$this->obj_form->render_field("highest_attr_id");

		print "<tr>";
		print "<td colspan=\"4\"></td>";
		print "</tr>";

		// form submit
		print "<tr class=\"header\">";
		print "<td colspan=\"4\"><b>". lang_trans("submit") ."</b></td>";
		print "</tr>";	

		if (user_permissions_get("customers_write"))
		{
			$this->obj_form->render_row("submit");
		}
		
		// end table + form
		print "</table>";
		print "</form>";

		if (!user_permissions_get("customers_write"))
		{
			format_msgbox("locked", "<p>Sorry, you do not have permission to edit this customer</p>");
		}

		// manually call form javascript functions
		$this->obj_form->render_javascript();

	} // end of render_html
	
} // end of page_output class

?>
