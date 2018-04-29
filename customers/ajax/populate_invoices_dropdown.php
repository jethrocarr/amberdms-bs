<?php 
/*
	customers/ajax/populate_invoices_dropdown.php

	Function called by pages wanting lists of invoice dropdowns relating to a specific customer.

	Access
	customers_view
	or
	accounts_ar_view	[used by credit and invoicing pages]

	Fields
	[GET] id_customer
	[GET] id_selected
 *      [GET] inccan - if set to 1, includes cancelled invoices

	Returns
	<option value="INVOICE_ID">INVOICE_CODE</option>
*/

require("../../include/config.php");
require("../../include/amberphplib/main.php");


if (user_permissions_get('customers_view') || user_permissions_get('accounts_ar_view'))
{
	$id_customer 		= @security_script_input_predefined("int", $_GET['id_customer']);
	$id_selected		= @security_script_input_predefined("int", $_GET['id_selected']);
	
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id, code_invoice FROM account_ar WHERE amount_paid>0 AND customerid=" .$id_customer ." AND cancelled=0";
	$sql_obj->execute();

	
	if ($sql_obj->num_rows())
	{
		$sql_obj->fetch_array();

		$option_string = "<option value=\"0\"> -- select -- </option>";

		foreach ($sql_obj->data as $data_row)
		{
			$option_string	.= "<option value=\"" .$data_row['id']. "\"";
				if ($data_row['id'] == $id_selected)
				{
					$option_string	.= " selected=\"selected\"";
				}
			$option_string	.= ">" .$data_row['code_invoice']. "</option>";
		}
	}
	else
	{
		$option_string .= "<option value=\"\"> -- there are no invoices associated with this customer -- </option>";
	}

	unset($sql_obj);

	echo $option_string;
	
	exit(0);
}

?>
