<?php
/*
	accounts/taxes/tax_collected.php
	
	access: accounts_taxes_view (read-only)

	Report on tax collected on either an invoiced or cash basis.
*/

// include tax functions
require("include/accounts/inc_taxes.php");


if (user_permissions_get('accounts_taxes_view'))
{
	if ($_GET["id"])
	{
		$id = $_GET["id"];
	}
	else
	{
		$id = $_GET["filter_id"];
	}
	

	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "Tax Details";
	$_SESSION["nav"]["query"][]	= "page=accounts/taxes/view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Tax Ledger";
	$_SESSION["nav"]["query"][]	= "page=accounts/taxes/ledger.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=accounts/taxes/ledger.php&id=$id";
	
	if (user_permissions_get('accounts_taxes_write'))
	{
		$_SESSION["nav"]["title"][]	= "Delete Tax";
		$_SESSION["nav"]["query"][]	= "page=accounts/taxes/delete.php&id=$id";
	}



	function page_render()
	{
		if ($_GET["id"])
		{
			$id = security_script_input('/^[0-9]*$/', $_GET["id"]);
		}
		else
		{
			$id = security_script_input('/^[0-9]*$/', $_GET["filter_id"]);
		}
		
		/*
			Verify that the tax exists
		*/
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_taxes WHERE id='$id' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			print "<p><b>Error: The requested tax does not exist. <a href=\"index.php?page=accounts/taxes/taxes.php\">Try looking for your tax on the taxes list page.</a></b></p>";
		}
		else
		{
			/*
				Page Heading
			*/
			print "<h3>TAX COLLECTED</h3>";
			print "<p>This page allows you to generate reports on how much tax has been collected on invoices on either an Accural/Invoice or Cash basis for a selectable time period.</p>";

			print "<p><i>Note: The cash selection mode will only display invoices which have been fully paid - any partially paid invoices will only appear when the Accural/Invoice selection mode is used.</i></p>";


			/*
				Display tax report
			*/

			taxes_report_transactions("collected", $id);
		
		
		} // end if tax exists

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
