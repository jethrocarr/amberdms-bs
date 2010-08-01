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
	/*
		Init Objects
	*/

	$obj_customer		= New customer;

	$obj_attributes		= New attributes;



	/*
		Import form data
	*/

	$obj_customer->id			= @security_form_input_predefined("int", "id_customer", 1, "");
	$data = array();
	$data["highest_attr_id"]		= @security_form_input_predefined("int", "highest_attr_id", 0, "");
	$data["new_groups"]			= @security_form_input_predefined("any", "new_groups", 0, "");
	
	//print $data["new_groups"]; 
	$group_array = explode(",", $data["new_groups"]);
	for ($i=0; $i<count($group_array); $i++)
	{
//		$ $attr_list; 
		if(!empty($group_array[$i])){
			$data["new_group_attributes"][$group_array[$i]] = @security_form_input_predefined("any", "group_".$group_array[$i]."_attribute_list", 0, "");
		}
	}
		
	


	/*
		Fetch & Verify attribute data
	*/

	for ($i = 0; $i <= $data["highest_attr_id"]; $i++)
	{
//	 print "  i  :".$i;
		/*
			Fetch data
		*/
//	$test_key = @security_form_input_predefined("any", "attribute_". $i ."_key", 0, "");
//	if ($test_key != ""){print "yay"; die;} else {print $test_key; die;}
		$data_tmp			= array();
		$data_tmp["id"]			= $i;
		$data_tmp["key"]		= @security_form_input_predefined("any", "attribute_". $i ."_key", 0, "");
		$data_tmp["value"]		= @security_form_input_predefined("any", "attribute_". $i ."_value", 0, "");
		$data_tmp["delete_undo"]	= @security_form_input_predefined("any", "attribute_". $i ."_delete_undo", 0, "");
		$data_tmp["id_group"]	 	= @security_form_input_predefined("int", "attribute_". $i ."_group", 0, "");
		

		/*
			Process Raw Data
		*/
		if (!empty($data_tmp["key"]) && $data_tmp["delete_undo"] == "true")
		{
			//print "  delete  ";
			$data_tmp["mode"] = "delete";
			$data["attributes"][] = $data_tmp;
//			print "hi"; die;
		}
		
		elseif (empty($data_tmp["key"]) && empty($data_tmp["value"]))
		{
			//print "   empty  ";
//			error_flag_field("attribute_" .$data_tmp["id"]. "_key");
//			log_write("error", "page_output", "Both the key and value fields must be completed");
			continue;
		}
		
		elseif (empty($data_tmp["key"]) || empty($data_tmp["value"]))
		{
			//print " one empty ";
			error_flag_field("attribute_" .$data_tmp["id"]. "_key");
			error_flag_field("attribute_" .$data_tmp["id"]. "_value");
			log_write("error", "page_output", "Both the key and value fields must be completed");
		}
		
		else
		{
			//$data_tmp["mode"] = "update";
			$data["attributes"][] = $data_tmp;
//				print_r($data_tmp);
//				print_r($data); die;
		}
		
		
//		error_flag_field($col);
//					error_flag_field($col2);
//				 	log_write("error", "page_output", "Each column must be assigned a unique role.");

				
//			if(empty($attribute["key"]) || $attribute["value"])
//			{
//				$_SESSION["error"]["attribute" .$attribute["id"] ."-error"] = "Both key and attribute must be set";
//				header("Location: ../index.php?page=customers/attributes.php&id_customer=". $obj_customer->id ."");
//				exit(0);
//			}
		
		
//print_r($data); die;

		/*
			Add to array
		*/
//		$data["attributes"][] = $data_tmp;
	}
//print "<pre>";
//print_r($data); print "</pre>"; die;

	/*
		Error Handling
	*/


	// verify customer
	if (!$obj_customer->verify_id())
	{
		log_write("error", "process", "The supplied customer ID of ". $obj_customer->id ." is not valid");
	}


	// return to input page in event of an error
	if ($_SESSION["error"]["message"])
	{	
		$tmp_string = "";
		print_r($data["new_group_attributes"]);
		foreach($data["new_group_attributes"] as $group=>$attributes)
		{
			$tmp_string .= "&group_" . $group . "_attributes_list=" . $attributes;	
			print $tmp_string;	
		}
		//die;
		$_SESSION["error"]["form"]["attributes_customer"] = "failed";
		header("Location: ../index.php?page=customers/attributes.php&id_customer=". $obj_customer->id ."&new_groups=". $data["new_groups"]. $tmp_string);
		exit(0);
	}



	/*
		Transaction Start
	*/

	$sql_obj = New sql_query;
	$sql_obj->trans_begin();



	/*
		Update Attributes
	*/

	log_write("debug", "process", "Updating Attributes");


	// fetch all current records
	//$obj_attributes->load_data_all();
	


	// update records
	foreach ($data["attributes"] as $attribute)
	{
		$obj_attribute = New attributes;
		$obj_attribute->load_data();
		$obj_attribute->id  = $attribute["id"];
		
		if ($attribute["mode"] == "delete")
		{
//			print $attribute["id"]; die;
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
				
//				//check if a change needs to be made
//				if ($obj_attribute->data["value"] != $attribute["value"] || $obj_attribute->data["key"] != $attribute["key"] || $obj_attribute->data["id_group"] != $attribute["id_group"])
//				{
//					log_write("debug", "process", "Updating attribute ". $attribute["id"] ." due to changed details");
//					
//					$obj_attribute->id_owner		= $obj_customer->id;
//					$obj_attribute->type			= "customer";
//					$obj_attribute->id_group		= $attribute["id_group"];			
//					$obj_attribute->data["key"]		= $attribute["key"];
//					$obj_attribute->data["value"]		= $attribute["value"];
//					$obj_attribute->action_update();
//				}
//			}
			//otherwise, update
			elseif ($obj_attribute->data["value"] != $attribute["value"] || $obj_attribute->data["key"] != $attribute["key"] || $obj_attribute->data["id_group"] != $attribute["id_group"])
			{
				log_write("debug", "process", "Updating attribute ". $attribute["id"] ." due to changed details");
				$obj_attribute->action_update();
			}
		}		
	}
//		if (!empty($attribute["mode"]))
//		{
//			$obj_attribute		= New attributes;
//
//
//			if ($attribute["id"])
//			{
//				$obj_attribute->id = $attribute["id"];
////$obj_attribute->id = 5;
////	log_write("debug", "process", "LOAD DATA");
////				print $obj_attribute->load_data(); die;
////				print "Hello";
////				print $attribute["id"];
////				print_r($obj_attribute->data);
////				die;
//				
//				
//			}
//
//
//			if ($attribute["mode"] == "update")
//			{
//				// data sent through, we should update an existing attribute. But first, let's check if we actually need to
//				// make a change or not.
//
//				if ($obj_attribute->data["value"] != $attribute["value"] || $obj_attribute->data["key"] != $attribute["key"] || $obj_attribute->data["id_group"] != $attribute["id_group"])
//				{
//					/*
//						Update attribute
//					*/
//					log_write("debug", "process", "Updating attribute ". $attribute["id"] ." due to changed details");
//
//
//					$obj_attribute->id_owner		= $obj_customer->id;
//					$obj_attribute->type			= "customer";
//					$obj_attribute->id_group		= $attribute["id_group"];
//			
////					$obj_attribute->data["key"]		= $attribute["key"];
////					$obj_attribute->data["value"]		= $attribute["value"];
////					$obj_attribute->data["id_group"]	= $attribute["id_group"];
//
//					$obj_attribute->action_update();
//				}
//				else
//				{
//					log_write("debug", "process", "Not updating attribute ". $attribute["id"] ." due to no change in details");
//				}
//			}
//			elseif ($attribute["mode"] == "delete")
//			{
//				$obj_attribute->action_delete();
//			}
//		}
//		else
//		{
//			// new row but empty/deleted
//		}
//
//	}
//
//	log_write("notification", "process", "Customer attributes updated.");




	/*
		Commit / Error Handle
	*/
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
	
	/*check if any groups no longer have attributes assigned to them. if so, delete the group*/
	//get all group ids
	//for each one, test if there is an attribute
	
	$group_ids = New sql_query;
	$group_ids->string = "SELECT id FROM attributes_group";
	$group_ids->execute();
	
	$group_ids->fetch_array();
	//print_r($group_ids->data); 	
	foreach ($group_ids->data as $data)
	{
		$test_val = sql_get_singlevalue("SELECT id AS value FROM attributes WHERE id_group = " .$data["id"]);
		//print $test_val . "     ";
		if($test_val == 0)
		{
		//print "ere";
			$delete_group = New sql_query;
			$delete_group->string = "DELETE FROM attributes_group WHERE id=" .$data["id"];
			$delete_group->execute();
		}
	}
	

	// display updated details
	header("Location: ../index.php?page=customers/attributes.php&id_customer=". $obj_customer->id);
	exit(0);

}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
