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
    $col_array = $_POST;
    $_SESSION["col_array"] = $col_array;
    $error_dup = 0;
    for ($i=1; $i<count($col_array); $i++)
    {
 	$col = "column".$i;	
	for ($j=$i+1; $j<count($col_array); $j++)
	{
	    $col2 = "column".$j;
	    if ($col_array[$col] == $col_array[$col2])
	    {
 		error_flag_field($col);
 		error_flag_field($col2);
 		log_write("error", "page_output", "Each column must be assigned a unique role.");
	    }
	}
    }
        
    if (error_check())
    {
	header("Location: ../../index.php?page=accounts/import/bankstatement-csv.php");
    }
    else
    {
	$csv_array = $_SESSION["csv_array"];
	$statement_array = array();
	
	for ($i=0; $i<count($csv_array); $i++)
	{
	    for ($j=0; $j<(count($csv_array[0])-1); $j++)
	    {
		$post_col_name = "column".($j+1);
		if (isset($col_array[$post_col_name]))
		{
		    $col_name = $col_array[$post_col_name];
		    $statement_array[$i][$col_name] = $csv_array[$i][$j];
		}
	    }
	}
	
	
    
	$_SESSION["statement_array"] = $statement_array;
	unset($_POST);
	header("Location: ../../index.php?page=accounts/import/bankstatement-assign.php");
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