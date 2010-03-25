<?php
/*
	bankstatement-csv.php
	
	access: "accounts_import_statement" group members

	Allows user to assign names to CSV columns so the transactions can be assigned
*/

class page_output
{
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

	} 



	/*
		Output: HTML format
	*/
	function render_html()
	{
		    // Title + Summary
		print "<h3>Label Imported Transactions</h3><br>";
		print "<p>Please select the type of each uploaded transaction.</p>";
	}	

} // end class page_output
?>