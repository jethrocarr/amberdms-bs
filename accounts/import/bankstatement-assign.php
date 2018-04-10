<?php
/*
	accounts/import/bankstatement-import.php

	access:
		accounts_import_statement

	Takes the session data of an imported bank statement and allows the user to do assignments
	to different transaction types. Some level of automatic matching takes place here, such as
	matching payments to customers and invoices automatically.

	See the associated javascript import file for important sections of the page processing logic.
*/

class page_output
{
	var $requires;

	var $statement_array;
	var $obj_form;
	

	function __construct()
	{
		$this->requires["javascript"][]		= "include/accounts/javascript/import.js";
		$this->requires["css"][]		= "include/accounts/css/bankstatement-assign.css";
	}

	function check_permissions()
	{
		return user_permissions_get('accounts_import_statement');
	}

	function check_requirements()
	{
		// nothing todo
		return 1;
	}


	/*
		Define fields
	*/
	function execute()
	{
		$this->statement_array	= $_SESSION["statement_array"];
		$values_array		= array("ar", "ap", "transfer", "bank_fee", "interest");
		
		$this->obj_form			= New form_input;
		$this->obj_form->formname	= "bankstatement_assign";
		
		$i=1;

		foreach ($this->statement_array as $transaction=>$data)
		{
			$name 			= "transaction".$i;


			/*
				1. DETERMINE TRANSACTION TYPE

				Determine what type of row this bank statement line item is. We do this
				by keyword matching and checking for information such as amount being
				positive/negative.
			*/

			$row_assignment		= null;

			if ($data["amount"] > 0)
			{ 
				$row_assignment = 'ar';
			}
			
			$lowercased_strings = strtolower($data['other_party'])." ".
			strtolower($data['transaction_type'])." ".
			strtolower($data['particulars'])." ".
			strtolower($data['code']);
			
			$search_regex ="/(interest|transfer|fee|tax|account\smaintenance){1}/";
			
			
			preg_match($search_regex, $lowercased_strings, $matches);
			
			
			switch($matches[1]) 
			{
				case 'tax':
				case 'transfer':
				$row_assignment = 'transfer';
				break;
				
				case 'interest':
				$row_assignment = 'interest';
				break;
				
				case 'fee':
				case 'account maintenance':
				$row_assignment = 'bank_fee';
				break;
				
				default:
				if($row_assignment == null)
				{
					$row_assignment = 'ap';
				}
				break;
			}

			// assignment drop down
			$structure			= NULL;
			$structure["fieldname"]		= $name."-assign";
			$structure["type"]		= "dropdown";
			$structure["values"]		= $values_array;
			$structure["defaultvalue"] = $row_assignment;
			$this->obj_form->add_input($structure);
			

			
			/*
				2. HIDDEN ATTRIBUTES NEEDED FOR PROCESSING
			*/

			// hidden date field
			$structure			= NULL;
			$structure["fieldname"]		= $name."-date";
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= date("Y-m-d", strtotime(str_replace("/", "-",$data["date"])));
			$this->obj_form->add_input($structure);
			
			// hidden amount field
			$structure			= NULL;
			$structure["fieldname"]		= $name."-amount";
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= $data["amount"];
			$this->obj_form->add_input($structure);
			
			//hidden other_party field
			if(isset($data["other_party"]) && $data["other_party"]!="")
			{
				$structure			= NULL;
				$structure["fieldname"]		= $name."-other_party";
				$structure["type"]		= "hidden";
				$structure["defaultvalue"]	= $data["other_party"];
				$this->obj_form->add_input($structure);
			}
			
			
			//hidden transaction_type field
			if(isset($data["transaction_type"]) && $data["transaction_type"]!="")
			{
				$structure			= NULL;
				$structure["fieldname"]		= $name."-transaction_type";
				$structure["type"]		= "hidden";
				$structure["defaultvalue"]	= $data["transaction_type"];
				$this->obj_form->add_input($structure);
			}			
			
			//hidden code field
			if(isset($data["code"]) && $data["code"]!="")
			{
				$structure			= NULL;
				$structure["fieldname"]		= $name."-code";
				$structure["type"]		= "hidden";
				$structure["defaultvalue"]	= $data["code"];
				$this->obj_form->add_input($structure);
			}
			
			//hidden reference field
			if(isset($data["reference"]) && $data["reference"]!="")
			{
				$structure			= NULL;
				$structure["fieldname"]		= $name."-reference";
				$structure["type"]		= "hidden";
				$structure["defaultvalue"]	= $data["reference"];
				$this->obj_form->add_input($structure);
			}
			
			//hidden particulars field
			if(isset($data["particulars"]) && $data["particulars"]!="")
			{
				$structure			= NULL;
				$structure["fieldname"]		= $name."-particulars";
				$structure["type"]		= "hidden";
				$structure["defaultvalue"]	= $data["particulars"];
				$this->obj_form->add_input($structure);
			}
			


			/*
				3. AR TRANSACTION OPTIONS

				We attempt to match the customer and/or the invoice here.
			*/

			// customer drop down
			$structure			= NULL;
			$structure			= form_helper_prepare_dropdownfromdb($name."-customer", "SELECT id, code_customer AS label, name_customer AS label1 FROM customers ORDER BY code_customer ASC");
			
			$search_values		= array();			
			$search_values[]	= strtolower(preg_quote($data['other_party'], "/"));

			if ($data["reference"] != null)
			{
				$search_values[] = strtolower(preg_quote($data['reference'], "/"));
			}

			if ($data["code"] != null)
			{
				$search_values[] = strtolower(preg_quote($data['code'], "/"));
			}

			if ($data["particulars"] != null)
			{
				$search_values[] = strtolower(preg_quote($data['particulars'], "/"));
			}

			if (is_array($search_values) && is_array($structure["translations"]))
			{
				$matching_entries = preg_grep("/".implode("|", $search_values)."/i", $structure['translations']);
				$matching_entry_keys = array_keys($matching_entries);
			
				$structure["defaultvalue"] = $matching_entry_keys[0];
			}
			$this->obj_form->add_input($structure);

			
			// AR invoice drop down
			$structure			= NULL;
			$structure			= form_helper_prepare_dropdownfromdb($name."-arinvoice", "SELECT id, code_invoice AS label, amount_total AS label1 FROM account_ar WHERE amount_paid < amount_total ORDER BY code_invoice ASC");
			
			$search_values		= array();	
			$search_values[]	= strtolower(preg_quote(str_replace("-", '', " ".$data['amount']), "/"));

			if ($data["reference"] != null)
			{
				$search_values[] = strtolower(preg_quote($data['reference'], "/"));
			}

			if ($data["code"] != null)
			{
				$search_values[] = strtolower(preg_quote($data['code'], "/"));
			}

			if ($data["particulars"] != null)
			{
				$search_values[] = strtolower(preg_quote($data['particulars'], "/"));
			}

			if (is_array($search_values) && is_array($structure["translations"]))
			{
				$matching_entries = preg_grep("/".implode("|", $search_values)."/i", $structure['translations']);
				$matching_entry_keys = array_keys($matching_entries);
			
				$structure["defaultvalue"] = $matching_entry_keys[0];
			}
			$this->obj_form->add_input($structure);
			
			
			

			/*
				4. AP TRANSACTION OPTIONS

				We attempt to match the vendor and/or the invoice here.
			*/
			
			
			// vendors drop down
			$structure			= NULL;
			$structure			= form_helper_prepare_dropdownfromdb($name."-vendor", "SELECT id, code_vendor AS label, name_vendor AS label1 FROM vendors ORDER BY code_vendor ASC");

			$search_values		= array();			
			$search_values[]	= strtolower(preg_quote($data['other_party'], "/"));

			if ($data["reference"] != null)
			{
				$search_values[] = strtolower(preg_quote($data['reference'], "/"));
			}

			if ($data["code"] != null)
			{
				$search_values[] = strtolower(preg_quote($data['code'], "/"));
			}

			if ($data["particulars"] != null)
			{
				$search_values[] = strtolower(preg_quote($data['particulars'], "/"));
			}

			if (is_array($search_values) && is_array($structure["translations"]))
			{
				$matching_entries = preg_grep("/".implode("|", $search_values)."/i", $structure['translations']);
				$matching_entry_keys = array_keys($matching_entries);
			
				$structure["defaultvalue"] = $matching_entry_keys[0];
			}

			$this->obj_form->add_input($structure);
			
			
			
			//AP invoice drop down
			$structure			= NULL;
			$structure			= form_helper_prepare_dropdownfromdb($name."-apinvoice", "SELECT id, code_invoice AS label, amount_total AS label1 FROM account_ap WHERE amount_paid < amount_total ORDER BY code_invoice ASC");
			
			$search_values		= array();	
			$search_values[]	= strtolower(preg_quote(str_replace("-", '', " ".$data['amount']), "/"));

			if ($data["reference"] != null)
			{
				$search_values[] = strtolower(preg_quote($data['reference'], "/"));
			}

			if ($data["code"] != null)
			{
				$search_values[] = strtolower(preg_quote($data['code'], "/"));
			}

			if ($data["particulars"] != null)
			{
				$search_values[] = strtolower(preg_quote($data['particulars'], "/"));
			}
			
			if (is_array($search_values) && is_array($structure["translations"]))
			{
				$matching_entries = preg_grep("/".implode("|", $search_values)."/i", $structure['translations']);
				$matching_entry_keys = array_keys($matching_entries);
			
				$structure["defaultvalue"] = $matching_entry_keys[0];
			}

			$this->obj_form->add_input($structure);
			
			

			/*
				5. BANK FEE/CHARGES

				Bank charges are always going to be from the current asset account to an expense account.
			*/
			
			//Bank fees expense account drop down
			$structure			= NULL;
			$structure			= form_helper_prepare_dropdownfromdb($name."-bankfeesexpense", "SELECT ac.id, ac.code_chart AS label, ac.description AS label1 FROM account_charts ac JOIN account_chart_type act ON ac.chart_type = act.id WHERE act.value = \"Expense\" ORDER BY ac.code_chart ASC");
			$this->obj_form->add_input($structure);
			
			//Bank fees asset account drop down
			$structure			= NULL;
			$structure			= form_helper_prepare_dropdownfromdb($name."-bankfeesasset", "SELECT ac.id, ac.code_chart AS label, ac.description AS label1 FROM account_charts ac JOIN account_chart_type act ON ac.chart_type = act.id WHERE act.value = \"Asset\" ORDER BY ac.code_chart ASC");
			
			$structure["defaultvalue"]		= $_SESSION["dest_account"];

			$this->obj_form->add_input($structure);




			/*
				6. INTEREST
			*/
			
			//Interest asset account drop down
			$structure			= NULL;
			$structure			= form_helper_prepare_dropdownfromdb($name."-interestasset", "SELECT ac.id, ac.code_chart AS label, ac.description AS label1 FROM account_charts ac JOIN account_chart_type act ON ac.chart_type = act.id WHERE act.value = \"Asset\" ORDER BY ac.code_chart ASC");
			$this->obj_form->add_input($structure);
			
			//Interest income account drop down
			$structure			= NULL;
			$structure			= form_helper_prepare_dropdownfromdb($name."-interestincome", "SELECT ac.id, ac.code_chart AS label, ac.description AS label1 FROM account_charts ac JOIN account_chart_type act ON ac.chart_type = act.id WHERE act.value = \"Income\" ORDER BY ac.code_chart ASC");
			$this->obj_form->add_input($structure);
			

			/*
				7. GENERAL ACCOUNT TRANSFER

				Not much to do for account transfers, but we can choose which account is selected for to/from based on
				the default selected account.
			*/

			if ($data["amount"] > 0)
			{
				// Transfer INTO the selected account
				//

				// Transfer from account drop down
				$structure			= NULL;
				$structure			= form_helper_prepare_dropdownfromdb($name."-transferfrom", "SELECT ac.id, ac.code_chart AS label, ac.description AS label1 FROM account_charts ac JOIN account_chart_type act ON ac.chart_type = act.id ORDER BY ac.code_chart ASC");
				$this->obj_form->add_input($structure);
			
				//Transfer to account drop down
				$structure			= NULL;
				$structure			= form_helper_prepare_dropdownfromdb($name."-transferto", "SELECT ac.id, ac.code_chart AS label, ac.description AS label1 FROM account_charts ac JOIN account_chart_type act ON ac.chart_type = act.id ORDER BY ac.code_chart ASC");
			
				$structure["defaultvalue"]		= $_SESSION["dest_account"];

				$this->obj_form->add_input($structure);
			}
			else
			{
				// Transfer FROM the selected account
				//

				// Transfer from account drop down
				$structure			= NULL;
				$structure			= form_helper_prepare_dropdownfromdb($name."-transferfrom", "SELECT ac.id, ac.code_chart AS label, ac.description AS label1 FROM account_charts ac JOIN account_chart_type act ON ac.chart_type = act.id ORDER BY ac.code_chart ASC");
				
				$structure["defaultvalue"]		= $_SESSION["dest_account"];
				
				$this->obj_form->add_input($structure);
				
				//Transfer to account drop down
				$structure			= NULL;
				$structure			= form_helper_prepare_dropdownfromdb($name."-transferto", "SELECT ac.id, ac.code_chart AS label, ac.description AS label1 FROM account_charts ac JOIN account_chart_type act ON ac.chart_type = act.id ORDER BY ac.code_chart ASC");
				$this->obj_form->add_input($structure);
			}	
			


			/*
				8. ENABLE/DISABLE FIELD

				Users have the option of excluding rows that they don't want processed by unchecking them from the import.
			*/
			
			// Hidden enabled field
			$structure			= NULL;
			$structure["fieldname"]		= $name."-enabled";
			$structure["type"]		= "hidden";
			
			// if the amount is not a number, disable the row
			if (!is_numeric($data["amount"]))
			{
				$structure["defaultvalue"]	= "false";
			}
			else
			{
				$structure["defaultvalue"]	= "true";
			}

			$this->obj_form->add_input($structure);
			
			
			$i++;
		}
		

		// hidden field for number of transactions
		$structure			= NULL;
		$structure["fieldname"]		= "num_trans";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= count($this->statement_array);
		$this->obj_form->add_input($structure);
		
		$structure 			= NULL;
		$structure["fieldname"]		= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Apply";
		$this->obj_form->add_input($structure);
		
		if (error_check())
		{
			$this->obj_form->load_data_error();
		}
	} 



	/*
		Output: HTML format
	*/
	function render_html()
	{
		
		// Title + Summary
		print "<h3>Label Imported Transactions</h3><br>";
		print "<p>Please select the type of each transaction. If you do not wish to import a transaction, it can be removed using the '-' sign in the left-most column. All information about a transaction must be completed. A tick mark will appear in the right-most column when all required information has been completed.</p>";
		
		// display the form
		print "<form class=\"form_standard\" action=\"accounts/import/bankstatement-assign-process.php\" method=\"post\" enctype=\"multipart/form-data\">";
		
		print "<table class=\"form_table\" id=\"import_table\">";
		
			print "<tr class=\"header\">";
				print "<td><b>Import</b></td>";
				print "<td><b>Date</b></td>";
				print "<td><b>Type</b></td>";
				print "<td><b>Amount</b></td>";
				print "<td><b>Other Party</b></td>";
				print "<td><b>Other Information</b></td>";
				print "<td class=\"dropdown\"><b>Assign...</b></td>";
				print "<td><b>Done</b></td>";
			print "</tr>";
		    
			$i=1;
			foreach ($this->statement_array as $transaction=>$data)
			{
				$name		= "transaction".$i;
				$name_error	= $name."-error";

				foreach (array_keys($_SESSION["error"]) as $error)
				{
					if (preg_match("/^$name\S*-error$/", $error))
					{
						$_SESSION["error"][$name_error] = 1;
					}
				}

				if (isset($_SESSION["error"][$name_error]))
				{
					print "<tr id=\"".$name."\" class=\"form_error transaction_row\">";
				}
				else
				{
					print "<tr id=\"".$name."\" class=\"transaction_row\">";
				}
			
				//remove
				print "<td class=\"include remove\" style=\"cursor:pointer\">";
					print "<img src=\"images/icons/minus.gif\" />&nbsp;&nbsp;";
					$this->obj_form->render_field($name."-enabled");
				print "</td></a>";
				
				//date
				print "<td>";
					print $data["date"];
					$this->obj_form->render_field($name."-date");
				print "</td>";
				
				//type
				print "<td>";
					print $data["transaction_type"];
				print "</td>";
				
				//amount
				print "<td>";
					print $data["amount"];
					$this->obj_form->render_field($name."-amount");
				print "</td>";
				
				//other party
				print "<td>";
					print $data["other_party"];			
				
					if (isset($data["other_party"]) && $data["other_party"]!="")
					{
						$this->obj_form->render_field($name."-other_party");
					}
				print "</td>";
				
				//other information
				print "<td>";
				if (isset($data["particulars"]) && $data["particulars"]!="")
				{
					print $data["particulars"]."<br />";
					$this->obj_form->render_field($name."-particulars");
				}
				
				if (isset($data["code"]) && $data["code"]!="")
				{
					print $data["code"]."<br />";
					$this->obj_form->render_field($name."-code");
				}
				
				if (isset($data["reference"]) && $data["reference"]!="")
				{
					print $data["reference"]."<br />";
					$this->obj_form->render_field($name."-reference");
				}				
				
				if (isset($data["transaction_type"]) && $data["transaction_type"]!="")
				{
					$this->obj_form->render_field($name."-transaction_type");
				}			
				print "</td>";
			    
				//assign
				print "<td class=\"dropdown\">";
					//selector
					print "<div class=\"assign\">";
					$this->obj_form->render_field($name."-assign");
					print "</div>";
					
					//ar transactions
					print "<div class=\" hide_element toggle_ar\" >";
					print "From customer: ";
					$this->obj_form->render_field($name."-customer");
					print " for invoice: ";
					$this->obj_form->render_field($name."-arinvoice");
					print "</div>";
					
					//ap transactions
					print "<div class=\" hide_element toggle_ap\">";
					print "To vendor: ";
					$this->obj_form->render_field($name."-vendor");
					print " for invoice: ";
					$this->obj_form->render_field($name."-apinvoice");
					print "</div>";
					
					//bank fee transactions
					print "<div class=\" hide_element toggle_bank_fee\">";
					print "Assign expense account: ";
					$this->obj_form->render_field($name."-bankfeesexpense");
					print " subtract from asset account: ";
					$this->obj_form->render_field($name."-bankfeesasset");
					print "</div>";
					
					//interest transactions
					print "<div class=\" hide_element toggle_interest\">";
					print "Interest into asset account: ";
					$this->obj_form->render_field($name."-interestasset");
					print " from income account: ";
					$this->obj_form->render_field($name."-interestincome");
					print "</div>";
					
					//transfer transactions
					print "<div class=\" hide_element toggle_transfer\">";
					print "Into account: ";
					$this->obj_form->render_field($name."-transferto");
					print " from account: ";
					$this->obj_form->render_field($name."-transferfrom");
					print "</div>";
				print "</td>";
			    
				//done
				print "<td class=\"done\">";
					print "<img class=\"hide_element\" src=\"images/icons/check.gif\">";
				print "</td>";
			
				print "</tr>";
				$i++;
			}

			print "<tr><td colspan=\"8\"><br />&nbsp;<br /></td></tr>";
			print "<tr class=\"header\">";
			print "<td colspan=\"8\"><b>Apply</b></td>";
			print "</tr>";
		    
			print "<tr id=\"submit\">";
			print "<td colspan=\"3\">";
			$this->obj_form->render_field("submit");
			$this->obj_form->render_field("num_trans");
			print "</td>";
			print "</tr>";

		print "</table>";
		print "</form>";
	}	

} // end class page_output
?>
