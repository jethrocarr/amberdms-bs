<?php 

/* 
 * populate_phases_dropdown.php
 * 
 * function called by timereg-day-edit.js to generate phases dropdown via ajax
 */

require("../../config.php");
require("../../amberphplib/main.php");


if (user_permissions_get('timekeeping'))
{
	$product_id 		= @security_script_input_predefined("int", $_GET['project_id']);
	$timereg_id		= @security_script_input_predefined("int", $_GET['timereg_id']);
	$selected		= @security_script_input_predefined("any", $_GET['selected']);
	$edit			= @security_script_input_predefined("any", $_GET['edit']);
	
	$option_string		= "";
	
	$phase_id		= sql_get_singlevalue("SELECT phaseid AS value FROM timereg WHERE id='". $timereg_id ."'");
	
	if ($edit == "true")
	{	
		$option_string	.= "<option>".$edit."  ".$selected."  </option>";
		$selected = $phase_id;
	}
		
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id, name_phase FROM project_phases WHERE projectid =" .$product_id. " ORDER BY name_phase";
	$sql_obj->execute();

	
	if ($sql_obj->num_rows())
	{
		$sql_obj->fetch_array();

		foreach ($sql_obj->data as $data_row)
		{
			$option_string	.= "<option value=\"" .$data_row['id']. "\"";
				if ($data_row['id'] == $selected)
				{
					$option_string	.= " selected=\"selected\"";
				}
			$option_string	.= ">" .$data_row['name_phase']. "</option>";
		}
	}
	else
	{
		$option_string .= "<option value=\"\"> -- there are no phases associated with this project -- </option>";
	}

	unset($sql_obj);

	echo $option_string;
//	echo $phase_id;
	
	exit(0);
}

?>