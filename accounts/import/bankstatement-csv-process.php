<?php
/*
	bankstatement-csv-process.php
	
	access: "accounts_import_statement" group members

	Modifies array with new column names
*/
include_once("../../include/config.php");
include_once("../../include/amberphplib/main.php");

if (user_permissions_get("accounts_import_statement"))
{
	/*
		Fetch Form/Session Data
	*/

	$num_cols = @security_form_input_predefined("int", "num_cols", 1, "");

	for ($i=1; $i <= $num_cols; $i++)
	{
		$data["column$i"] = @security_form_input_predefined("any", "column$i", 0, "");
	}




	/*
		Error Handling
	*/

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
	$values_required	= array("transaction_type", "other_party", "amount", "date");
	$values_acceptable	= array("transaction_type", "other_party", "amount", "date", "code", "reference", "particulars");

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
		$_SESSION["error"]["form"]["bankstatement_csv"] = "failed";

		header("Location: ../../index.php?page=accounts/import/bankstatement-csv.php");
		exit(0);
	}
	else
	{
		$csv_array = $_SESSION["csv_array"];
		$statement_array = array();
	
		for ($i=0; $i < count($csv_array); $i++)
		{
		    for ($j=0; $j < count($csv_array[0]); $j++)
		    {
			$post_col_name = "column".($j+1);
			if (isset($data[$post_col_name]))
			{
			    $col_name = $data[$post_col_name];
			    $statement_array[$i][$col_name] = $csv_array[$i][$j];
			}
		    }
		}
	}
		
	$_SESSION["statement_array"] = $statement_array;


	header("Location: ../../index.php?page=accounts/import/bankstatement-assign.php");
	exit(0);
}
else
{
	// user does not have permissions to access this page.
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}

?>
