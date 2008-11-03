<?php
/*
	include/accounts/inc_ledger.php

	Provides functions for working with the ledger, in particular a number of double entry accounting
	functions.

	Double entry accounting note:
	
		Double entry accounting requires two entries for any financial invoice
		1. Credit the source of the invoice (eg: withdrawl funds from current account)
		2. Debit the destination (eg: pay an expense account)
			
		For AR/AP invoices, we credit the summary account choosen by the user
		and debit the various accounts for all the items.
*/



/*
	FUNCTIONS
*/


/*
	ledger_trans_add

	This function creates a new entry in account_trans in the database - this function is
	used by both the general ledger and also the invoicing code in order to make transactions.

	Values
	mode		"credit" or "debit" - the direction of the transaction
	type		Value for type field
	customid	custom linking ID
	date_trans	date in YYYY-MM-DD format
	chartid		ID of the chart/account
	amount		financial amount
	source		description field used by payments
	memo		description/memo field

	Return codes
	0		failure
	1		success
*/	
function ledger_trans_add($mode, $type, $customid, $date_trans, $chartid, $amount, $source, $memo)
{
	log_debug("inc_ledger", "ledger_trans_add($mode, $type, $customid, $date_trans, $chartid, $amount, $source, $memo)");

	
	// insert transaction
	$sql_obj		= New sql_query;
	$sql_obj->string	= "INSERT "
				."INTO account_trans ("
				."type, "
				."customid, "
				."date_trans, "
				."chartid, "
				."amount_$mode, "
				."source, "
				."memo "
				.") VALUES ("
				."'$type', "
				."'$customid', "
				."'". $date_trans ."', "
				."'". $chartid ."', "
				."'". $amount ."', "
				."'". $source ."', "
				."'". $memo ."' "
				.")";
	if ($sql_obj->execute())
	{
		return 1;
	}
	
	return 0;
	
} // end of ledger_trans_add




/*
	CLASSES
*/



/*
	class ledger_account_list

	Provides functions for generating a ledger list and rendering it.

	Most of the work is performed by the Amberphplib table class, with the ledger_account_list
	class providing wrapper functions as well as a structure to allow customisation by the scripts
	calling it.

	For example, one common desire is to tweak the SQL query made to generate the ledger list, which
	can be done by accessing the $this->obj_table->sql_obj object directly.
*/

class ledger_account_list
{
	var $ledgername;			// name of the ledger - used for internal purposes, not displayed
	var $language = "en_us";		// language to use for the form labels.
	
	var $chartid;				// ID of account to fetch ledger for
	var $obj_table;				// object used for hosting the table object


	/*
		ledger_account_list()

		Constructor Function
	*/
	function ledger_account_list()
	{
		// init the table object
		$this->obj_table = New table;
	}


	/*
		prepare_ledger()

		Defines and configures the ledger
	*/
	function prepare_ledger()
	{
		log_debug("ledger_account_list", "Executing prepare_ledger()");
		
		// configure the table
		$this->obj_table->language	= $_SESSION["user"]["lang"];
		$this->obj_table->tablename	= $this->ledgername;

		// define all the columns and structure
		$this->obj_table->add_column("date", "date_trans", "account_trans.date_trans");
		$this->obj_table->add_column("standard", "item_id", "account_trans.customid");
//		$this->obj_table->add_column("standard", "dest_name_chart", "CONCAT_WS(' -- ',account_charts.code_chart,account_charts.description)");
		$this->obj_table->add_column("price", "debit", "account_trans.amount_debit");
		$this->obj_table->add_column("price", "credit", "account_trans.amount_credit");
		$this->obj_table->add_column("standard", "source", "account_trans.source");
		$this->obj_table->add_column("standard", "memo", "account_trans.memo");

		// total rows
		$this->obj_table->total_columns		= array("credit", "debit");
		$this->obj_table->total_rows		= array("credit", "debit");
		$this->obj_table->total_rows_mode	= "incrementing";
		
		// defaults
		$this->obj_table->columns		= array("date_trans", "item_id", "debit", "credit");
		$this->obj_table->columns_order		= array("date_trans");

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("account_trans");
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "account_trans.id");
		$this->obj_table->sql_obj->prepare_sql_addfield("type", "account_trans.type");
//		$this->obj_table->sql_obj->prepare_sql_addfield("item_id", "account_trans.customid");
		$this->obj_table->sql_obj->prepare_sql_addwhere("chartid='". $this->chartid ."'");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN account_charts ON account_charts.id = account_trans.chartid");
	}





	/*
		prepare_generate_sql()

		Generate the sql commands to fetch all the data.
	*/
	function prepare_generate_sql()
	{
		// add ID orderby rule to make sure if a payment and invoice item have the same date,
		// that the invoice will come first.
		//
		// TODO: This isn't a perfect solution, look into a better solution
		//
		$this->obj_table->sql_obj->prepare_sql_addorderby("account_trans.id");


		// fetch all the ledger information
		$this->obj_table->generate_sql();
	}


	/*
		prepare_load_data()

		Load the data by executing the SQL query
	*/
	function prepare_load_data()
	{
		log_debug("ledger_account_list", "Executing prepare_load_data()");
		
		// execute the SQL command to import the data into
		// the table structure
		$this->obj_table->load_data_sql();
	}



	/*
		render_options_form()

		Configures and displays an options form
	*/
	function render_options_form()
	{
		log_debug("ledger_account_list", "Executing render_options_form()");
		
		// acceptable filter options
		$this->obj_table->add_fixed_option("id", $this->chartid);
			
		$structure = NULL;
		$structure["fieldname"] = "date_start";
		$structure["type"]	= "date";
		$structure["sql"]	= "account_trans.date_trans >= 'value'";
		$this->obj_table->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] = "date_end";
		$structure["type"]	= "date";
		$structure["sql"]	= "account_trans.date_trans <= 'value'";
		$this->obj_table->add_filter($structure);
			
		// options form
		$this->obj_table->load_options_form();
		$this->obj_table->render_options_form();
	}




	/*
		render_table_html()

		Generate HTML table
	*/
	function render_table_html()
	{
		log_debug("ledger_account_list", "Executing render_table_html()");

		/*
			Label the items the transaction belongs to
		
			Because there are range of different items types (ar, ap, general ledger, etc) we need
			to check the type of the ledger entry, then display the correct title and link
		*/
		if ($this->obj_table->data_num_rows)
		{
			for ($i=0; $i < count(array_keys($this->obj_table->data)); $i++)
			{
				switch ($this->obj_table->data[$i]["type"])
				{
					case "ar":
					case "ar_tax":

						// for AR invoices/transaction fetch the invoice ID
						$result = sql_get_singlevalue("SELECT code_invoice as value FROM account_ar WHERE id='". $this->obj_table->data[$i]["item_id"] ."'");
						
						$this->obj_table->data[$i]["item_id"] = "<a href=\"index.php?page=accounts/ar/invoice-view.php&id=". $this->obj_table->data[$i]["item_id"] ."\">AR invoice $result</a>";
					break;

					case "ar_pay":
						// for AR invoice payments fetch the invoice ID
						$result = sql_get_singlevalue("SELECT code_invoice as value FROM account_ar WHERE id='". $this->obj_table->data[$i]["item_id"] ."'");
						
						$this->obj_table->data[$i]["item_id"] = "<a href=\"index.php?page=accounts/ar/invoice-payments.php&id=". $this->obj_table->data[$i]["item_id"] ."\">AR payment $result</a>";
					break;



					default:
						$this->obj_table->data[$i]["item_id"] = "unknown";
					break;
				}
				
			}
		}


		if (!count($this->obj_table->columns))
		{
			print "<p><b>Please select some valid options to display.</b></p>";
		}
		elseif (!$this->obj_table->data_num_rows)
		{
			print "<p><b>No transactions which match your search criteria belong in this ledger.</b></p>";
		}
		else
		{
/*
			TODO: the links are going to depend on the type of transaction
// view link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("view", "ar/accounts/view.php", $structure);
*/

			// display the table
			$this->obj_table->render_table();

			// TODO: display CSV download link
		}

	}
	
} // end of ledger_account_list class



?>
