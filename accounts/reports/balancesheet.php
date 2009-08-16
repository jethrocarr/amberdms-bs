<?php
/*
	accounts/reports/balancesheet.php
	
	access: accounts_reports

*/

class page_output
{
	var $data_assets;
	var $data_liabilities;
	var $data_equity;
	var $data_totals;

	var $data_income;
	var $data_expenses;

	var $date_end;
	var $mode;

	var $obj_form;
	

	function check_permissions()
	{
		return user_permissions_get('accounts_reports');
	}

	function check_requirements()
	{
		// nothing todo
		return 1;
	}


	function execute()
	{

		/*
			Filter selection form
		*/

		// fetch existing values
		$this->date_end		= security_script_input("/^[0-9]*-[0-9]*-[0-9]*$/", $_GET["date_as_of_yyyy"] ."-". $_GET["date_as_of_mm"] ."-". $_GET["date_as_of_dd"]);
		$this->mode		= security_script_input("/^\S*$/", $_GET["mode"]);

		if (!$this->mode)
		{
			if ($_SESSION["account_reports"]["mode"])
			{
				$this->mode = $_SESSION["account_reports"]["mode"];
			}
			else
			{
				$this->mode = "Accrual/Invoice";
			}
		}

		if (!$this->date_end || $this->date_end == "--")
		{
			if ($_SESSION["account_reports"]["date_end"])
			{
				$this->date_end = $_SESSION["account_reports"]["date_end"];
			}
			else
			{
				$this->date_end = date("Y-m-d");
			}
		}

		// save to session vars
		$_SESSION["account_reports"]["date_end"]	= $this->date_end;
		$_SESSION["account_reports"]["mode"]		= $this->mode;


							

		// define form
		$this->obj_form = New form_input;
		$this->obj_form->method = "get";
		$this->obj_form->action = "index.php";

		$this->obj_form->formname = "accounts_report_incomestatement";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		// hidden values
		$structure = NULL;
		$structure["fieldname"] 	= "page";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $_GET["page"];
		$this->obj_form->add_input($structure);



		// date selection
		$structure = NULL;
		$structure["fieldname"] 	= "date_as_of";
		$structure["type"]		= "date";
		$structure["defaultvalue"]	= $this->date_end;
		$this->obj_form->add_input($structure);

		// mode selection
		$structure = NULL;
		$structure["fieldname"]		= "mode";
		$structure["type"]		= "radio";
		$structure["values"]		= array("Accrual/Invoice", "Cash");
		$structure["defaultvalue"]	= $this->mode;
		$this->obj_form->add_input($structure);

		// submit
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Apply Filter Options";
		$this->obj_form->add_input($structure);
	
	


		/*
			Asset Accounts
		*/
		
		// chart details
		$sql_obj = New sql_query;
		$sql_obj->prepare_sql_settable("account_charts");
		$sql_obj->prepare_sql_addfield("id");
		$sql_obj->prepare_sql_addfield("code_chart");
		$sql_obj->prepare_sql_addfield("description");
		$sql_obj->prepare_sql_addwhere("chart_type='2'");
		$sql_obj->generate_sql();
		$sql_obj->execute();
		$sql_obj->fetch_array();

		$this->data_assets = $sql_obj->data;
		unset($sql_obj);


		/*
			Liability Accounts
		*/

		// chart details
		$sql_obj = New sql_query;
		$sql_obj->prepare_sql_settable("account_charts");
		$sql_obj->prepare_sql_addfield("id");
		$sql_obj->prepare_sql_addfield("code_chart");
		$sql_obj->prepare_sql_addfield("description");
		$sql_obj->prepare_sql_addwhere("chart_type='3'");
		$sql_obj->generate_sql();
		$sql_obj->execute();
		$sql_obj->fetch_array();

		$this->data_liabilities = $sql_obj->data;
		unset($sql_obj);


		/*
			Equitity Accounts
		*/

		// chart details
		$sql_obj = New sql_query;
		$sql_obj->prepare_sql_settable("account_charts");
		$sql_obj->prepare_sql_addfield("id");
		$sql_obj->prepare_sql_addfield("code_chart");
		$sql_obj->prepare_sql_addfield("description");
		$sql_obj->prepare_sql_addwhere("chart_type='4'");
		$sql_obj->generate_sql();
		$sql_obj->execute();
		$sql_obj->fetch_array();

		$this->data_equity = $sql_obj->data;
		unset($sql_obj);


		/*
			Income Charts
		*/
		
		// chart details
		$sql_obj = New sql_query;
		$sql_obj->prepare_sql_settable("account_charts");
		$sql_obj->prepare_sql_addfield("id");
		$sql_obj->prepare_sql_addfield("code_chart");
		$sql_obj->prepare_sql_addfield("description");
		$sql_obj->prepare_sql_addwhere("chart_type='5'");
		$sql_obj->generate_sql();
		$sql_obj->execute();
		$sql_obj->fetch_array();

		$this->data_income = $sql_obj->data;
		unset($sql_obj);


		/*
			Expense Charts
		*/

		// chart details
		$sql_obj = New sql_query;
		$sql_obj->prepare_sql_settable("account_charts");
		$sql_obj->prepare_sql_addfield("id");
		$sql_obj->prepare_sql_addfield("code_chart");
		$sql_obj->prepare_sql_addfield("description");
		$sql_obj->prepare_sql_addwhere("chart_type='6'");
		$sql_obj->generate_sql();
		$sql_obj->execute();
		$sql_obj->fetch_array();

		$this->data_expense = $sql_obj->data;
		unset($sql_obj);




		/*
			Amounts
			
			This section fetches the total amounts for the different accounts. This code is a bit different to the invoicestatement code
			and instead of working on an invoice basis, works on a transaction basis.

			Accural/Invoice:
				1. Fetch all transactions from account_trans
				2. Total up credits+debits for each account

			Cash:
				1. Fetch all transactions from account_trans.
				2. Total up any ar_pay, ap_pay or gl transactions.
				2. For all other transactions, do a lookup against the invoice - if the invoice has been paid at all, (either partially
				   or fully) then include the transaction.

				Note: The behaviour of including partically paid invoces is different to how all the other application features (such as tax
				collected/paid) work, however it is required in order to have the balance sheet showing correct tax/income amounts.

				This behaviour is also the same as how SQL-Ledger generates balance sheets, which will not confuse users whom have migrated.
		*/

		
		// Run through all the transactions
		$sql_obj = New sql_query;
		
		$sql_obj->prepare_sql_settable("account_trans");
		$sql_obj->prepare_sql_addfield("id");
		$sql_obj->prepare_sql_addfield("type");
		$sql_obj->prepare_sql_addfield("customid");
		$sql_obj->prepare_sql_addfield("chartid");
		$sql_obj->prepare_sql_addfield("amount_debit");
		$sql_obj->prepare_sql_addfield("amount_credit");

		// date options
		if ($this->date_end)
		{
			$sql_obj->prepare_sql_addwhere("date_trans <= '". $this->date_end ."'");
		}


		// run through transaction entries
		$sql_obj->generate_sql();
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$sql_obj->fetch_array();

			foreach ($sql_obj->data as $data_trans)
			{
				log_debug("balancesheet", "Processing transaction ". $data_trans["id"] ." with type ". $data_trans["type"] ."");

				$valid = 0;
				
				if ($this->mode == "Cash")
				{
					// CASH

					if ($data_trans["type"] == "ar_pay" || $data_trans["type"] == "ap_pay" || $data_trans["type"] == "gl")
					{
						$valid = 1;
					}
					else
					{
						// check if the transaction invoice has any payments or not
						$sql_invoice_obj = New sql_query;
						
						if ($data_trans["type"] == "ap" || $data_trans["type"] == "ap_tax")
						{
							$sql_invoice_obj->prepare_sql_settable("account_ap");
						}
						else
						{
							$sql_invoice_obj->prepare_sql_settable("account_ar");
						}

						$sql_invoice_obj->prepare_sql_addfield("amount_paid");
						$sql_invoice_obj->prepare_sql_addwhere("id='". $data_trans["customid"] ."'");
						$sql_invoice_obj->prepare_sql_setlimit("1");
						
						$sql_invoice_obj->generate_sql();
						$sql_invoice_obj->execute();

						if ($sql_invoice_obj->num_rows())
						{
							$sql_invoice_obj->fetch_array();

							if ($sql_invoice_obj->data[0]["amount_paid"] > 0)
							{
								// invoice has some amount of payment against it, and should therefore be displayed.
								$valid = 1;
							}
							
						} // end if invoice exists
						else
						{
							log_write("error", "balancesheet", "Unable to find parent invoice (". $data_trans["customid"] .") for transaction ". $data_trans["id"] ." - Database might be damanged.");
						}

						unset($sql_invoice_obj);
					}

				}
				else
				{
					// ACCURAL/INVOICE
					$valid = 1;
				}


				if ($valid)
				{
					log_debug("balancesheet", "Transaction is valid - chartid: ". $data_trans["chartid"] .", credit: ". $data_trans["amount_credit"] .", debit: ". $data_trans["amount_debit"] ."");
					
					// run through asset charts
					for ($i = 0; $i < count(array_keys($this->data_assets)); $i++)
					{
						if ($data_trans["chartid"] == $this->data_assets[$i]["id"])
						{
							$this->data_assets[$i]["amount"] += $data_trans["amount_debit"];
							$this->data_assets[$i]["amount"] -= $data_trans["amount_credit"];
						}
					
					} // end of loop through asset charts


					// run through liability charts
					for ($i = 0; $i < count(array_keys($this->data_liabilities)); $i++)
					{
						if ($data_trans["chartid"] == $this->data_liabilities[$i]["id"])
						{
							$this->data_liabilities[$i]["amount"] -= $data_trans["amount_debit"];
							$this->data_liabilities[$i]["amount"] += $data_trans["amount_credit"];
						}
					
					} // end of loop through liability charts


					// run through equity charts
					for ($i = 0; $i < count(array_keys($this->data_equity)); $i++)
					{
						if ($data_trans["chartid"] == $this->data_equity[$i]["id"])
						{
							$this->data_equity[$i]["amount"] -= $data_trans["amount_debit"];
							$this->data_equity[$i]["amount"] += $data_trans["amount_credit"];
						}
					
					} // end of loop through equity charts


					// run through income charts
					for ($i = 0; $i < count(array_keys($this->data_income)); $i++)
					{
						if ($data_trans["chartid"] == $this->data_income[$i]["id"])
						{
							$this->data_income[$i]["amount"] -= $data_trans["amount_debit"];
							$this->data_income[$i]["amount"] += $data_trans["amount_credit"];
						}
					
					} // end of loop through income charts


					// run through expense charts
					for ($i = 0; $i < count(array_keys($this->data_expense)); $i++)
					{
						if ($data_trans["chartid"] == $this->data_expense[$i]["id"])
						{
							$this->data_expense[$i]["amount"] += $data_trans["amount_debit"];
							$this->data_expense[$i]["amount"] -= $data_trans["amount_credit"];
						}
					
					} // end of loop through expense charts

					
					
				} // end if valid
					
					
			} // end of transaction loop
			
		} // end if transaction exist










		/*
			Totals
		*/

		// assets
		if ($this->data_assets)
		{
			for ($i = 0; $i < count(array_keys($this->data_assets)); $i++)
			{
				$this->data_totals["assets"] += $this->data_assets[$i]["amount"];
			}
		}


		// liabilities
		if ($this->data_liabilities)
		{
			for ($i = 0; $i < count(array_keys($this->data_liabilities)); $i++)
			{
				$this->data_totals["liabilities"] += $this->data_liabilities[$i]["amount"];
			}
		}


		// equity
		if ($this->data_equity)
		{
			for ($i = 0; $i < count(array_keys($this->data_equity)); $i++)
			{
				$this->data_totals["equity"] += $this->data_equity[$i]["amount"];
			}
		}

	
		// income
		if ($this->data_income)
		{
			for ($i = 0; $i < count(array_keys($this->data_income)); $i++)
			{
				$this->data_totals["income"] += $this->data_income[$i]["amount"];
			}
		}
		
		// expense
		if ($this->data_expense)
		{
			for ($i = 0; $i < count(array_keys($this->data_expense)); $i++)
			{
				$this->data_totals["expense"] += $this->data_expense[$i]["amount"];
			}
		}
				

		// final
		$this->data_totals["current_earnings"]		 = $this->data_totals["income"] - $this->data_totals["expense"];
		$this->data_totals["equity"]			+= $this->data_totals["current_earnings"];
		$this->data_totals["liabilities_and_equity"]	 = $this->data_totals["liabilities"] + $this->data_totals["equity"];
	

		// formatting
		$this->data_totals["liabilities"]		= format_money($this->data_totals["liabilities"]);
		$this->data_totals["assets"]			= format_money($this->data_totals["assets"]);
		$this->data_totals["equity"]			= format_money($this->data_totals["equity"]);
		
		$this->data_totals["current_earnings"]		= format_money($this->data_totals["current_earnings"]);
		$this->data_totals["liabilities_and_equity"]	= format_money($this->data_totals["liabilities_and_equity"]);
	}


	function render_html()
	{
		// heading
		print "<h3>BALANCE SHEET</h3>";
		print "<p>This report shows assets, liabilities and equity for the selected time period.</p>";


		/*
			Date selection form
		*/

		print "<form method=\"". $this->obj_form->method ."\" action=\"". $this->obj_form->action ."\" class=\"form_standard\">";
		
		$this->obj_form->render_field("page");
		
		print "<table width=\"100%\" class=\"table_highlight\">";
		print "<tr>";
			$this->obj_form->render_row("date_as_of");
			$this->obj_form->render_row("mode");
			$this->obj_form->render_row("submit");
		print "</tr>";
		print "</table>";

		print "</form>";
		print "<br>";



		if (!$this->data_income || !$this->data_expense)
		{
			format_msgbox("important", "<p>No income and/or expense accounts have been configured.</p>");
		}
		else
		{
			/*
				Define template
			*/
			
			// start the html template
			$template_html = New template_engine;

			// load template
			$template_html->prepare_load_template("templates/html/report_balancesheet.html");



			/*
				Fill in template fields
			*/

			// totals
			$template_html->prepare_add_field("amount_total_current_earnings", $this->data_totals["current_earnings"]);
			$template_html->prepare_add_field("amount_total_assets", $this->data_totals["assets"]);
			$template_html->prepare_add_field("amount_total_liabilities", $this->data_totals["liabilities"]);
			$template_html->prepare_add_field("amount_total_equity", $this->data_totals["equity"]);
			$template_html->prepare_add_field("amount_total_liabilities_and_equity", $this->data_totals["liabilities_and_equity"]);


			// asset data
			if ($this->data_assets)
			{
				$structure_main = NULL;

				foreach ($this->data_assets as $itemdata)
				{
					$structure = array();
				
					$structure["name_chart"] 	= $itemdata["code_chart"] . " -- ". $itemdata["description"];
					$structure["amount"]		= format_money($itemdata["amount"]);

					$structure_main[] = $structure;
				}

				$template_html->prepare_add_array("table_assets", $structure_main);
			}


			// liabilities data
			if ($this->data_liabilities)
			{
				$structure_main = NULL;
				
				foreach ($this->data_liabilities as $itemdata)
				{
					$structure = array();
			
					$structure["name_chart"] 	= $itemdata["code_chart"] . " -- ". $itemdata["description"];
					$structure["amount"]		= format_money($itemdata["amount"]);

					$structure_main[] = $structure;
				}

				$template_html->prepare_add_array("table_liabilities", $structure_main);
			}


			// equity data
			if ($this->data_equity)
			{
				$structure_main = NULL;
				
				foreach ($this->data_equity as $itemdata)
				{
					$structure = array();
			
					$structure["name_chart"] 	= $itemdata["code_chart"] . " -- ". $itemdata["description"];
					$structure["amount"]		= format_money($itemdata["amount"]);

					$structure_main[] = $structure;
				}

				$template_html->prepare_add_array("table_equity", $structure_main);
			}




			/*
				Output Template
			*/

			// fill template
			$template_html->prepare_filltemplate();

			// display html
			foreach ($template_html->processed as $line)
			{
				print $line;
			}


			// display CSV/PDF download link
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=csv&page=accounts/reports/balancesheet.php\">Export as CSV</a></p>";
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=pdf&page=accounts/reports/balancesheet.php\">Export as PDF</a></p>";

		} // end if accounts exist
		
	} // end of render_html




	function render_csv()
	{
		/*
			Define template
		*/
		
		// start the csv template
		$template_csv = New template_engine;

		// load template
		$template_csv->prepare_load_template("templates/csv/report_balancesheet.csv");



		/*
			Fill in template fields
		*/

		// mode
		$template_csv->prepare_add_field("mode", $this->mode);
		
		// dates
		$template_csv->prepare_add_field("date_end", time_format_humandate($this->date_end));
		$template_csv->prepare_add_field("date_created", time_format_humandate());


		// totals
		$template_csv->prepare_add_field("amount_total_current_earnings", $this->data_totals["current_earnings"]);
		$template_csv->prepare_add_field("amount_total_assets", $this->data_totals["assets"]);
		$template_csv->prepare_add_field("amount_total_liabilities", $this->data_totals["liabilities"]);
		$template_csv->prepare_add_field("amount_total_equity", $this->data_totals["equity"]);
		$template_csv->prepare_add_field("amount_total_liabilities_and_equity", $this->data_totals["liabilities_and_equity"]);


		// asset data
		$structure_main = NULL;
			
		foreach ($this->data_assets as $itemdata)
		{
			$structure = array();
		
			$structure["name_chart"] 	= $itemdata["code_chart"] . " -- ". $itemdata["description"];
			$structure["amount"]		= format_money($itemdata["amount"]);

			$structure_main[] = $structure;
		}

		$template_csv->prepare_add_array("table_assets", $structure_main);


		// liabilities data
		$structure_main = NULL;
			
		foreach ($this->data_liabilities as $itemdata)
		{
			$structure = array();
		
			$structure["name_chart"] 	= $itemdata["code_chart"] . " -- ". $itemdata["description"];
			$structure["amount"]		= format_money($itemdata["amount"]);

			$structure_main[] = $structure;
		}

		$template_csv->prepare_add_array("table_liabilities", $structure_main);


		// equity data
		$structure_main = NULL;
			
		foreach ($this->data_equity as $itemdata)
		{
			$structure = array();
		
			$structure["name_chart"] 	= $itemdata["code_chart"] . " -- ". $itemdata["description"];
			$structure["amount"]		= format_money($itemdata["amount"]);

			$structure_main[] = $structure;
		}

		$template_csv->prepare_add_array("table_equity", $structure_main);



		/*
			Output Template
		*/

		// fill template
		$template_csv->prepare_filltemplate();

		// display csv
		foreach ($template_csv->processed as $line)
		{
			print $line;
		}
		
		
	} // end of render_csv



	function render_pdf()
	{
		// start the PDF object
		$template_pdf = New template_engine_latex;

		// load template
		$template_pdf->prepare_load_template("templates/latex/report_balancesheet.tex");



		/*
			Fill in template fields
		*/

		// company logo
		$template_pdf->prepare_add_file("company_logo", "png", "COMPANY_LOGO", 0);

		// mode
		$template_pdf->prepare_add_field("mode", $this->mode);
		
		// dates
		$template_pdf->prepare_add_field("date\_end", time_format_humandate($this->date_end));
		$template_pdf->prepare_add_field("date\_created", time_format_humandate());


		// totals
		$template_pdf->prepare_add_field("amount\_total\_current\_earnings", $this->data_totals["current_earnings"]);
		$template_pdf->prepare_add_field("amount\_total\_assets", $this->data_totals["assets"]);
		$template_pdf->prepare_add_field("amount\_total\_liabilities", $this->data_totals["liabilities"]);
		$template_pdf->prepare_add_field("amount\_total\_equity", $this->data_totals["equity"]);
		$template_pdf->prepare_add_field("amount\_total\_liabilities\_and\_equity", $this->data_totals["liabilities_and_equity"]);


		// asset data
		$structure_main = NULL;
			
		foreach ($this->data_assets as $itemdata)
		{
			$structure = array();
		
			$structure["name_chart"] 	= $itemdata["code_chart"] . " -- ". $itemdata["description"];
			$structure["amount"]		= format_money($itemdata["amount"]);

			$structure_main[] = $structure;
		}

		$template_pdf->prepare_add_array("table_assets", $structure_main);


		// liabilities data
		$structure_main = NULL;
			
		foreach ($this->data_liabilities as $itemdata)
		{
			$structure = array();
		
			$structure["name_chart"] 	= $itemdata["code_chart"] . " -- ". $itemdata["description"];
			$structure["amount"]		= format_money($itemdata["amount"]);

			$structure_main[] = $structure;
		}

		$template_pdf->prepare_add_array("table_liabilities", $structure_main);


		// equity data
		$structure_main = NULL;
			
		foreach ($this->data_equity as $itemdata)
		{
			$structure = array();
		
			$structure["name_chart"] 	= $itemdata["code_chart"] . " -- ". $itemdata["description"];
			$structure["amount"]		= format_money($itemdata["amount"]);

			$structure_main[] = $structure;
		}

		$template_pdf->prepare_add_array("table_equity", $structure_main);



		/*
			Output PDF
		*/

		// perform string escaping for latex
		$template_pdf->prepare_escape_fields();
		
		// fill template
		$template_pdf->prepare_filltemplate();

		// generate PDF output
		$template_pdf->generate_pdf();

		// display PDF
		print $template_pdf->output;
//		foreach ($template_pdf->processed as $line)
//		{
//			print $line;
//		}
		
	} // end of render_pdf
}


?>
