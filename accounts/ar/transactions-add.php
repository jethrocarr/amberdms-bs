<?php
/*
	accounts/ar/add-transaction.php
	
	access: account_ar_write

	Form to add a new transaction to the database.

	This page is a lot more complicated than most of the other forms in this program, since
	it needs to allow the user to "update" the form, so that the form adds additional input
	fields for more transaction listings.

	The update option will also generate and return totals back to the program.
	
*/

// custom includes
require("include/accounts/inc_transactions.php");
require("include/accounts/inc_charts.php");


if (user_permissions_get('accounts_ar_write'))
{
	function page_render()
	{
		$id		= security_script_input('/^[0-9]*$/', $_GET["id"]);
		$num_trans	= security_script_input('/^[0-9]*$/', $_SESSION["error"]["num_trans"]);

		if (!$num_trans)
			$num_trans = 1;

		/*
			Title + Summary
		*/
		print "<h3>ADD TRANSACTION</h3><br>";
		print "<p>This page allows you to add a new transaction to accounts recievables.</p>";

		/*
			Define form structure
		*/
		$form = New form_input;
		$form->formname = "ar_transaction_add";
		$form->language = $_SESSION["user"]["lang"];



		// basic details
		$structure = form_helper_prepare_dropdownfromdb("customerid", "SELECT id, name_customer as label FROM customers");
		$structure["options"]["width"]	= 300;
		$form->add_input($structure);
		
		$structure = form_helper_prepare_dropdownfromdb("employeeid", "SELECT id, name_staff as label FROM staff");
		$structure["options"]["width"]	= 300;
		$form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "code_invoice";
		$structure["type"]		= "input";
		$form->add_input($structure);
	
		$structure = NULL;
		$structure["fieldname"] 	= "code_ordernumber";
		$structure["type"]		= "input";
		$form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "code_ponumber";
		$structure["type"]		= "input";
		$form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "notes";
		$structure["type"]		= "textarea";
		$structure["options"]["height"]	= "60";
		$structure["options"]["width"]	= 300;
		$form->add_input($structure);



		// dates
		$structure = NULL;
		$structure["fieldname"] 	= "date_transaction";
		$structure["type"]		= "date";
		$structure["defaultvalue"]	= date("Y-m-d");
		$form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "date_due";
		$structure["type"]		= "date";
		$structure["defaultvalue"]	= transaction_calc_duedate(date("Y-m-d"));
		$form->add_input($structure);


		// transaction rows
		for ($i = 0; $i < $num_trans; $i++)
		{
			// amount field
			$structure = NULL;
			$structure["fieldname"] 	= "trans_". $i ."_amount";
			$structure["type"]		= "input";
			$structure["options"]["width"]	= "80";
			$form->add_input($structure);
				
			// account
			$structure = charts_form_prepare_acccountdropdown("trans_". $i ."_account", 2);
			$form->add_input($structure);
				
			// description
			$structure = NULL;
			$structure["fieldname"] 	= "trans_". $i ."_description";
			$structure["type"]		= "textarea";
			$form->add_input($structure);
		}

		// tax amount
		$structure = NULL;
		$structure["fieldname"] 		= "amount_tax";
		$structure["type"]			= "input";
		$structure["options"]["width"]		= "80";
		$form->add_input($structure);
		
		// tax enable/disable
		$structure = NULL;
		$structure["fieldname"] 		= "tax_enable";
		$structure["type"]			= "checkbox";
		$structure["defaultvalue"]		= "enabled";
		$structure["options"]["label"]		= " ";
		$form->add_input($structure);
		
		
		// tax account dropdown
		$structure = form_helper_prepare_dropdownfromdb("tax_id", "SELECT id, name_tax as label FROM account_taxes");

		if (count(array_keys($structure["values"])) == 1)
		{
			// if there is only 1 tax option avaliable, select it as the default
			$structure["options"]["noselectoption"] = "yes";
		}
		
		$form->add_input($structure);
		

		
		// destination account
		$structure = charts_form_prepare_acccountdropdown("dest_account", 1);

		if (count(array_keys($structure["values"])) == 1)
		{
			// if there is only 1 tax option avaliable, select it as the default
			$structure["options"]["noselectoption"] = "yes";
		}
		
		$form->add_input($structure);
		


		// hidden fields
		$structure = NULL;
		$structure["fieldname"] 	= "num_trans";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $num_trans;
		$form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "amount_tax_orig";
		$structure["type"]		= "hidden";
		$form->add_input($structure);



		// load any data returned due to errors
		$form->load_data_error();








		/*
			Display the form
		*/

		// start form/table structure
		print "<form method=\"post\" action=\"accounts/ar/transactions-edit-process.php\" class=\"form_standard\">";
		print "<table class=\"form_table\" width=\"100%\">";

		// form header
		print "<tr class=\"header\">";
		print "<td colspan=\"2\"><b>". language_translate_string($_SESSION["user"]["lang"], "transaction_add_general") ."</b></td>";
		print "</tr>";


		/*
			Basic Details

			This section is just like any normal form
		*/
		
		// details row
		print "<tr>";
		print "<td width=\"60%\" valign=\"top\">";
	
			// details table
			print "<table>";
			$form->render_row("customerid");
			$form->render_row("employeeid");
			$form->render_row("notes");
			print "</table>";
			
		print "</td>";
		print "<td width=\"40%\" valign=\"top\">";

			// details table
			print "<table>";
			$form->render_row("code_invoice");
			$form->render_row("code_ordernumber");
			$form->render_row("code_ponumber");
			$form->render_row("date_transaction");
			$form->render_row("date_due");
			print "</table>";
		print "</td>";
		

		print "</tr>";


		/*
			Transactions

			This section of the form is quite complex. We need to display all the transaction entries
			that the user has added, as well as displaying totals and tax figures for the entered transactions.

			To generate totals or new transaction rows, the user needs to click the update button, however
			in future this could be extended with javascript so the user only has to use the update button if
			their browser is not javascript capable.
		*/
		print "<tr class=\"header\">";
		print "<td colspan=\"2\"><b>". language_translate_string($_SESSION["user"]["lang"], "transaction_add_transactions") ."</b></td>";
		print "</tr>";

			print "<tr>";
			print "<td colspan=\"2\">";

			// header
			print "<table width=\"100%\">";
			print "<tr>";
				print "<td width=\"10%\"><b>Amount</b></td>";
				print "<td width=\"5%\"></td>";				// checkbox space
				print "<td width=\"35%\"><b>Account</b></td>";
				print "<td width=\"50%\"><b>Description</b></td>";
			print "</tr>";


			/*
				Transaction Rows
				
				There can be any number of transactions (minimum/default is 1) that we need
				to display
			*/
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


				// amount field
				print "<td width=\"10%\" valign=\"top\">";
				$form->render_field("trans_". $i ."_amount");
				print "</td>";


				// ignore checkbox column
				print "<td width=\"5%\" valign=\"top\"></td>";


				// account
				print "<td width=\"35%\" valign=\"top\">";
				$form->render_field("trans_". $i ."_account");
				print "</td>";


				// description
				print "<td width=\"50%\" valign=\"top\">";
				$form->render_field("trans_". $i ."_description");
				print "</td>";

			
				print "</tr>";
			}


			/*
				Tax Row

				Display the dropdown to select the tax in use and include
				a space to show the amount of tax added, which can be manually
				edited if required.
			*/


			print "<tr>";
		
		
			// amount of tax
			print "<td width=\"10%\" valign=\"top\">";
			$form->render_field("amount_tax");
			print "</td>";


			// checkbox - enable/disable tax
			print "<td width=\"5%\" valign=\"top\">";
			$form->render_field("tax_enable");
			print "</td>";


			// tax selection dropdown
			print "<td width=\"35%\" valign=\"top\">";
			$form->render_field("tax_id");
			print "</td>";
				
			// description field - filler
			print "<td width=\"50%\"></td>";

			print "</tr>";




			/*
				Totals Display
			*/
			
			print "<tr class=\"table_highlight\">";

			// total amount of transaction
			if ($_SESSION["error"]["amount_total"])
			{
				$amount = "$". $_SESSION["error"]["amount_total"];
			}
			else
			{
				$amount = "---";
			}
				
			
			print "<td width=\"10%\"><b>". $amount ."</b></td>";
			
			// joining/filler column
			print "<td width=\"5%\">to</td>";
			
			// destination account (usually always accounts recivable)
			print "<td width=\"35%\">";
			$form->render_field("dest_account");
			print "</td>";
			
			// description field - filler
			print "<td width=\"50%\"></td>";
			
			print "</tr>";



			print "</table>";

			print "</td>";
			print "</tr>";



		// hidden fields
		$form->render_field("num_trans");
		$form->render_field("amount_tax_orig");


		// form submit
		print "<tr class=\"header\">";
		print "<td colspan=\"2\"><b>". language_translate_string($_SESSION["user"]["lang"], "transaction_add_submit") ."</b></td>";
		print "</tr>";

		print "<tr>";
		print "<td colspan=\"2\">";
		print "<input type=\"submit\" name=\"action\" value=\"update\"> <i>Will re-calculate totals and allow you to enter additional rows to the transactions section.</i><br>";
		print "<br>";
		print "<input type=\"submit\" name=\"action\" value=\"save\"> <i>Will create the transaction</i>";
		print "</td>";
		print "</tr>";

		// end table + form
		print "</table>";		
		print "</form>";

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
