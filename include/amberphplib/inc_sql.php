<?php
/*
	sql.php

	Provides abstracted SQL handling functions. At this stage all the functions are written to use MySQL, but
	could be expanded in the future to allow different database backends.
*/


class sql_query
{
	var $structure;		// structure avaliable to be used to build SQL queries

	/*
		Structure:
		["tablename"]			SQL table to fetch data from
		["fields"]			Array of fieldnames to perform SELECT on
		["fields_dbnames"][$fieldname]	Settting this variable will cause generate_sql to do a rename during the
						query (eg: SELECT ["fields_dbname"][$fieldname] as $fieldname)
		["joins"]			Array of SQL string to execute as JOIN queries
		["where"]			Array of SQL strings to execute as WHERE queries (joined by AND)
		["groupby"]			Array of fieldname to group by
		["orderby"]			Array of fieldnames to order by
	*/
	
	var $string;		// SQL statement to use

	var $query_result;	// query result
	
	var $data_num_rows;	// number of rows returned
	var $data;		// associate array of data returned



	/*
		BASIC QUERY COMMANDS
	*/


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

		if (strlen($this->string) < 1000)
		{
			log_write("sql", "sql_query", $this->string);
		}
		else
		{
			log_write("sql", "sql_query", "SQL query too long to display for debug.");
		}
		
		if (!$this->query_result = mysql_query($this->string))
		{
			log_debug("sql", "sql_query", "Error: Problem executing SQL query - ". mysql_error());
			return 0;
		}
		else
		{
			return 1;
		}
	}


	/*
		fetch_insert_id()

		Fetches the ID of the last insert statement.

		Return codes:
		0	failure
		#	ID of last insert statement
	*/
	function fetch_insert_id()
	{
		log_debug("sql_query", "Executing fetch_insertid()");

		$id = mysql_insert_id();

		if ($id)
		{
			return $id;
		}
		
		return 0;
	}


	/*
		num_rows()

		Returns the number of rows in the results and also saves into $this->data_num_rows.
	*/
	function num_rows()
	{
		log_debug("sql_query", "Executing num_rows()");

		if ($this->data_num_rows)
		{
			// have already got the num of rows
			return $this->data_num_rows;
		}
		else
		{
			// fetch the number of rows
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




	/*
		SMART SQL QUERY PREPERATION + GENERATION FUNCTIONS
	*/


	/*
		generate_sql()

		This function generates a SQL query based on the structure defined in $this->structure
		and then saves it to $this->string for use.

	*/
	function generate_sql()
	{
		log_debug("sql_query", "Executing generate_sql()");

		$this->string = "SELECT ";


		// add all select fields
		$num_values = count($this->sql_structure["fields"]);

		for ($i=0; $i < $num_values; $i++)
		{
			$fieldname = $this->sql_structure["fields"][$i];

			if ($this->sql_structure["field_dbnames"][$fieldname])
			{
				$this->string .= $this->sql_structure["field_dbnames"][$fieldname] ." as ";
			}
			
			$this->string .= $fieldname;
			

			if ($i < ($num_values - 1))
			{
				$this->string .= ", ";
			}
		}

		$this->string .= " ";


		// add database query
		$this->string .= "FROM `". $this->sql_structure["tablename"] ."` ";

		// add all joins
		if ($this->sql_structure["joins"])
		{
			foreach ($this->sql_structure["joins"] as $sql_join)
			{
				$this->string .= $sql_join ." ";
			}
		}


		// add WHERE queries
		if ($this->sql_structure["where"])
		{
			$this->string .= "WHERE ";
		
			$num_values = count($this->sql_structure["where"]);
	
			for ($i=0; $i < $num_values; $i++)
			{
				$this->string .= $this->sql_structure["where"][$i] . " ";

				if ($i < ($num_values - 1))
				{
					$this->string .= "AND ";
				}
			}
		}

		// add groupby rules
		if ($this->sql_structure["groupby"])
		{
			$this->string .= "GROUP BY ";
			
			$num_values = count($this->sql_structure["groupby"]);
	
			for ($i=0; $i < $num_values; $i++)
			{
				$this->string .= $this->sql_structure["groupby"][$i] . " ";

				if ($i < ($num_values - 1))
				{
					$this->string .= ", ";
				}
			}
		}


		// add orderby rules
		if ($this->sql_structure["orderby"])
		{
			$this->string .= "ORDER BY ";
		
		
			// run through all the order by fields
			$num_values = count($this->sql_structure["orderby"]);

			for ($i=0; $i < $num_values; $i++)
			{
				// fieldname
				$this->string .= $this->sql_structure["orderby"][$i]["fieldname"];
			
				// sort method
				if ($this->sql_structure["orderby"][$i]["type"] == "asc")
				{
					$this->string .= " ASC ";
				}
				else
				{
					$this->string .= " DESC ";
				}

				// add joiner
				if ($i < ($num_values - 1))
				{
					$this->string .= ", ";
				}
			}
		}
		
		return 1;
	}


	/*
		prepare_sql_settable($tablename)

		Sets the table name to fetch the data from
	*/
	function prepare_sql_settable($tablename)
	{
		log_debug("sql_query", "Executing prepare_settable($tablename)");

		$this->sql_structure["tablename"] = $tablename;
	}
	
	

	/*
		prepare_sql_addfield($fieldname, $dbname)

		Adds a select field to the database
	*/
	function prepare_sql_addfield($fieldname, $dbname)
	{
		log_debug("sql_query", "Executing prepare_sql_addfield($fieldname, $dbname)");
		
		if ($dbname)
		{
			$this->sql_structure["field_dbnames"][$fieldname] = $dbname;
		}
		
		$this->sql_structure["fields"][] = "$fieldname";
	}

	/*
		prepare_sql_addjoin($joinquery)

		Add join queries to the SQL statement.
	*/
	function prepare_sql_addjoin($joinquery)
	{
		log_debug("sql_query", "Executing prepare_sql_addjoin($joinquery)");

		$this->sql_structure["joins"][] = $joinquery;
	}


	/*
		prepare_sql_addwhere($sqlquery)

		Add a WHERE statement.
	*/
	function prepare_sql_addwhere($sqlquery)
	{
		log_debug("sql_query", "Executing prepare_sql_addwhere($sqlquery)");

		$this->sql_structure["where"][] = $sqlquery;
	}

	/*
		prepare_sql_addorderby($fieldname)
	
		Add a field to the orderby statement
	*/
	function prepare_sql_addorderby($fieldname)
	{
		log_debug("sql_query", "Executing prepare_sql_addorderby($fieldname)");

		$this->prepare_sql_addorderby_asc($fieldname);
	}

	/*
		prepare_sql_addorderby_asc($fieldname)
	
		Add a field to the orderby statement
	*/
	function prepare_sql_addorderby_asc($fieldname)
	{
		log_debug("sql_query", "Executing prepare_sql_addorderby_asc($fieldname)");

		// work out what number to use for this orderby rule
		// (it is important that we choose a series of numbers in order so that the orderby rules
		//  are created correctly)
		$i = count($this->sql_structure["orderby"]);

		$this->sql_structure["orderby"][$i]["fieldname"]	= $fieldname;
		$this->sql_structure["orderby"][$i]["type"]		= "asc";
	}

	/*
		prepare_sql_addorderby_desc($fieldname)
	
		Add a field to the orderby statement in descending sort
	*/
	function prepare_sql_addorderby_desc($fieldname)
	{
		log_debug("sql_query", "Executing prepare_sql_addorderby_desc($fieldname)");

		// work out what number to use for this orderby rule
		// (it is important that we choose a series of numbers in order so that the orderby rules
		//  are created correctly)
		$i = count($this->sql_structure["orderby"]);

		$this->sql_structure["orderby"][$i]["fieldname"]	= $fieldname;
		$this->sql_structure["orderby"][$i]["type"]		= "desc";
	}


	/*
		prepare_sql_addgroupby($fieldname)
	
		Add a field to the groupby statement
	*/
	function prepare_sql_addgroupby($sqlquery)
	{
		log_debug("sql_query", "Executing prepare_sql_addgroupby($sqlquery)");

		$this->sql_structure["groupby"][] = $sqlquery;
	}





} // end sql_query class


/*
	STANDALONE FUNCTIONS
*/


/*
	sql_get_singlevalue($string)

	Fetches a single value from the database and returns it.
	(Very handy for fetching configuration values from the database)

	Note: The value returned must have the label "value". You may need to make
	the SQL statment re-write the value name in order to comply.

	Return codes:
	0	failure
	?	data desired
*/
function sql_get_singlevalue($string)
{
	log_debug("sql", "Executing sql_get_singlevalue(SQL query)");

	// so many bugs are caused by forgetting to request fields from the DB as "value", so
	// this function has been added.
	if (!strstr($string, 'value'))
	{
		die("Error: SQL queries to sql_get_singlevalue must request the field with the name of \"value\". Eg: \"SELECT name as value FROM mytable WHERE id=foo\"");
	}

	// fetch and return data
	$sql_obj		= New sql_query;
	$sql_obj->string	= $string;
	$sql_obj->execute();

	if (!$sql_obj->num_rows())
	{
		return 0;
	}
	else
	{
		$sql_obj->fetch_array();
		return $sql_obj->data[0]["value"];
	}
}




?>
