<?php
/*
	projects/timebilled-edit.php
	
	access: projects_timegroup

	Form to add or edit a time grouping.
*/

class page_output
{
	var $id;
	var $groupid;
	
	var $obj_menu_nav;
	var $obj_form;

	var $obj_sql_entries;

	var $locked = 0;


	function page_output()
	{
		// fetch variables
		$this->id	= security_script_input('/^[0-9]*$/', $_GET["id"]);
		$this->groupid	= security_script_input('/^[0-9]*$/', $_GET["groupid"]);

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
		return user_permissions_get("projects_timegroup");
	}



	function check_requirements()
	{
		// verify that project exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM projects WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested project (". $this->id .") does not exist - possibly the project has been deleted.");
			return 0;
		}

		unset($sql_obj);
		
		// verify that the time group exists and belongs to this project
		if ($this->groupid)
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT projectid, locked FROM time_groups WHERE id='". $this->groupid ."' LIMIT 1";
			$sql_obj->execute();

			if (!$sql_obj->num_rows())
			{
				log_write("error", "page_output", "The requested time group (". $this->groupid .") does not exist - possibly the time group has been deleted.");
				return 0;
			}
			else
			{
				$sql_obj->fetch_array();

				$this->locked = $sql_obj->data[0]["locked"];

				if ($sql_obj->data[0]["projectid"] != $this->id)
				{
					log_write("error", "page_output", "The requested time group (". $this->groupid .") does not belong to the selected project (". $this->id .")");
					return 0;
				}
			}
			
			unset($sql_obj);
		}

		return 1;
	}


	function execute()
	{
		/*
			Define form structure
		*/
		$this->obj_form = New form_input;
		$this->obj_form->formname = "timebilled_view";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "projects/timebilled-edit-process.php";
		$this->obj_form->method = "post";
	
	
		// general
		$structure = NULL;
		$structure["fieldname"] 	= "name_group";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);

		$structure = form_helper_prepare_dropdownfromdb("customerid", "SELECT id, name_customer as label FROM customers");
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);

		if ($this->groupid)
		{
			$structure = NULL;
			$structure["fieldname"] 	= "code_invoice";
			$structure["type"]		= "text";
			$this->obj_form->add_input($structure);
		}

		$structure = NULL;
		$structure["fieldname"] 	= "description";
		$structure["type"]		= "textarea";
		$structure["options"]["width"]	= "600";
		$structure["options"]["height"]	= "60";
		$this->obj_form->add_input($structure);


		// hidden values
		$structure = NULL;
		$structure["fieldname"]		= "projectid";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->id;
		$this->obj_form->add_input($structure);
		
		$structure = null;
		$structure["fieldname"]		= "groupid";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->groupid;
		$this->obj_form->add_input($structure);
	
		

		/*
			Define checkboxes for all unassigned time entries
		*/

		$this->obj_sql_entries = New sql_query;
		
		$this->obj_sql_entries->prepare_sql_settable("timereg");
		
		$this->obj_sql_entries->prepare_sql_addfield("id", "timereg.id");
		$this->obj_sql_entries->prepare_sql_addfield("date", "timereg.date");
		$this->obj_sql_entries->prepare_sql_addfield("name_phase", "project_phases.name_phase");
		$this->obj_sql_entries->prepare_sql_addfield("name_staff", "staff.name_staff");
		$this->obj_sql_entries->prepare_sql_addfield("description", "timereg.description");
		$this->obj_sql_entries->prepare_sql_addfield("time_booked", "timereg.time_booked");
		$this->obj_sql_entries->prepare_sql_addfield("groupid", "timereg.groupid");
		$this->obj_sql_entries->prepare_sql_addfield("billable", "timereg.billable");
		
		$this->obj_sql_entries->prepare_sql_addjoin("LEFT JOIN staff ON timereg.employeeid = staff.id");
		$this->obj_sql_entries->prepare_sql_addjoin("LEFT JOIN project_phases ON timereg.phaseid = project_phases.id");

		if ($this->groupid)
		{
			$this->obj_sql_entries->prepare_sql_addwhere("groupid='". $this->groupid ."' OR !groupid");
		}
		else
		{
			$this->obj_sql_entries->prepare_sql_addwhere("!groupid");
		}

		$this->obj_sql_entries->generate_sql();
		$this->obj_sql_entries->execute();

		if ($this->obj_sql_entries->num_rows())
		{
			$this->obj_sql_entries->fetch_array();

			foreach ($this->obj_sql_entries->data as $data)
			{
				// define the billable check box
				$structure = NULL;
				$structure["fieldname"]		= "time_". $data["id"] ."_bill";
				$structure["type"]		= "checkbox";
				$structure["options"]["label"]	= " ";

				if ($data["groupid"] == $this->groupid && $data["billable"] == "1")
				{
					$structure["defaultvalue"] = "on";
				}
				
				$this->obj_form->add_input($structure);

				// define the nobill check box
				$structure = NULL;
				$structure["fieldname"]		= "time_". $data["id"] ."_nobill";
				$structure["type"]		= "checkbox";
				$structure["options"]["label"]	= " ";

				if ($data["groupid"] == $this->groupid && $data["billable"] == "0")
				{
					$structure["defaultvalue"] = "on";
				}
				
				$this->obj_form->add_input($structure);

			}
		}


		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
			
		if ($this->groupid)
		{
			$structure["defaultvalue"]	= "Save Changes";
		}
		else
		{
			$structure["defaultvalue"]	= "Create Time Group";
		}
		
		$this->obj_form->add_input($structure);

		


		// fetch the form data if editing
		if ($this->groupid)
		{
			$this->obj_form->sql_query = "SELECT time_groups.name_group, time_groups.customerid, time_groups.description, account_ar.code_invoice FROM time_groups LEFT JOIN account_ar ON account_ar.id = time_groups.invoiceid WHERE time_groups.id='". $this->groupid ."' LIMIT 1";
			$this->obj_form->load_data();
		}
		else
		{
			// load any data returned due to errors
			$this->obj_form->load_data_error();
		}

	}


	function render_html()
	{
		// Title + Summary
		if ($this->groupid)
		{
			print "<h3>EDIT TIME GROUP</h3><br>";
			print "<p>This page allows you to modify a time grouping.</p>";
		}
		else
		{
			print "<h3>ADD NEW TIME GROUP</h3><br>";
			print "<p>This page allows you to add a new time group entry to a project.</p>";
		}
		


	
		/*
			Display the form

			Because we need all the columns for the different time items, we have to do
			a custom display for this form.
		*/

		// start form
		print "<form enctype=\"multipart/form-data\" method=\"". $this->obj_form->method ."\" action=\"". $this->obj_form->action ."\" class=\"form_standard\">";


		// GENERAL INPUTS
		
		// start table
		print "<table class=\"form_table\" width=\"100%\">";

		// form header
		print "<tr class=\"header\">";
		print "<td colspan=\"2\"><b>". language_translate_string($this->obj_form->language, "timebilled_details") ."</b></td>";
		print "</tr>";

		// display all the rows
		$this->obj_form->render_row("name_group");
		$this->obj_form->render_row("customerid");

		if ($this->groupid)
		{
			$this->obj_form->render_row("code_invoice");
		}

		$this->obj_form->render_row("description");


		// end table
		print "</table><br>";



		// TIME SELECTION

		print "<table class=\"form_table\" width=\"100%\">";
		print "<tr class=\"header\">";
		print "<td colspan=\"2\"><b>". language_translate_string($this->obj_form->language, "timebilled_selection") ."</b></td>";
		print "</tr>";
		print "</table>";

		
		if ($this->obj_sql_entries->num_rows())
		{
			// start table
			print "<p>Select all the time that should belong to this group from the list below - this list only shows time currently unassigned to any group.</p>";
			print "<p>You can choose whether to add the time as billable or as unbillable. This is used to group hours that are unbilled, eg: internal paper work
			for the customer's account or other administrative overheads so that they won't continue to show in this list.</p>";

			
			print "<table class=\"table_content\" width=\"100%\">";

			// form header
			print "<tr class=\"header\">";
			print "<td><b>". language_translate_string($this->obj_form->language, "date") ."</b></td>";
			print "<td><b>". language_translate_string($this->obj_form->language, "name_phase") ."</b></td>";
			print "<td><b>". language_translate_string($this->obj_form->language, "name_staff") ."</b></td>";
			print "<td><b>". language_translate_string($this->obj_form->language, "description") ."</b></td>";
			print "<td><b>". language_translate_string($this->obj_form->language, "time_booked") ."</b></td>";
			print "<td><b>". language_translate_string($this->obj_form->language, "time_bill") ."</b></td>";
			print "<td><b>". language_translate_string($this->obj_form->language, "time_nobill") ."</b></td>";
			print "</tr>";

			// display all the rows
			foreach ($this->obj_sql_entries->data as $data)
			{
				print "<tr>";
					print "<td>". time_format_humandate($data["date"]) ."</td>";
					print "<td>". $data["name_phase"] ."</td>";
					print "<td>". $data["name_staff"] ."</td>";
					print "<td>". $data["description"] ."</td>";
					print "<td>". time_format_hourmins($data["time_booked"]) ."</td>";

					print "<td>";
					$this->obj_form->render_field("time_". $data["id"] ."_bill");
					print "</td>";

					print "<td>";
					$this->obj_form->render_field("time_". $data["id"] ."_nobill");
					print "</td>";
					
				print "</tr>";
			}

			// end table
			print "</table><br>";
		}
		else
		{
			print "<p><i>There is currently no un-grouped time that can be selected.</i></p>";
		}



		// HIDDEN FIELDS
		$this->obj_form->render_field("projectid");
		$this->obj_form->render_field("groupid");


		// SUBMIT
		
		// start table
		print "<table class=\"form_table\" width=\"100%\">";

		// form header
		print "<tr class=\"header\">";
		print "<td colspan=\"2\"><b>". language_translate_string($this->obj_form->language, "submit") ."</b></td>";
		print "</tr>";

		// display all the rows
		if (!$this->locked)
		{
			$this->obj_form->render_row("submit");
		}

		// end table
		print "</table>";


		// end form
		print "</form>";


		// locked
		if ($this->locked)
		{
			format_msgbox("locked", "<p>This time group has now been locked and can no longer be adjusted.</p>");
		}

	}
	
}
?>
