<?php
/*
	services/groups.php

	access:	services_write

	Allows definition of service groups that can be used to organise services for billing purposes.
*/


class page_output
{
	var $obj_table;


	function check_permissions()
	{
		return user_permissions_get("services_write");
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
		$this->obj_table->tablename	= "service_groups";


		// define all the columns and structure
		$this->obj_table->add_column("standard", "group_name", "");
		$this->obj_table->add_column("standard", "group_description", "");

		// defaults
		$this->obj_table->columns		= array("group_name", "group_description");
		$this->obj_table->columns_order		= array("group_name");

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("service_groups");
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "");

		// fetch all the service group information
		$this->obj_table->generate_sql();
		$this->obj_table->load_data_sql();

	}



	function render_html()
	{
		// heading
		print "<h3>SERVICE GROUPS</h3><br>";
		print "<p>Here you can define the different service group options available for organising services into. These are used for headers when generating invoices.</p>";


		// display options form
		$this->obj_table->render_options_form();


		// display table
		if (!$this->obj_table->data_num_rows)
		{
			format_msgbox("important", "<p>There are currently no service groups in the database.</p>");
		}
		else
		{
			// links
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("tbl_lnk_details", "services/groups-view.php", $structure);

			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("tbl_lnk_delete", "services/groups-delete.php", $structure);


			// display the table
			$this->obj_table->render_table_html();

			// display CSV/PDF download link
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=csv&page=services/groups.php\">Export as CSV</a></p>";
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=pdf&page=services/groups.php\">Export as PDF</a></p>";

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
