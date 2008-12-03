<?php
/*
	inc_templateengines.php

	Functions and classes for generating rendered output.
	
	This function is currently used for generging PS/PDF output by
	using latex, but in future could be expanded to offer other
	formats as well.
*/


/*
	CLASS RENDERENGINE

	Base clase used by all other render classes. Provides basic template loading
	options as well as field replacement.
*/
class templateengine
{
	var $data;		// array of all the fields and values to insert into the template
				// structure:
				//	fieldname => value

	var $template;		// template contents

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
		log_debug("templateengine", "Executing prepare_load_template($templatefile))");
		
		// load template data into memory.
		$this->template = file($templatefile);

		if (!$this->template)
		{
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
		log_debug("templateengine", "Executing prepare_add_field($fieldname, value)");
		
		$this->data[$fieldname] = $value;
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
		log_debug("templateengine", "Executing prepare_filltemplate()");

		
		for ($i=0; $i < count($this->template); $i++)
		{
			foreach (array_keys($this->data) as $fieldname)
			{
				$template[$i] = str_replace("[$fieldname]", $this->data[$fieldname], $this->template[$i]);
			}
		}
	}

	
} // end of templateengine class




/*
	CLASS RENDERENGINE_LATEX
*/
class templateengine_latex extends templateengine
{

	/*
		generate_pdf()

		Generate a PDF file and stores it in $this->output.

		Returns
		0		failure
		1		success
	*/
	
	function generate_pdf()
	{
		log_debug("templateengine_latex", "Executing generate_pdf()");

		// calculate a temporary filename
		$uniqueid = 0;
		while ($tmp_filename == "")
		{
			if (file_exists("amberdms_billing_system_". mktime() ."-$uniqueid"))
			{
				// the filename has already been used, try incrementing
				$uniqueid++;
			}
			else
			{
				// found an avaliable ID
				$tmp_filename = "amberdms_billing_system_". mktime() ."-$uniqueid";
			}
		}


		// write out template data
		if (!file_put_contents("$tmp_filename.tex", $this->template))
		{	
			log_debug("templateengine_latex", "Failed to create temporary file ($tmp_filename.tex) with template data");
			return 0;
		}


		// process with pdflatex
		$app_pdflatex = sql_get_singlevalue("SELECT value FROM config WHERE name='APP_PDFLATEX' LIMIT 1");
		
		system("$app_pdflatex -halt-on-error $tmp_filename.tex");
		

		// check that a PDF was generated
		if (file_exists("$tmp_filename.pdf"))
		{
			log_debug("templateengine_latex", "Temporary PDF $tmp_filename.pdf generated");
			
			// import file data into memory
			$this->output = file_get_contents($tmp_filename);

			// remove temporary files from disk
			unlink("$tmp_filename.tex");
			unlink("$tmp_filename.pdf");

			return 1;
		}
		else
		{
			log_debug("templateengine_latex", "Unable to use pdflatex ($app_pdflatex) to generate PDF file");
			return 0;
		}
		
	} // generate_pdf


	

	
} // end of templateengine_latex class






?>
