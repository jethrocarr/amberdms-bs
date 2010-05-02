<?php
/*
	services/cdr-rates-import-csv-process.php
	
	access: services_write

	Fetches column selection and import options, verifies as correct and imports.
*/

require("../include/config.php");
require("../include/amberphplib/main.php");

require("../include/services/inc_services.php");
require("../include/services/inc_services_cdr.php");


if (user_permissions_get("services_write"))
{
	/*
		Fetch Form/Session Data
	*/

	$obj_rate_table						= New cdr_rate_table;
	$obj_rate_table->id					= @security_form_input_predefined("int", "id_rate_table", 1, "");

	$data["cdr_rate_import_mode"]				= @security_form_input_predefined("any", "cdr_rate_import_mode", 1, "");
	$data["cdr_rate_import_cost_price"]			= @security_form_input_predefined("any", "cdr_rate_import_cost_price", 1, "");
	$data["cdr_rate_import_sale_price"]			= @security_form_input_predefined("any", "cdr_rate_import_sale_price", 1, "");
	$data["cdr_rate_import_sale_price_margin"]		= @security_form_input_predefined("float", "cdr_rate_import_sale_price_margin", 0, "");


	$num_cols = @security_form_input_predefined("int", "num_cols", 1, "");

	for ($i=1; $i <= $num_cols; $i++)
	{
		$data["column$i"] = @security_form_input_predefined("any", "column$i", 0, "");
	}





	/*
		Error Handling
	*/


	// verify valid rate table
	if (!$obj_rate_table->verify_id())
	{
		log_write("error", "process", "The CDR rate table you have attempted to edit - ". $obj_rate_table->id ." - does not exist in this system.");
	}



	// verify that there is no duplicate configuration in the columns
	for ($i=1; $i <= $num_cols; $i++)
	{
		$col = "column".$i;	

		for ($j = $i + 1; $j <= $num_cols; $j++)
		{
			$col2 = "column".$j;

			if (!empty($data[$col2]))
			{
				if ($data[$col] == $data[$col2])
				{
					error_flag_field($col);
					error_flag_field($col2);
				 	log_write("error", "page_output", "Each column must be assigned a unique role.");
				}
			}
		}
	}
    

	// verify that the user has selected all of the REQUIRED columns, if they haven't selected one of the required
	// columns, return errors.
	$values_count		= 0;
	$values_required	= array("col_destination", "col_prefix");
	$values_acceptable	= array("col_destination", "col_prefix", "col_cost_price", "col_sale_price");

	for ($i=1; $i <= $num_cols; $i++)
	{
		if (!empty($data["column$i"]))
		{
			if (in_array($data["column$i"], $values_required))
			{
				$values_count++;
			}
			else
			{
				if (!in_array($data["column$i"], $values_acceptable))
				{
					log_write("error", "page_output", "The option ". $data["column$i"] ." is not a valid column type");
					error_flag_field("column$i");
				}
			}
		}
	}

	if ($values_count != count($values_required))
	{
		log_write("error", "page_output", "Make sure you have selected all the required column types (". format_arraytocommastring($values_required) .")");
	}



	/*
		Process Data
	*/
	if (error_check())
	{
		$_SESSION["error"]["form"]["cdr_import_csv"] = "failed";

		header("Location: ../index.php?page=services/cdr-rates-import-csv.php&id=". $obj_rate_table->id);
		exit(0);
	}
	else
	{
		/*
			Read in CSV Data
		*/
		$csv_array		= $_SESSION["csv_array"];
		$import_array_raw	= array();
	
		for ($i=0; $i < count($csv_array); $i++)
		{
		    for ($j=0; $j < count($csv_array[0]); $j++)
		    {
			$post_col_name = "column".($j+1);
			if (isset($data[$post_col_name]))
			{
			    $col_name = $data[$post_col_name];
			    $import_array_raw[$i][$col_name] = $csv_array[$i][$j];
			}
		    }
		}



		/*
			Import rates
		*/

		$sql_obj = New sql_query;
		$sql_obj->trans_begin();


		// fetch or delete existing data
		if ($data["cdr_rate_import_mode"] == "cdr_import_delete_existing")
		{
			// delete all the current rates, except default
			$sql_obj->string	= "DELETE FROM cdr_rate_tables_values WHERE id_rate_table='". $obj_rate_table->id ."' AND rate_prefix!='DEFAULT' AND rate_prefix!='LOCAL'";
			$sql_obj->execute();
		}
/*		else
		{
			// import existing rate information
			$sql_obj->string	= "SELECT rate_prefix, rate_description, rate_price_sale, rate_price_cost FROM cdr_rate_tables_values WHERE id_rate_table='". $obj_rate_table->id ."'";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				$sql_obj->fetch_array();

				// TODO: write me
			}
		}
*/

		// track bad lines
		$dud_lines = 0;

		// run through and insert rates
		foreach ($import_array_raw as $import_row)
		{
			$data_row = array();

			// calculate the cost price
			switch ($data["cdr_rate_import_cost_price"])
			{
				case "cdr_import_cost_price_use_csv":
					$data_row["cost_price"]		= $import_row["col_cost_price"];
				break;

				case "cdr_import_cost_price_nothing":
					$data_row["cost_price"]		= "";
				break;
			}

			// calculate the sale price
			switch ($data["cdr_rate_import_sale_price"])
			{
				case "cdr_import_sale_price_use_csv":

					$data_row["sale_price"]		= $import_row["col_sale_price"];

				break;


				case "cdr_import_sale_price_margin":

					$data_row["sale_price_margin"]	= ($data["cdr_rate_import_sale_price_margin"] / 100) + 1;

					$data_row["sale_price"]		= $import_row["col_cost_price"] * $data_row["sale_price_margin"];

				break;


				case "cdr_import_sale_price_nothing":

					$data_row["sale_price"]		= "";

				break;
			}


			// split the prefix items
			$prefixes = explode(",", $import_row["col_prefix"]);

			foreach ($prefixes as $prefix)
			{
				// strip any junk from the file
				$prefix = str_replace(' ', '', $prefix);
				$prefix = str_replace('\n', '', $prefix);
				$prefix = str_replace('\r', '', $prefix);

				// verify valid prefix (integer only)
				if (preg_match('/^[0-9][0-9]*$/', $prefix))
				{
					$sql_obj->string	= "INSERT INTO cdr_rate_tables_values (
													id_rate_table,
													rate_prefix,
													rate_description,
													rate_price_sale,
													rate_price_cost)
													VALUES (
													'". $obj_rate_table->id ."',
													'". $prefix ."',
													'". $import_row["col_destination"] ."',
													'". $data_row["sale_price"] ."',
													'". $data_row["cost_price"] ."')";
					$sql_obj->execute();
				}
				else
				{
					$dud_lines++;
				}
			}
		}


	
		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "process", "An database error occured whilst trying to import the supplied file.");
		}
		else
		{
			$sql_obj->trans_commit();

			log_write("notification", "process", "Call rates successfully imported! Please review to ensure there have been no inaccurances or mistakes imported.");
			log_write("notification", "process", "Note that $dud_lines lines from the import files could not be processed, this can sometimes happen due to header lines, blank lines, etc");
		}



		header("Location: ../index.php?page=services/cdr-rates-items.php&id=". $obj_rate_table->id);
		exit(0);
	}
}
else
{
	// user does not have permissions to access this page.
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}

?>
