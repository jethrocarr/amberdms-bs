<?php
/*
	include/accounts/inc_taxes

	Provides functions and classes for working with taxes.
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

					Option #2 may be a bit inefficent on large queries, but at worst the user will most
					likely only be looking at between 1 to 12 months worth of invoices.

					Possibly some tests should be carried out in order to determine the optimal query method
					here.
				*/
			
				$sql_obj		= New sql_query;
				$sql_obj->string	= "SELECT SUM(amount) as amount FROM account_items WHERE type='tax' AND customid='". $this->taxid ."' AND invoicetype='". $this->type ."' AND invoiceid='". $this->obj_table->data[$i]["id"] ."'";
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





/*
	CLASS: tax

	Provides functions for managing taxes.
*/

class tax
{
	var $id;		// holds tax ID.
	var $data;		// holds values of record fields



	/*
		verify_id

		Checks that the provided ID is a valid tax

		Results
		0	Failure to find the ID
		1	Success - tax exists
	*/

	function verify_id()
	{
		log_debug("inc_taxes", "Executing verify_id()");

		if ($this->id)
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM `account_taxes` WHERE id='". $this->id ."' LIMIT 1";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				return 1;
			}
		}

		return 0;

	} // end of verify_id



	/*
		verify_name_tax

		Checks that the name_tax value supplied has not already been taken.

		Results
		0	Failure - name in use
		1	Success - name is available
	*/

	function verify_name_tax()
	{
		log_debug("inc_taxes", "Executing verify_name_tax()");

		$sql_obj			= New sql_query;
		$sql_obj->string		= "SELECT id FROM `account_taxes` WHERE name_tax='". $this->data["name_tax"] ."' ";

		if ($this->id)
			$sql_obj->string	.= " AND id!='". $this->id ."'";

		$sql_obj->string		.= " LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			return 0;
		}
		
		return 1;

	} // end of verify_name_tax



	/*
		verify_valid_chart

		Makes sure that the chartid for this tax is valid.

		Results
		0	Failure - invalid chart
		1	Success - acceptable chart
	*/

	function verify_valid_chart()
	{
		log_debug("inc_taxes", "Executing verify_valid_chart)");


		// make sure the selected chart exists
		$sql_obj = New sql_query;
		$sql_obj->string = "SELECT id FROM account_charts WHERE id='". $this->data["chartid"] ."' LIMIT 1";
		$sql_obj->execute();
	
		if ($sql_obj->num_rows())
		{
			return 1;
		}


		// failure
		return 0;

	} // end of verify_valid_chart



	/*
		check_delete_lock

		Checks if a tax is safe to delete or not.

		Results
		0	Unlocked
		1	Locked
	*/

	function check_delete_lock()
	{
		log_debug("inc_taxes", "Executing check_delete_lock()");


		// check if tax belongs to any invoices
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_items WHERE type='tax' AND customid='". $this->id ."'";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			return 1;
		}

		unset($sql_obj);


		// unlocked
		return 0;

	}  // end of check_delete_lock



	/*
		load_data

		Load the tax's information into the $this->data array.

		Returns
		0	failure
		1	success
	*/
	function load_data()
	{
		log_debug("inc_taxes", "Executing load_data()");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT * FROM account_taxes WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$sql_obj->fetch_array();

			$this->data = $sql_obj->data[0];

			return 1;
		}

		// failure
		return 0;

	} // end of load_data





	/*
		action_create

		Create a new tax based on the data in $this->data

		Results
		0	Failure
		#	Success - return ID
	*/
	function action_create()
	{
		log_debug("inc_taxes", "Executing action_create()");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "INSERT INTO `account_taxes` (name_tax) VALUES ('". $this->data["name_tax"]. "')";
		
		if (!$sql_obj->execute())
		{
			log_write("error", "inc_taxes", "Unexpected DB error whilst attempting to create a new tax");
			return 0;
		}

		$this->id = $sql_obj->fetch_insert_id();

		return $this->id;

	} // end of action_create




	/*
		action_update

		Update tax details based on data in $this->data. If no ID is provided,
		it will first call the action_create function.

		Returns
		0	failure
		#	success - returns the ID
	*/
	function action_update()
	{
		log_debug("inc_taxes", "Executing action_update()");


		// if no ID exists, create a new tax first
		if (!$this->id)
		{
			$mode = "create";

			if (!$this->action_create())
			{
				return 0;
			}
		}
		else
		{
			$mode = "update";
		}


		/*
			Update tax details
		*/
		$sql_obj		= New sql_query;
		$sql_obj->string	= "UPDATE `account_taxes` SET "
						."name_tax='". $this->data["name_tax"] ."', "
						."taxrate='". $this->data["taxrate"] ."', "
						."chartid='". $this->data["chartid"] ."', "
						."taxnumber='". $this->data["taxnumber"] ."', "
						."description='". $this->data["description"] ."' "
						."WHERE id='$this->id'";

		if (!$sql_obj->execute())
		{
			log_write("error", "inc_taxes", "Unexpected DB error attempting to update tax information");
			return 0;
		}

		unset($sql_obj);


		/*
			Create customer/vendor tax selection mappings if requested
		*/
		if ($this->data["autoenable_tax_customers"] == "on")
		{
			// loop through customers
			$sql_cust_obj			= New sql_query;
			$sql_cust_obj->string		= "SELECT id FROM customers";
			$sql_cust_obj->execute();

			if ($sql_cust_obj->num_rows())
			{
				$sql_cust_obj->fetch_array();

				foreach ($sql_cust_obj->data as $data_customer)
				{
					// insert tax assignment for this customer
					$sql_obj		= New sql_query;
					$sql_obj->string	= "INSERT INTO customers_taxes (customerid, taxid) VALUES ('". $data_customer["id"] ."', '". $this->id ."')";
					$sql_obj->execute();
				}
			}
		}

		if ($this->data["autoenable_tax_vendors"] == "on")
		{
			// loop through customers
			$sql_vendor_obj			= New sql_query;
			$sql_vendor_obj->string		= "SELECT id FROM vendors";
			$sql_vendor_obj->execute();

			if ($sql_vendor_obj->num_rows())
			{
				$sql_vendor_obj->fetch_array();

				foreach ($sql_vendor_obj->data as $data_vendor)
				{
					// insert tax assignment for this vendor
					$sql_obj		= New sql_query;
					$sql_obj->string	= "INSERT INTO vendors_taxes (vendorid, taxid) VALUES ('". $data_vendor["id"] ."', '". $this->id ."')";
					$sql_obj->execute();
				}
			}
		}



		// return notification
		if ($mode == "update")
		{
			log_write("notification", "inc_taxes", "Tax successfully updated.");
		}
		else
		{
			log_write("notification", "inc_taxes", "Tax successfully created.");
		}


		// success
		return $this->id;

	} // end of action_update



	/*
		action_delete

		Deletes a tax

		Note: the check_delete_lock function should be executed before calling this function to ensure database integrity.


		Results
		0	failure
		1	success
	*/
	function action_delete()
	{
		log_debug("inc_taxes", "Executing action_delete()");


		/*
			Delete Tax
		*/
			
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM account_taxes WHERE id='". $this->id ."'";
			
		if (!$sql_obj->execute())
		{
			log_write("error", "inc_taxes", "A fatal SQL error occured whilst trying to delete the tax");
			return 0;
		}


		/*
			Delete tax from any products it is assigned to
		*/

		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM products_taxes WHERE taxid='". $this->id ."'";
		$sql_obj->execute();



		/*
			Delete tax from any vendors it is assigned to
		*/

		// delete mapping from table
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM vendors_taxes WHERE taxid='". $this->id ."'";
		$sql_obj->execute();

		// unset any defaulttax usage
		$sql_obj		= New sql_query;
		$sql_obj->string	= "UPDATE vendors SET tax_default='0' WHERE tax_default='". $this->id ."'";
		$sql_obj->execute();



		/*
			Delete tax from any customers it is assigned to
		*/

		// delete mapping from table
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM customers_taxes WHERE taxid='". $this->id ."'";
		$sql_obj->execute();

		// unset any defaulttax usage
		$sql_obj		= New sql_query;
		$sql_obj->string	= "UPDATE customers SET tax_default='0' WHERE tax_default='". $this->id ."'";
		$sql_obj->execute();


		log_write("notification", "inc_taxes", "Tax has been successfully deleted.");

		return 1;
	}


} // end of class:tax



?>
