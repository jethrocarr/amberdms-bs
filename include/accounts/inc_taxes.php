<?php
/*
	include/accounts/inc_taxes

	Proves functions for working with taxes, in particular it provides
	a function for generating tax reports.
*/



/*
	class: taxes_report_transactions

	Displays a table showing all the tax collected (AR) or paid (AP).
*/

class taxes_report_transactions
{
	var $taxid;		// ID of the tax to display
	var $mode;		// "collected" or "paid"
	
	var $type;

	var $obj_table;


	function execute()
	{
		log_debug("taxes_report_transactions", "Executing execute()");

	
		if ($this->mode == "collected")
		{
			$this->type = "ar";
		}
		elseif ($this->mode == "paid")
		{
			$this->type = "ap";
		}
		else
		{
			return 0;
		}


		/*
			Define table structure
		*/
		
		$this->obj_table = New table;

		// configure the table
		$this->obj_table->language	= $_SESSION["user"]["lang"];
		$this->obj_table->tablename	= "tax_report_". $this->type;

		// define all the columns and structure
		$this->obj_table->add_column("date", "date_trans", "account_". $this->type .".date_trans");
		
		$this->obj_table->add_column("standard", "code_invoice", "account_". $this->type .".code_invoice");

		if ($this->type == "ap")
		{
			$this->obj_table->add_column("standard", "name_vendor", "vendors.name_vendor");
		}
		else
		{
			$this->obj_table->add_column("standard", "name_customer", "customers.name_customer");
		}
			
		$this->obj_table->add_column("money", "amount", "account_". $this->type .".amount");
		$this->obj_table->add_column("money", "amount_tax", "NONE");


		// total rows
		$this->obj_table->total_columns		= array("amount", "amount_tax",);
		$this->obj_table->total_rows		= array("amount", "amount_tax");

		// defaults
		if ($this->type == "ap")
		{
			$this->obj_table->columns		= array("date_trans", "code_invoice", "name_vendor", "amount", "amount_tax");
			$this->obj_table->columns_order		= array("date_trans", "name_vendor");
		}
		else
		{
			$this->obj_table->columns		= array("date_trans", "code_invoice", "name_customer", "amount", "amount_tax");
			$this->obj_table->columns_order		= array("date_trans", "name_customer");
		}

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("account_". $this->type);

		if ($this->type == "ap")
		{
			$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN vendors ON account_". $this->type .".vendorid = vendors.id");
		}
		else
		{
			$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN customers ON account_". $this->type .".customerid = customers.id");
		}
		
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "account_". $this->type .".id");



		/*
			Filter Options
		*/

		// acceptable filter options
		$this->obj_table->add_fixed_option("id", $this->taxid);
			
		$structure = NULL;
		$structure["fieldname"] = "date_start";
		$structure["type"]	= "date";
		$this->obj_table->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] = "date_end";
		$structure["type"]	= "date";
		$this->obj_table->add_filter($structure);
		
		$structure = NULL;
		$structure["fieldname"] = "mode";
		$structure["type"]	= "radio";
		$structure["values"]	= array("Accrual/Invoice", "Cash");
		$this->obj_table->add_filter($structure);


		// load options
		$this->obj_table->load_options_form();



		/*
			Create SQL filters from user-selected options

			These filters are too complex to perform using the standard SQL based filtering
			of the tables class proved by amberphplib, so we have to use this code
			to manipulate the class data structure directly
		*/

		// depending on the filter options, generate SQL filtering rules
		if ($this->obj_table->filter["filter_mode"]["defaultvalue"] == "Cash")
		{
			// cash mode


			// select all invoices in the desired time period
			$this->obj_table->sql_obj->prepare_sql_addwhere("date_trans >= '". $this->obj_table->filter["filter_date_start"]["defaultvalue"] ."'");
			$this->obj_table->sql_obj->prepare_sql_addwhere("date_trans <= '". $this->obj_table->filter["filter_date_end"]["defaultvalue"] ."'");

			// limit invoice selection to only fully paid invoices
			$this->obj_table->sql_obj->prepare_sql_addwhere("amount_total=amount_paid");
		}
		else
		{
			// invoice mode
			
			// select all invoices in the desired time period
			$this->obj_table->sql_obj->prepare_sql_addwhere("date_trans >= '". $this->obj_table->filter["filter_date_start"]["defaultvalue"] ."'");
			$this->obj_table->sql_obj->prepare_sql_addwhere("date_trans <= '". $this->obj_table->filter["filter_date_end"]["defaultvalue"] ."'");
		}



		// execute SQL and load data
		$this->obj_table->generate_sql();
		$this->obj_table->load_data_sql();



		/*
			Generate tax totals per invoice

			Note that the account_$this->type.total_tax may include the amount of other taxes,
			so we need to total up the tax ourselves and work out the sum.
		*/


		if ($this->obj_table->data_num_rows)
		{

			$deleted_invoices = 0;

			for ($i=0; $i < $this->obj_table->data_num_rows; $i++)
			{
				/*
					TODO

					There are two approaches to solving this problem:
					
					1. Fetch totals for the selected tax type for all invoices into
					   an array, and then pull the data we want from that
					   
					2. Fetch total for each invoice by using a seporate sql query. This is
					   the approach chosen here.

					Option #1 will be more efficent initally, but could cause huge slowdowns once users
					end up with large databases of many/complex invoices.

					Option #2 may be a bit inefficent on large queries, but at worst the user will only
					be looking at between 1 to 12 months worth of invoices.

					Possibly some tests should be carried out in order to determine the optimal query method
					here.
				*/
			
				$sql_obj		= New sql_query;
				$sql_obj->string	= "SELECT SUM(amount) as amount FROM account_items WHERE type='tax' AND customid='". $this->taxid ."' AND invoiceid='". $this->obj_table->data[$i]["id"] ."'";
				$sql_obj->execute();
				$sql_obj->fetch_array();

				if (!$sql_obj->data[0]["amount"])
				{
					// delete this invoice from the list, since it has no tax items of the type that we want
					unset($this->obj_table->data[$i]);
					$deleted_invoices++;
				}
				else
				{
					
					// add the amount to the data
					$this->obj_table->data[$i]["amount_tax"] = $sql_obj->data[0]["amount"];
				}
			}

			// re-index the data results to fix any holes created
			// by deleted invoices
			$this->obj_table->data		= array_values($this->obj_table->data);
			$this->obj_table->data_num_rows	= $this->obj_table->data_num_rows - $deleted_invoices;

		}

		return 1;
	}


	function render_html()
	{
		log_debug("taxes_report_transactions", "Executing render_html()");

		// display options form
		$this->obj_table->render_options_form();

		// Display Table
		// Note that the render_table_html function also performs the total row and total column generation tasks.
		if (!$this->obj_table->filter["filter_date_start"]["defaultvalue"] || !$this->obj_table->filter["filter_date_end"]["defaultvalue"])
		{
			format_msgbox("important", "<p><b>Please select a time period to display using the filter options above.</b></p>");
			return 0;
		}
		else
		{
			$this->obj_table->render_table_html();
		}

		return 1;
	}


	function render_csv()
	{
		$this->obj_table->render_table_csv();
	}

	
} // end of taxes_report_transactions



?>
