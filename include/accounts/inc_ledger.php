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
	ledger_trans_typelabel

	Performs a look up based on the suppplied type, to generate a hyperlinked label
	for use in ledgers.

	Values
	type		Transaction type (account_trans.type)
	customid	Custom ID filed (account_trans.customid)

	Return
	string		Label to be used
*/
function ledger_trans_typelabel($type, $customid)
{
	log_debug("inc_ledger", "Executing ledger_trans_typelabel($type, $customid)");

	switch ($type)
	{
		case "ar":
		case "ar_tax":
			// for AR invoices/transaction fetch the invoice ID
			$result = sql_get_singlevalue("SELECT code_invoice as value FROM account_ar WHERE id='$customid'");
						
			$result = "<a href=\"index.php?page=accounts/ar/invoice-view.php&id=$customid\">AR invoice $result</a>";
		break;

		case "ar_pay":
			// for AR invoice payments fetch the invoice ID
			$result = sql_get_singlevalue("SELECT code_invoice as value FROM account_ar WHERE id='$customid'");
						
			$result = "<a href=\"index.php?page=accounts/ar/invoice-payments.php&id=$customid\">AR payment $result</a>";
		break;

		
		case "ap":
		case "ap_tax":
			// for AP invoices/transaction fetch the invoice ID
			$result = sql_get_singlevalue("SELECT code_invoice as value FROM account_ap WHERE id='$customid'");
						
			$result = "<a href=\"index.php?page=accounts/ap/invoice-view.php&id=$customid\">AP invoice $result</a>";
		break;

		case "ap_pay":
			// for AP invoice payments fetch the invoice ID
			$result = sql_get_singlevalue("SELECT code_invoice as value FROM account_ap WHERE id='$customid'");
						
			$result = "<a href=\"index.php?page=accounts/ap/invoice-payments.php&id=$customid\">AP payment $result</a>";
		break;

		

		default:
			$result = "unknown";
		break;
	}

	return $result;

} // end of ledger_trans_typelabel





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
		$this->obj_table->add_column("standard", "source", "account_trans.source");
		$this->obj_table->add_column("standard", "memo", "account_trans.memo");
		$this->obj_table->add_column("money", "debit", "account_trans.amount_debit");
		$this->obj_table->add_column("money", "credit", "account_trans.amount_credit");
//		$this->obj_table->add_column("money", "total", "NONE");

		// total rows
		$this->obj_table->total_columns		= array("debit", "credit");
		$this->obj_table->total_rows		= array("debit", "credit");

		// determine add mode - depending on the account type, we either need to add debit or add credit
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT account_chart_type.total_mode FROM account_charts LEFT JOIN account_chart_type ON account_chart_type.id = account_charts.chart_type WHERE account_charts.id='". $this->chartid ."' LIMIT 1";
		$sql_obj->execute();
		$sql_obj->fetch_array();
		
		$this->obj_table->total_rows_mode	= "ledger_add_". $sql_obj->data[0]["total_mode"];


		
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

		Load the data by executing the SQL query and perform any custom 
		modifications.
	*/
	function prepare_load_data()
	{
		log_debug("ledger_account_list", "Executing prepare_load_data()");
	
		/*
			Import the data from the SQL database
		*/
		$this->obj_table->load_data_sql();		


		/*
			Calculate the total column

		if ($this->obj_table->data_num_rows)
		{
			$total_counter = 0;
			
			for ($i=0; $i < count(array_keys($this->obj_table->data)); $i++)
			{
				if ($this->obj_table->data[$i]["debit"] > 0)
				{
					$total_counter = $total_counter + $this->obj_table->data[$i]["debit"];
				}
				else
				{
					$total_counter = $total_counter - $this->obj_table->data[$i]["credit"];
				}

				$this->obj_table->data[$i]["total"] = $total_counter;
			}
		}
		*/
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
				$this->obj_table->data[$i]["item_id"] = ledger_trans_typelabel($this->obj_table->data[$i]["type"], $this->obj_table->data[$i]["item_id"]);
			}
		}



		/*
			Display the table
		*/
		
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
