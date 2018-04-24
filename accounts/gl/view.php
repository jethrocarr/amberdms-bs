<?php
/*
	account/gl/view.php
	
	access: accounts_gl_view
		accounts_gl_write

	Displays all the details for the transaction and if the user has correct
	permissions allows the transaction to be updated.
*/


class page_output
{
	var $requires;

	var $id;
	var $obj_menu_nav;
	var $obj_form;

	var $locked;
	var $num_trans;


	function __construct()
	{
		// define page dependencies
		$this->requires["javascript"][]		= "include/accounts/javascript/gl.js";


		// fetch variables
		$this->id = @security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Transaction Details", "page=accounts/gl/view.php&id=". $this->id ."", TRUE);

		if (user_permissions_get("accounts_gl_write"))
		{
			$this->obj_menu_nav->add_item("Delete Transaction", "page=accounts/gl/delete.php&id=". $this->id ."");
		}
	}


	function check_permissions()
	{
		return user_permissions_get("accounts_gl_view");
	}



	function check_requirements()
	{
		// verify transaction exists and fetch locked status
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id, locked FROM account_gl WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested transaction (". $this->id .") does not exist - possibly the transaction has been deleted.");
			return 0;
		}
		else
		{
			$sql_obj->fetch_array();

			$this->locked = $sql_obj->data[0]["locked"];
		}

		unset($sql_obj);


		return 1;
	}


	function execute()
	{
		/*
			Define form structure
		*/
		$this->obj_form = New form_input;
		$this->obj_form->formname = "transaction_view";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "accounts/gl/edit-process.php";
		$this->obj_form->method = "post";
		

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "code_gl";
		$structure["type"]		= "input";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "date_trans";
		$structure["type"]		= "date";
		$structure["defaultvalue"]	= date("Y-m-d");
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);
		
		$sql_struct_obj	= New sql_query;
		$sql_struct_obj->prepare_sql_settable("staff");
		$sql_struct_obj->prepare_sql_addfield("id", "staff.id");
		$sql_struct_obj->prepare_sql_addfield("label", "staff.staff_code");
		$sql_struct_obj->prepare_sql_addfield("label1", "staff.name_staff");
		$sql_struct_obj->prepare_sql_addorderby("staff_code");
		$sql_struct_obj->prepare_sql_addwhere("id = 'CURRENTID' OR date_end = '0000-00-00'");
		
		$structure = form_helper_prepare_dropdownfromobj("employeeid", $sql_struct_obj);
		$structure["options"]["req"]		= "yes";
		$structure["options"]["autoselect"]	= "yes";
		$structure["options"]["width"]		= "600";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "description";
		$structure["type"]		= "input";
		$structure["options"]["width"]	= "600";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "description_useall";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "Check this to use the description above as the description in all the rows below. Untick if you wish to have different messages for each transaction item.";
		$structure["defaultvalue"]	= "on";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "notes";
		$structure["type"]		= "textarea";
		$structure["options"]["width"]	= "600";
		$structure["options"]["height"]	= "50";
		$this->obj_form->add_input($structure);



		/*
			Define transaction form structure
		*/

		// unless there has been error data returned, fetch all the transactions
		// from the DB, and work out the number of rows
		if (!isset($_SESSION["error"]["form"][$this->obj_form->formname]))
		{
			$sql_trans_obj		= New sql_query;
			$sql_trans_obj->string	= "SELECT date_trans, amount_debit, amount_credit, chartid, source, memo FROM `account_trans` WHERE type='gl' AND customid='". $this->id ."'";
			$sql_trans_obj->execute();
	
			if ($sql_trans_obj->num_rows())
			{
				$sql_trans_obj->fetch_array();
		
				$this->num_trans = $sql_trans_obj->data_num_rows+1;
			}
		}
		else
		{
			$this->num_trans = @security_script_input('/^[0-9]*$/', $_SESSION["error"]["num_trans"])+1;
		}

		

		// ensure there are always 2 rows at least, additional rows are added if required (ie viewing
		// an existing transaction) or on the fly when needed by javascript UI.
		
		if ($this->num_trans < 2)
		{
			$this->num_trans = 2;
		}

		// transaction rows
		for ($i = 0; $i < $this->num_trans; $i++)
		{					
			// account
			$structure = form_helper_prepare_dropdownfromdb("trans_". $i ."_account", "SELECT id, code_chart as label, description as label1 FROM account_charts WHERE chart_type!='1' ORDER BY code_chart");
			$structure["options"]["width"]	= "200";
			$this->obj_form->add_input($structure);
			
			// debit field
			$structure = NULL;
			$structure["fieldname"] 	= "trans_". $i ."_debit";
			$structure["type"]		= "input";
			$structure["options"]["width"]	= "80";
			$this->obj_form->add_input($structure);

			// credit field
			$structure = NULL;
			$structure["fieldname"] 	= "trans_". $i ."_credit";
			$structure["type"]		= "input";
			$structure["options"]["width"]	= "80";
			$this->obj_form->add_input($structure);
		
			
			// source
			$structure = NULL;
			$structure["fieldname"] 	= "trans_". $i ."_source";
			$structure["type"]		= "input";
			$structure["options"]["width"]	= "100";
			$this->obj_form->add_input($structure);
			
			// description
			$structure = NULL;
			$structure["fieldname"] 	= "trans_". $i ."_description";
			$structure["type"]		= "textarea";
			$this->obj_form->add_input($structure);
			

			// if we have data from a sql query, load it in
			if ($sql_trans_obj->data_num_rows)
			{
				if (isset($sql_trans_obj->data[$i]["chartid"]))
				{
					$this->obj_form->structure["trans_". $i ."_debit"]["defaultvalue"]		= $sql_trans_obj->data[$i]["amount_debit"];
					$this->obj_form->structure["trans_". $i ."_credit"]["defaultvalue"]		= $sql_trans_obj->data[$i]["amount_credit"];
					$this->obj_form->structure["trans_". $i ."_account"]["defaultvalue"]		= $sql_trans_obj->data[$i]["chartid"];
					$this->obj_form->structure["trans_". $i ."_source"]["defaultvalue"]		= $sql_trans_obj->data[$i]["source"];
					$this->obj_form->structure["trans_". $i ."_description"]["defaultvalue"]	= $sql_trans_obj->data[$i]["memo"];
				}
			}
		}


		// total fields
		$structure = NULL;
		$structure["fieldname"] 	= "total_debit";
		$structure["type"]		= "hidden";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "total_credit";
		$structure["type"]		= "hidden";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"]		= "money_format";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= format_money(0);
		$this->obj_form->add_input($structure);


		// hidden
		$structure = NULL;
		$structure["fieldname"] 	= "id_transaction";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->id;
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "num_trans";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= "$this->num_trans";
		$this->obj_form->add_input($structure);

	
	
		// submit section
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Save Changes";
		$this->obj_form->add_input($structure);
		
		
		// fetch the general form data
		$this->obj_form->sql_query = "SELECT * FROM `account_gl` WHERE id='". $this->id ."' LIMIT 1";
		$this->obj_form->load_data();


		// calculate totals
		for ($i = 0; $i < $this->num_trans; $i++)
		{
			@$this->obj_form->structure["total_debit"]["defaultvalue"]	+= $this->obj_form->structure["trans_". $i ."_debit"]["defaultvalue"];
			@$this->obj_form->structure["total_credit"]["defaultvalue"]	+= $this->obj_form->structure["trans_". $i ."_credit"]["defaultvalue"];
		}

	}



	function render_html()
	{
		// Title + Summary
		print "<h3>TRANSACTION DETAILS</h3><br>";
		print "<p>This page allows you to view and adjust the selected transaction.</p>";


		/*
			Display the form

			We have to do this manually in order to be able to handle all the transaction rows
		*/

		// start form/table structure
		print "<form method=\"". $this->obj_form->method ."\" action=\"". $this->obj_form->action ."\" class=\"form_standard\">";
		print "<table class=\"form_table\" width=\"100%\">";


		// general form fields
		print "<tr class=\"header\">";
		print "<td colspan=\"2\"><b>". language_translate_string($_SESSION["user"]["lang"], "general_ledger_transaction_details") ."</b></td>";
		print "</tr>";

		$this->obj_form->render_row("code_gl");	
		$this->obj_form->render_row("date_trans");
		$this->obj_form->render_row("employeeid");
		$this->obj_form->render_row("description");
		$this->obj_form->render_row("description_useall");
		$this->obj_form->render_row("notes");


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
		for ($i = 0; $i < $this->num_trans; $i++)
		{
			if (isset($_SESSION["error"]["trans_". $i ."-error"]))
			{
				print "<tr class=\"form_error\">";
			}
			else
			{
				print "<tr class=\"table_highlight\">";
			}

			// account
			print "<td width=\"20%\" valign=\"top\">";
			$this->obj_form->render_field("trans_". $i ."_account");
			print "</td>";

			// debit
			print "<td width=\"15%\" valign=\"top\">";
			$this->obj_form->render_field("trans_". $i ."_debit");
			print "</td>";

			// credit
			print "<td width=\"15%\" valign=\"top\">";
			$this->obj_form->render_field("trans_". $i ."_credit");
			print "</td>";

			// source
			print "<td width=\"15%\" valign=\"top\">";
			$this->obj_form->render_field("trans_". $i ."_source");
			print "</td>";
		
			// description
			print "<td width=\"35%\" valign=\"top\">";
			$this->obj_form->render_field("trans_". $i ."_description");
			print "</td>";

	
			print "</tr>";
		}


		/*
			Totals Display
		*/
	
		print "<tr class=\"table_highlight\">";

		// joining/filler columns
		print "<td width=\"20%\"></td>";
	

		// total debit
		print "<td width=\"15%\">";
		$this->obj_form->render_field("total_debit");
		print "</td>";

		// total credit
		print "<td width=\"15%\">";
		$this->obj_form->render_field("total_credit");
		print "</td>";
	
		// joining/filler columns
		print "<td width=\"15%\"></td>";
		print "<td width=\"35%\"></td>";
		
		print "</tr>";

		

		print "</table>";
		print "</td>";
		print "</tr>";

		// hidden fields
		$this->obj_form->render_field("id_transaction");
		$this->obj_form->render_field("num_trans");
		$this->obj_form->render_field("money_format");


		// form submit
		print "<tr class=\"header\">";
		print "<td colspan=\"2\"><b>". language_translate_string($_SESSION["user"]["lang"], "general_ledger_transaction_submit") ."</b></td>";
		print "</tr>";
		
		if (user_permissions_get("accounts_gl_write") && !$this->locked)
		{
			$this->obj_form->render_row("submit");
		}
		
		// end table + form
		print "</table>";		
		print "</form>";

		if (!user_permissions_get("accounts_gl_write"))
		{
			format_msgbox("locked", "<p>Sorry, you do not have permission to adjust this transaction</p>");
		}
		elseif ($this->locked)
		{
			format_msgbox("locked", "<p>This transaction has been locked and can no longer be adjusted.</p>");
		}
	}
}

?>
