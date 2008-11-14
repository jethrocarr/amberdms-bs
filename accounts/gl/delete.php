<?php
/*
	accounts/charts/delete.php
	
	access:	accounts_charts_write

	Allows an unwanted chart to be deleted.
*/

if (user_permissions_get('accounts_charts_write'))
{
	$id = $_GET["id"];
	
	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "Account Details";
	$_SESSION["nav"]["query"][]	= "page=accounts/charts/view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Account Ledger";
	$_SESSION["nav"]["query"][]	= "page=accounts/charts/ledger.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Delete Account";
	$_SESSION["nav"]["query"][]	= "page=accounts/charts/delete.php&id=$id";
	$_SESSION["nav"]["query"][]	= "page=accounts/charts/delete.php&id=$id";



	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		print "<h3>DELETE ACCOUNT</h3><br>";
		print "<p>This page allows you to delete an unwanted account, provided that account has no transactions in it.</p>";

		$mysql_string	= "SELECT id FROM `account_charts` WHERE id='$id'";
		$mysql_result	= mysql_query($mysql_string);
		$mysql_num_rows	= mysql_num_rows($mysql_result);

		if (!$mysql_num_rows)
		{
			print "<p><b>Error: The requested account does not exist. <a href=\"index.php?page=charts/charts.php\">Try looking for your account on the chart of accounts page.</a></b></p>";
		}
		else
		{
			/*
				Define form structure
			*/
			$form = New form_input;
			$form->formname = "chart_delete";
			$form->language = $_SESSION["user"]["lang"];

			$form->action = "accounts/charts/delete-process.php";
			$form->method = "post";
			

			// general
			$structure = NULL;
			$structure["fieldname"] 	= "code_chart";
			$structure["type"]		= "text";
			$form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"] 	= "description";
			$structure["type"]		= "text";
			$form->add_input($structure);


			// hidden
			$structure = NULL;
			$structure["fieldname"] 	= "id_chart";
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= "$id";
			$form->add_input($structure);
			
			
			// confirm delete
			$structure = NULL;
			$structure["fieldname"] 	= "delete_confirm";
			$structure["type"]		= "checkbox";
			$structure["options"]["label"]	= "Yes, I wish to delete this account and realise that once deleted the data can not be recovered.";
			$form->add_input($structure);



			/*
				Check that the chart can be deleted
			*/

			$locked = 0;
			

			// make sure chart has no transactions in it
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM account_trans WHERE chartid='$id'";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				$locked = 1;
			}
			

			// make sure chart has no items belonging to it
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM account_items WHERE chartid='$id'";
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
				$structure["defaultvalue"]	= "<i>This accounts can not be deleted because it has transactions or items belonging to it.</i>";
			}
			else
			{
				$structure["type"]		= "submit";
				$structure["defaultvalue"]	= "delete";
			}
					
			$form->add_input($structure);


			
			// define subforms
			$form->subforms["chart_delete"]		= array("code_chart", "description");
			$form->subforms["hidden"]		= array("id_chart");
			$form->subforms["submit"]		= array("delete_confirm", "submit");

			
			// fetch the form data
			$form->sql_query = "SELECT code_chart, description FROM `account_charts` WHERE id='$id' LIMIT 1";
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
