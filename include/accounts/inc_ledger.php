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
	customid2	customid2 linking ID
	date_trans	date in YYYY-MM-DD format
	chartid		ID of the chart/account
	amount		financial amount
	source		description field used by payments
	memo		description/memo field

	Return codes
	0		failure
	1		success
*/	
function ledger_trans_add($mode, $type, $customid, $customid2, $date_trans, $chartid, $amount, $source, $memo)
{
	log_debug("inc_ledger", "ledger_trans_add($mode, $type, $customid, $customid2, $date_trans, $chartid, $amount, $source, $memo)");

	
	// insert transaction
	$sql_obj		= New sql_query;
	$sql_obj->string	= "INSERT "
				."INTO account_trans ("
				."type, "
				."customid, "
				."customid2, "
				."date_trans, "
				."chartid, "
				."amount_$mode, "
				."source, "
				."memo "
				.") VALUES ("
				."'$type', "
				."'$customid', "
				."'$customid2', "
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



?>
