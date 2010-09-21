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

	$selected_structure = @security_form_input_predefined("int", "selected_structure", 1, "");
	
	if( $selected_structure > 0 )
	{
		$sql_trans_obj		= New sql_query;
		$sql_trans_obj->string	= "SELECT `field_src`, `field_dest` FROM `input_structure_items` WHERE id_structure='$selected_structure' ORDER BY `field_src` ASC";
		$sql_trans_obj->execute();

		$sql_trans_obj->fetch_array();
		$input_structure_items = $sql_trans_obj->data;
		
		
		$i = 1;
		foreach($input_structure_items as $input_structure_item)
		{
			$data["column$i"] = $input_structure_item['field_dest'];
			$i++;
		}
		
		
		//exit("<pre>".print_r($data,true)."</pre>");
	}
	else 
	{
		$num_cols = @security_form_input_predefined("int", "num_cols", 1, "");
		
		
		$structure_id = @security_form_input_predefined("int", "structure_id", 0, "");
		$structure_name = @security_form_input_predefined("any", "name", 0, "");
		$structure_description = @security_form_input_predefined("any", "description", 0, "");
	
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
	
		$new_input_structure = array();
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
				
				$new_input_structure[$i] = $data["column$i"];
			}
		}
	}
	
		
	if ($values_count != count($values_required))
	{
		log_write("error", "page_output", "Make sure you have selected all the required column types (". format_arraytocommastring($values_required) .")");
	}



	/*
	*	Process Data
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
		
		
		if(count($new_input_structure) > 0)
		{
			if(isset($structure_name) && ($structure_name != null)) 
			{
			//$structure_name
			//$structure_description
			$sql_obj = New sql_query;
			$sql_obj->trans_begin();
			
			
			if($structure_id > 0)
			{
				$sql_obj->string	= "UPDATE `input_structures` SET `name`= '$structure_name', `description` = '$structure_description' WHERE `id` = $structure_id;";
				if (!$sql_obj->execute())
				{
					log_debug("csv_import", "Failure to update input_structure entry.");
				}
		
				
				$sql_obj->string	= "SELECT `id`, `id_structure` , `field_src`, `field_dest` FROM `input_structure_items` WHERE id_structure='$structure_id' ORDER BY `field_src` ASC";
				$sql_obj->execute();		
				$sql_obj->fetch_array();
				$existing_input_structure_items = (array)$sql_obj->data;
			}
			else 
			{
				$sql_obj->string	= "INSERT INTO input_structures ( name, description, type_input, type_file ) VALUES ('".$structure_name."', '".$structure_description."', 'bank_statement', 'csv');";
				if (!$sql_obj->execute())
				{
					log_debug("csv_import", "Failure whilst creating initial input_structure entry.");
				}
		
				$structure_id = $sql_obj->fetch_insert_id();
				$existing_input_structure_items = array();
			}
			
			
			if($structure_id > 0) 
			{
				$sql_parts = array();
				
				if(count($existing_input_structure_items) > 0)
				{
					$reindexed_input_structure_items = array();
					foreach($existing_input_structure_items as $structure_row)
					{
				 		$reindexed_input_structure_items[$structure_row['field_src']] = $structure_row;
					}
					
					
				
					foreach($new_input_structure as $input_structure_key => $input_structure_value)
					{
						$row = $reindexed_input_structure_items[$input_structure_key];
						if($row['field_dest'] != $input_structure_value)
						{
							$sql_obj->string = "UPDATE `input_structure_items` SET `field_src` = '$input_structure_key', `field_dest` = '$input_structure_value' WHERE `id` = {$row['id']};";
							
							if (!$sql_obj->execute())
							{
								log_debug("csv_import", "Failure whilst updating input_structure item entries.");
							}
						}
						unset($reindexed_input_structure_items[$input_structure_key]);
					}
					
					$items_to_delete = array();
					foreach($reindexed_input_structure_items as $reindexed_input_structure_item) 
					{
						$items_to_delete[] = $reindexed_input_structure_item['id'];
					}
					
					
					if(count($items_to_delete) > 0)
					{
						$sql_obj->string = "DELETE FROM `input_structure_items` WHERE `id` IN (".implode(", ",$items_to_delete).") LIMIT ".count($items_to_delete).";";
						if (!$sql_obj->execute())
						{
							log_debug("csv_import", "Failure whilst deleting input_structure item entries.");
						}
					}
				}
				else
				{
			
			
					foreach($new_input_structure as $input_structure_key => $input_structure_value)
					{
			 			$sql_parts[] = "( {$structure_id}, '{$input_structure_key}', '{$input_structure_value}', '')";
					}
					
					$sql_obj->string = "INSERT INTO `input_structure_items` (`id_structure`, `field_src`, `field_dest`, `regex`) VALUES ".implode(", ", $sql_parts).";";
				
				
					if (!$sql_obj->execute())
					{
						log_debug("csv_import", "Failure whilst creating input_structure item entries.");
					}
				}	
		
			}
			
			if (error_check())
				{
					$sql_obj->trans_rollback();
					log_write("error", "page_output", "An error occured whilst creating the input structure. No changes have been made.");
				}
				else
				{
					$sql_obj->trans_commit();
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
