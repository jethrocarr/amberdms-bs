<?php
/*
	bankstatement-assign-process.php
	
	access: "accounts_import_statement" group members

	Applies transactions to invoices and ledger
*/
include_once("../../include/config.php");
include_once("../../include/amberphplib/main.php");

if (user_permissions_get("accounts_import_statement"))
{
	/*
		Fetch Form/Session Data
	*/
	
	$num_trans = @security_form_input_predefined("int", "num_trans", 1, "");
	
	for ($i=1; $i<=$num_trans; $i++)
	{
		$name = "transaction".$i;
		//check if enabled
		$enabled = @security_form_input_predefined("any", $name."-enabled", 1, "");
		if ($enabled == "true")
		{
			//determine transaction type, date, amount, code, reference, and particulars
			$type		= @security_form_input_predefined("any", $name."-assign", 1, "");
			$date		= @security_form_input_predefined("any", $name."-date", 1, "");
			$amount		= @security_form_input_predefined("any", $name."-amount", 1, "");
			$code		= @security_form_input_predefined("any", $name."-code", 0, "");
			$reference	= @security_form_input_predefined("any", $name."-reference", 0, "");
			$particulars	= @security_form_input_predefined("any", $name."-particulars", 0, "");
			
			//if ap
			if ($type == "ap")
			{
				//determine vendor and invoice
				$vendor		= @security_form_input_predefined("int", $name."-vendor", 1, "");
				$apinvoice	= @security_form_input_predefined("int", $name."-apinvoice", 1, "");
			}
			//if ar
			elseif ($type == "ar")
			{
				//determine customer and invoice
				$customer	= @security_form_input_predefined("int", $name."-customer", 1, "");
				$arinvoice	= @security_form_input_predefined("int", $name."-arinvoice", 1, "");
			}
			//if transfer
			elseif ($type == "transfer")
			{
				//determine to and from account
				$transferto	= @security_form_input_predefined("int", $name."-transferto", 1, "");
				$transferfrom	= @security_form_input_predefined("int", $name."-transferfrom", 1, "");
			}
			//if bank fee
			elseif ($type == "bank_fee")
			{
				//determine expense and asset account
				$bankfeeexpense	= @security_form_input_predefined("int", $name."-bankfeeexpense", 1, "");
				$bankfeeasset	= @security_form_input_predefined("int", $name."-bankfeeasset", 1, "");				
			}
			//if interest
			else
			{
				//determine asset, expense, and income account
				$interestasset		= @security_form_input_predefined("int", $name."-interestasset", 1, "");
				$interestexpense	= @security_form_input_predefined("int", $name."-interestexpense", 1, "");
				$interestincome		= @security_form_input_predefined("int", $name."-interestincome", 1, "");				
			}
		}
	}
	
	if (error_check())
	{
		$_SESSION["error"]["form"]["bankstatement_assign"] = "failed";

		header("Location: ../../index.php?page=accounts/import/bankstatement-assign.php");
		exit(0);
	}
	else
	{
		log_write("notification", "process", "Your transactions have been imported.");
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