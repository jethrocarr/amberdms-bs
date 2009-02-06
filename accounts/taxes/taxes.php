<?php
/*
	accounts/taxes/taxes.php
	
	access: accounts_taxes_view

	Displays a list of all the taxes on the system.
*/


class page_output
{
	var $obj_table;


	function check_permissions()
	{
		return user_permissions_get('accounts_taxes_view');
	}

	function check_requirements()
	{
		// nothing todo
		return 1;
	}


	function execute()
	{
		// establish a new table object
		$this->obj_table = New table;

		$this->obj_table->language	= $_SESSION["user"]["lang"];
		$this->obj_table->tablename	= "account_taxes";

		// define all the columns and structure
		$this->obj_table->add_column("standard", "name_tax", "account_taxes.name_tax");
		$this->obj_table->add_column("standard", "taxrate", "account_taxes.taxrate");
		$this->obj_table->add_column("standard", "chartid", "account_charts.code_chart");
		$this->obj_table->add_column("standard", "taxnumber", "account_taxes.taxnumber");
		$this->obj_table->add_column("standard", "description", "account_taxes.description");

		// defaults
		$this->obj_table->columns		= array("name_tax", "taxrate", "chartid", "taxnumber", "description");
		$this->obj_table->columns_order		= array("name_tax");

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("account_taxes");
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "account_taxes.id");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN account_charts ON account_charts.id = account_taxes.chartid");

		// fetch all the tax information
		$this->obj_table->generate_sql();
		$this->obj_table->load_data_sql();


	}


	function render_html()
	{
		// heading
		print "<h3>TAXES</h3>";
		print "<p>This page list all the taxes added to the system.</p>";
		

		// display table
		if (!count($this->obj_table->columns))
		{
			format_msgbox("important", "<p>Please select some valid options to display.</p>");
		}
		elseif (!$this->obj_table->data_num_rows)
		{
			format_msgbox("info", "<p>You currently have no taxes in your database.</p>");
		}
		else
		{
			// view link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("view", "accounts/taxes/view.php", $structure);

			// tax collected link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("collected", "accounts/taxes/tax_collected.php", $structure);

			// tax paid link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("paid", "accounts/taxes/tax_paid.php", $structure);

			// display the table
			$this->obj_table->render_table_html();

			// display CSV download link
			print "<p align=\"right\"><a href=\"index-export.php?mode=csv&page=accounts/taxes/taxes.php\">Export as CSV</a></p>";
		}
	}
	
}

?>
