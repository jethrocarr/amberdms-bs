<?php
/*
	accounts/quotes/quotes.php
	
	access: accounts_quotes_view

	Lists all the quotes and allows the user to search through them.
*/

class page_output
{
	var $obj_table;


	function check_permissions()
	{
		return user_permissions_get('accounts_quotes_view');
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

		$this->obj_table->language		= $_SESSION["user"]["lang"];
		$this->obj_table->tablename		= "account_quotes";

		// define all the columns and structure
		$this->obj_table->add_column("standard", "name_customer", "customers.name_customer");
		$this->obj_table->add_column("standard", "name_staff", "staff.name_staff");
		$this->obj_table->add_column("standard", "code_quote", "account_quotes.code_quote");
		$this->obj_table->add_column("date", "date_trans", "account_quotes.date_trans");
		$this->obj_table->add_column("date", "date_validtill", "account_quotes.date_validtill");
		$this->obj_table->add_column("price", "amount_tax", "account_quotes.amount_tax");
		$this->obj_table->add_column("price", "amount", "account_quotes.amount");
		$this->obj_table->add_column("price", "amount_total", "account_quotes.amount_total");
		$this->obj_table->add_column("bool_tick", "sent", "account_quotes.sentmethod");

		// totals
		$this->obj_table->total_columns	= array("amount_tax", "amount", "amount_total");

		
		// defaults
		$this->obj_table->columns	= array("name_customer", "code_quote", "date_trans", "amount_total");
		$this->obj_table->columns_order	= array("code_quote");

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("account_quotes");
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "account_quotes.id");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN customers ON customers.id = account_quotes.customerid");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN staff ON staff.id = account_quotes.employeeid");


		// acceptable filter options
		$structure = NULL;
		$structure["fieldname"] = "date_start";
		$structure["type"]	= "date";
		$structure["sql"]	= "date_trans >= 'value'";
		$this->obj_table->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] = "date_end";
		$structure["type"]	= "date";
		$structure["sql"]	= "date_trans <= 'value'";
		$this->obj_table->add_filter($structure);
		
		$structure		= form_helper_prepare_dropdownfromdb("employeeid", "SELECT id, name_staff as label FROM staff ORDER BY name_staff ASC");
		$structure["sql"]	= "account_quotes.employeeid='value'";
		$this->obj_table->add_filter($structure);

		$structure		= form_helper_prepare_dropdownfromdb("customerid", "SELECT id, name_customer as label FROM customers ORDER BY name_customer ASC");
		$structure["sql"]	= "account_quotes.customerid='value'";
		$this->obj_table->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "hide_closed";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "Hide Expired Quotes";
		$structure["defaultvalue"]	= "enabled";
		$structure["sql"]		= "account_quotes.date_validtill > '". date("Y-m-d") ."'";
		$this->obj_table->add_filter($structure);

		// load options
		$this->obj_table->load_options_form();

		// fetch all the chart information
		$this->obj_table->generate_sql();
		$this->obj_table->load_data_sql();

	}


	function render_html()
	{
		// heading
		print "<h3>LIST OF QUOTES</h3><br><br>";

		// display options form
		$this->obj_table->render_options_form();

		// display data
		if (!count($this->obj_table->columns))
		{
			format_msgbox("important", "<p>Please select some valid options to display.</p>");
		}
		elseif (!$this->obj_table->data_num_rows)
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM account_quotes LIMIT 1";
			$sql_obj->execute();
			
			if ($sql_obj->num_rows())
			{
				format_msgbox("important", "<p>Your current filter options do not match to any quotes.</p>");
			}
			else
			{
				format_msgbox("info", "<p>You currently have no quotes in your database.</p>");
			}
		}
		else
		{
			// details link 
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("details", "accounts/quotes/quotes-view.php", $structure);

			// items link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("items", "accounts/quotes/quotes-items.php", $structure);

			// journal link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("journal", "accounts/quotes/journal.php", $structure);



			// display the table
			$this->obj_table->render_table_html();

			// display CSV download link
			print "<p align=\"right\"><a href=\"index-export.php?mode=csv&page=accounts/ar/ar.php\">Export as CSV</a></p>";
			
		}

	} // end page_render


	function render_csv()
	{
		$this->obj_table->render_table_csv();
	}

}

?>
