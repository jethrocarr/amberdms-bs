<?php
/*
	charts/ledger.php
	
	access: accounts_charts_view (read-only)
		accounts_charts_write (write access)

	Displays a ledger for the selected chart, with options to be able to search, select date periods
	and other filter options.
*/

// include ledger functions
require("include/accounts/inc_ledger.php");


class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_ledger;


	function page_output()
	{
		// fetch variables
		$this->id = @security_script_input('/^[0-9]*$/', $_GET["id"]);

		if (!$this->id)
		{
			$this->id = @security_script_input('/^[0-9]*$/', $_GET["filter_id"]);
		}

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Account Details", "page=accounts/charts/view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Account Ledger", "page=accounts/charts/ledger.php&id=". $this->id ."", TRUE);

		if (user_permissions_get("accounts_charts_write"))
		{
			$this->obj_menu_nav->add_item("Delete Account", "page=accounts/charts/delete.php&id=". $this->id ."");
		}
	}



	function check_permissions()
	{
		return user_permissions_get("accounts_charts_view");
	}



	function check_requirements()
	{
		// verify that the account exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_charts WHERE id='". $this->id ."'";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested account (". $this->id .") does not exist - possibly the account has been deleted.");
			return 0;
		}

		unset($sql_obj);


		return 1;
	}


	function execute()
	{
		// define ledger
		$this->obj_ledger			= New ledger_account_list;
		$this->obj_ledger->ledgername	= "account_ledger";
		$this->obj_ledger->chartid	= $this->id;
		
		$this->obj_ledger->prepare_ledger();

		// define SQL structure
		$this->obj_ledger->prepare_generate_sql();

		// load data
		$this->obj_ledger->prepare_load_data();
	}


	function render_html()
	{
		// page title
		print "<h3>ACCOUNT LEDGER</h3>";
		print "<p>This page displays a list of transactions for the selected account. You can use the filter options to define dates and other search/filtering criteria.</p>";

		// display options form
		$this->obj_ledger->render_options_form();

		// display ledger
		$this->obj_ledger->render_table_html();

		// display CSV/PDF download link
		print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=csv&page=accounts/charts/ledger.php&id=". $this->id ."\">Export as CSV</a></p>";
		print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=pdf&page=accounts/charts/ledger.php&id=". $this->id ."\">Export as PDF</a></p>";
	}


	function render_csv()
	{
		// display ledger	
		$this->obj_ledger->render_table_csv();
	}


	function render_pdf()
	{
		// display ledger	
		$this->obj_ledger->render_table_pdf();
	}
}

?>
