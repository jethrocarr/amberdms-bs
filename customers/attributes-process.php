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

	// determine number of rows
	$data["num_values"]			= @security_form_input_predefined("int", "num_values", 0, "");


	/*
		Fetch & Verify attribute data
	*/

	for ($i = 0; $i < $data["num_values"]; $i++)
	{
		/*
			Fetch data
		*/
		$data_tmp			= array();
		$data_tmp["id"]			= @security_form_input_predefined("int", "attribute_". $i ."_id", 0, "");
		$data_tmp["key"]		= @security_form_input_predefined("any", "attribute_". $i ."_key", 0, "");
		$data_tmp["value"]		= @security_form_input_predefined("any", "attribute_". $i ."_value", 0, "");
		$data_tmp["delete_undo"]	= @security_form_input_predefined("any", "attribute_". $i ."_delete_undo", 0, "");
		

		/*
			Process Raw Data
		*/
		if ($data_tmp["id"] && $data_tmp["delete_undo"] == "true")
		{
			$data_tmp["mode"] = "delete";
		}
		else
		{
			if (!empty($data_tmp["key"]) && $data_tmp["delete_undo"] == "false")
			{
				$data_tmp["mode"] = "update";
			}
		}


		/*
			Add to array
		*/
		$data["attributes"][] = $data_tmp;
	}



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
		$_SESSION["error"]["form"]["attributes_customer"] = "failed";
		header("Location: ../index.php?page=customer/attributes.php&id_customer=". $obj_customer->id ."");
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
	$obj_attributes->load_data_all();


	// update records
	foreach ($data["attributes"] as $attribute)
	{
		if (!empty($attribute["mode"]))
		{
			$obj_attribute		= New attributes;


			if ($attribute["id"])
			{
				$obj_attribute->id = $attribute["id"];
	
				$obj_attribute->load_data();
			}


			if ($attribute["mode"] == "update")
			{
				// data sent through, we should update an existing attribute. But first, let's check if we actually need to
				// make a change or not.

				if ($obj_attribute->data["value"] != $attribute["name"] || $obj_attribute->data["key"] != $attribute["key"])
				{
					/*
						Update attribute
					*/
					log_write("debug", "process", "Updating attribute ". $attribute["id"] ." due to changed details");


					$obj_attribute->id_owner		= $obj_customer->id;
					$obj_attribute->type			= "customer";
			
					$obj_attribute->data["key"]		= $attribute["key"];
					$obj_attribute->data["value"]		= $attribute["value"];

					$obj_attribute->action_update();
				}
				else
				{
					log_write("debug", "process", "Not updating attribute ". $attribute["id"] ." due to no change in details");
				}
			}
			elseif ($attribute["mode"] == "delete")
			{
				$obj_attribute->action_delete();
			}
		}
		else
		{
			// new row but empty/deleted
		}

	}

	log_write("notification", "process", "Customer attributes updated.");


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
