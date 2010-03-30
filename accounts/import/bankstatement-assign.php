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
		    
		    $structure			= NULL;
		    $structure["fieldname"]	= $name."-assign";
		    $structure["type"]		= "dropdown";
		    $structure["values"]	= $values_array;
		    $this->obj_form->add_input($structure);
		    
		    $structure			= NULL;
		    $structure			= form_helper_prepare_dropdownfromdb($name."-customer", "SELECT id, code_customer AS label, name_customer AS label1 FROM customers ORDER BY code_customer ASC");
		    $this->obj_form->add_input($structure);
		    
		    $structure			= NULL;
		    $structure			= form_helper_prepare_dropdownfromdb($name."-arinvoice", "SELECT id, code_invoice AS label, amount_total AS label1 FROM account_ar ORDER BY code_invoice ASC");
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
			    print "<td class=\"toggle_include remove\" style=\"cursor:pointer\">";
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
				$this->obj_form->render_field($name."-assign");
				print "<div id=\"toggle_ar\">";
				    print "From customer: ";
				    $this->obj_form->render_field($name."-customer");
				    print "for invoice: ";
				    $this->obj_form->render_field($name."-arinvoice");
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