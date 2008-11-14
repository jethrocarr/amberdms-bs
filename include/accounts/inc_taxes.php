<?php
/*
	include/accounts/inc_taxes

	Proves functions for working with taxes, in particular it provides
	a function for generating tax reports.
*/


/*
	FUNCTIONS
*/


/*
	taxes_report_transactions

	This function displays a table showing all the tax collected (AR) or paid (AP).

	Values
	mode		"collected" or "paid"
	taxid		ID of the tax to display

	Return codes
	0		failure
	1		success
*/	
function taxes_report_transactions($mode, $taxid)
{

	if ($mode == "collected")
	{
		$type = "ar";
	}
	elseif ($mode == "paid")
	{
		$type = "ap";
	}
	else
	{
		return 0;
	}


	/*
		Define table structure
	*/
	
	$tax_table = New table;

	// configure the table
	$tax_table->language	= $_SESSION["user"]["lang"];
	$tax_table->tablename	= "tax_report_$type";

	// define all the columns and structure
	$tax_table->add_column("date", "date_trans", "account_$type.date_trans");
	
	$tax_table->add_column("standard", "code_invoice", "account_$type.code_invoice");

	if ($type == "ap")
	{
		$tax_table->add_column("standard", "name_vendor", "vendors.name_vendor");
	}
	else
	{
		$tax_table->add_column("standard", "name_customer", "customers.name_customer");
	}
		
	$tax_table->add_column("money", "amount", "account_$type.amount");
	$tax_table->add_column("money", "amount_tax", "NONE");


	// total rows
	$tax_table->total_columns	= array("amount", "amount_tax",);
	$tax_table->total_rows		= array("amount", "amount_tax");

	// defaults
	if ($type == "ap")
	{
		$tax_table->columns		= array("date_trans", "code_invoice", "name_vendor", "amount", "amount_tax");
		$tax_table->columns_order	= array("date_trans", "name_vendor");
	}
	else
	{
		$tax_table->columns		= array("date_trans", "code_invoice", "name_customer", "amount", "amount_tax");
		$tax_table->columns_order	= array("date_trans", "name_customer");
	}

	// define SQL structure
	$tax_table->sql_obj->prepare_sql_settable("account_$type");

	if ($type == "ap")
	{
		$tax_table->sql_obj->prepare_sql_addjoin("LEFT JOIN vendors ON account_$type.vendorid = vendors.id");
	}
	else
	{
		$tax_table->sql_obj->prepare_sql_addjoin("LEFT JOIN customers ON account_$type.customerid = customers.id");
	}
	
	$tax_table->sql_obj->prepare_sql_addfield("id", "account_$type.id");



	/*
		Filter Options
	*/

	// acceptable filter options
	$tax_table->add_fixed_option("id", $taxid);
		
	$structure = NULL;
	$structure["fieldname"] = "date_start";
	$structure["type"]	= "date";
	$tax_table->add_filter($structure);

	$structure = NULL;
	$structure["fieldname"] = "date_end";
	$structure["type"]	= "date";
	$tax_table->add_filter($structure);
	
	$structure = NULL;
	$structure["fieldname"] = "mode";
	$structure["type"]	= "radio";
	$structure["values"]	= array("Accrual/Invoice", "Cash");
	$tax_table->add_filter($structure);


	// options form
	$tax_table->load_options_form();
	$tax_table->render_options_form();



	/*
		Create SQL filters from user-selected options

		These filters are too complex to perform using the standard SQL based filtering
		of the tables class proved by amberphplib, so we have to use this code
		to manipulate the class data structure directly
	*/

	// depending on the filter options, generate SQL filtering rules
	if ($tax_table->filter["filter_mode"]["defaultvalue"] == "Cash")
	{
		// cash mode


		// select all invoices in the desired time period
		$tax_table->sql_obj->prepare_sql_addwhere("date_trans >= '". $tax_table->filter["filter_date_start"]["defaultvalue"] ."'");
		$tax_table->sql_obj->prepare_sql_addwhere("date_trans <= '". $tax_table->filter["filter_date_end"]["defaultvalue"] ."'");

		// limit invoice selection to only fully paid invoices
		$tax_table->sql_obj->prepare_sql_addwhere("amount_total=amount_paid");
	}
	else
	{
		// invoice mode
		
		// select all invoices in the desired time period
		$tax_table->sql_obj->prepare_sql_addwhere("date_trans >= '". $tax_table->filter["filter_date_start"]["defaultvalue"] ."'");
		$tax_table->sql_obj->prepare_sql_addwhere("date_trans <= '". $tax_table->filter["filter_date_end"]["defaultvalue"] ."'");
	}



	// execute SQL and load data
	$tax_table->generate_sql();
	$tax_table->load_data_sql();



	/*
		Generate tax totals per invoice

		Note that the account_$type.total_tax may include the amount of other taxes,
		so we need to total up the tax ourselves and work out the sum.
	*/


	if ($tax_table->data_num_rows)
	{

		$deleted_invoices = 0;

		for ($i=0; $i < $tax_table->data_num_rows; $i++)
		{
			/*
				TODO

				There are two approaches to solving this problem:
				
				1. Fetch totals for the selected tax type for all invoices into
				   an array, and then pull the data we want from that
				   
				2. Fetch total for each invoice by using a seporate sql query. This is
				   the approach chosen here.

				Option #1 will be more efficent initally, but could cause huge slowdowns once users
				end up with large databases of many/complex invoices.

				Option #2 may be a bit inefficent on large queries, but at worst the user will only
				be looking at between 1 to 12 months worth of invoices.

				Possibly some tests should be carried out in order to determine the optimal query method
				here.
			*/
		
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT SUM(amount) as amount FROM account_items WHERE type='tax' AND customid='$taxid' AND invoiceid='". $tax_table->data[$i]["id"] ."'";
			$sql_obj->execute();
			$sql_obj->fetch_array();

			if (!$sql_obj->data[0]["amount"])
			{
				// delete this invoice from the list, since it has no tax items of the type that we want
				unset($tax_table->data[$i]);
				$deleted_invoices++;
			}
			else
			{
				
				// add the amount to the data
				$tax_table->data[$i]["amount_tax"] = $sql_obj->data[0]["amount"];
			}
		}

		// re-index the data results to fix any holes created
		// by deleted invoices
		$tax_table->data		= array_values($tax_table->data);
		$tax_table->data_num_rows	= $tax_table->data_num_rows - $deleted_invoices;

	}
		


	/*
		Display Table

		Note that the render_table function also performs the total row and total column generation tasks.
	*/
	
	if (!$tax_table->filter["filter_date_start"]["defaultvalue"] || !$tax_table->filter["filter_date_end"]["defaultvalue"])
	{
		print "<p><b>Please enter a time period using the form above.</b></p>";
	}
	else
	{
		$tax_table->render_table();
	}
		

	return 1;
	
} // end of taxes_report_transactions



?>
