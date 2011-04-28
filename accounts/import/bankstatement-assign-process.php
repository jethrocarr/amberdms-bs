<?php
/*
	bankstatement-assign-process.php
	
	access: "accounts_import_statement" group members

	Takes uploaded transaction information and applies to invoices/ledgers.

	TODO: This file requires further cleanup and improvements
*/

require("../../include/config.php");
require("../../include/amberphplib/main.php");

require("../../include/accounts/inc_invoices.php");
require("../../include/accounts/inc_invoices_items.php");
require("../../include/accounts/inc_gl.php");


if (user_permissions_get("accounts_import_statement"))
{
	/*
		Fetch Form/Session Data
	*/
	
	$num_trans	= @security_form_input_predefined("int", "num_trans", 1, "");
	
	$dest_account	= $_SESSION["dest_account"];
	$employeeid	= $_SESSION["employeeid"];
	

	/*
		Start SQL Transaction
	*/

	$sql_obj = New sql_query;
	$sql_obj->trans_begin();
	


	/*
		Run through imported transactions
	*/

	for ($i=1; $i<=$num_trans; $i++)
	{
		$name = "transaction".$i;

		// check if enabled
		$enabled = @security_form_input_predefined("any", $name."-enabled", 1, "");
		
		if ($enabled == "true")
		{

			// determine transaction type, date, amount, code, reference, and particulars
			$type				= @security_form_input_predefined("any", $name."-assign", 1, "");
			$date				= @security_form_input_predefined("any", $name."-date", 1, "");
			$amount				= @security_form_input_predefined("any", $name."-amount", 1, "");
			$code				= @security_form_input_predefined("any", $name."-code", 0, "");
			$reference			= @security_form_input_predefined("any", $name."-reference", 0, "");
			$particulars			= @security_form_input_predefined("any", $name."-particulars", 0, "");
			$other_party			= @security_form_input_predefined("any", $name."-other_party", 0, "");
			$transaction_type		= @security_form_input_predefined("any", $name."-transaction_type", 0, "");
			
			switch ($type)
			{
				case "ap":
				case "ar":
					/*
						AR // AP INVOICE PAYMENTS

						Handling AR and AP payments is almost identical - we need to validate
						and then create a payment item on the invoice.
					*/

					$data = array();
				
					$item = New invoice_items;
					
					if ($type == "ar")
					{
						// determine customer and invoice
						$customer		= @security_form_input_predefined("int", $name."-customer", 1, "");
						$arinvoice		= @security_form_input_predefined("int", $name."-arinvoice", 1, "");
						$item->id_invoice	= $arinvoice;
					}
					else
					{
						// determine vendor and invoice
						$vendor			= @security_form_input_predefined("int", $name."-vendor", 1, "");
						$apinvoice		= @security_form_input_predefined("int", $name."-apinvoice", 1, "");
						$item->id_invoice	= $apinvoice;
					}
					
					
					
					$item->type_invoice	= $type;
					$item->type_item	= 'payment';
					
					
					$data["date_trans"]	= $date;
					
					if ($amount < 0)
					{
						$data["amount"]		= $amount * -1;
					}
					else
					{
						$data["amount"]		= $amount;
					}
					
					
					$data["chartid"]	= $dest_account;
					$data["source"]		= "";
					$data["description"]	= "";


					/*
						Process data
					*/

					if (!error_check())
					{
						// prepare data
						if (!$item->prepare_data($data))
						{
							log_write("error", "process", "An error was encountered whilst processing supplied data.");
						}
						else
						{
							// create item
							$item->action_create();
							$item->action_update();

							// update invoice summary totals
							$item->action_update_total();
							$item->action_update_ledger();
						}

					}

					unset($item);
					unset($data);
				break;


				case "transfer":
				case "bank_fee":
				case "interest":
					/*
						TRANSFER / BANK_FEE / INTEREST

						These transaction types are all basic GL transactions, we just need to validate to/from accounts
						and to create the transaction.
					*/

					$account	= array();
					$data		= array();
					
					$obj_gl	= New gl_transaction;
					
					
					if ($type == "transfer")
					{
						//determine to and from account
						$transferto	= @security_form_input_predefined("int", $name."-transferto", 1, "");
						$transferfrom	= @security_form_input_predefined("int", $name."-transferfrom", 1, "");
						
						$account['origin']	= $transferto;
						$account['destination']	= $transferfrom;
					}
					else if ($type == "bank_fee")
					{
						//determine expense and asset account
						$bankfeeexpense	= @security_form_input_predefined("int", $name."-bankfeesexpense", 1, "");
						$bankfeeasset	= @security_form_input_predefined("int", $name."-bankfeesasset", 1, "");	

						$account['origin']		= $bankfeeasset;
						$account['destination']		= $bankfeeexpense;
					}
					else
					{
						//determine asset, expense, and income account
						$interestasset		= @security_form_input_predefined("int", $name."-interestasset", 1, "");
						$interestincome		= @security_form_input_predefined("int", $name."-interestincome", 1, "");				
						
						$account['origin']		= $interestincome;
						$account['destination']		= $interestasset;
					}
					
					
					if($amount < 0)
					{
						$data["amount"]		= $amount * -1;
					}
					else
					{
						$data["amount"]		= $amount;
					}
					
					
					if ($type == "transfer")
					{
						// TODO: ? Is something needed here?
					}
								
					
					$obj_gl->data["code_gl"]			= "";
					$obj_gl->data["date_trans"]			= $date;
					$obj_gl->data["employeeid"]			= $employeeid;
					$obj_gl->data["description"]			= $transaction_type;
					$obj_gl->data["description_useall"]		= "";
					$obj_gl->data["chart_type"]			= "";
					
					
					$j = 1; 
					$obj_gl->data["trans"][$j]["account"]		= $account['origin'];
					$obj_gl->data["trans"][$j]["debit"]		= 0;
					$obj_gl->data["trans"][$j]["credit"]		= $data["amount"];
					$obj_gl->data["trans"][$j]["source"]		= "";
					$obj_gl->data["trans"][$j]["description"]	= $transaction_type;
					
					$j++;
					
					$obj_gl->data["trans"][$j]["account"]		= $account['destination'];
					$obj_gl->data["trans"][$j]["debit"]		= $data["amount"];
					$obj_gl->data["trans"][$j]["credit"]		= 0;
					$obj_gl->data["trans"][$j]["source"]		= "";
					$obj_gl->data["trans"][$j]["description"]	= $transaction_type;
					
					
					
					// transaction rows
					$obj_gl->data["num_trans"] = count($obj_gl->data["trans"]);

					
					// make sure we don't choose a transaction code number that is already in use
					if ($obj_gl->data["code_gl"])
					{
						if (!$obj_gl->verify_code_gl())
						{
							// TODO: ?? Is this check even useful?
						}
					}
				
				
					// verify transaction data
					if ($obj_gl->data["num_trans"])
					{
						if (!$obj_gl->verify_valid_trans())
						{
							// TODO: ??
						}
					}
				
					/*
						Update Database
					*/

					if (!error_check())
					{
						$obj_gl->action_update();
					}
						
					unset($obj_gl);
					unset($account);
					unset($data);
				break;


				default:
					/*
						Unknown/System Failure
					*/
					log_write("error", "process", "Unknown transaction type \"$type\" could not be processed");

				break;

			} // end of case type
		}
	}
	


	/*
		Commit Changes
	*/

	if (error_check())
	{
		$sql_obj->trans_rollback();

		log_write("error", "process", "An error occuring whilst attempting to process the supplied transactions, no changes have been made as a result");
	}
	else
	{
		$sql_obj->trans_commit();

		log_write("notification", "process", "Your transactions have been imported.");
	}



	/*
		Error Handling
	*/
	if (error_check())
	{
		$_SESSION["error"]["form"]["bankstatement_assign"] = "failed";

		header("Location: ../../index.php?page=accounts/import/bankstatement-assign.php");
		exit(0);
	}
	else
	{
		header("Location: ../../index.php?page=accounts/import/bankstatement.php");
		exit(0);
	}
}
else
{
	// user does not have permissions to access this page.
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}

?>
