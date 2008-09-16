<?php
/*
	sql.php

	Provides abstracted SQL handling functions. At this stage all the functions are written to use MySQL, but
	could be expanded in the future to allow different database backends.
*/


class sql_query
{
	var $string;		// SQL statement to use

	var $query_result;	// query result
	
	var $data_num_rows;	// number of rows returned
	var $data;		// associate array of data returned


	/*
		execute()

		This function executes the SQL query and saves the result.

		Return codes:
		0	failure
		1	success
	*/
	function execute()
	{
		log_debug("sql_query", "Executing execute()");
		log_debug("sql_query", "SQL:". $this->string);
		
		if (!$this->query_result = mysql_query($this->string))
		{
			log_debug("sql_query", "Error: Problem executing SQL query - ". mysql_error());
			return 0;
		}
		else
		{
			return 1;
		}
	}


	/*
		num_rows()

		Returns the number of rows in the results and also saves into $this->data_num_rows.
	*/
	function num_rows()
	{
		log_debug("sql_query", "Executing num_rows()");

		if ($this->query_result)
		{
			$this->data_num_rows = mysql_num_rows($this->query_result);
			return $this->data_num_rows;
		}
		else
		{
			log_debug("sql_query", "Error: No DB result avaliable for use to fetch num row information.");
			return 0;
		}
	}
			

	/*
		fetch_array()

		Fetches the data from the DB into the $this->data variable.

		Return codes:
		0	failure
		1	success
	*/
	function fetch_array()
	{
		log_debug("sql_query", "Executing fetch_array()");
		
		if ($this->query_result)
		{
			while ($mysql_data = mysql_fetch_array($this->query_result))
			{
				$this->data[] = $mysql_data;
			}

			return 1;
		}
		else
		{
			log_debug("sql_query", "Error: No DB result avaliable for use to fetch data.");
			return 0;
		}
		
	}

} // end sql_query class


?>
