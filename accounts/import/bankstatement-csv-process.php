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
    $error_blank = 0;
    $error_dup = 0;
    for ($i=1; $i<count($col_array); $i++)
    {
	$col = "column".$i;
	if ($col_array[$col] == "")
	{
	    error_flag_field($col);
	    $error_blank++;
	}
	
	for ($j=$i+1; $j<count($col_array); $j++)
	{
	    $col2 = "column".$j;
	    if ($col_array[$col] == $col_array[$col2])
	    {
 		error_flag_field($col);
 		error_flag_field($col2);
 		$error_dup++;
	    }
	}
    }
    
    if($error_blank)
    {
	log_write("error", "page_output", "Each column must be assigned a role, no blanks are permitted.");
    }
    
    if($error_dup)
    {
	log_write("error", "page_output", "Each column must be assigned a unique role.");
    }
    
    if (error_check())
    {
	header("Location: ../../index.php?page=accounts/import/bankstatement-csv.php");
    }
    else
    {
	$temp_array = $_SESSION["csv_array"];
	$csv_array = array();
	
	for ($i=0; $i<count($temp_array); $i++)
	{
	    for ($j=0; $j<(count($temp_array[0])-1); $j++)
	    {
		$post_col_name = "column".($j+1);
		$col_name = $col_array[$post_col_name];
		$csv_array[$i][$col_name] = $temp_array[$i][$j];
	    }
	}
    
	$_SESSION["statement_array"] = $csv_array;    
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