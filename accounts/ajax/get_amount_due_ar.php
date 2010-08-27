<?php 
// includes
require("../../include/config.php");
require("../../include/amberphplib/main.php");

if (user_permissions_get('accounts_ar_write'))
{
	$id			= @security_script_input_predefined("int", $_GET['id']);

	// verify input
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT amount_total, amount_paid FROM `account_ar` WHERE id='". $id ."'";
	$sql_obj->execute();

	if ($sql_obj->num_rows())
	{
		$sql_obj->fetch_array();

		//amount due is total amount - amount paid
		$amount_due = $sql_obj->data[0]["amount_total"] - $sql_obj->data[0]["amount_paid"];
		
		//don't show negatives
		if ($amount_due < 0)
		{
			$amount_due = 0;
		}
		
		echo format_money($amount_due, true);
	}	

}

exit(0);

?>