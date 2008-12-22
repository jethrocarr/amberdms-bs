<?php
/*
	inc_template_engines.php

	Functions and classes for generating rendered output.
	
	This function is currently used for generging PS/PDF output by
	using latex, but in future could be expanded to offer other
	formats as well.
*/


/*
	CLASS TEMPLATE_ENGINE

	Base clase used by all other render classes. Provides basic template loading
	options as well as field replacement.
*/
class template_engine
{
	var $data;		// data for standard fields
	var $data_array;	// data for array field	

	var $template;		// template contents
	var $processed;		// processed lines of the templates (template with field data merged)

	var $output;		// field to hold processed information (eg: binary file data)


	/*
		prepare_load_template

		Reads a template file into memory

		Values
		templatefile		Filename/path of the template file

		Returns
		0			failure to load
		1			success
	*/
	function prepare_load_template($templatefile)
	{
		log_debug("template_engine", "Executing prepare_load_template($templatefile))");
		
		// load template data into memory.
		$this->template = file($templatefile);

		if (!$this->template)
		{
			log_write("error", "template_engine", "Unable to load template file $templatefile into memory");
			return 0;
		}

		return 1;
	}



	/*
		prepare_add_field

		Add a new field to the template

		Values
		fieldname		name of the field in the template to match
		value			value to insert into the template

		Return
		0			failure
		1			success
	*/
	function prepare_add_field($fieldname, $value)
	{
		log_debug("template_engine", "Executing prepare_add_field($fieldname, value)");
		
		$this->data[$fieldname] = $value;
		return 1;
	}


	/*
		prepare_add_array

		Add a new array to the template - this will tell the code to create
		multiple rows in the template based on the provided data

		Values
		fieldname		name of the field in the template to match
		structure		array of values to insert for each row
					
						structure:
						$structure[0]["field1"] = "banana";
						$structure[0]["field2"] = "$10";;
						$structure[1]["field1"] = "apple";
						$structure[1]["field2"] = "$5";
		
		Return
		0			failure
		1			success
	*/
	function prepare_add_array($fieldname, $structure)
	{
		log_debug("template_engine", "Executing prepare_add_array($fieldname, array)");
		
		$this->data_array[$fieldname] = $structure;
		return 1;
	}


	


	/*
		prepare_filltemplate

		Runs through the template and performs replacements for all the defined
		fields.
		
		Returns
		0			failure
		1			success
	*/
	function prepare_filltemplate()
	{
		log_debug("template_engine", "Executing prepare_filltemplate()");

		$fieldname	= "";
		$inloop		= 0;
		
		for ($i=0; $i < count($this->template); $i++)
		{
			$line = $this->template[$i];

			
			// if $inloop is set, then this line should be repeated for each row in the array
			if ($inloop)
			{
				// check for loop end
				if (preg_match("/^\S*\send/", $line))
				{
					$inloop = 0;
				}
				else
				{
					// remove commenting from the front of the line
					$line = preg_replace("/^\S*\s/", "", $line);
				
					/*
						For this line, run through all the rows and add a new, processed
						row for every row required.
					*/
					for ($j=0; $j < count($this->data_array[$fieldname]); $j++)
					{
						$line_tmp = $line;
						
						foreach (array_keys($this->data_array[$fieldname][$j]) as $var)
						{
							$line_tmp = str_replace("($var)", $this->data_array[$fieldname][$j][$var], $line_tmp);
						}

						// save processed output
						$this->processed[] = $line_tmp;
					}

				}
				
			}
			else
			{
				// NOT IN LOOP SECTION

				// check for loop start
				if (preg_match("/^\S*\sforeach\s(\S*)/", $line, $matches))
				{
					$fieldname = $matches[1];
				
					log_debug("template_engine","Processing array field $fieldname");

					$inloop = 1;
				}
				else
				{
					// process any single variables in this line
					$line_tmp = $line;
				
					if ($this->data)
					{
						foreach (array_keys($this->data) as $var)
						{
							$line_tmp = str_replace("($var)", $this->data[$var], $line_tmp);
						}
					}
					
					$this->processed[] = $line_tmp;
				}
			}

		}
		
	}

	
} // end of template_engine class




/*
	CLASS TEMPLETE_ENGINE_LATEX
*/
class template_engine_latex extends template_engine
{
	/*
		prepare_escape_fields()

		Escapes bad characters for latex - eg: \, % and others.
		
		Returns
		0		failure
		1		success
	*/
	function prepare_escape_fields()
	{
		log_debug("template_engine_latex", "Executing prepare_escape_fields()");

		$target		= array('/%/', '/_/', '/\$/');
		$replace	= array('\%', '\_', '\\\$');

		// escape single fields
		if ($this->data)
		{
			foreach (array_keys($this->data) as $var)
			{
				$this->data[$var] = preg_replace($target, $replace, $this->data[$var]);
			}
		}


		// escape arrays
		if ($this->data_array)
		{
			foreach (array_keys($this->data_array) as $fieldname)
			{
				for ($j=0; $j < count($this->data_array[$fieldname]); $j++)
				{
					$line_tmp = $line;
						
					foreach (array_keys($this->data_array[$fieldname][$j]) as $var)
					{
						$this->data_array[$fieldname][$j][$var] = preg_replace($target, $replace, $this->data_array[$fieldname][$j][$var]);
					}
				}
			}
		}
		
	} // end of prepare_escape_fields


	/*
		generate_pdf()

		Generate a PDF file and stores it in $this->output.

		Returns
		0		failure
		1		success
	*/
	
	function generate_pdf()
	{
		log_debug("template_engine_latex", "Executing generate_pdf()");

		// calculate a temporary filename
		$uniqueid = 0;
		while ($tmp_filename == "")
		{
			if (file_exists("/tmp/amberdms_billing_system_". mktime() ."-$uniqueid"))
			{
				// the filename has already been used, try incrementing
				$uniqueid++;
			}
			else
			{
				// found an avaliable ID
				$tmp_filename = "/tmp/amberdms_billing_system_". mktime() ."-$uniqueid";
			}
		}


		// write out template data
		if (!$handle = fopen("$tmp_filename.tex", "w"))
		{	
			log_write("error", "template_engine_latex", "Failed to create temporary file ($tmp_filename.tex) with template data");
			return 0;
		}

		foreach ($this->processed as $line)
		{
			if (fwrite($handle, $line) === FALSE)
			{
				log_write("error", "template_engine_latex", "Error occured whilst writing file ($tmp_filename.tex)");
				return 0;
			}
		}

		fclose($handle);



		// process with pdflatex
		$app_pdflatex = sql_get_singlevalue("SELECT value FROM config WHERE name='APP_PDFLATEX' LIMIT 1");
	
		chdir("/tmp");
		exec("$app_pdflatex $tmp_filename.tex");

		// check that a PDF was generated
		if (file_exists("$tmp_filename.pdf"))
		{
			log_debug("template_engine_latex", "Temporary PDF $tmp_filename.pdf generated");
			
			// import file data into memory
			$this->output = file_get_contents("$tmp_filename.pdf");

			// remove temporary files from disk
			unlink("$tmp_filename.aux");
			unlink("$tmp_filename.log");
			unlink("$tmp_filename.tex");
			unlink("$tmp_filename.pdf");

			return 1;
		}
		else
		{
			log_write("error", "template_engine_latex", "Unable to use pdflatex ($app_pdflatex) to generate PDF file");
			return 0;
		}
		
	} // end of generate_pdf


	

	
} // end of template_engine_latex class






?>
