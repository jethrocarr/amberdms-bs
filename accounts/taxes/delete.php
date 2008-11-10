<?php
/*
	taxes/delete.php
	
	access:	accounts_taxes_view

	Allows an unwanted tax to be deleted.
*/

if (user_permissions_get('accounts_taxes_write'))
{
	$id = $_GET["id"];
	
	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "Tax Details";
	$_SESSION["nav"]["query"][]	= "page=accounts/taxes/view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Tax Ledger";
	$_SESSION["nav"]["query"][]	= "page=accounts/taxes/ledger.php&id=$id";
	
	$_SESSION["nav"]["title"][]	= "Delete Tax";
	$_SESSION["nav"]["query"][]	= "page=accounts/taxes/delete.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=accounts/taxes/delete.php&id=$id";



	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		print "<h3>DELETE TAX</h3><br>";
		print "<p>This page allows you to delete an unwanted tax, provided that the tax has not been used for any invoices.</p>";

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
			$form->formname = "tax_delete";
			$form->language = $_SESSION["user"]["lang"];

			$form->action = "accounts/taxes/delete-process.php";
			$form->method = "post";
			

			// general
			$structure = NULL;
			$structure["fieldname"] 	= "name_tax";
			$structure["type"]		= "text";
			$form->add_input($structure);


			// hidden
			$structure = NULL;
			$structure["fieldname"] 	= "id_tax";
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= "$id";
			$form->add_input($structure);
			
			
			// confirm delete
			$structure = NULL;
			$structure["fieldname"] 	= "delete_confirm";
			$structure["type"]		= "checkbox";
			$structure["options"]["label"]	= "Yes, I wish to delete this tax and realise that once deleted the data can not be recovered.";
			$form->add_input($structure);



			/*
				Check that the tax can be deleted
			*/

			$locked = 0;
			

			// make sure tax does not belong to any invoices
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM account_items WHERE type='tax' AND customid='$id'";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				$locked = 1;
			}
			

			// define submit field
			$structure = NULL;
			$structure["fieldname"] = "submit";

			if ($locked)
			{
				$structure["type"]		= "message";
				$structure["defaultvalue"]	= "<i>This tax can not be deleted because it has been used in an invoice.</i>";
			}
			else
			{
				$structure["type"]		= "submit";
				$structure["defaultvalue"]	= "delete";
			}
					
			$form->add_input($structure);


			
			// define subforms
			$form->subforms["tax_delete"]		= array("name_tax");
			$form->subforms["hidden"]		= array("id_tax");
			$form->subforms["submit"]		= array("delete_confirm", "submit");

			
			// fetch the form data
			$form->sql_query = "SELECT name_tax FROM `account_taxes` WHERE id='$id' LIMIT 1";
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
