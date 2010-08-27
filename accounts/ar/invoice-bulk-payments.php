<?php
/*
	accounts/ar/invoice-bulk-payments.php
	
	access: accounts_ar_write

	Lists all invoices in system and allows user to load multiple payments
*/
		
require("include/accounts/inc_charts.php");

class page_output
{
	var $obj_table;
	var $obj_form;
	
	function page_output()
	{
		// requirements
		$this->requires["css"][]		= "include/accounts/css/invoice-bulk-payments.css";
		$this->requires["javascript"][]		= "include/accounts/javascript/invoice-bulk-payments_ar.js";
	}

	function check_permissions()
	{
		return user_permissions_get('accounts_ar_write');
	}

	function check_requirements()
	{
		// nothing todo
		return 1;
	}


	function execute()
	{
		// establish a new table object
		$this->obj_table = New table;

		$this->obj_table->language	= $_SESSION["user"]["lang"];
		$this->obj_table->tablename	= "invoice-bulk-payments";

		// define all the columns and structure
		$this->obj_table->add_column("standard", "code_invoice", "account_ar.code_invoice");
		$this->obj_table->add_column("standard", "code_ordernumber", "account_ar.code_ordernumber");
		$this->obj_table->add_column("standard", "code_ponumber", "account_ar.code_ponumber");
		$this->obj_table->add_column("standard", "name_customer", "CONCAT_WS(' -- ', customers.code_customer, customers.name_customer)");
		$this->obj_table->add_column("standard", "name_staff", "CONCAT_WS(' -- ', staff.staff_code, staff.name_staff)");
		$this->obj_table->add_column("date", "date_trans", "account_ar.date_trans");
		$this->obj_table->add_column("date", "date_due", "account_ar.date_due");
		$this->obj_table->add_column("price", "amount_tax", "account_ar.amount_tax");
		$this->obj_table->add_column("price", "amount", "account_ar.amount");
		$this->obj_table->add_column("price", "amount_total", "account_ar.amount_total");
		$this->obj_table->add_column("price", "amount_paid", "account_ar.amount_paid");
		$this->obj_table->add_column("bool_tick", "sent", "account_ar.sentmethod");
		$this->obj_table->add_column("standard", "pay", "NONE");
		
		// defaults
		$this->obj_table->columns		= array("code_invoice", "name_customer", "date_trans", "amount_total", "amount_paid", "pay");
		$this->obj_table->columns_order		= array("code_invoice");
		$this->obj_table->columns_order_options	= array("code_invoice", "code_ordernumber", "code_ponumber", "name_customer", "name_staff", "date_trans", "date_due", "sent", "pay");

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("account_ar");
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "account_ar.id");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN customers ON customers.id = account_ar.customerid");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN staff ON staff.id = account_ar.employeeid");

		// acceptable filter options
		$structure = NULL;
		$structure["fieldname"] = "date_start";
		$structure["type"]	= "date";
		$structure["sql"]	= "date_trans >= 'value'";
		$this->obj_table->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] = "date_end";
		$structure["type"]	= "date";
		$structure["sql"]	= "date_trans <= 'value'";
		$this->obj_table->add_filter($structure);
		
		$structure				= form_helper_prepare_dropdownfromdb("employeeid", "SELECT id, staff_code as label, name_staff as label1 FROM staff ORDER BY name_staff");
		$structure["sql"]			= "account_ar.employeeid='value'";
		$structure["options"]["search_filter"]	= "enabled";
		$this->obj_table->add_filter($structure);

		$structure				= form_helper_prepare_dropdownfromdb("customerid", "SELECT id, code_customer as label, name_customer as label1 FROM customers ORDER BY name_customer");
		$structure["sql"]			= "account_ar.customerid='value'";
		$structure["options"]["search_filter"]	= "enabled";
		$this->obj_table->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "hide_closed";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "Hide Closed Invoices";
		$structure["defaultvalue"]	= "enabled";
		$structure["sql"]		= "account_ar.amount_paid!=account_ar.amount_total";
		$this->obj_table->add_filter($structure);

		// load options
		$this->obj_table->load_options_form();


		// fetch all the chart information
		$this->obj_table->generate_sql();
		$this->obj_table->load_data_sql();

		
		
		//establish new form object
		$this->obj_form = New form_input;
		$this->obj_form->formname = "invoice-bulk-payments";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "accounts/ar/invoice-bulk-payments-process.php";
		$this->obj_form->method = "post";
		
		$highest_invoice_id = 0;
		
		//create form fields
		for ($i=0; $i < $this->obj_table->data_num_rows; $i++)
		{
			$structure = NULL;	
			$structure["fieldname"]			= "pay_invoice_" .$this->obj_table->data[$i]["id"];
			$structure["type"]			= "checkbox";
			$structure["options"]["nolabel"]	= "true";
			$this->obj_form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"]			= "checked_status_invoice_" .$this->obj_table->data[$i]["id"];
			$structure["type"]			= "hidden";
			$structure["defaultvalue"]		= "false";	
			$this->obj_form->add_input($structure);		
			
			$structure = NULL;
			$structure["fieldname"]			= "amount_invoice_" .$this->obj_table->data[$i]["id"];
			$structure["type"]			= "money";
			$this->obj_form->add_input($structure);
			
			if ($this->obj_table->data[$i]["id"] > $highest_invoice_id)
			{
				$highest_invoice_id = $this->obj_table->data[$i]["id"];
			}
		}
		
		
		$structure = NULL;
		$structure["fieldname"]			= "payment_date";
		$structure["type"]			= "date";
		$structure["defaultvalue"]		= date("Y-m-d");
		$structure["options"]["prelabel"]	= "Payment Date: ";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure = charts_form_prepare_acccountdropdown("chartid", "ar_payment");
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"]			= "highest_invoice_id";
		$structure["type"]			= "hidden";
		$structure["defaultvalue"]		= $highest_invoice_id;
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"]		= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Submit Payments";
		$this->obj_form->add_input($structure);		
		
		//load any error data
		$this->obj_form->load_data();
	}


	function render_html()
	{

		// heading
		print "<h3>BULK PAYMENTS ON ACCOUNTS RECEIVABLES INVOICES</h3><br><br>";

		// display options form
		$this->obj_table->render_options_form();

		// display table
		if (!count($this->obj_table->columns))
		{
			format_msgbox("important", "<p>Please select some valid options to display.</p>");
		}
		elseif (!$this->obj_table->data_num_rows)
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM account_ar LIMIT 1";
			$sql_obj->execute();
			
			if ($sql_obj->num_rows())
			{
				format_msgbox("important", "<p>Your current filter options do not match to any invoices.</p>");
			}
			else
			{
				format_msgbox("info", "<p>You currently have no AR invoices in your database.</p>");
			}
		}			
		else
		{
			print "<form enctype=\"multipart/form-data\" method=\"". $this->obj_form->method ."\" action=\"". $this->obj_form->action ."\" class=\"form_standard\">";
			
			// calculate all the totals and prepare processed values
			$this->obj_table->render_table_prepare();

			// display header row
			print "<table class=\"table_content\" cellspacing=\"0\" width=\"100%\">";			
			print "<tr>";
				foreach ($this->obj_table->columns as $column)
				{
					print "<td class=\"header\"><b>". $this->obj_table->render_columns[$column] ."</b></td>";
				}
			print "</tr>";
			
			// display data
			for ($i=0; $i < $this->obj_table->data_num_rows; $i++)
			{
				print "<tr>";
				foreach ($this->obj_table->columns as $columns)
				{
					print "<td valign=\"top\">";
						$content = $this->obj_table->data_render[$i][$columns];
						
						if ($columns == "pay")
						{
							$this->obj_form->render_field("pay_invoice_" .$this->obj_table->data[$i]["id"]);
							$this->obj_form->render_field("checked_status_invoice_" .$this->obj_table->data[$i]["id"]);
							print "&nbsp;&nbsp;";
							$this->obj_form->render_field("amount_invoice_" .$this->obj_table->data[$i]["id"]);
						}
						
						if ($this->obj_table->structure[$columns]["type"] == "bool_tick")
						{
							if ($content == "Y")
							{
								print "<img src=\"images/icons/tick_16.gif\" alt=\"Y\"></img>";
							}
							else
							{
								print "<img src=\"images/icons/cross_16.gif\" alt=\"N\"></img>";
							}
						}
						else
						{
							if ($this->obj_table->data_render[$i][$columns])
							{
								print $this->obj_table->data_render[$i][$columns];
							}
							else
							{
								print "&nbsp;";
							}
						}
					print "</td>";
				}
				print "</tr>";
			}
			print "</table>";
			print "<br />";

			//universal options and submit button
			print "<table  id=\"submit\" class=\"table_highlight_bubble\">";
			print "<tr>";
				print "<td>";
				print "<div id=\"submit_div\">";
					$this->obj_form->render_field("highest_invoice_id");
					print "Pay Into:&nbsp;";
					$this->obj_form->render_field("chartid");
					print "<br /><br />";
					$this->obj_form->render_field("payment_date");
					print "<br /><br />";
					$this->obj_form->render_field("submit");
					print "</div>";
				print "</td>";
			print "</tr>";
			print "</table>";
			print "</form>";
		}
	}
}

?>
