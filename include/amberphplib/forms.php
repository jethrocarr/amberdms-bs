<?php
/*
	forms.php

	Provides classes/functions used for generating and processing forms.

	class form_input	Functions for UI component of forms.

	standalone functions:

	function form_helper_prepare_dropdownfromdb

*/

class form_input
{
	var $formname;			// name of the form - needs to be unique per form
	var $language = "en_us";	// language to use for the form labels.
	
	var $action;			// page to process the data
	var $method;			// method to use - POST or GET

	var $sql_query;			// SQL query used to fetch data
	
	var $subforms;			// associative array of sub form titles and contents
	var $structure;			// array structure of all variables and options


	/*
		add_input ($option_array)

		This function adds a new field to the form, based on the information in the array. Please refer
		to the render_field array for details on the array structure.
	*/
	function add_input ($option_array)
	{
		log_debug("form", "Executing add_input for field ". $option_array["fieldname"]);
		
		if (!$option_array["fieldname"] || !$option_array["type"])
			print "Warning: missing fieldname and/or type from add_input function<br>";

		// add data to the structure
		$this->structure[ $option_array["fieldname"] ] = $option_array;
		
	}

	/*
		load_data()

		Wrapper function that decided whether or not to call the load_data_sql or the load_data_error functions.
	*/
	function load_data()
	{
		log_debug("form", "Executing load_data()");
		
		if ($_SESSION["error"]["form"][$this->formname])
		{
			return $this->load_data_error();
		}
		else
		{
			return $this->load_data_sql();
		}
	}


	/*
		load_data_error()

		Imports data from the error arrays into the form structure
	*/
	function load_data_error()
	{
		log_debug("form", "Executing load_data_error()");
	
		if ($_SESSION["error"]["form"][$this->formname])
		{

			foreach (array_keys($this->structure) as $fieldname)
			{
				// make sure we don't import any data for non-user editable fields
				// since these fields
				if ($this->structure[$fieldname]["type"] != "submit" 
					&& $this->structure[$fieldname]["type"] != "message" 
					&& $this->structure[$fieldname]["type"] != "text"
					&& $this->structure[$fieldname]["type"] != "hidden")
				{
					$this->structure[$fieldname]["defaultvalue"] = stripslashes($_SESSION["error"][$fieldname]);
				}
			}
		}
		else
		{
			log_debug("form", "No error data to import.");
		}
	
		return 1;
	}

	/*
		load_data_sql()

		Loads data from MySQL to fill in the default values for the defined structure.
		Note: All add_input calls need to be made before this command it run

	*/
	function load_data_sql()
	{
		log_debug("form", "Executing load_data_sql()");
		log_debug("form", "SQL: ". $this->sql_query); 
		
		// execute the SQL query
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
				// insert the data into the structure value as the default value
				foreach (array_keys($this->structure) as $fieldname)
				{
					if ($mysql_data[$fieldname])
					{
						$this->structure[$fieldname]["defaultvalue"] = $mysql_data[$fieldname];
					}
				}
			}

			return $mysql_num_rows;
		}
	}



	/*
		render_row ($fieldname)

		Displays a table row, including the field itself.
	*/
	function render_row ($fieldname)
	{
		log_debug("form", "Executing render_row($fieldname)");
	
	
		// if the fieldname has experienced an error, we want to highlight the field
		if ($_SESSION["error"]["$fieldname-error"])
		{
			print "<tr class=\"form_error\">";
		}
		else
		{
			print "<tr>";
		}

		switch ($this->structure[$fieldname]["type"])
		{
			case "submit":
				// special submit button row
				print "<td width=\"100%\" colspan=\"2\">";

				// only display the message below if actually required
				$req = 0;
				foreach (array_keys($this->structure) as $tmp_fieldname)
				{
					if ($this->structure[$tmp_fieldname]["options"]["req"])
						$req = 1;
				}

				if ($req)
					print "<p><i>Please note that all fields marked with \"*\" must be filled in.</i></p>";

					
				$this->render_field($fieldname);
				print "</td>";
			break;

			case "message":
				// messages
				print "<td width=\"100%\" colspan=\"2\">";
				$this->render_field($fieldname);
				print "</td>";
			break;


			default:
			
				// field name
				$translation = language_translate($this->language, array($fieldname));
				print "<td width=\"30%\" valign=\"top\">";
				print $translation[$fieldname];
				
				if ($this->structure[$fieldname]["options"]["req"])
					print " *";
				
				print "</td>";

				// display form input field
				print "<td width=\"70%\">";

				$this->render_field($fieldname);

				print "</td>";
			break;

		}

		print "</tr>";	
	}


	/*
		render_field ($fieldname)

		Displays the specified field, based on the information in the structure table.

		The information used should first be added by the add_input($option_array) function,
		with the following syntax for the option array:

			$option_array["fieldname"]		Name of the field
			$option_array["type"]			Type of form input

				input		- standard input box
				password	- password input box
				hidden		- hidden field
				text		- text only display, with hidden field as well

				textarea	- space for blocks of text
				
				date		- special date field - splits a timestamp into 3 DD/MM/YYYY fields
				hourmins	- splits the specified number of seconds into hours, and minutes

				checkbox	- checkboxs (tick boxes)
				radio		- radio buttons
				dropdown 	- dropdown/select boxes

				submit		- submit button
				message		- prints the defaultvalue - used for inserting message into forms
				
			
			$option_array["defaultvalue"]		Default value (if any)
			$option_array["options"]
						["req"]		Set to "yes" to mark the field as being required
						["max_length"]	Max length for input/password types
						["rows"]	Num of rows for textarea
						["cols"]	Num of cols for textarea
						["label"]	Label field for checkboxes to use instead of a translation
		
			$option_array["values"] = array();		Array of values - used for radio or dropdown type fields
			$option_array["translations"] = array();	Associate array used for labeling the values in radio or dropdown type fields
		
	*/
	function render_field ($fieldname)
	{
		log_debug("form", "Executing render_field($fieldname)");
		
		switch ($this->structure[$fieldname]["type"])
		{
			case "input":
				print "<input name=\"$fieldname\" value=\"". $this->structure[$fieldname]["defaultvalue"] ."\"";
			break;

			case "password":
				print "<input type=\"password\" name=\"$fieldname\" value=\"". $this->structure[$fieldname]["defaultvalue"] ."\">";
			break;
			
			case "hidden":
				print "<input type=\"hidden\" name=\"$fieldname\" value=\"". $this->structure[$fieldname]["defaultvalue"] ."\">";
			break;

			case "text":
				$translation = language_translate_string($this->language, $this->structure[$fieldname]["defaultvalue"]);

				print "$translation";
				print "<input type=\"hidden\" name=\"$fieldname\" value=\"". $this->structure[$fieldname]["defaultvalue"] ."\">";
			break;

			case "textarea":
				print "<textarea name=\"$fieldname\" ";
				print "rows=\"". $this->structure[$fieldname]["options"]["rows"] ."\" ";
				print "cols=\"". $this->structure[$fieldname]["options"]["cols"] ."\" ";
				print ">". $this->structure[$fieldname]["defaultvalue"] ."</textarea></td>";
			break;

			case "date":
				if ($this->structure[$fieldname]["defaultvalue"] == "0000-00-00" || $this->structure[$fieldname]["defaultvalue"] == 0)
				{
					$date_a = array("","","");
				}
				else
				{
					$date_a = split("-", $this->structure[$fieldname]["defaultvalue"]);
				}

				print "<input name=\"". $fieldname ."_dd\" style=\"width: 25px;\" maxlength=\"2\" value=\"". $date_a[2] ."\"> ";
				print "<input name=\"". $fieldname ."_mm\" style=\"width: 25px;\" maxlength=\"2\" value=\"". $date_a[1] ."\"> ";
				print "<input name=\"". $fieldname ."_yyyy\" style=\"width: 50px;\" maxlength=\"4\" value=\"". $date_a[0] ."\">";
				print " <i>(dd/mm/yyyy)</i>";

				// TODO: it would be good to have a javascript calender pop-up to use here.
			break;

			case "hourmins":
				if ($this->structure[$fieldname]["defaultvalue"] == 0)
				{
					$time_hours	= "";
					$time_mins	= "";
				}
				else
				{
					$time_processed	= split(":", time_format_hourmins($this->structure[$fieldname]["defaultvalue"]));
					$time_hours	= $time_processed[0];
					$time_mins	= $time_processed[1];
				}

				print "<input name=\"". $fieldname ."_hh\" style=\"width: 25px;\" maxlength=\"2\" value=\"$time_hours\"> hours ";
				print "<input name=\"". $fieldname ."_mm\" style=\"width: 25px;\" maxlength=\"2\" value=\"$time_mins\"> mins";
				

			case "radio":
				// TODO: write me
			break;

			case "checkbox":
				print "<input ";

				if ($this->structure[$fieldname]["defaultvalue"] == "on" || $this->structure[$fieldname]["defaultvalue"] == "1" || $this->structure[$fieldname]["defaultvalue"] == "enabled")
					print "checked ";

				if ($this->structure[$fieldname]["options"]["label"])
				{
					$translation = $this->structure[$fieldname]["options"]["label"];
				}
				else
				{
					$translation = language_translate_string($this->language, $fieldname);
				}
				print "type=\"checkbox\" name=\"". $fieldname ."\">". $translation ."<br>";
			break;

			case "dropdown":

				/*
					there are two ways to draw drop down tables:
					
					1. Just pass it the array of values, and the code will translate them using the language DB

					2. Pass it an array of translation values with the array keys matching the value names. This
					   is useful when you want to populate a dropdown with data from a different table.
				*/


				if ($this->structure[$fieldname]["translations"])
				{
					$translations = $this->structure[$fieldname]["translations"];
				}
				else
				{
					// get translation for all options
					$translations = language_translate($this->language, $this->structure[$fieldname]["values"]);
				}


				// start dropdown/select box
				print "<select name=\"$fieldname\" size=\"1\">";

				// if there is no current entry, add a select entry as default
				if (!$this->structure[$fieldname]["defaultname"])
				{
					print "<option selected value=\"\">-- select --</option>";
				}
			
				// add all the options
				foreach ($this->structure[$fieldname]["values"] as $value)
				{
					// is the current row, the one that is in use? If so, add the 'selected' tag to it
					if ($value == $this->structure[$fieldname]["defaultvalue"])
					{
						print "<option selected ";
					}
					else
					{
						print "<option ";
					}
					
					print "value=\"$value\">" . $translations[$value] ."</option>";
				}

				// end of select/drop down
				print "</select>";
			
			break;

			case "submit":
				$translation = language_translate_string($this->language, $this->structure[$fieldname]["defaultvalue"]);

				print "<input type=\"submit\" value=\"$translation\">";
			break;

			case "message":
				print $this->structure[$fieldname]["defaultvalue"];
			break;
		}

		return 1;
	}


	/*
		render_form()

		Displays a complete form - it either creates a single form, or
		can split the form into different sections with unique titles
	*/
	function render_form()
	{
		log_debug("form", "Executing render_form()");
	
		if (!$this->action || !$this->method)
		{
			print "Warning: No form action or method defined for form class<br>";
		}


		// if we have not choosen to use subforms, then add all values to a single form.
		if (!$this->subforms)
		{
			$this->subforms[$this->formname] = array_keys($this->structure);
		}


		// start form
		print "<form method=\"". $this->method ."\" action=\"". $this->action ."\" class=\"form_standard\">";


		// draw each sub form
		foreach (array_keys($this->subforms) as $form_label)
		{
			if ($form_label == "hidden")
			{
				/*
					Form contains hidden fields, we don't want to create table rows
				*/
				foreach ($this->subforms[$form_label] as $fieldname)
				{
					$this->render_field($fieldname);
				}
			}
			else
			{
				// start table
				print "<table class=\"form_table\" width=\"100%\">";

				// form header
				print "<tr class=\"header\">";
				print "<td colspan=\"2\"><b>". language_translate_string($this->language, $form_label) ."</b></td>";
				print "</tr>";

				// display all the rows
				foreach ($this->subforms[$form_label] as $fieldname)
				{
					$this->render_row($fieldname);
				}

				// end table
				print "</table><br>";
			}
		}

		// end form
		print "</form>";
	}



} // end of class form_input


/*
	Standalone Functions
*/


/*
	form_helper_prepare_dropdownfromdb($fieldname, $sqlquery)

	This function generates the relevent structure needed to add a drop down
	to a form (or the option form for a table) based on the values provided from
	the SQL statement.
	
	All the SQL statement needs todo, is to provide 2 different values, labeled "id" and "label"
	and the function will do the rest.

	Returns the structure array, which can then be passed directly to form::add_input($structure_array);
*/

function form_helper_prepare_dropdownfromdb($fieldname, $sqlquery)
{
	log_debug("form", "Executing form_helper_prepare_dropdownfromdb($fieldname, $sqlquery)");
	
	$structure = NULL;
	$structure["fieldname"] = $fieldname;
	$structure["type"]	= "dropdown";

	if (!$mysql_result = mysql_query($sqlquery))
		log_debug("timereg", "FATAL SQL: ". mysql_error());
			
	$mysql_num_rows	= mysql_num_rows($mysql_result);

	while ($mysql_data = mysql_fetch_array($mysql_result))
	{
		// only add an option if there is an id and label for it
		if ($mysql_data["id"] && $mysql_data["label"])
		{
			$structure["values"][]					= $mysql_data["id"];
			$structure["translations"][ $mysql_data["id"] ]		= $mysql_data["label"];
		}
	}

	// return the structure
	return $structure;
}


?>
