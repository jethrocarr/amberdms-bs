<?php
/*
	accounts/reports/incomestatement.php
	
	access: accounts_reports

	Generates a report which shows the status of income and expenses for the selected time period.
*/

class page_output
{
	var $data_expense;
	var $data_income;
	var $data_totals;

	var $date_start;
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
		$this->date_start	= @security_script_input("/^[0-9]*-[0-9]*-[0-9]*$/", $_GET["date_start_yyyy"] ."-". $_GET["date_start_mm"] ."-". $_GET["date_start_dd"]);
		$this->date_end		= @security_script_input("/^[0-9]*-[0-9]*-[0-9]*$/", $_GET["date_end_yyyy"] ."-". $_GET["date_end_mm"] ."-". $_GET["date_end_dd"]);
		$this->mode		= @security_script_input("/^\S*$/", $_GET["mode"]);

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

		if (!$this->date_start || $this->date_start == "--")
		{
			if ($_SESSION["account_reports"]["date_start"])
			{
				$this->date_start = $_SESSION["account_reports"]["date_start"];
			}
			else
			{
				$this->date_start = NULL;
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
				$this->date_end = NULL;
			}
		}

		// save to session vars
		$_SESSION["account_reports"]["date_start"]	= $this->date_start;
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
		$structure["fieldname"] 	= "date_start";
		$structure["type"]		= "date";
		$structure["defaultvalue"]	= $this->date_start;
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "date_end";
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

			This section needs to total up the the amounts in each account - however, we are unable to just
			pull the information from account_trans, because we need to be able to fetch either the invoiced/accural
			amount OR the cash (ie: paid) amount.

			The reports for taxes can handle it simpler, by just calcuating the invoice item total, but we also have
			to include general ledger transactions.

			Accural/Invoice:
				1. Run though all invoices
				2. Add item amounts to accounts
				3. Run through all general ledger transactions
				4. Add GL amounts to accounts

			Cash:
				1. Run through all invoices
				2. If invoices are fully paid, then add item amounts to accounts.
				3. Impossible to handle partially paid invoices properly, so we ignore them.
				4. Run through all general ledger transactions
				5. Add GL amounts to accounts.

				Note: The date checks are made against the invoice date, not the payment date.
		*/


		//
		// AR INVOICES
		//
		$sql_obj = New sql_query;
		
		$sql_obj->prepare_sql_settable("account_ar");
		$sql_obj->prepare_sql_addfield("id");

		// date options
		if ($this->date_start)
		{
			$sql_obj->prepare_sql_addwhere("date_trans >= '". $this->date_start ."'");
		}
		
		if ($this->date_end)
		{
			$sql_obj->prepare_sql_addwhere("date_trans <= '". $this->date_end ."'");
		}


		// paid invoices only
		if ($this->mode == "Cash")
		{
			$sql_obj->prepare_sql_addwhere("amount_total=amount_paid");
		}

		// run through invoices
		$sql_obj->generate_sql();
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$sql_obj->fetch_array();

			foreach ($sql_obj->data as $data_invoice)
			{
				// fetch all items for this invoice type
				$sql_item_obj		= New sql_query;
				$sql_item_obj->string	= "SELECT chartid, amount FROM account_items WHERE invoiceid='". $data_invoice["id"] ."' AND invoicetype='ar'";
				$sql_item_obj->execute();

				if ($sql_item_obj->num_rows())
				{
					$sql_item_obj->fetch_array();

					foreach ($sql_item_obj->data as $data_item)
					{

						// run through income charts
						for ($i = 0; $i < count(array_keys($this->data_income)); $i++)
						{
							if ($data_item["chartid"] == $this->data_income[$i]["id"])
							{
								@$this->data_income[$i]["amount"] += $data_item["amount"];
							}
							
						} // end of loop through charts

					} // end of invoice item loop
					
				} // end if invoice items
				
			} // end of invoice loop
			
		} // end if invoices

		unset($sql_obj);



		//
		// AP INVOICES
		//
		$sql_obj = New sql_query;
		
		$sql_obj->prepare_sql_settable("account_ap");
		$sql_obj->prepare_sql_addfield("id");

		// date options
		if ($this->date_start)
		{
			$sql_obj->prepare_sql_addwhere("date_trans >= '". $this->date_start ."'");
		}
		
		if ($this->date_end)
		{
			$sql_obj->prepare_sql_addwhere("date_trans <= '". $this->date_end ."'");
		}


		// paid invoices only
		if ($this->mode == "Cash")
		{
			$sql_obj->prepare_sql_addwhere("amount_total=amount_paid");
		}

		// run through invoices
		$sql_obj->generate_sql();
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$sql_obj->fetch_array();

			foreach ($sql_obj->data as $data_invoice)
			{
				// fetch all items for this invoice type
				$sql_item_obj		= New sql_query;
				$sql_item_obj->string	= "SELECT chartid, amount FROM account_items WHERE invoiceid='". $data_invoice["id"] ."' AND invoicetype='ap'";
				$sql_item_obj->execute();

				if ($sql_item_obj->num_rows())
				{
					$sql_item_obj->fetch_array();

					foreach ($sql_item_obj->data as $data_item)
					{
						// run through expense charts
						for ($i = 0; $i < count(array_keys($this->data_expense)); $i++)
						{
							if ($data_item["chartid"] == $this->data_expense[$i]["id"])
							{
								@$this->data_expense[$i]["amount"] += $data_item["amount"];
							}
							
						} // end of loop through charts

					} // end of invoice item loop
					
				} // end if invoice items
				
			} // end of invoice loop
			
		} // end if invoices

		unset($sql_obj);




		//
		// GL TRANSACTIONS
		//
		// Fetch all the GL transactions during this period and add to totals.
		//

		$sql_obj = New sql_query;
		
		$sql_obj->prepare_sql_settable("account_trans");
		$sql_obj->prepare_sql_addfield("chartid");
		$sql_obj->prepare_sql_addfield("amount_debit");
		$sql_obj->prepare_sql_addfield("amount_credit");
		$sql_obj->prepare_sql_addwhere("type='gl'");

		// date options
		if ($this->date_start)
		{
			$sql_obj->prepare_sql_addwhere("date_trans >= '". $this->date_start ."'");
		}
		
		if ($this->date_end)
		{
			$sql_obj->prepare_sql_addwhere("date_trans <= '". $this->date_end ."'");
		}


		// run through GL entries
		$sql_obj->generate_sql();
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$sql_obj->fetch_array();

			foreach ($sql_obj->data as $data_trans)
			{
				// run through income charts
				for ($i = 0; $i < count(array_keys($this->data_income)); $i++)
				{
					if ($data_trans["chartid"] == $this->data_income[$i]["id"])
					{
						$this->data_income[$i]["amount"] += $data_trans["amount_credit"];
					}
							
				} // end of loop through income charts

			
				// run through expense charts
				for ($i = 0; $i < count(array_keys($this->data_expense)); $i++)
				{
					if ($data_trans["chartid"] == $this->data_expense[$i]["id"])
					{
						$this->data_expense[$i]["amount"] += $data_trans["amount_debit"];
					}
							
				} // end of loop through expense charts
					
			} // end of transaction loop
			
		} // end if transaction exist





		/*
			Totals
		*/

		// income
		$this->data_totals["income"] = 0;
		for ($i = 0; $i < count(array_keys($this->data_income)); $i++)
		{
			@$this->data_totals["income"] += $this->data_income[$i]["amount"];
		}
		


		// expense
		for ($i = 0; $i < count(array_keys($this->data_expense)); $i++)
		{
			@$this->data_totals["expense"] += $this->data_expense[$i]["amount"];
		}
		

		// final
		$this->data_totals["final"]	= @$this->data_totals["income"] - $this->data_totals["expense"];
		
		$this->data_totals["income"]	= @format_money($this->data_totals["income"]);
		$this->data_totals["expense"]	= @format_money($this->data_totals["expense"]);
		$this->data_totals["final"]	= @format_money($this->data_totals["final"]);
	}


	function render_html()
	{
		// heading
		print "<h3>INCOME STATEMENT</h3>";
		print "<p>This report shows income and expenses for the selected period.</p>";


		/*
			Date selection form
		*/

		print "<form method=\"". $this->obj_form->method ."\" action=\"". $this->obj_form->action ."\" class=\"form_standard\">";
		
		$this->obj_form->render_field("page");
		
		print "<table width=\"100%\" class=\"table_highlight\">";
		print "<tr>";
			$this->obj_form->render_row("date_start");
			$this->obj_form->render_row("date_end");
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
			$template_html->prepare_load_template("templates/html/report_incomestatement.html");



			/*
				Fill in template fields
			*/

			// totals
			$template_html->prepare_add_field("amount_total_income", $this->data_totals["income"]);
			$template_html->prepare_add_field("amount_total_expense", $this->data_totals["expense"]);
			$template_html->prepare_add_field("amount_total_final", $this->data_totals["final"]);


			// income data
			$structure_main = NULL;
				
			foreach ($this->data_income as $itemdata)
			{
				$structure = array();
			
				$structure["name_chart"] 	= $itemdata["code_chart"] . " -- ". $itemdata["description"];
				$structure["amount"]		= @format_money($itemdata["amount"]);

				$structure_main[] = $structure;
			}

			$template_html->prepare_add_array("table_income", $structure_main);


			// income data
			$structure_main = NULL;
				
			foreach ($this->data_expense as $itemdata)
			{
				$structure = array();
			
				$structure["name_chart"] 	= $itemdata["code_chart"] . " -- ". $itemdata["description"];
				$structure["amount"]		= @format_money($itemdata["amount"]);

				$structure_main[] = $structure;
			}

			$template_html->prepare_add_array("table_expense", $structure_main);




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


			// display CSV download link
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=csv&page=accounts/reports/incomestatement.php\">Export as CSV</a></p>";
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=pdf&page=accounts/reports/incomestatement.php\">Export as PDF</a></p>";

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
		$template_csv->prepare_load_template("templates/csv/report_incomestatement.csv");



		/*
			Fill in template fields
		*/

		// mode
		$template_csv->prepare_add_field("mode", $this->mode);
		
		// dates
		$template_csv->prepare_add_field("date_start", time_format_humandate($this->date_start));
		$template_csv->prepare_add_field("date_end", time_format_humandate($this->date_end));
		$template_csv->prepare_add_field("date_created", time_format_humandate());

		// totals
		$template_csv->prepare_add_field("amount_total_income", $this->data_totals["income"]);
		$template_csv->prepare_add_field("amount_total_expense", $this->data_totals["expense"]);
		$template_csv->prepare_add_field("amount_total_final", $this->data_totals["final"]);


		// income data
		$structure_main = NULL;
			
		foreach ($this->data_income as $itemdata)
		{
			$structure = array();
		
			$structure["name_chart"] 	= $itemdata["code_chart"] . " -- ". $itemdata["description"];
			$structure["amount"]		= format_money($itemdata["amount"]);

			$structure_main[] = $structure;
		}

		$template_csv->prepare_add_array("table_income", $structure_main);


		// income data
		$structure_main = NULL;
			
		foreach ($this->data_expense as $itemdata)
		{
			$structure = array();
		
			$structure["name_chart"] 	= $itemdata["code_chart"] . " -- ". $itemdata["description"];
			$structure["amount"]		= format_money($itemdata["amount"]);

			$structure_main[] = $structure;
		}

		$template_csv->prepare_add_array("table_expense", $structure_main);



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
		$template_pdf->prepare_load_template("templates/latex/report_incomestatement.tex");


		/*
			Fetch data + define fields
		*/

		// company logo
		$template_pdf->prepare_add_file("company_logo", "png", "COMPANY_LOGO", 0);
		
		// mode
		$template_pdf->prepare_add_field("mode", $this->mode);

		// dates
		$template_pdf->prepare_add_field("date\_start", time_format_humandate($this->date_start));
		$template_pdf->prepare_add_field("date\_end", time_format_humandate($this->date_end));
		$template_pdf->prepare_add_field("date\_created", time_format_humandate());


		// totals
		$template_pdf->prepare_add_field("amount\_total\_income", $this->data_totals["income"]);
		$template_pdf->prepare_add_field("amount\_total\_expense", $this->data_totals["expense"]);
		$template_pdf->prepare_add_field("amount\_total\_final", $this->data_totals["final"]);


		// income data
		$structure_main = NULL;
			
		foreach ($this->data_income as $itemdata)
		{
			$structure = array();
		
			$structure["name_chart"] 	= $itemdata["code_chart"] . " -- ". $itemdata["description"];
			$structure["amount"]		= format_money($itemdata["amount"]);

			$structure_main[] = $structure;
		}

		$template_pdf->prepare_add_array("table_income", $structure_main);


		// income data
		$structure_main = NULL;
			
		foreach ($this->data_expense as $itemdata)
		{
			$structure = array();
		
			$structure["name_chart"] 	= $itemdata["code_chart"] . " -- ". $itemdata["description"];
			$structure["amount"]		= format_money($itemdata["amount"]);

			$structure_main[] = $structure;
		}

		$template_pdf->prepare_add_array("table_expense", $structure_main);


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
