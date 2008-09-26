<?php
/*
	charts/view.php
	
	access: accounts_charts_view (read-only)
		accounts_charts_write (write access)

	Displays all the details for the chart and if the user has correct
	permissions allows the chart to be updated.
*/

if (user_permissions_get('accounts_charts_view'))
{
	$id = $_GET["id"];
	
	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "Account Details";
	$_SESSION["nav"]["query"][]	= "page=accounts/charts/view.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=accounts/charts/view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Delete Account";
	$_SESSION["nav"]["query"][]	= "page=accounts/charts/delete.php&id=$id";


	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		print "<h3>ACCOUNT DETAILS</h3><br>";
		print "<p>This page allows you to view and adjust the selected account.</p>";

		$mysql_string	= "SELECT id FROM `account_charts` WHERE id='$id'";
		$mysql_result	= mysql_query($mysql_string);
		$mysql_num_rows	= mysql_num_rows($mysql_result);

		if (!$mysql_num_rows)
		{
			print "<p><b>Error: The requested chart does not exist. <a href=\"index.php?page=charts/charts.php\">Try looking for your chart on the chart list page.</a></b></p>";
		}
		else
		{

			/*
				Define form structure
			*/
			$form = New form_input;
			$form->formname = "chart_view";
			$form->language = $_SESSION["user"]["lang"];

			$form->action = "accounts/charts/edit-process.php";
			$form->method = "post";
			

			// general
			$structure = NULL;
			$structure["fieldname"] 	= "code_chart";
			$structure["type"]		= "input";
			$structure["options"]["req"]	= "yes";
			$form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"] 	= "description";
			$structure["type"]		= "input";
			$structure["options"]["req"]	= "yes";
			$form->add_input($structure);
			
			$structure = form_helper_prepare_radiofromdb("chart_type", "SELECT id, value as label FROM account_chart_type");
			$structure["options"]["req"]	= "yes";
			$form->add_input($structure);
		
			$structure = NULL;
			$structure["fieldname"] 	= "chart_category";
			$structure["type"]		= "input";
			$structure["options"]["req"]	= "yes";
			$form->add_input($structure);


			// hidden
			$structure = NULL;
			$structure["fieldname"] 	= "id_chart";
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= "$id";
			$form->add_input($structure);


		
			// submit section
			if (user_permissions_get("accounts_charts_write"))
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
			$form->subforms["general"]	= array("code_chart", "description", "chart_type", "chart_category");
			$form->subforms["hidden"]	= array("id_chart");
			$form->subforms["submit"]	= array("submit");

			
			// fetch the form data
			$form->sql_query = "SELECT * FROM `account_charts` WHERE id='$id' LIMIT 1";		
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
