<?php
/*
	bankstatement-csv.php
	
	access: "accounts_import_statement" group members

	Allows user to assign names to CSV columns so the transactions can be assigned
*/

class page_output
{
	var $statement_array;
	var $obj_form;
	
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
		$this->statement_array = $_SESSION["statement_array"];
		$values_array = array("ar", "ap", "transfer", "bank_fee", "interest");
		
		$this->obj_form	= New form_input;
		
		$i=1;
		foreach ($this->statement_array as $transaction=>$data)
		{
			$name 			= "transaction".$i;
		    
			//assignment drop down
			$structure			= NULL;
			$structure["fieldname"]	= $name."-assign";
			$structure["type"]		= "dropdown";
			$structure["values"]	= $values_array;
			$this->obj_form->add_input($structure);
			
			//customer drop down
			$structure			= NULL;
			$structure			= form_helper_prepare_dropdownfromdb($name."-customer", "SELECT id, code_customer AS label, name_customer AS label1 FROM customers ORDER BY code_customer ASC");
			$this->obj_form->add_input($structure);
			
			//AR invoice drop down
			$structure			= NULL;
			$structure			= form_helper_prepare_dropdownfromdb($name."-arinvoice", "SELECT id, code_invoice AS label, amount_total AS label1 FROM account_ar WHERE amount_paid < amount_total ORDER BY code_invoice ASC");
			$this->obj_form->add_input($structure);
			
			//vendors drop down
			$structure			= NULL;
			$structure			= form_helper_prepare_dropdownfromdb($name."-vendor", "SELECT id, code_vendor AS label, name_vendor AS label1 FROM vendors ORDER BY code_vendor ASC");
			$this->obj_form->add_input($structure);
			
			//AP invoice drop down
			$structure			= NULL;
			$structure			= form_helper_prepare_dropdownfromdb($name."-apinvoice", "SELECT id, code_invoice AS label, amount_total AS label1 FROM account_ap WHERE amount_paid < amount_total ORDER BY code_invoice ASC");
			$this->obj_form->add_input($structure);
			
			//Bank fees expense account drop down
			$structure			= NULL;
			$structure			= form_helper_prepare_dropdownfromdb($name."-bankfeesexpense", "SELECT ac.id, ac.code_chart AS label, ac.description AS label1 FROM account_charts ac JOIN account_chart_type act ON ac.chart_type = act.id WHERE act.value = \"Expense\" ORDER BY ac.code_chart ASC");
			$this->obj_form->add_input($structure);
			
			//Bank fees asset account drop down
			$structure			= NULL;
			$structure			= form_helper_prepare_dropdownfromdb($name."-bankfeesasset", "SELECT ac.id, ac.code_chart AS label, ac.description AS label1 FROM account_charts ac JOIN account_chart_type act ON ac.chart_type = act.id WHERE act.value = \"Asset\" ORDER BY ac.code_chart ASC");
			$this->obj_form->add_input($structure);
			
			//Interest asset account drop down
			$structure			= NULL;
			$structure			= form_helper_prepare_dropdownfromdb($name."-interestasset", "SELECT ac.id, ac.code_chart AS label, ac.description AS label1 FROM account_charts ac JOIN account_chart_type act ON ac.chart_type = act.id WHERE act.value = \"Asset\" ORDER BY ac.code_chart ASC");
			$this->obj_form->add_input($structure);
			
			//Interest expense account drop down
			$structure			= NULL;
			$structure			= form_helper_prepare_dropdownfromdb($name."-interestexpense", "SELECT ac.id, ac.code_chart AS label, ac.description AS label1 FROM account_charts ac JOIN account_chart_type act ON ac.chart_type = act.id WHERE act.value = \"Expense\" ORDER BY ac.code_chart ASC");
			$this->obj_form->add_input($structure);
			
			//Interest income account drop down
			$structure			= NULL;
			$structure			= form_helper_prepare_dropdownfromdb($name."-interestincome", "SELECT ac.id, ac.code_chart AS label, ac.description AS label1 FROM account_charts ac JOIN account_chart_type act ON ac.chart_type = act.id WHERE act.value = \"Income\" ORDER BY ac.code_chart ASC");
			$this->obj_form->add_input($structure);
			
			//Transfer from account drop down
			$structure			= NULL;
			$structure			= form_helper_prepare_dropdownfromdb($name."-transferfrom", "SELECT ac.id, ac.code_chart AS label, ac.description AS label1 FROM account_charts ac JOIN account_chart_type act ON ac.chart_type = act.id ORDER BY ac.code_chart ASC");
			$this->obj_form->add_input($structure);
			
			//Transfer to account drop down
			$structure			= NULL;
			$structure			= form_helper_prepare_dropdownfromdb($name."-transferto", "SELECT ac.id, ac.code_chart AS label, ac.description AS label1 FROM account_charts ac JOIN account_chart_type act ON ac.chart_type = act.id ORDER BY ac.code_chart ASC");
			$this->obj_form->add_input($structure);
			
			$i++;
		}
		
		$structure 			= NULL;
		$structure["fieldname"]		= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Apply";
		$this->obj_form->add_input($structure);
	} 



	/*
		Output: HTML format
	*/
	function render_html()
	{
		
		// Title + Summary
		print "<h3>Label Imported Transactions</h3><br>";
		print "<p>Please select the type of each uploaded transaction.</p>";
		
		// display the form
		print "<form class=\"form_standard\" action=\"accounts/import/bankstatement-assign-process.php\" method=\"post\" enctype=\"multipart/form-data\">";
		
		print "<table class=\"form_table\">";
		
			print "<tr class=\"header\">";
				print "<td><b>Remove</b></td>";
				print "<td><b>Date</b></td>";
				print "<td><b>Type</b></td>";
				print "<td><b>Amount</b></td>";
				print "<td><b>Other Party</b></td>";
				print "<td><b>Other Information</b></td>";
				print "<td><b>Assign...</b></td>";
				print "<td><b>Done</b></td>";
			print "</tr>";
		    
			$i=1;
			foreach ($this->statement_array as $transaction=>$data)
			{
				$name = "transaction".$i;
				$name_error = $name."-error";
				if (isset($_SESSION["error"][$name_error]))
				{
					print "<tr class=\"form_error\">";
				}
				else
				{
					print "<tr id=\"".$name."\" class=\"transaction_row\">";
				}
			
				//remove
				print "<td class=\"include remove\" style=\"cursor:pointer\">";
					print "<img src=\"images/icons/minus.gif\" />";
				print "</td></a>";
				
				//date
				print "<td>";
					print $data["date"];
				print "</td>";
				
				//type
				print "<td>";
					print $data["transaction_type"];
				print "</td>";
				
				//amount
				print "<td>";
					print $data["amount"];
				print "</td>";
				
				//other party
				print "<td>";
					print $data["other_party"];
				print "</td>";
				
				//other information
				print "<td>";
				if (isset($data["particulars"]) && $data["particulars"]!="")
				{
					print $data["particulars"]."<br />";
				}
				if (isset($data["code"]) && $data["code"]!="")
				{
					print $data["code"]."<br />";
				}
				if (isset($data["reference"]) && $data["reference"]!="")
				{
					print $data["reference"]."<br />";
				}
				print "</td>";
			    
			    //assign
				print "<td class=\"dropdown\">";
					//selector
					print "<div class=\"assign\">";
					$this->obj_form->render_field($name."-assign");
					print "</div>";
					
					//ar transactions
					print "<div class=\"toggle_ar\" hidden=\"hidden\">";
					print "From customer: ";
					$this->obj_form->render_field($name."-customer");
					print " for invoice: ";
					$this->obj_form->render_field($name."-arinvoice");
					print "</div>";
					
					//ap transactions
					print "<div class=\"toggle_ap\">";
					print "To vendor: ";
					$this->obj_form->render_field($name."-vendor");
					print " for invoice: ";
					$this->obj_form->render_field($name."-apinvoice");
					print "</div>";
					
					//bank fee transactions
					print "<div class=\"toggle_bankfee\">";
					print "From expense account: ";
					$this->obj_form->render_field($name."-bankfeesexpense");
					print "for asset account: ";
					$this->obj_form->render_field($name."-bankfeesasset");
					print "</div>";
					
					//interest transactions
					print "<div class=\"toggle_interest\">";
					print "Interest into asset account: ";
					$this->obj_form->render_field($name."-interestasset");
					print " and tax into expense account: ";
					$this->obj_form->render_field($name."-interestexpense");
					print " from income account: ";
					$this->obj_form->render_field($name."-interestincome");
					print "</div>";
					
					//transfer transactions
					print "<div class=\"toggle_transfer\">";
					print "Into account: ";
					$this->obj_form->render_field($name."-transferto");
					print "from account: ";
					$this->obj_form->render_field($name."-transferfrom");
					print "</div>";
				print "</td>";
			    
			    //done
				print "<td class=\"done\">";
					print "<img src=\"images/icons/check.gif\">";
				print "</td>";
			
				print "</tr>";
				$i++;
			}
		
		
		print "</table>";
		print "</form>";
	}	

} // end class page_output
?>