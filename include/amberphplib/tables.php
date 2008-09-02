<?php
/*
	tables.php

	Provides classes/functions used for generating all the tables and forms
	used.

	Some of the handy features it provides:
	* Ability to select/unselect columns to display on tables
	* Lookups of column names against word database allows for different language translations.
	* CSV export function

	Class provided is "table"

*/

class table
{
	var $language = "en_us";	// language to use for the form labels.
	
	var $columns;			// array containing all columns to be displayed
	var $columns_order;		// array containing columns to order by
	
	var $data;			// table content
	var $data_num_rows;		// number of rows

	var $sql_table;			// SQL table to get the data from
	var $sql_query;			// SQL query used

	var $render_columns;		// human readable column names


	/*
		generate_sql()

		This function generates the SQL query to be used for generating the table.
	*/
	function generate_sql()
	{
		// prepare the select statement
		$this->sql_query = "SELECT ";
		
		foreach ($this->columns as $column)
		{
			$this->sql_query .= "$column, ";
		}

		$this->sql_query .= "id FROM `". $this->sql_table ."` ORDER BY ";


		// add the order statements - make sure we don't add an extra comma on the end
		$count = 0;
		foreach ($this->columns_order as $column_order)
		{
			$count++;
			
			if ($count < count($this->columns_order))
			{
				$this->sql_query .= $column_order .", ";
			}
			else
			{
				$this->sql_query .= $column_order;
			}
		}
		
		$this->sql_query .= " DESC";

		return 1;
	}


	/*
		generate_data()

		This function executes the SQL statement and fetches all the data from
		MySQL into an associate array.

		This data can then be used directly to generate the table, or can be
		modified by other code to produce the desired result before creating
		the final output.

		Returns the number of rows found.
	*/
	function generate_data()
	{
		$mysql_result		= mysql_query($this->sql_query);
		$mysql_num_rows		= mysql_num_rows($mysql_result);
		$this->data_num_rows	= $mysql_num_rows;

		if (!$mysql_num_rows)
		{
			return 0;
		}
		else
		{
			while ($mysql_data = mysql_fetch_array($mysql_result))
			{
				$this->data[] = $mysql_data;
			}

			return $mysql_num_rows;
		}
	}


	/*
		render_column_names($language)

		This function looks up the human-translation of the column names and returns the results.

		Defaults to US english (en_us) if no language is specified.
	*/
	function render_column_names()
	{
		$this->render_columns = language_translate($this->language, $this->columns);
		return 1;
	}	



} // end of table class



?>
