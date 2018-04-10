<?php
/*
	accounts/taxes/ledger.php
	
	access: accounts_taxes_view

	Provides a link to the view ledger page.
*/



class page_output
{
	var $id;
	var $obj_menu_nav;
	
	var $obj_sql_tax;


	function __construct()
	{
		// fetch variables
		$this->id = @security_script_input('/^[0-9]*$/', $_GET["id"]);

		if (!$this->id)
		{
			$this->id = @security_script_input('/^[0-9]*$/', $_GET["filter_id"]);
		}

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Tax Details", "page=accounts/taxes/view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Tax Ledger", "page=accounts/taxes/ledger.php&id=". $this->id ."", TRUE);
		$this->obj_menu_nav->add_item("Tax Collected", "page=accounts/taxes/tax_collected.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Tax Paid", "page=accounts/taxes/tax_paid.php&id=". $this->id ."");


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
		print "<p>Transactions for this tax are entered against account \"". $this->obj_sql_tax->data[0]["name_chart"] ."\". If you want to inspect the ledger itself, use the button below, but be aware that the ledger could potentially be used by other taxes as well.</p>";
		print "<p><a class=\"button\" href=\"index.php?page=accounts/charts/ledger.php&id=". $this->obj_sql_tax->data[0]["chartid"] ."\">View Account Ledger</a></p>";
	}
}


?>
