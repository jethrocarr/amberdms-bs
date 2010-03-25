<?php
/*
	bankstatement-csv.php
	
	access: "accounts_import_statement" group members

	Allows user to assign names to CSV columns so the transactions can be assigned
*/

class page_output
{
	var $obj_form;
	var $num_col;
	var $example_array;
	
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
		Define fields and column examples
	*/
	function execute()
	{
		$this->num_col	= count($_SESSION["csv_array"][0]);
		$values_array	= array("transaction_type", "other_party", "particulars", "code", "reference", "amount", "date");
		
		$this->obj_form			= New form_input;
		
		for ($i=1; $i<$this->num_col; $i++)
		{
		    $name	= "column".$i;
		    
		    $structure 			= NULL;
		    $structure["fieldname"]	= $name;
		    $structure["type"]		= "dropdown";
		    $structure["values"]	= $values_array;
		    if (isset($_SESSION["col_array"]))
		    {
			$structure["defaultvalue"]	= $_SESSION["col_array"][$name];
		    }
		    $this->obj_form->add_input($structure);
		}
		
		$structure 			= NULL;
		$structure["fieldname"]		= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Apply";
		$this->obj_form->add_input($structure);
		
		//populate an array of examples
		//create one for each entry in the sub arrays
		for ($i=0; $i<($this->num_col)-1; $i++)
		{
		    //check for example in each array
		    for ($j=0; $j<count($_SESSION["csv_array"]); $j++)
		    {
			if ($_SESSION["csv_array"][$j][$i] != "")
			{
			    $this->example_array[$i+1]	= $_SESSION["csv_array"][$j][$i];
			    break;
			} 
		    }
		}
	} 



	/*
		Output: HTML format
	*/
	function render_html()
	{
		    // Title + Summary
		print "<h3>CSV COLUMN CLARIFICATION</h3><br>";
		print "<p>Please identify what each column in your CSV file represents. An example has been provided from each column- please note that these examples may not all be from the same transaction.</p>";
	
		// display the form
		print "<form class=\"form_standard\" action=\"accounts/import/bankstatement-csv-process.php\" method=\"post\" enctype=\"multipart/form-data\">";
		
		print "<table class=\"form_table\">";
		
		    print "<tr class=\"header\">";
			print "<td><b>Column</b></td>";
			print "<td><b>Example</b></td>";
			print "<td><b>Field</b></td>";
		    print "</tr>";
		    print "<pre>"; print_r($_POST); print "</pre>";
		    for($i=1; $i<$this->num_col; $i++)
		    {
			$name = "column".$i;
			$name_error = $name."-error";
			if (isset($_SESSION["error"][$name_error]))
			{
			    print "<tr class=\"form_error\">";
			}
			else
			{
			    print "<tr id=\"".$name."\">";
			}
			print "<td>";
			    print "Column ".$i;
			print "</td>";
			print "<td>";
			    print $this->example_array[$i];
			print "</td>";
			print "<td>";
			    $this->obj_form->render_field($name);
			print "</td>";
			print "</tr>";
		    }
		    
		    print "<tr class=\"header\">";
			print "<td colspan=\"3\"><b>Apply Choices</b></td>";
		    print "</tr>";
		    
		    print "<tr id=\"submit\">";
			print "<td colspan=\"3\">";
			$this->obj_form->render_field("submit");
			print "</td>";
		    print "</tr>";
		print "</table>";
		print "</form>";
	}	

} // end class page_output
?>