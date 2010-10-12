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
		$sql_trans_obj->string	= "SELECT `field_src`, `field_dest`, `processing_regex`, `data_format` FROM `input_structure_items` WHERE id_structure='$selected_structure' ORDER BY `field_src` ASC";
		$sql_trans_obj->execute();

		$sql_trans_obj->fetch_array();
		$input_structure_items = $sql_trans_obj->data;
		
		
		$data = array();
		$selected_field = array();
		foreach((array)$input_structure_items as $input_structure_item)
		{
			$i = $input_structure_item['field_src'];
			$new_input_structure[$i]['field_src'] = $input_structure_item['field_src'];
			$new_input_structure[$i]['field_dest'] = $input_structure_item["field_dest"];
			$new_input_structure[$i]['data_format'] = $input_structure_item["data_format"];  
		}
		unset($i);
	}
 
	$num_cols = @security_form_input_predefined("int", "num_cols", 1, "");
	
	
	$structure_id = @security_form_input_predefined("int", "structure_id", 0, "");
	
	
	if(($structure_id == $selected_structure) || ($selected_structure == 0))
	{
		$structure_name = @security_form_input_predefined("any", "name", 0, "");
		$structure_description = @security_form_input_predefined("any", "description", 0, "");
	
		for ($i=1; $i <= $num_cols; $i++)
		{
			$data["column$i"] = @security_form_input_predefined("any", "column$i", 0, "");
			$data["format$i"] = @security_form_input_predefined("any", "format$i", 0, "");
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
		
		// Other party, date and amount (of some form) are the only columns we can rely on being present.
		$values_count		= 0;
		$values_required	= array("other_party","date");

		$value_multi_requirement = 0;
		$values_require_one	= array("amount", "amount_credit", "amount_debit");
		
		$values_acceptable	= array("transaction_type", "other_party", "amount", "amount_credit", "amount_debit", "date", "code", "reference", "particulars");

		
		$values_paired = array(
		"amount_credit" => "amount_debit"
		);
		
		
		$new_input_structure = array();
		$selected_field = array();
		for ($i=1; $i <= $num_cols; $i++)
		{
			if (!empty($data["column$i"]))
			{
				if (in_array($data["column$i"], $values_required))
				{
					$values_count++;
				}
				else if(in_array($data["column$i"], $values_require_one))
				{
					$value_multi_requirement++;
				}
				else
				{
					if (!in_array($data["column$i"], $values_acceptable))
					{
						log_write("error", "page_output", "The option ". $data["column$i"] ." is not a valid column type");
						error_flag_field("column$i");
					}
				}
				
				$new_input_structure[$i]['field_src'] = $i;
				$new_input_structure[$i]['field_dest'] = $data["column$i"];
				$new_input_structure[$i]['data_format'] = $data["format$i"];
				$selected_fields[$i] = $data["column$i"];
			}
		}
		if(in_array("amount", $selected_fields)) 
		{
			$paired_value_state = true;
		}
		else
		{
			$paired_value_count = 0;
			foreach($values_paired as $value_pair_key => $value_pair_value )
			{
				if(in_array($value_pair_key, $selected_fields) && in_array($value_pair_value, $selected_fields))
				{
					$paired_value_count++;
				}
			}
		}
		
	
		//exit($paired_value_state);
		if (($values_count != count($values_required)) || ($value_multi_requirement < 1) ||
		 	((count((array)$values_paired) != $paired_value_count) && ($paired_value_count > 0)))
		{	
			log_write("error", "page_output", "Make sure you have selected all the required column types (". format_arraytocommastring($values_required) .")");
		}
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
		// Process the editing of the selected input structure, if the ID is positive and numeric, edit, otherwise, add.
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
		
				$sql_obj->string	= "SELECT `id`, `id_structure` , `field_src`, `field_dest`, `data_format` FROM `input_structure_items` WHERE id_structure='$structure_id' ORDER BY `field_src` ASC";
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
			
			
			// Add or edit the sub items of the input structure.
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
					
					foreach($new_input_structure as $input_structure_key => $input_structure_data)
					{
						$row = $reindexed_input_structure_items[$input_structure_key];
						if(($row['field_dest'] != $input_structure_data['field_dest']) || ($input_structure_data['data_format'] != $row['data_format']) )
						{
							$sql_obj->string = "UPDATE `input_structure_items` SET `field_src` = '$input_structure_key', `field_dest` = '{$input_structure_data['field_dest']}', `data_format` = '{$input_structure_data['data_format']}' WHERE `id` = {$row['id']};";
							
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
					foreach($new_input_structure as $input_structure_key => $input_structure_data)
					{
			 			$sql_parts[] = "( {$structure_id}, '{$input_structure_data['field_src']}', '{$input_structure_data['field_dest']}', '', '{$input_structure_data['data_format']}')";
					}
					
					$sql_obj->string = "INSERT INTO `input_structure_items` (`id_structure`, `field_src`, `field_dest`, `regex`, `data_format`) VALUES ".implode(", ", $sql_parts).";";
				
				
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
		
		
				
		// Process the CSV data into the correct comments, do any post processing actions here, too
		
		$month_names= array( 
			'01' => "jan",
			'02' => "feb",
			'03' => "mar",
			'04' => "apr",
			'05' => "may",
			'06' => "jun",
			'07' => "jul",
			'08' => "aug",
			'09' => "sep",
			'10' => "oct",
			'11' => "nov",
			'12' => "dec"
		);
		
		$csv_array = $_SESSION["csv_array"];
		$statement_array = array();
		$parsed_data_list = array();
		for ($i=0; $i < count($csv_array); $i++)
		{
			// ignore the lines with only one item in them.
			if(count($csv_array[$i]) > 1) 
			{
			    for ($j=0; $j < count($csv_array[$i]); $j++)
			    {
					$post_col_name = "column".($j+1);
					if (!empty($new_input_structure[$j+1])) 
					{
						$format = $new_input_structure[$j+1]['data_format'];
					    $col_name = $new_input_structure[$j+1]['field_dest'];
					    switch($col_name)
					    {
						    case 'date': 
						    	$undesirable_characters = array("/","_",".",","," ",":",";","\\" ,"|");
						    	$cleaned_date = str_replace($undesirable_characters, "-", $csv_array[$i][$j]);
						    	$date_values = explode("-", $cleaned_date);
						    	$sorted_date_values = array();
							    switch($format)
							    {
								    case 'dd-mm-yyyy':
										$sorted_date_values['day'] = $date_values[0];
										$sorted_date_values['month'] = $date_values[1];
										$sorted_date_values['year'] = $date_values[2];
							    	break;
								    
								    case 'mm-dd-yyyy':
										$sorted_date_values['day'] = $date_values[1];
										$sorted_date_values['month'] = $date_values[0];
										$sorted_date_values['year'] = $date_values[2];
							    	break;
							    	
								    case 'yyyy-mm-dd':
										$sorted_date_values['day'] = $date_values[2];
										$sorted_date_values['month'] = $date_values[1];
										$sorted_date_values['year'] = $date_values[0];
							    	break;
							    }
							    
							    if(count($date_values) == 3)
							    {
							    	if(!is_numeric($sorted_date_values['month']))
							    	{
							    		// First three characters, lowercased
								    	$month = substr(strtolower($sorted_date_values['month']), 0, 3);
								    	$month_numeric = array_search($month, $month_names);
								    	$sorted_date_values['month'] = $month_numeric;
								    						    	
							    	}
							    	
							    	$new_date = $sorted_date_values['year']."-".$sorted_date_values['month']."-".$sorted_date_values['day'];
							    }
						    	$csv_array[$i][$j] = $new_date;
						    break;
						    
						    case 'amount_debit':
						    case 'amount_credit':
						    case 'amount':
								// replace configs with standard symbols for processing
								$config_array = array($GLOBALS["config"]["CURRENCY_DEFAULT_SYMBOL"], $GLOBALS["config"]["CURRENCY_DEFAULT_THOUSANDS_SEPARATOR"], $GLOBALS["config"]["CURRENCY_DEFAULT_DECIMAL_SEPARATOR"]);
								$default_array = array("", "", ".");
								$formatted_string = $csv_array[$i][$j];
								$formatted_string = str_replace($config_array, $default_array, $formatted_string);
								
								$expression = "/(-?[0-9]*\.[0-9]*){1}/";
								preg_match($expression, $formatted_string, $matches);
								
								if( $matches[1] != null )
								{
									$formatted_string = $matches[1];
							    	// if this is a amount_debit column, multiply it by negative 1 before sticking it in the amount column
							    	if(($col_name == 'amount_debit'))
							    	{
							    		$formatted_string *= -1;
							    	}
							    	
									$formatted_string = sprintf("%0.2f", $formatted_string);
							    	$col_name = 'amount';
								}
						    	$csv_array[$i][$j] = $formatted_string;
						    	
						    break;
					    }
					    
					    $statement_array[$i][$col_name] = $csv_array[$i][$j];
					}
			    }
			}
		}
		
		
	}
	
	//exit("<pre>".print_r($statement_array, true)."</pre>");
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
