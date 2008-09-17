<?php
/*
	journal.php

	Provides classes & functions to process and render journal entries
*/


class journal_display
{
	var $journalname;		// name of the journal (used to fetch data from MySQL)
	var $language = "en_us";	// language to use for the labels
	
	var $sql_query;			// used to hold the SQL query


	/*
		prepare_sql_query();

		Prepares the SQL query
	*/
	function prepare_sql_query()
	{
		log_debug("journal_display", "Executing prepare_sql_query()");

		$this->sql_query = "SELECT timestamp, content FROM `journal` WHERE";


		// add WHERE queries
		if ($this->sql_structure["where"])
		{
			$this->sql_query .= "WHERE ";
		
			$num_values = count($this->sql_structure["where"]);
	
			for ($i=0; $i < $num_values; $i++)
			{
				$this->sql_query .= $this->sql_structure["where"][$i] . " ";

				if ($i < ($num_values - 1))
				{
					$this->sql_query .= "AND ";
				}
			}
		}


		
	}
	
	/*
		prepare_sql_addwhere($sqlquery)

		Add a WHERE statement.
	*/
	function prepare_sql_addwhere($sqlquery)
	{
		log_debug("table", "Executing prepare_sql_addwhere($sqlquery)");

		$this->sql_structure["where"][] = $sqlquery;
	}



	/*
		load_data();

		Reads in all the data using the SQL statement
	*/


	/*
		render_journal();

		Displays the full journal.
	*/


} // end journal_display


?>
