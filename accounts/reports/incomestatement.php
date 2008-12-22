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
			Date selection form
		*/

		// fetch existing dates
		$this->date_start	= security_script_input("/^[0-9]*-[0-9]*-[0-9]*$/", $_GET["date_start_yyyy"] ."-". $_GET["date_start_mm"] ."-". $_GET["date_start_dd"]);
		$this->date_end		= security_script_input("/^[0-9]*-[0-9]*-[0-9]*$/", $_GET["date_end_yyyy"] ."-". $_GET["date_end_mm"] ."-". $_GET["date_end_dd"]);
	
		if (!$this->date_start || $this->date_start == "--")
		{
			if ($_SESSION["account_reports"]["date_start"])
			{
				$this->date_start = $_SESSION["account_reports"]["date_start"];
			}
		}
		
		if (!$this->date_end || $this->date_end == "--")
		{
			if ($_SESSION["account_reports"]["date_end"])
			{
				$this->date_end = $_SESSION["account_reports"]["date_end"];
			}
		}

		// save to session vars
		$_SESSION["account_reports"]["date_start"]	= $this->date_start;
		$_SESSION["account_reports"]["date_end"]	= $this->date_end;


							

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

		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Apply Date Selection";
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
		*/

		// fetch amounts for all charts in advance - this
		// is better than running a query per chart just to get all the totals
		$sql_amount_obj = New sql_query;
		
		$sql_amount_obj->prepare_sql_settable("account_trans");
		$sql_amount_obj->prepare_sql_addfield("chartid");
		$sql_amount_obj->prepare_sql_addfield("credit", "SUM(amount_credit)");
		$sql_amount_obj->prepare_sql_addfield("debit", "SUM(amount_debit)");
		
		
		if ($this->date_start)
		{
			$sql_amount_obj->prepare_sql_addwhere("date_trans >= '". $this->date_start ."'");
		}
		
		
		if ($this->date_end)
		{
			$sql_amount_obj->prepare_sql_addwhere("date_trans <= '". $this->date_end ."'");
		}
	
		
		$sql_amount_obj->prepare_sql_addgroupby("chartid");
		$sql_amount_obj->generate_sql();
		$sql_amount_obj->execute();

		if ($sql_amount_obj->num_rows())
		{
			$sql_amount_obj->fetch_array();


			// run through income
			for ($i = 0; $i < count(array_keys($this->data_income)); $i++)
			{
				foreach ($sql_amount_obj->data as $data_amount)
				{
					if ($data_amount["chartid"] == $this->data_income[$i]["id"])
					{
						$this->data_income[$i]["amount"] = $data_amount["credit"] - $data_amount["debit"];
					}
				}
			}


			// run through expenses
			for ($i = 0; $i < count(array_keys($this->data_expense)); $i++)
			{
				foreach ($sql_amount_obj->data as $data_amount)
				{
					if ($data_amount["chartid"] == $this->data_expense[$i]["id"])
					{
						$this->data_expense[$i]["amount"] = $data_amount["debit"] - $data_amount["credit"];
					}
				}
			}
		}


		/*
			Totals
		*/

		// income
		$this->data_totals["income"] = 0;
		for ($i = 0; $i < count(array_keys($this->data_income)); $i++)
		{
			$this->data_totals["income"] += $this->data_income[$i]["amount"];
		}
		


		// expense
		for ($i = 0; $i < count(array_keys($this->data_expense)); $i++)
		{
			$this->data_totals["expense"] += $this->data_expense[$i]["amount"];
		}
		

		// final
		$this->data_totals["final"] = $this->data_totals["income"] - $this->data_totals["expense"];
		
		$this->data_totals["income"] = sql_get_singlevalue("SELECT value FROM config WHERE name='CURRENCY_DEFAULT_SYMBOL'") . sprintf("%0.2f", $this->data_totals["income"]);
		$this->data_totals["expense"] = sql_get_singlevalue("SELECT value FROM config WHERE name='CURRENCY_DEFAULT_SYMBOL'") . sprintf("%0.2f", $this->data_totals["expense"]);
		$this->data_totals["final"] = sql_get_singlevalue("SELECT value FROM config WHERE name='CURRENCY_DEFAULT_SYMBOL'") . sprintf("%0.2f", $this->data_totals["final"]);
	}


	function render_html()
	{
		// heading
		print "<h3>INCOME STATEMENT</h3>";
		print "<p>This report shows income and expenses for the selected period.</p>";


		/*
			Date selection form
		*/

		print "<form method=\"". $this->obj_form->method ."\" action=\"". $this->obj_form->action ."\">";
		
		$this->obj_form->render_field("page");
		
		print "<table width=\"100%\" class=\"table_highlight\">";
		print "<tr>";
			$this->obj_form->render_row("date_start");
			$this->obj_form->render_row("date_end");
			$this->obj_form->render_row("submit");
		print "</tr>";
		print "</table>";

		print "</form>";
		print "<br>";
		


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
			$structure["amount"]		= sql_get_singlevalue("SELECT value FROM config WHERE name='CURRENCY_DEFAULT_SYMBOL'") . sprintf("%0.2f", $itemdata["amount"]);

			$structure_main[] = $structure;
		}

		$template_html->prepare_add_array("table_income", $structure_main);


		// income data
		$structure_main = NULL;
			
		foreach ($this->data_expense as $itemdata)
		{
			$structure = array();
		
			$structure["name_chart"] 	= $itemdata["code_chart"] . " -- ". $itemdata["description"];
			$structure["amount"]		= sql_get_singlevalue("SELECT value FROM config WHERE name='CURRENCY_DEFAULT_SYMBOL'") . sprintf("%0.2f", $itemdata["amount"]);

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
		print "<p align=\"right\"><a href=\"index-export.php?mode=csv&page=accounts/reports/incomestatement.php\">Export as CSV</a></p>";
		print "<p align=\"right\"><a href=\"index-export.php?mode=pdf&page=accounts/reports/incomestatement.php\">Export as PDF</a></p>";
		
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

		// dates
		$template_csv->prepare_add_field("date_start", $this->date_start);
		$template_csv->prepare_add_field("date_end", $this->date_end);
		$template_csv->prepare_add_field("date_created", date("Y-m-d"));

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
			$structure["amount"]		= sql_get_singlevalue("SELECT value FROM config WHERE name='CURRENCY_DEFAULT_SYMBOL'") . sprintf("%0.2f", $itemdata["amount"]);

			$structure_main[] = $structure;
		}

		$template_csv->prepare_add_array("table_income", $structure_main);


		// income data
		$structure_main = NULL;
			
		foreach ($this->data_expense as $itemdata)
		{
			$structure = array();
		
			$structure["name_chart"] 	= $itemdata["code_chart"] . " -- ". $itemdata["description"];
			$structure["amount"]		= sql_get_singlevalue("SELECT value FROM config WHERE name='CURRENCY_DEFAULT_SYMBOL'") . sprintf("%0.2f", $itemdata["amount"]);

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


		// dates
		$template_pdf->prepare_add_field("date\_start", $this->date_start);
		$template_pdf->prepare_add_field("date\_end", $this->date_end);
		$template_pdf->prepare_add_field("date\_created", date("Y-m-d"));


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
			$structure["amount"]		= sql_get_singlevalue("SELECT value FROM config WHERE name='CURRENCY_DEFAULT_SYMBOL'") . sprintf("%0.2f", $itemdata["amount"]);

			$structure_main[] = $structure;
		}

		$template_pdf->prepare_add_array("table_income", $structure_main);


		// income data
		$structure_main = NULL;
			
		foreach ($this->data_expense as $itemdata)
		{
			$structure = array();
		
			$structure["name_chart"] 	= $itemdata["code_chart"] . " -- ". $itemdata["description"];
			$structure["amount"]		= sql_get_singlevalue("SELECT value FROM config WHERE name='CURRENCY_DEFAULT_SYMBOL'") . sprintf("%0.2f", $itemdata["amount"]);

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
