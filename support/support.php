<?php
/*
	support/support.php

	access: support_view

	Displays a list of all the support tickets currently on the system, and allows
	the user to filter or sort though them to find the one they need.
*/

class page_output
{
	var $obj_table;


	function check_permissions()
	{
		if (user_permissions_get('support_view'))
		{
			return 1;
		}
	}

	function check_requirements()
	{
		// nothing todo
		return 1;
	}


	/*
		Define table and load data
	*/
	function execute()
	{
		// establish a new table object
		$this->obj_table = New table;

		$this->obj_table->language	= $_SESSION["user"]["lang"];
		$this->obj_table->tablename	= "support_ticket_list";

		// define all the columns and structure
		$this->obj_table->add_column("standard", "title", "support_tickets.title");
		$this->obj_table->add_column("standard", "status", "support_tickets_status.value");
		$this->obj_table->add_column("standard", "priority", "support_tickets_priority.value");
		$this->obj_table->add_column("date", "date_start", "");
		$this->obj_table->add_column("date", "date_end", "");

		// defaults
		$this->obj_table->columns		= array("title", "status", "priority");
		$this->obj_table->columns_order		= array("status");
		$this->obj_table->columns_order_options	= array("title", "status", "priority", "date_start", "date_end");

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("support_tickets");
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "support_tickets.id");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN support_tickets_status ON support_tickets.status = support_tickets_status.id");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN support_tickets_priority ON support_tickets.priority = support_tickets_priority.id");


		// acceptable filter options
		$structure = NULL;
		$structure["fieldname"] = "date_start";
		$structure["type"]	= "date";
		$structure["sql"]	= "date_start >= 'value'";
		$this->obj_table->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] = "date_end";
		$structure["type"]	= "date";
		$structure["sql"]	= "date_end <= 'value' AND date_end != '0000-00-00'";
		$this->obj_table->add_filter($structure);
		
		$structure = NULL;
		$structure["fieldname"] = "searchbox";
		$structure["type"]	= "input";
		$structure["sql"]	= "(title LIKE '%value%')";
		$this->obj_table->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "hide_ex_tickets";
		$structure["type"]		= "checkbox";
		$structure["sql"]		= "date_end='0000-00-00'";
		$structure["defaultvalue"]	= "on";
		$structure["options"]["label"]	= "Hide completed support tickets";
		$this->obj_table->add_filter($structure);


		// load options
		$this->obj_table->load_options_form();

		// fetch all the support_ticket information
		$this->obj_table->generate_sql();
		$this->obj_table->load_data_sql();
	}
	

	function render_html()
	{
		// heading
		print "<h3>SUPPORT TICKETS</h3><br><br>";

		// display options
		$this->obj_table->render_options_form();


		// display table
		if (!count($this->obj_table->columns))
		{
			format_msgbox("important", "<p>Please select some valid options to display.</p>");
		}
		elseif (!$this->obj_table->data_num_rows)
		{
			format_msgbox("info", "<p>You currently have no support tickets in your database.</p>");
		}
		else
		{
			// view link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("view", "support/view.php", $structure);

			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("journal", "support/journal.php", $structure);

			
			// display the table
			$this->obj_table->render_table_html();
			
			// display CSV/PDF download link
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=csv&page=support/support.php\">Export as CSV</a></p>";
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=pdf&page=support/support.php\">Export as PDF</a></p>";
		}
	}


	function render_csv()
	{
		$this->obj_table->render_table_csv();	
	}


	function render_pdf()
	{
		$this->obj_table->render_table_pdf();
	}

}

?>
