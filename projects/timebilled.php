<?php
/*
	timebilled.php
	
	access: "projects_view" and "projects_timegroup" group members

	Displays groups of time for invoicing purposes.
*/

class page_output
{
	var $id;
	var $name_project;
	
	var $obj_menu_nav;
	var $obj_table;


	function page_output()
	{
		// fetch variables
		$this->id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Project Details", "page=projects/view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Project Phases", "page=projects/phases.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Timebooked", "page=projects/timebooked.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Timebilled/Grouped", "page=projects/timebilled.php&id=". $this->id ."", TRUE);
		$this->obj_menu_nav->add_item("Project Journal", "page=projects/journal.php&id=". $this->id ."");

		if (user_permissions_get("projects_write"))
		{
			$this->obj_menu_nav->add_item("Delete Project", "page=projects/delete.php&id=". $this->id ."");
		}
	}



	function check_permissions()
	{
		if (user_permissions_get("projects_view"))
		{
			// accept user if they have access to all staff
			if (user_permissions_get("timekeeping_all_view"))
			{
				return 1;
			}

			// select the IDs that the user does have access to
			if ($this->access_staff_ids = user_permissions_staff_getarray("timereg_view"))
			{
				return 1;
			}
			else
			{
				log_render("error", "page", "Before you can view project hours, your administrator must configure the staff accounts you may access, or set the timekeeping_all_view permission.");
			}
		}
	}



	function check_requirements()
	{
		// verify that project exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id, name_project FROM projects WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested project (". $this->id .") does not exist - possibly the project has been deleted.");
			return 0;
		}
		else
		{
			$sql_obj->fetch_array();

			$this->name_project = $sql_obj->data[0]["name_project"];
		}

		unset($sql_obj);


		return 1;
	}



	function execute()
	{
	
		/// Basic Table Structure

		// establish a new table object
		$this->obj_table = New table;

		$this->obj_table->language	= $_SESSION["user"]["lang"];
		$this->obj_table->tablename	= "time_billed";

		// define all the columns and structure
		$this->obj_table->add_column("standard", "name_group", "time_groups.name_group");
		$this->obj_table->add_column("standard", "name_customer", "CONCAT_WS(' -- ', customers.code_customer, customers.name_customer)");
		$this->obj_table->add_column("standard", "code_invoice", "account_ar.code_invoice");
		$this->obj_table->add_column("standard", "description", "time_groups.description");
		$this->obj_table->add_column("hourmins", "time_billed", "NONE");
		$this->obj_table->add_column("hourmins", "time_not_billed", "NONE");

		// defaults
		$this->obj_table->columns		= array("name_group", "name_customer", "code_invoice", "description", "time_billed", "time_not_billed");
		$this->obj_table->columns_order		= array("name_customer", "name_group");
		$this->obj_table->columns_order_options	= array("name_customer", "name_group", "name_customer", "code_invoice", "description");

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("time_groups");
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "time_groups.id");
		$this->obj_table->sql_obj->prepare_sql_addfield("invoiceid", "time_groups.invoiceid");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN customers ON time_groups.customerid = customers.id");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN account_ar ON time_groups.invoiceid = account_ar.id");
		$this->obj_table->sql_obj->prepare_sql_addwhere("time_groups.projectid = '". $this->id ."'");
		
		
		/// Filtering/Display Options

		// fixed options
		$this->obj_table->add_fixed_option("id", $this->id);


		// acceptable filter options
		$structure		= form_helper_prepare_dropdownfromdb("customerid", "SELECT id, code_customer as label, name_customer as label1 FROM customers ORDER BY name_customer");
		$structure["sql"]	= "time_groups.customerid='value'";
		$this->obj_table->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "hide_closed";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "Hide time groups belong to invoices";
		$structure["defaultvalue"]	= "enabled";
		$structure["sql"]		= "time_groups.invoiceid='0'";
		$this->obj_table->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] = "searchbox";
		$structure["type"]	= "input";
		$structure["sql"]	= "(time_groups.description LIKE '%value%' OR time_groups.name_group LIKE '%value%')";
		$this->obj_table->add_filter($structure);



		$this->obj_table->total_columns	= array("time_billed", "time_not_billed");


		// load options
		$this->obj_table->load_options_form();


		// generate & execute SQL query			
		$this->obj_table->generate_sql();
		$this->obj_table->load_data_sql();


		// run through all the data rows to make custom changes
		if ($this->obj_table->data_num_rows)
		{
			for ($i=0; $i < $this->obj_table->data_num_rows; $i++)
			{
				// fetch the time totals
				// (because we have to do two different sums, we can't use a join)
				$sql_obj		= New sql_query;
				$sql_obj->string	= "SELECT time_booked, billable FROM timereg WHERE groupid='". $this->obj_table->data[$i]["id"] ."'";
				$sql_obj->execute();
				
				if ($sql_obj->num_rows())
				{
					$sql_obj->fetch_array();

					foreach ($sql_obj->data as $data)
					{
						if ($data["billable"] == 0)
						{
							$this->obj_table->data[$i]["time_not_billed"] += $data["time_booked"];
						}
						else
						{
							$this->obj_table->data[$i]["time_billed"] += $data["time_booked"];
						}
					}
				}
			}
		}
		
	}


	function render_html()
	{
		// heading
		print "<h3>TIME BILLED/GROUPED</h3>";

		// TODO: add more details explaining how to use time grouping
		print "<p>This page shows all the time that has been grouped and invoiced for the ". $this->name_project ." project.</p>";

		// display options form
		$this->obj_table->render_options_form();

		// display table data
		if (!$this->obj_table->data_num_rows)
		{
			format_msgbox("info", "<p>There is currently no time registered to this project that matches your filter options.</p>");
		}
		else
		{
			// run through all the data rows to make custom changes
			for ($i=0; $i < $this->obj_table->data_num_rows; $i++)
			{
				if ($this->obj_table->data[$i]["code_invoice"])
				{
					$this->obj_table->data[$i]["code_invoice"] = "<a href=\"index.php?page=accounts/ar/invoice-view.php&id=". $this->obj_table->data[$i]["invoiceid"] ."\">AR ". $this->obj_table->data[$i]["code_invoice"] ."</a>";
				}
			}
			
		
			if (user_permissions_get("projects_timegroup"))
			{
				// add view/edit link
				$structure = NULL;
				$structure["id"]["value"]		= $this->id;
				$structure["groupid"]["column"]		= "id";
				$this->obj_table->add_link("view/edit", "projects/timebilled-edit.php", $structure);

	
				// add delete link
				$structure = NULL;
				$structure["id"]["value"]		= $this->id;
				$structure["groupid"]["column"]		= "id";
				$this->obj_table->add_link("delete", "projects/timebilled-delete.php", $structure);
			}


			// display table data
			$this->obj_table->render_table_html();
				
		}


		print "<table width=\"100%\"><tr>";
		print "<td valign=\"top\">";

		if (user_permissions_get("projects_timegroup"))
		{
			// display add time group link
			print "<p><a class=\"button\" href=\"index.php?page=projects/timebilled-edit.php&id=". $this->id ."\">Add new time group</a></p>";
		}

		print "</td><td align=\"right\">";

		if ($this->obj_table->data_num_rows)
		{
			// display CSV/PDF download link
			print "<p><a class=\"button_export\" href=\"index-export.php?mode=csv&page=projects/timebilled.php&id=". $this->id ."\">Export as CSV</a></p>";
			print "<p><a class=\"button_export\" href=\"index-export.php?mode=pdf&page=projects/timebilled.php&id=". $this->id ."\">Export as PDF</a></p>";
		}

		print "</td>";
		print "</tr></table>";

	}

	function render_csv()
	{
		$this->obj_table->render_table_csv();
	}
	
}

?>
