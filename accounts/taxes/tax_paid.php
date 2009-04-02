<?php
/*
	accounts/taxes/tax_paid.php
	
	access: accounts_taxes_view

	Report on tax paid on either an invoiced or cash basis.
*/

// include tax functions
require("include/accounts/inc_taxes.php");




class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_taxreport;


	function page_output()
	{
		// fetch variables
		$this->id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		if (!$this->id)
			$this->id = security_script_input('/^[0-9]*$/', $_GET["filter_id"]);
		

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
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_taxes WHERE id='". $this->id ."' LIMIT 1";
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
		$this->obj_taxreport 		= New taxes_report_transactions;
		$this->obj_taxreport->mode	= "paid";
		$this->obj_taxreport->taxid	= $this->id;

		$this->obj_taxreport->execute();
	}

	function render_html()
	{
		// page heading
		print "<h3>TAX PAID</h3>";
		print "<p>This page allows you to generate reports on how much tax has been paid on either an Accural/Invoice or Cash basis for a selectable time period.</p>";
		
		print "<p><i>Note: The cash selection mode will display based on the payments falling in the data selection period and will include partially paid invoices. The only invoices not displayed would be an invoice which has been overpaid.</i></p>";


		// display tax report
		if ($this->obj_taxreport->render_html())
		{
			// display CSV download link
			print "<p align=\"right\"><a href=\"index-export.php?mode=csv&page=accounts/taxes/tax_paid.php&id=". $this->id ."\">Export as CSV</a></p>";
		}
	}


	function render_csv()
	{
		$this->obj_taxreport->render_csv();
	}

}

?>
