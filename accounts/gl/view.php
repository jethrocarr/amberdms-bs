<?php
/*
	account/gl/view.php
	
	access: accounts_gl_view (read-only)
		accounts_gl_write (write access)

	Displays all the details for the transaction and if the user has correct
	permissions allows the transaction to be updated.
*/


if (user_permissions_get('accounts_gl_view'))
{
	$id = $_GET["id"];
	
	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "Transaction Details";
	$_SESSION["nav"]["query"][]	= "page=accounts/gl/view.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=accounts/gl/view.php&id=$id";

	if (user_permissions_get('accounts_gl_write'))
	{
		$_SESSION["nav"]["title"][]	= "Delete Transaction";
		$_SESSION["nav"]["query"][]	= "page=accounts/gl/delete.php&id=$id";
	}


	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		print "<h3>TRANSACTION DETAILS</h3><br>";
		print "<p>This page allows you to view and adjust the selected transaction.</p>";

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM `account_gl` WHERE id='$id'";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			print "<p><b>Error: The requested transaction does not exist. <a href=\"index.php?page=account/gl/gl.php\">Try looking for your transaction on the general ledger.</a></b></p>";
		}
		else
		{



			/*
				Define form structure
			*/
			$form = New form_input;
			$form->formname = "transaction_view";
			$form->language = $_SESSION["user"]["lang"];

			$form->action = "accounts/gl/edit-process.php";
			$form->method = "post";
			

			// general
			$structure = NULL;
			$structure["fieldname"] 	= "code_gl";
			$structure["type"]		= "input";
			$form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"] 	= "date_trans";
			$structure["type"]		= "date";
			$structure["defaultvalue"]	= date("Y-m-d");
			$structure["options"]["req"]	= "yes";
			$form->add_input($structure);
			
			$structure = form_helper_prepare_dropdownfromdb("employeeid", "SELECT id, name_staff as label FROM staff");
			$structure["options"]["req"]	= "yes";
			$form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"] 	= "description";
			$structure["type"]		= "input";
			$form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"] 	= "description_useall";
			$structure["type"]		= "checkbox";
			$structure["options"]["label"]	= "Check this to use the description above as the description in all the rows below. Untick if you wish to have different messages for each transaction item.";
			$structure["defaultvalue"]	= "on";
			$form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"] 	= "notes";
			$structure["type"]		= "textarea";
			$form->add_input($structure);



			/*
				Define transaction form structure
			*/

			// unless there has been error data returned, fetch all the transactions
			// from the DB, and work out the number of rows
			if (!$_SESSION["error"]["form"][$form->formname])
			{
				$sql_trans_obj		= New sql_query;
				$sql_trans_obj->string	= "SELECT date_trans, amount_debit, amount_credit, chartid, source, memo FROM `account_trans` WHERE type='gl' AND customid='$id'";
				$sql_trans_obj->execute();
		
				if ($sql_trans_obj->num_rows())
				{
					$sql_trans_obj->fetch_array();
			
					$num_trans = $sql_trans_obj->data_num_rows;
				}
			}
			else
			{
				$num_trans = security_script_input('/^[0-9]*$/', $_SESSION["error"]["num_trans"]);
			}

			// increment the row counter by 1 so we always have a spare row and make
			// sure that there are at least 10 rows

			// TODO: would be nice if we could do some javascript magic to allow the user to easily add
			// more rows when they want
			
			if ($num_trans < 10)
			{
				$num_trans = 10;
			}
			else
			{
				$num_trans++;
			}


			// transaction rows
			for ($i = 0; $i < $num_trans; $i++)
			{					
				// account
				$structure = form_helper_prepare_dropdownfromdb("trans_". $i ."_account", "SELECT id, code_chart as label, description as label1 FROM account_charts ORDER BY code_chart");
				$structure["options"]["width"]	= "200";
				$form->add_input($structure);
				
				// debit field
				$structure = NULL;
				$structure["fieldname"] 	= "trans_". $i ."_debit";
				$structure["type"]		= "input";
				$structure["options"]["width"]	= "80";
				$form->add_input($structure);

				// credit field
				$structure = NULL;
				$structure["fieldname"] 	= "trans_". $i ."_credit";
				$structure["type"]		= "input";
				$structure["options"]["width"]	= "80";
				$form->add_input($structure);
			
				
				// source
				$structure = NULL;
				$structure["fieldname"] 	= "trans_". $i ."_source";
				$structure["type"]		= "input";
				$structure["options"]["width"]	= "100";
				$form->add_input($structure);
				
				// description
				$structure = NULL;
				$structure["fieldname"] 	= "trans_". $i ."_description";
				$structure["type"]		= "textarea";
				$form->add_input($structure);
				

				// if we have data from a sql query, load it in
				if ($sql_trans_obj->data_num_rows)
				{
					if ($sql_trans_obj->data[$i]["chartid"])
					{
						$form->structure["trans_". $i ."_debit"]["defaultvalue"]	= $sql_trans_obj->data[$i]["amount_debit"];
						$form->structure["trans_". $i ."_credit"]["defaultvalue"]	= $sql_trans_obj->data[$i]["amount_credit"];
						$form->structure["trans_". $i ."_account"]["defaultvalue"]	= $sql_trans_obj->data[$i]["chartid"];
						$form->structure["trans_". $i ."_source"]["defaultvalue"]	= $sql_trans_obj->data[$i]["source"];
						$form->structure["trans_". $i ."_description"]["defaultvalue"]	= $sql_trans_obj->data[$i]["memo"];
					}
				}
			}


			// total fields
			$structure = NULL;
			$structure["fieldname"] 	= "total_debit";
			$structure["type"]		= "text";
			$form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"] 	= "total_credit";
			$structure["type"]		= "text";
			$form->add_input($structure);



			// hidden
			$structure = NULL;
			$structure["fieldname"] 	= "id_transaction";
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= "$id";
			$form->add_input($structure);

			$structure = NULL;
			$structure["fieldname"] 	= "num_trans";
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= "$num_trans";
			$form->add_input($structure);



		
		
			// submit section
			if (user_permissions_get("accounts_gl_write"))
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
				$structure["defaultvalue"]	= "<p><i>Sorry, you don't have permissions to make changes to this transaction.</i></p>";
				$form->add_input($structure);
			}
	
	
			
			// fetch the general form data
			$form->sql_query = "SELECT * FROM `account_gl` WHERE id='$id' LIMIT 1";
			$form->load_data();






			/*
				Display the form

				We have to do this manually in order to be able to handle all the transaction rows
			*/

			// start form/table structure
			print "<form method=\"". $form->method ."\" action=\"". $form->action ."\" class=\"form_standard\">";
			print "<table class=\"form_table\" width=\"100%\">";


			// general form fields
			print "<tr class=\"header\">";
			print "<td colspan=\"2\"><b>". language_translate_string($_SESSION["user"]["lang"], "general_ledger_transaction_details") ."</b></td>";
			print "</tr>";

			$form->render_row("code_gl");	
			$form->render_row("date_trans");
			$form->render_row("employeeid");
			$form->render_row("description");
			$form->render_row("description_useall");
			$form->render_row("notes");


			print "</tr>";



			/*
				Transaction Rows
	
				This section is the most complex part of the form, where we add new rows to the form
				for each transactions.
			*/
			print "<tr class=\"header\">";
			print "<td colspan=\"2\"><b>". language_translate_string($_SESSION["user"]["lang"], "general_ledger_transaction_rows") ."</b></td>";
			print "</tr>";

			print "<tr>";
			print "<td colspan=\"2\">";

			// header
			print "<table width=\"100%\">";

			print "<tr>";
			print "<td width=\"100%\" colspan=\"5\"><p>Enter all the parts of the transaction in the fields below. Because this is a double-entry accounting system, remember that you need to credit the source account and then debit the destination account, and that the totals for both the credit and debit accounts needs to match.</p></td>";
			print "</tr>";
			
			print "<tr>";
			print "<td width=\"20%\"><b>Account</b></td>";
			print "<td width=\"15%\"><b>Debit (dest)</b></td>";
			print "<td width=\"15%\"><b>Credit (src)</b></td>";
			print "<td width=\"15%\"><b>Source</b></td>";
			print "<td width=\"35%\"><b>Description</b></td>";		
			print "</tr>";


			// display all the rows
			for ($i = 0; $i < $num_trans; $i++)
			{
				if ($_SESSION["error"]["trans_". $i ."-error"])
				{
					print "<tr class=\"form_error\">";
				}
				else
				{
					print "<tr class=\"table_highlight\">";
				}

				// account
				print "<td width=\"20%\" valign=\"top\">";
				$form->render_field("trans_". $i ."_account");
				print "</td>";

				// debit
				print "<td width=\"15%\" valign=\"top\">";
				$form->render_field("trans_". $i ."_debit");
				print "</td>";

				// credit
				print "<td width=\"15%\" valign=\"top\">";
				$form->render_field("trans_". $i ."_credit");
				print "</td>";

				// source
				print "<td width=\"15%\" valign=\"top\">";
				$form->render_field("trans_". $i ."_source");
				print "</td>";
			
				// description
				print "<td width=\"35%\" valign=\"top\">";
				$form->render_field("trans_". $i ."_description");
				print "</td>";

		
				print "</tr>";
			}


			/*
				Totals Display
			*/
		
			print "<tr class=\"table_highlight\">";

			// joining/filler columns
			print "<td width=\"20%\"></td>";
		

			// TODO: change this from $ to any currancy
			// TODO: make totals javascript compatible so they auto-update
			
			// total debit
			print "<td width=\"15%\"><b>$";
			$form->render_field("total_debit");
			print "</b></td>";

			// total credit
			print "<td width=\"15%\"><b>$";
			$form->render_field("total_credit");
			print "</b></td>";
		
			// joining/filler columns
			print "<td width=\"15%\"></td>";
			print "<td width=\"35%\"></td>";
			
			print "</tr>";



			print "</table>";

			print "</td>";
			print "</tr>";

			// hidden fields
			$form->render_field("id_transaction");
			$form->render_field("num_trans");


			// form submit
			print "<tr class=\"header\">";
			print "<td colspan=\"2\"><b>". language_translate_string($_SESSION["user"]["lang"], "general_ledger_transaction_submit") ."</b></td>";
			print "</tr>";

			$form->render_row("submit");

			// end table + form
			print "</table>";		
			print "</form>";


		} // end if transaction exists


	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
