<?php
/*
	accounts/taxes/ledger.php
	
	access: accounts_taxes_view

	Links to other pages with information for the best way to get tax ledgers.
*/

// include ledger functions
require("include/accounts/inc_ledger.php");



class page_output
{
	var $id;
	var $obj_menu_nav;
	
	var $obj_sql_tax;


	function page_output()
	{
		// fetch variables
		$this->id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		if (!$this->id)
		{
			$this->id = security_script_input('/^[0-9]*$/', $_GET["filter_id"]);
		}

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Tax Details", "page=accounts/taxes/view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Tax Ledger", "page=accounts/taxes/ledger.php&id=". $this->id ."", TRUE);

		if (user_permissions_get("accounts_taxes_write"))
		{
			$this->obj_menu_nav->add_item("Delete Tax", "page=accounts/taxes/delete.php&id=". $this->id ."");
		}
	}



	function check_permissions()
	{
		return user_permissions_get("accounts_taxes_view");
	}



	function check_requirements()
	{
		// verify that the tax exists
		$this->obj_sql_tax		= New sql_query;
		$this->obj_sql_tax->string	= "SELECT chartid, CONCAT_WS(' -- ',account_charts.code_chart,account_charts.description) as name_chart FROM account_taxes LEFT JOIN account_charts ON account_charts.id = account_taxes.chartid WHERE account_taxes.id='". $this->id ."' LIMIT 1";
		$this->obj_sql_tax->execute();

		if (!$this->obj_sql_tax->num_rows())
		{
			log_write("error", "page_output", "The requested tax (". $this->id .") does not exist - possibly the tax has been deleted.");
			return 0;
		}

		return 1;
	}


	function execute()
	{
		$this->obj_sql_tax->fetch_array();
	}

	function render_html()
	{
		/*
			Page Heading
		*/
		print "<h3>TAX LEDGER</h3>";
		print "<p>There are 3 different types of ledger-style reports you can generate for taxes.</p>";


		print "<br><p>";
		print "<b>1. Account Ledger</b><br><br>";
		print "Transactions for this tax are entered against account \"". $this->obj_sql_tax->data[0]["name_chart"] ."\". You can <a href=\"index.php?page=accounts/charts/ledger.php&id=". $this->obj_sql_tax->data[0]["chartid"] ."\">view the ledger for this account here</a>.<br>";
		print "</p>";

		print "<br><p>";
		print "<b>2. Tax Collected Report</b><br><br>";
		print "Generate reports on the amount of tax collected either on an invoice or cash basis from accounts recievables using the <a href=\"index.php?page=accounts/taxes/tax_collected.php&id=". $this->id ."\">AR tax collected report</a>.<br>";
		print "</p>";

		print "<br><p>";
		print "<b>3. Tax Paid Report</b><br><br>";
		print "Generate reports on the amount of tax paid either on an invoice or cash basis from accounts payable using the <a href=\"index.php?page=accounts/taxes/tax_paid.php&id=". $this->id ."\">AP tax paid report</a>.<br>";
		print "</p>";
	
	}
}


?>
