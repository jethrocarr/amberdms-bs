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
		$this->obj_table->add_column("standard", "description", "account_taxes.description");

		// defaults
		$this->obj_table->columns		= array("name_tax", "taxrate", "chartid", "description");
		$this->obj_table->columns_order		= array("name_tax");

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("account_taxes");
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "account_taxes.id");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN account_charts ON account_charts.id = account_taxes.chartid");

/*
		// acceptable filter options
		$structure = NULL;
		$structure["fieldname"] = "date_start";
		$structure["type"]	= "date";
		$structure["sql"]	= "date >= 'value'";
		$this->obj_table->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] = "date_end";
		$structure["type"]	= "date";
		$structure["sql"]	= "date <= 'value'";
		$this->obj_table->add_filter($structure);
		
		$structure = NULL;
		$structure["fieldname"] = "searchbox";
		$structure["type"]	= "input";
		$structure["sql"]	= "name_tax LIKE '%value%' OR name_contact LIKE '%value%' OR contact_email LIKE '%value%' OR contact_phone LIKE '%value%' OR contact_fax LIKE '%fax%'";
		$this->obj_table->add_filter($structure);
*/


		// load options
		$this->obj_table->load_options_form();

		// fetch all the tax information
		$this->obj_table->generate_sql();
		$this->obj_table->load_data_sql();



	}


	function render_html()
	{
		// heading
		print "<h3>TAXES</h3><br><br>";

		// display options form
		$this->obj_table->render_options_form();


		// display table
		if (!count($this->obj_table->columns))
		{
			print "<p><b>Please select some valid options to display.</b></p>";
		}
		elseif (!$this->obj_table->data_num_rows)
		{
			print "<p><b>You currently have no taxes in your database.</b></p>";
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
