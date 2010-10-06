<?php
/*
	accounts/ar/account-statements.php
	
	access: accounts_ar_write (send email)
		accounts_ar_view (view overdues)
	
	Lists all invoices in order of due dates and allows user to send reminders en masse
*/
		
require("include/accounts/inc_charts.php");

class page_output
{
	var $obj_table;
	var $obj_form;
	
	function page_output()
	{
		// requirements
		$this->requires["css"][]		= "include/accounts/css/account-statements.css";
	}

	function check_permissions()
	{
		return user_permissions_get('accounts_ar_view');
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
		$this->obj_table->tablename	= "account-statements";

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
		$this->obj_table->add_column("date", "date_sent", "account_ar.date_sent");
		$this->obj_table->add_column("bool_tick", "sent", "account_ar.sentmethod");
		$this->obj_table->add_column("standard", "days_overdue", "DATEDIFF(CURDATE(), account_ar.date_due)");
		$this->obj_table->add_column("standard", "send_reminder", "NONE");
		
		// defaults
		$this->obj_table->columns		= array("code_invoice", "name_customer", "amount_total", "date_due", "date_sent", "days_overdue", "send_reminder");
		$this->obj_table->columns_order		= array("date_due");
		$this->obj_table->columns_order_options	= array("code_invoice", "name_customer", "amount_total", "date_due", "date_sent", "days_overdue", "send_reminder"); 

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("account_ar");
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "account_ar.id");
		$this->obj_table->sql_obj->prepare_sql_addfield("since_sent", "DATEDIFF(CURDATE(), account_ar.date_sent)");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN customers ON customers.id = account_ar.customerid");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN staff ON staff.id = account_ar.employeeid");
		$this->obj_table->sql_obj->prepare_sql_addwhere("account_ar.amount_total > account_ar.amount_paid");

		// acceptable filter options
		$structure = NULL;
		$structure["fieldname"] = "days_overdue";
		$structure["type"]	= "input";
		$structure["sql"]	= "DATEDIFF(CURDATE(), account_ar.date_due) >= 'value'";
		$structure["options"]["width"]	= 30;
		$this->obj_table->add_filter($structure);
		
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


		// load options
		$this->obj_table->load_options_form();


		// fetch all the chart information
		$this->obj_table->generate_sql();
		$this->obj_table->load_data_sql();

		
		//establish new form object
		$this->obj_form = New form_input;
		$this->obj_form->formname = "account-statements";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "accounts/ar/account-statements-process.php";
		$this->obj_form->method = "post";

		
		//create form fields
		for ($i=0; $i < $this->obj_table->data_num_rows; $i++)
		{
			$structure = NULL;	
			$structure["fieldname"]			= "send_reminder_$i";
			$structure["type"]			= "checkbox";
			$structure["options"]["nolabel"]	= "true";
			$this->obj_form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"]			= "invoice_id_$i";
			$structure["type"]			= "hidden";
			$structure["defaultvalue"]		= $this->obj_table->data[$i]["id"];
			$this->obj_form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"]			= "days_overdue_$i";
			$structure["type"]			= "hidden";
			$structure["defaultvalue"]		= $this->obj_table->data[$i]["days_overdue"];
			$this->obj_form->add_input($structure);
		}
		
		$structure = NULL;
		$structure["fieldname"]		= "num_records";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->obj_table->data_num_rows;
		$this->obj_form->add_input($structure);	
		
		$structure = NULL;
		$structure["fieldname"]		= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Send Reminders";
		$this->obj_form->add_input($structure);		
		
		//load any error data
		$this->obj_form->load_data();
	}


	function render_html()
	{
		// heading
		print "<h3>OVERDUE AR INVOICES</h3><br><br>";
		print "<p>This page shows all overdue AR invoices and allows you to send reminder e-mails.</p><br />";

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
				format_msgbox("info", "<p>You currently have no unpaid AR invoices in your database.</p>");
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
					//send reminder tick box
					if ($columns == "send_reminder")
					{
						print "<td valign=\"top\" class=\"send_reminder_cell\">";
						$this->obj_form->render_field("send_reminder_$i");
						$this->obj_form->render_field("invoice_id_$i");
						$this->obj_form->render_field("days_overdue_$i");
					}
					
					//due date
					else if ($columns == "days_overdue")
					{
						if ($this->obj_table->data[$i]["days_overdue"] >= 90)
						{
							print "<td valign=\"top\" class=\"overdue_90\">";
						}
						else if ($this->obj_table->data[$i]["days_overdue"] >= 60)
						{
							print "<td valign=\"top\" class=\"overdue_60\">";
						}
						else if ($this->obj_table->data[$i]["days_overdue"] >= 30)
						{
							print "<td valign=\"top\" class=\"overdue_30\">";
						}
						else
						{
							print "<td valign=\"top\" class=\"not_overdue\">";
						}
						print $this->obj_table->data_render[$i][$columns];
					}
					
					//sent date
					else if ($columns == "date_sent")
					{
						if ($this->obj_table->data[$i]["since_sent"] == "" && $this->obj_table->data[$i]["days_overdue"] >= 30)
						{
							print "<td valign=\"top\" class=\"last_sent_30\">";
						}						
						else if ($this->obj_table->data[$i]["since_sent"] >= 30 && $this->obj_table->data[$i]["days_overdue"] >= 30)
						{
							print "<td valign=\"top\" class=\"last_sent_30\">";
						}
						else 
						{
							print "<td valign=\"top\" class=\"last_sent_recent\">";
						}
						print $this->obj_table->data_render[$i][$columns];
					}

					//boolean (sent)
					else if ($this->obj_table->structure[$columns]["type"] == "bool_tick")
					{
						print "<td valign=\"top\">";
						if ($this->obj_table->data_render[$i][$columns] == "Y")
						{
							print "<img src=\"images/icons/tick_16.gif\" alt=\"Y\"></img>";
						}
						else
						{
							print "<img src=\"images/icons/cross_16.gif\" alt=\"N\"></img>";
						}
					}
					
					//all others
					else
					{
						print "<td valign=\"top\">";
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
			$this->obj_form->render_field("num_records");
			print "<br />";

			print "<div id=\"submit_button\">";
				$this->obj_form->render_field("submit");
			print "</div>";
			print "</form>";
		}
	}
}

?>
