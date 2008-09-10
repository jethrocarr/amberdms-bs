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
	var $tablename;			// name of the table - used for internal purposes, not displayed
	var $language = "en_us";	// language to use for the form labels.
	
	var $columns;			// array containing the list of all the columns to display
	var $columns_order;		// array containing columns to order by

	var $total_columns;		// array of columns to create totals for
	var $total_rows;		// array of columns to create per-row totals for

	var $links;			// array of links to place in a final column

	var $structure;			// contains the structure of all the defined columns.

	var $data;			// table content
	var $data_num_rows;		// number of rows

	var $sql_table;			// SQL table to get the data from
	var $sql_query;			// SQL query used

	var $render_columns;		// human readable column names


	/*
		add_column($type, $name, $dbname)
	
		Defines the column structure.
		type	- A known type of column
				standard
				date		- YYYY-MM-DD format date field
				fullname	- there are two fields in the DB that need to be merged to make a full name)
				price		- displays a price field correctly
				hourmins	- input is a number of seconds, display as H:MM
				
		name	- name/label of the column for display purposes

		dbname	- name of the field in the DB or session data to use for the input data
	*/
	function add_column($type, $name, $dbname)
	{
		if (!$dbname)
		{
			$dbname = $name;
		}
		
		$this->structure[$name]["type"]		= $type;
		$this->structure[$name]["dbname"]	= $dbname;
		
	}


	/*
		add_link($name, $page, $options_array)

		Adds a new link to the links array, with "name" becomming the link after undergoing
		translation. Note that $page is equal to the page to display, you don't need to define
		"index.php?page=" or anything.
		
		$options_array is used to specifiy get values, and has the following structure:
		$options_array["get_field_name"]["value"]	= "value";
		$options_array["get_field_name"]["column"]	= "columnname";

		If the value option is specified, a GET field will be added with the specified value,
		otherwise if the column option is

		
	*/
	function add_link($name, $page, $options_array)
	{
		$this->links[$name]["page"]	= $page;
		$this->links[$name]["options"]	= $options_array;
	}

	

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
			$dbname = $this->structure[$column]["dbname"];
			
			/*
				See the add_column function for comments about
				the different possible types.
			*/
			if ($this->structure[$column]["type"] == "fullname")
			{
				$this->sql_query .= $dbname ."_firstname, ";
				$this->sql_query .= $dbname ."_lastname, ";
			}
			else
			{
				// default, standard query
				$this->sql_query .= "$dbname, ";
			}
		}

		$this->sql_query .= "id FROM `". $this->sql_table ."` ";
		
		
		
		if ($this->columns_order)
		{
			$this->sql_query .= "ORDER BY ";

			// add the order statements - make sure we don't add an extra comma on the end
			$count = 0;
			foreach ($this->columns_order as $column_order)
			{
				$count++;
				$dbname = $this->structure[$column_order]["dbname"];

				/*
						See the add_column function for comments about
						the different possible types.
				*/
				if ($this->structure[$column_order]["type"] == "fullname")
				{
					$this->sql_query .= $dbname ."_firstname, ";
					$this->sql_query .= $dbname ."_lastname";
				}
				else
				{
					$this->sql_query .= $dbname;
				}


				// add seporator if required
				if ($count < count($this->columns_order))
				{
					$this->sql_query .= ", ";
				}
			}
			
			$this->sql_query .= " ASC";
		}
		

		return 1;
	}


	/*
		load_data_sql()
		
		This function executes the SQL statement and fetches all the data from
		MySQL into an associate array.

		This data can then be used directly to generate the table, or can be
		modified by other code to produce the desired result before creating
		the final output.

		Returns the number of rows found.
	*/
	function load_data_sql()
	{
		if (!$mysql_result = mysql_query($this->sql_query))
		{
			print "<p><b>A fatal SQL error occured: ". mysql_error() ."</b></p>";
			return 0;
		}
		
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
				$tmparray = array();
				
				foreach (array_keys($this->structure) as $structurecol)
				{
					/*
						See the add_column function for comments about
						the different possible types.
					*/
					switch ($this->structure[$structurecol]["type"])
					{
						case "fullname":
							$tmparray[$structurecol] = $mysql_data[ $this->structure[$structurecol]["dbname"]."_firstname" ] ." ". $mysql_data[$this->structure[$structurecol]["dbname"]."_lastname" ];
						break;
							
						default:
							// standard DB type
							$tmparray[$structurecol] = $mysql_data[$this->structure[$structurecol]["dbname"]];
						break;
					}
				}

				// add the id field
				$tmparray["id"] = $mysql_data["id"];
			
				// save data to final results
				$this->data[] = $tmparray;
			}

			return $mysql_num_rows;
		}
	}


	/*
		load_options_form()

		Imports data from POST or SESSION which matches this form to be used for the options.
	*/
	function load_options_form()
	{
		/*
			Form options can be passed in two ways:
			1. POST - this occurs when the options have been passed at the last reload
			2. SESSION - if the user goes away and returns.

		*/
		if ($_GET["table_display_options"])
		{
			$this->columns		= array();
			$this->columns_order	= array();

			// load checkboxes
			foreach (array_keys($this->structure) as $column)
			{
				$column_setting = security_script_input("/^[a-z]*$/", $_GET[$column]);
				
				if ($column_setting == "on")
				{
					$this->columns[] = $column;
				}
			}

			// load orderby options
			$num_cols = count(array_keys($this->structure));
			for ($i=0; $i < $num_cols; $i++)
			{
				if ($_GET["order_$i"])
				{
					$this->columns_order[] = security_script_input("/^\S*$/", $_GET["order_$i"]);
				}
			}
		}
		elseif ($_SESSION["form"][$this->tablename]["columns"])
		{
			// load checkboxes
			$this->columns		= $_SESSION["form"][$this->tablename]["columns"];
			$this->columns_order	= $_SESSION["form"][$this->tablename]["columns_order"];
		}

		// save options to session data
		$_SESSION["form"][$this->tablename]["columns"]		= $this->columns;
		$_SESSION["form"][$this->tablename]["columns_order"]	= $this->columns_order;

		return 1;
	}


	/*
		render_column_names($language)

		This function looks up the human-translation of the column names and saves it into $this->render_columns

		Defaults to US english (en_us) if no language is specified.
	*/
	function render_column_names()
	{
		$this->render_columns = language_translate($this->language, $this->columns);
		return 1;
	}


	/*
		render_field($column, $row)

		This function correctly formats/processes values based on their type, and then returns them.
	*/
	function render_field($column, $row)
	{
		/*
			See the add_column function for comments about
			the different possible types.
		*/
		switch ($this->structure[$column]["type"])
		{
			case "date":
				if ($this->data[$row][$column] == "0000-00-00" || $this->data[$row][$column] == 0)
				{
					// no date in this field, add filler
					$result = "---";
				}
				else
				{
					$result = $this->data[$row][$column];
				}
			break;

			case "price":
				// TODO: in future, have currency field here

				// for now, just add a $ symbol to the field.
				$tmparray[$structurecol] = "$". $this->data[$row][$column];
			break;

			case "hourmins":
				// value is a number of seconds, we need to convert into an H:MM format.
				$result = time_format_hourmins($this->data[$row][$column]);
			break;

			default:
				$result = $this->data[$row][$column];
			break;
			
		} // end of switch


		return $result;
	}



	/*
		render_options_form()
		
		Displays a list of all the avaliable columns for the user to select from.
	*/
	function render_options_form()
	{	
		// create tmp array to prevent excessive use of array_keys
		$columns_available = array_keys($this->structure);
		
		// get labels for all the columns
		$labels = language_translate($this->language, $columns_available);


		// start the form
		print "<form method=\"get\" class=\"form_standard\">";
		
		$form = New form_input;
		$form->formname = $this->tablename;
		$form->language = $this->language;

		// include page name
		$structure = NULL;
		$structure["fieldname"] 	= "page";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $_GET["page"];
		$form->add_input($structure);
		$form->render_field("page");


		// flag this form as the table_display_options form
		$structure = NULL;
		$structure["fieldname"] 	= "table_display_options";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->tablename;
		$form->add_input($structure);
		$form->render_field("table_display_options");



		// configure all the checkboxes
		$num_cols	= count($columns_available);
		$num_cols_half	= sprintf("%d", $num_cols / 2);
		
		for ($i=0; $i < $num_cols; $i++)
		{
			$column = $columns_available[$i];
			
			// define the checkbox
			$structure = NULL;
			$structure["fieldname"]		= $column;
			$structure["type"]		= "checkbox";
			
			if (in_array($column, $this->columns))
				$structure["defaultvalue"] = "on";
				
			$form->add_input($structure);

			// split the column options boxes into two different columns
			if ($i < $num_cols_half)
			{
				$column_a1[] = $column;
			}
			else
			{
				$column_a2[] = $column;
			}
			
		}
		

		// structure table
		print "<table width=\"100%\" style=\"border: 1px solid #000000;\"><tr>";
	
		print "<td width=\"25%\" valign=\"top\">";
		
			print "<b>Fields to display:</b><br><br>";

			// display the checkbox(s)
			foreach ($column_a1 as $column)
			{
				$form->render_field($column);
			}

		print "</td>";

		print "<td width=\"25%\" valign=\"top\">";
		
			print "<br><br>";
			
			// display the checkbox(s)
			foreach ($column_a2 as $column)
			{
				$form->render_field($column);
			}

		print "</td>";

		
		print "<td width=\"25%\" valign=\"top\"></td>";
		

		
		print "<td width=\"25%\" valign=\"top\">";

			print "<b>Order By (in the following order):</b><br><br>";

			// limit the number of order boxes to 4
			$num_cols = count($columns_available);

			if ($num_cols > 4)
				$num_cols = 4;

			
			for ($i=0; $i < $num_cols; $i++)
			{
				// define dropdown
				$structure = NULL;
				$structure["fieldname"]		= "order_$i";
				$structure["type"]		= "dropdown";
				
				if ($this->columns_order[$i])
					$structure["defaultvalue"] = $this->columns_order[$i];

				$structure["values"] = $columns_available;

				$form->add_input($structure);

				// display drop down
				$form->render_field($structure["fieldname"]);
				print "<br>";
			}
			
		print "</td>";

		
		// end of structure table
		print "</tr></table>";
	
		$structure = NULL;
		$structure["fieldname"]		= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Apply Options";
		$form->add_input($structure);

		print "<br>";
		$form->render_field("submit");
		print "<br><br>";

		print "</form>";
	}



	/*
		render_table()

		This function renders the entire table.
	*/
	function render_table()
	{
		// translate the column labels
		$this->render_column_names();

		// display header row
		print "<table class=\"table_content\" width=\"100%\">";
		print "<tr>";

		foreach ($this->render_columns as $columns)
		{
			print "<td class=\"header\"><b>". $columns ."</b></td>";
		}

		if ($this->links)
			print "<td class=\"header\"></td>";	// filler for link column
	
		print "</tr>";

		// display data
		for ($i=0; $i < $this->data_num_rows; $i++)
		{
			print "<tr>";

			foreach ($this->columns as $columns)
			{
				print "<td>". $this->render_field($columns, $i) ."</td>";
			}
			
			if ($this->links)
			{
				print "<td>";

				foreach (array_keys($this->links) as $link)
				{
					$linkname = language_translate_string($this->language, $link);

					// link to page
					print "<a href=\"index.php?page=". $this->links[$link]["page"] ."";

					// add each option
					foreach (array_keys($this->links[$link]["options"]) as $getfield)
					{
						/*
							There are two methods for setting the value of the variable:
							1. The value has been passed.
							2. The name of a column to take the value from has been passed
						*/
						if ($this->links[$link]["options"][$getfield]["value"])
						{
							print "&$getfield=". $this->links[$link]["options"][$getfield]["value"];
						}
						else
						{
							print "&$getfield=". $this->data[$i][ $this->links[$link]["options"][$getfield]["column"] ];
						}
					}

					// finish link
					print "\">$linkname</a>";
				}

				print "</td>";
			}
	
			print "</tr>";
		}


		// display totals for columns
		if ($this->total_columns)
		{
			print "<tr>";

			foreach ($this->render_columns as $column)
			{
				print "<td class=\"footer\">";
		
				if (in_array($column, $this->total_columns))
				{
					$this->data["total"][$column] = 0;
					
					for ($i=0; $i < $this->data_num_rows; $i++)
					{
						$this->data["total"][$column] += $this->data[$i][$column];
					}

					print "<b>". $this->render_field($column, "total") ."</b>";
				}
		
				print "</td>";
			}

			if ($this->links)
				print "<td class=\"footer\"></td>";	// filler for link column
			
			print "</tr>";
		}
	
		print "</table>";
		
		
	}

} // end of table class



?>
