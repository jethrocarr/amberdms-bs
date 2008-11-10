<?php
/*
	taxes/view.php
	
	access: accounts_taxes_view (read-only)
		accounts_taxes_write (write access)

	Displays all the details for the tax and if the user has correct
	permissions allows the tax to be updated.
*/


// custom includes
require("include/accounts/inc_charts.php");


if (user_permissions_get('accounts_taxes_view'))
{
	$id = $_GET["id"];
	
	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "Tax Details";
	$_SESSION["nav"]["query"][]	= "page=accounts/taxes/view.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=accounts/taxes/view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Tax Ledger";
	$_SESSION["nav"]["query"][]	= "page=accounts/taxes/ledger.php&id=$id";

	if (user_permissions_get('accounts_taxes_write'))
	{
		$_SESSION["nav"]["title"][]	= "Delete Tax";
		$_SESSION["nav"]["query"][]	= "page=accounts/taxes/delete.php&id=$id";
	}


	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		print "<h3>TAX DETAILS</h3><br>";
		print "<p>This page allows you to view and adjust the selected tax.</p>";

		$mysql_string	= "SELECT id FROM `account_taxes` WHERE id='$id'";
		$mysql_result	= mysql_query($mysql_string);
		$mysql_num_rows	= mysql_num_rows($mysql_result);

		if (!$mysql_num_rows)
		{
			print "<p><b>Error: The requested tax does not exist. <a href=\"index.php?page=taxes/taxes.php\">Try looking for your tax on the tax list page.</a></b></p>";
		}
		else
		{

			/*
				Define form structure
			*/
			$form = New form_input;
			$form->formname = "tax_view";
			$form->language = $_SESSION["user"]["lang"];

			$form->action = "accounts/taxes/edit-process.php";
			$form->method = "post";
			

			// general
			$structure = NULL;
			$structure["fieldname"] 	= "name_tax";
			$structure["type"]		= "input";
			$structure["options"]["req"]	= "yes";
			$form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"] 	= "taxrate";
			$structure["type"]		= "input";
			$structure["options"]["req"]	= "yes";
			$form->add_input($structure);
		
			$structure = NULL;
			$structure["fieldname"] 	= "description";
			$structure["type"]		= "input";
			$structure["options"]["req"]	= "yes";
			$form->add_input($structure);

			// tax account selection
			$structure = charts_form_prepare_acccountdropdown("chartid", "tax_summary_account");
			$structure["options"]["req"]	= "yes";

			if (!$structure["values"])
			{
				$structure["type"]		= "text";
				$structure["defaultvalue"]	= "<b>You need to add some tax accounts for this tax to belong to, before you can use this tax</b>";
			}
			$form->add_input($structure);


			// hidden
			$structure = NULL;
			$structure["fieldname"] 	= "id_tax";
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= "$id";
			$form->add_input($structure);


		
			// submit section
			if (user_permissions_get("accounts_taxes_write"))
			{
				$structure = NULL;
				$structure["fieldname"] 	= "submit";
				$structure["type"]		= "submit";
				$structure["defaultvalue"]	= "Save Changes";
				$form->add_input($structure);
			
			}
			else
			{
				$structure = NULL;
				$structure["fieldname"] 	= "submit";
				$structure["type"]		= "message";
				$structure["defaultvalue"]	= "<p><i>Sorry, you don't have permissions to make changes to the accounts.</i></p>";
				$form->add_input($structure);
			}
			
			
			// define subforms
			$form->subforms["general"]	= array("name_tax", "chartid", "taxrate", "description");
			$form->subforms["hidden"]	= array("id_tax");
			$form->subforms["submit"]	= array("submit");

			
			// fetch the form data
			$form->sql_query = "SELECT * FROM `account_taxes` WHERE id='$id' LIMIT 1";		
			$form->load_data();

			// display the form
			$form->render_form();

		}

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
