<?php
/*
	accounts/reports/trialbalance.php
	
	access: accounts_reports

	Displays the complete totals of all the accounts in the system. This differs from the chart of accounts
	page which shows the differences only.
*/

class page_output
{
	var $obj_table;

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
		$this->date_start	= @security_script_input("/^[0-9]*-[0-9]*-[0-9]*$/", $_GET["date_start_yyyy"] ."-". $_GET["date_start_mm"] ."-". $_GET["date_start_dd"]);
		$this->date_end		= @security_script_input("/^[0-9]*-[0-9]*-[0-9]*$/", $_GET["date_end_yyyy"] ."-". $_GET["date_end_mm"] ."-". $_GET["date_end_dd"]);
	
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


		// define form
		$this->obj_form = New form_input;
		$this->obj_form->method = "get";
		$this->obj_form->action = "index.php";

		$this->obj_form->formname = "accounts_report_trialbalance";
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

		// submit
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Apply Filter Options";
		$this->obj_form->add_input($structure);
	



	
		// establish a new table object
		$this->obj_table = New table;

		$this->obj_table->language	= $_SESSION["user"]["lang"];
		$this->obj_table->tablename	= "accounts_reports_trialbalance";

		// define all the columns and structure
		$this->obj_table->add_column("standard", "code_chart", "account_charts.code_chart");
		$this->obj_table->add_column("standard", "description", "account_charts.description");
		$this->obj_table->add_column("standard", "chart_type", "account_chart_type.value");

		// the debit and credit columns need to be calculated by a seporate query
		$this->obj_table->add_column("price", "debit", "NONE");
		$this->obj_table->add_column("price", "credit", "NONE");

		// defaults
		$this->obj_table->columns		= array("code_chart", "description", "chart_type", "debit", "credit");
		$this->obj_table->columns_order		= array("code_chart");

		// totals
		$this->obj_table->total_columns		= array("debit", "credit");
		$this->obj_table->total_rows		= array("debit", "credit");
		$this->obj_table->total_rows_mode	= "subtotal_nofinal";

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("account_charts");
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "account_charts.id");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN account_chart_type ON account_chart_type.id = account_charts.chart_type");
		$this->obj_table->sql_obj->prepare_sql_addwhere("account_charts.chart_type != '1'");


		// fetch all the chart information
		$this->obj_table->generate_sql();
		$this->obj_table->load_data_sql();


		// fetch debit and credit summaries for all charts in advance - this
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


			// run through all the chart rows and fill in the credit/debit fields
			for ($i = 0; $i < count(array_keys($this->obj_table->data)); $i++)
			{
				foreach ($sql_amount_obj->data as $data_amount)
				{
					if ($data_amount["chartid"] == $this->obj_table->data[$i]["id"])
					{
						$this->obj_table->data[$i]["debit"]	= $data_amount["debit"];
						$this->obj_table->data[$i]["credit"]	= $data_amount["credit"];
					}
				}
			}
		}

	}


	function render_html()
	{
		// heading
		print "<h3>TRIAL BALANCE</h3>";
		print "<p>This page lists all the accounts which transactions are filed against and provides a basic overview of the current state of the financials.</p>";


		/*
			Date selection form
		*/

		print "<form method=\"". $this->obj_form->method ."\" action=\"". $this->obj_form->action ."\" class=\"form_standard\">";
		
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
			Display Table
		*/
		if (!$this->obj_table->data_num_rows)
		{
			format_msgbox("important", "<p>You currently have no accounts in your database.</p>");
		}
		else
		{
			// display the table
			$this->obj_table->render_table_html();

			// display CSV + PDF download links
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=csv&page=accounts/reports/trialbalance.php\">Export as CSV</a></p>";
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=pdf&page=accounts/reports/trialbalance.php\">Export as PDF</a></p>";
		}
	}


	function render_csv()
	{
		$this->obj_table->render_table_csv();
	}


	function render_pdf()
	{
		// prepare table data
		$this->obj_table->render_table_prepare();
	
		// start the PDF object
		$template_pdf = New template_engine_latex;

		// load template
		$template_pdf->prepare_load_template("templates/latex/report_trialbalance.tex");


		/*
			Fetch data + define fields
		*/

		// company logo
		$template_pdf->prepare_add_file("company_logo", "png", "COMPANY_LOGO", 0);

		// dates
		$template_pdf->prepare_add_field("date_start", time_format_humandate($this->date_start));
		$template_pdf->prepare_add_field("date_end", time_format_humandate($this->date_end));
		$template_pdf->prepare_add_field("date_created", time_format_humandate());


		// totals
		$template_pdf->prepare_add_field("amount_total_credit", $this->obj_table->data_render["total"]["credit"]);
		$template_pdf->prepare_add_field("amount_total_debit", $this->obj_table->data_render["total"]["debit"]);


		// table rows
		$structure_main = NULL;
	
		for ($i=0; $i < $this->obj_table->data_num_rows; $i++)
		{
			$structure = array();

			$structure["code_chart"]	= $this->obj_table->data_render[$i]["code_chart"];
			$structure["description"]	= $this->obj_table->data_render[$i]["description"];
			$structure["chart_type"]	= $this->obj_table->data_render[$i]["chart_type"];
			$structure["credit"]		= $this->obj_table->data_render[$i]["credit"];
			$structure["debit"]		= $this->obj_table->data_render[$i]["debit"];
			$structure["balance"]		= $this->obj_table->data_render[$i]["total"];

			$structure_main[] = $structure;
		}

		$template_pdf->prepare_add_array("table_data", $structure_main);


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
	}
}


?>
