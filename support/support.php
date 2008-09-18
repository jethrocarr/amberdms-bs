<?php
/*
	support/support.php

	access: "support_view" group members

	Displays a list of all the support tickets currently on the system, and allows
	the user to filter or sort though them to find the one they need.
*/

if (user_permissions_get('support_view'))
{
	function page_render()
	{
		// establish a new table object
		$support_ticket_list = New table;

		$support_ticket_list->language	= $_SESSION["user"]["lang"];
		$support_ticket_list->tablename	= "support_ticket_list";

		// define all the columns and structure
		$support_ticket_list->add_column("standard", "title", "support_tickets.title");
		$support_ticket_list->add_column("standard", "status", "support_tickets_status.value");
		$support_ticket_list->add_column("standard", "priority", "support_tickets_priority.value");
		$support_ticket_list->add_column("date", "date_start", "");
		$support_ticket_list->add_column("date", "date_end", "");

		// defaults
		$support_ticket_list->columns		= array("title", "status", "priority");
		$support_ticket_list->columns_order	= array("status");

		// define SQL structure
		$support_ticket_list->sql_obj->prepare_sql_settable("support_tickets");
		$support_ticket_list->sql_obj->prepare_sql_addfield("id", "support_tickets.id");
		$support_ticket_list->sql_obj->prepare_sql_addjoin("LEFT JOIN support_tickets_status ON support_tickets.status = support_tickets_status.id");
		$support_ticket_list->sql_obj->prepare_sql_addjoin("LEFT JOIN support_tickets_priority ON support_tickets.priority = support_tickets_priority.id");


		// acceptable filter options
		$structure = NULL;
		$structure["fieldname"] = "date_start";
		$structure["type"]	= "date";
		$structure["sql"]	= "date >= 'value'";
		$support_ticket_list->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] = "date_end";
		$structure["type"]	= "date";
		$structure["sql"]	= "date <= 'value'";
		$support_ticket_list->add_filter($structure);
		
		$structure = NULL;
		$structure["fieldname"] = "searchbox";
		$structure["type"]	= "input";
		$structure["sql"]	= "title LIKE '%value%'";
		$support_ticket_list->add_filter($structure);



		// heading
		print "<h3>SUPPORT TICKETS</h3><br><br>";


		// options form
		$support_ticket_list->load_options_form();
		$support_ticket_list->render_options_form();


		// fetch all the support_ticket information
		$support_ticket_list->generate_sql();
		$support_ticket_list->load_data_sql();

		if (!count($support_ticket_list->columns))
		{
			print "<p><b>Please select some valid options to display.</b></p>";
		}
		elseif (!$support_ticket_list->data_num_rows)
		{
			print "<p><b>You currently have no support_tickets in your database.</b></p>";
		}
		else
		{
			// view link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$support_ticket_list->add_link("view", "support/view.php", $structure);

			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$support_ticket_list->add_link("journal", "support/journal.php", $structure);

			
			// display the table
			$support_ticket_list->render_table();

			// TODO: display CSV download link
		}

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
