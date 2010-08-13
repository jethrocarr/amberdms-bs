<?php
/*
	customers/attributes-process.php

	access: customers_write

	Allows a user to adjust the attributes for a customer.
*/

require("../include/config.php");
require("../include/amberphplib/main.php");

require("../include/customers/inc_customers.php");
require("../include/attributes/inc_attributes.php");



if (user_permissions_get('customers_write'))
{
	//initalise objects
	$obj_customer		= New customer;
	$obj_attributes		= New attributes;


	//import data
	$obj_customer->id			= @security_form_input_predefined("int", "id_customer", 1, "");
	
	$data = array();
	$data["highest_attr_id"]		= @security_form_input_predefined("int", "highest_attr_id", 0, "");
	$data["new_groups"]			= @security_form_input_predefined("any", "new_groups", 0, "");
	
	//grab lists of attributes for new groups
	$new_groups_array = explode(",", $data["new_groups"]);
	for ($i=0; $i<count($new_groups_array); $i++)
	{
		if(!empty($new_groups_array[$i])){
			$data["new_group_attributes"][$new_groups_array[$i]] = @security_form_input_predefined("any", "group_".$new_groups_array[$i]."_attribute_list", 0, "");
		}
	}
		

	//fetch and varify data
	$groups_array = array();
	for ($i = 0; $i <= $data["highest_attr_id"]; $i++)
	{
		$data_tmp			= array();
		$data_tmp["id"]			= $i;
		$data_tmp["key"]		= @security_form_input_predefined("any", "attribute_". $i ."_key", 0, "");
		$data_tmp["value"]		= @security_form_input_predefined("any", "attribute_". $i ."_value", 0, "");
		$data_tmp["delete_undo"]	= @security_form_input_predefined("any", "attribute_". $i ."_delete_undo", 0, "");
		$data_tmp["id_group"]	 	= @security_form_input_predefined("int", "attribute_". $i ."_group", 0, "");
		
		//create an array of group ids 
		if (!in_array($data_tmp["id_group"], $group_array_2))
		{
			$groups_array[] = $data_tmp["id_group"];
		}
		
		/*
		 * 	Verify data
		 * 	Check for delete requests
		 * 	Check for errors
		 */
		//if data, do nothing
		if (empty($data_tmp["key"]) && empty($data_tmp["value"]))
		{
			continue;
		}
		
		//set delete flags
		elseif ($data_tmp["delete_undo"] == "true")
		{
			$data_tmp["mode"] = "delete";
			$data["attributes"][] = $data_tmp;
		}
		
		//check for errors
		//both key and value fields must be completed
		elseif (empty($data_tmp["key"]) || empty($data_tmp["value"]))
		{
			error_flag_field("attribute_" .$data_tmp["id"]. "_key");
			error_flag_field("attribute_" .$data_tmp["id"]. "_value");
			log_write("error", "page_output", "Both the key and value fields must be completed");
		}
		
		//otherwise, add to array to be processed
		else
		{
			$data["attributes"][] = $data_tmp;
		}
	}

	//check for new attribute rows
	$new_attributes = array();
	for($i=0; $i<count($groups_array); $i++)
	{
		$new_attributes[$groups_array[$i]] = @security_form_input_predefined("any", "group_" .$groups_array[$i]. "_new_attributes", 0, "");
	}

	// verify customer
	if (!$obj_customer->verify_id())
	{
		log_write("error", "process", "The supplied customer ID of ". $obj_customer->id ." is not valid");
	}

	// return to input page in event of an error
	if ($_SESSION["error"]["message"])
	{	
		//prepare GET data to add to URL
		$tmp_string = "";
		//add new groups and attributes
		foreach($data["new_group_attributes"] as $group=>$attributes)
		{
			$tmp_string .= "&group_" . $group . "_attributes_list=" . $attributes;	
			print $tmp_string;	
		}
		//add new attributes
		foreach($new_attributes as $group=>$attributes)
		{
			$tmp_string .="&group_" .$group. "_new_attributes=" .$attributes;
		}

		$_SESSION["error"]["form"]["attributes_customer"] = "failed";
		header("Location: ../index.php?page=customers/attributes.php&id_customer=". $obj_customer->id ."&new_groups=". $data["new_groups"]. $tmp_string);
		exit(0);
	}

	/*
	 * 	Add/ delete attributes and groups from database
	 */
	$sql_obj = New sql_query;
	$sql_obj->trans_begin();

	// update records
	foreach ($data["attributes"] as $attribute)
	{
		$obj_attribute = New attributes;
		$obj_attribute->load_data();
		$obj_attribute->id  = $attribute["id"];
		
		//if attribute is to be deleted, call delete action
		if ($attribute["mode"] == "delete")
		{
			$obj_attribute->action_delete();
		}
		
		//if mode is empty, determine if mode should be update or create
		elseif (empty($attribute["mode"]))
		{
			$obj_attribute->id_owner	= $obj_customer->id;
			$obj_attribute->type		= "customer";
			$obj_attribute->id_group	= $attribute["id_group"];
			$obj_attribute->data["key"]	= $attribute["key"];
			$obj_attribute->data["value"]	= $attribute["value"];
			
			//if id doesn't exist, create new
			if(!$obj_attribute->verify_id())
			{
				$obj_attribute->action_create();	
			}
				
			//otherwise, update
			elseif ($obj_attribute->data["value"] != $attribute["value"] || $obj_attribute->data["key"] != $attribute["key"] || $obj_attribute->data["id_group"] != $attribute["id_group"])
			{
				log_write("debug", "process", "Updating attribute ". $attribute["id"] ." due to changed details");
				$obj_attribute->action_update();
			}
		}		
	}

	//check for errors
	if (!error_check())
	{

		$sql_obj->trans_commit();
	}
	else
	{
		// error encountered
		log_write("error", "process", "An unexpected error occured, the attributes remain unchanged");
		$sql_obj->trans_rollback();
	}
	
	/*
	 * 	If no attributes exist for any of the groups in the database, delete the group
	 */	
	$group_ids = New sql_query;
	$group_ids->string = "SELECT id FROM attributes_group";
	$group_ids->execute();
	
	$group_ids->fetch_array();
 	
	foreach ($group_ids->data as $data)
	{
		$test_val = sql_get_singlevalue("SELECT id AS value FROM attributes WHERE id_group = " .$data["id"]);
		if($test_val == 0)
		{
			$delete_group = New sql_query;
			$delete_group->string = "DELETE FROM attributes_group WHERE id=" .$data["id"];
			$delete_group->execute();
			
			log_write("notification", "process", "Group has been successfully deleted");
		}
	}
	
	// display updated details
	header("Location: ../index.php?page=customers/attributes.php&id_customer=". $obj_customer->id);
	exit(0);

}

else
{
	// user does not have permission to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}
?>
