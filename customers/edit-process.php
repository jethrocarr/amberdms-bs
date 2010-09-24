<?php
/*
	customers/edit-process.php

	access: customers_write

	Allows existing customers to be adjusted, or new customers to be added.
*/

// includes
require("../include/config.php");
require("../include/amberphplib/main.php");

// custom includes
require("../include/customers/inc_customers.php");


if (user_permissions_get('customers_write'))
{
	$obj_customer = New customer;
	$num_del_contacts = 0;
	$num_del_records = array();

	log_debug("inc_customers", "SESSION TRACK - beginning of edit-process -0" . $_SESSION["error"]["contact_0"]);
	log_debug("inc_customers", "SESSION TRACK - beginning of edit-process - 1 " . $_SESSION["error"]["contact_1"]);
	/*
		Load POST data
	*/

	$obj_customer->id				= @security_form_input_predefined("int", "id_customer", 0, "");
	
	$obj_customer->data["code_customer"]		= @security_form_input_predefined("any", "code_customer", 0, "");
	$obj_customer->data["name_customer"]		= @security_form_input_predefined("any", "name_customer", 1, "");

	$obj_customer->data["date_start"]		= @security_form_input_predefined("date", "date_start", 1, "");
	$obj_customer->data["date_end"]			= @security_form_input_predefined("date", "date_end", 0, "");

	$obj_customer->data["address1_street"]		= @security_form_input_predefined("any", "address1_street", 0, "");
	$obj_customer->data["address1_city"]		= @security_form_input_predefined("any", "address1_city", 0, "");
	$obj_customer->data["address1_state"]		= @security_form_input_predefined("any", "address1_state", 0, "");
	$obj_customer->data["address1_country"]		= @security_form_input_predefined("any", "address1_country", 0, "");
	$obj_customer->data["address1_zipcode"]		= @security_form_input_predefined("any", "address1_zipcode", 0, "");
	
	$obj_customer->data["address1_same_as_2"]	= @security_form_input_predefined("checkbox", "address1_same_as_2", 0, "");


	// If the address 1 is set to be the same as address 2
	if ($obj_customer->data["address1_same_as_2"])
	{
		$obj_customer->data["address2_street"]		= $obj_customer->data["address1_street"];
		$obj_customer->data["address2_city"]		= $obj_customer->data["address1_city"];
		$obj_customer->data["address2_state"]		= $obj_customer->data["address1_state"];
		$obj_customer->data["address2_country"]		= $obj_customer->data["address1_country"];
		$obj_customer->data["address2_zipcode"]		= $obj_customer->data["address1_zipcode"];
	}
	else
	{
		$obj_customer->data["address2_street"]		= @security_form_input_predefined("any", "address2_street", 0, "");
		$obj_customer->data["address2_city"]		= @security_form_input_predefined("any", "address2_city", 0, "");
		$obj_customer->data["address2_state"]		= @security_form_input_predefined("any", "address2_state", 0, "");
		$obj_customer->data["address2_country"]		= @security_form_input_predefined("any", "address2_country", 0, "");
		$obj_customer->data["address2_zipcode"]		= @security_form_input_predefined("any", "address2_zipcode", 0, "");
	}
	
	
	//contacts
	$num_contacts	= @security_form_input_predefined("int", "num_contacts", 0, "");
	$obj_customer->data["num_contacts"]	= $num_contacts;
	
	for ($i=0; $i < $num_contacts; $i++)
	{	
		$obj_customer->data["contacts"][$i]["contact_id"]	= @security_form_input_predefined("int", "contact_id_$i", 0, "");
		$obj_customer->data["contacts"][$i]["delete_contact"]	= @security_form_input_predefined("any", "delete_contact_$i", 0, "");
		$obj_customer->data["contacts"][$i]["contact"]		= @security_form_input_predefined("any", "contact_" .$i, 0, "");
		$obj_customer->data["contacts"][$i]["description"]	= @security_form_input_predefined("any", "description_$i", 0, "");
		
		//keep track of deleted contacts to ensure things display correctly after processing
		if ($obj_customer->data["contacts"][$i]["delete_contact"] == "true")
		{
			$num_del_contacts++;
		}
		
		//contact records
		$num_records	= @security_form_input_predefined("int", "num_records_$i", 0, "");
		$obj_customer->data["contacts"][$i]["num_records"]	= $num_records;
		
		$num_del_records[$i] = 0;
		
		for ($j=0; $j < $num_records; $j++)
		{
			$obj_customer->data["contacts"][$i]["records"][$j]["record_id"]	= @security_form_input_predefined("int", "contact_" .$i. "_record_id_" .$j, 0, "");
			$obj_customer->data["contacts"][$i]["records"][$j]["delete"]	= @security_form_input_predefined("any", "contact_" .$i. "_delete_" .$j, 0, "");
			$obj_customer->data["contacts"][$i]["records"][$j]["type"]	= @security_form_input_predefined("any", "contact_" .$i. "_type_" .$j, 0, "");
			$obj_customer->data["contacts"][$i]["records"][$j]["label"]	= @security_form_input_predefined("any", "contact_" .$i. "_label_" .$j, 0, "");
			
			if ($obj_customer->data["contacts"][$i]["records"][$j]["delete"] == "true")
			{
				$num_del_records[$i]++;
			}
			
			if ($obj_customer->data["contacts"][$i]["records"][$j]["type"] == "email")
			{
				$obj_customer->data["contacts"][$i]["records"][$j]["detail"]	= @security_form_input_predefined("email", "contact_" .$i. "_detail_" .$j, 0, "");
			}
			else
			{
				$obj_customer->data["contacts"][$i]["records"][$j]["detail"]	= @security_form_input_predefined("any", "contact_" .$i. "_detail_" .$j, 0, "");
			}
		}
	}

	
	//taxes	
	$obj_customer->data["tax_number"]		= @security_form_input_predefined("any", "tax_number", 0, "");
	$obj_customer->data["discount"]			= @security_form_input_predefined("float", "discount", 0, "");


	// get tax selection options
	$sql_taxes_obj		= New sql_query;
	$sql_taxes_obj->string	= "SELECT id FROM account_taxes";
	$sql_taxes_obj->execute();

	if ($sql_taxes_obj->num_rows())
	{
		// only get the default tax if taxes exist
		$obj_customer->data["tax_default"] = @security_form_input_predefined("int", "tax_default", 0, "");


		// fetch all the taxes and see which ones are enabled for the customer
		$sql_taxes_obj->fetch_array();

		foreach ($sql_taxes_obj->data as $data_tax)
		{
			$obj_customer->data["tax_". $data_tax["id"] ] = @security_form_input_predefined("any", "tax_". $data_tax["id"], 0, "");
		}
	}


	/*
		Error Handling
	*/


	// verify valid ID (if performing update)
	if ($obj_customer->id)
	{
		if (!$obj_customer->verify_id())
		{
			log_write("error", "process", "The customer you have attempted to edit - ". $obj_customer->id ." - does not exist in this system.");
		}
	}


	// make sure we don't choose a customer name that has already been taken
	if (!$obj_customer->verify_name_customer())
	{
		log_write("error", "process", "This customer name is already used for another customer - please choose a unique name.");
		$_SESSION["error"]["name_customer-error"] = 1;
	}


	// make sure we don't choose a customer code that has already been taken
	if (!$obj_customer->verify_code_customer())
	{
		log_write("error", "process", "This customer code is already used for another customer - please choose a unique code or leave blank for an automatically generated value.");
		$_SESSION["error"]["name_customer-error"] = 1;
	}


	// don't allow a date closed to be set if there are active services belonging to this customer
	if (!$obj_customer->verify_date_end())
	{
		log_write("error", "process", "You can not close this customer, as there are still active services on this account");
		$_SESSION["error"]["date_end-error"] = 1;
	}
	
	
	//make sure each contact has a name	
	for ($i=0; $i < $num_contacts; $i++)
	{
		if (!$obj_customer->verify_name_contact($i))
		{
			log_write("error", "process", "Each contact must be given a name - please ensure each contact has been assigned a unique name");
			error_flag_field("contact_" .$i);
			log_debug("edit-process", "NO NAME ERROR FLAG: contact_" .$i);
		}
	}	
	
	
	//make sure each contact name is unique
	for ($i=0; $i < ($num_contacts); $i++)
	{
		$uniqueness = $obj_customer->verify_uniqueness_contact($i);
		
		if ($uniqueness != "unique")
		{
			log_write("error", "process", "You have assigned the same name to two or more contacts - please choose unique names");
			error_flag_field("contact_" .$i);
			error_flag_field("contact_" .$uniqueness);
		}
	}
	

	// return to input page if any errors occurred
	if ($_SESSION["error"]["message"])
	{
		if ($obj_customer->id)
		{
			$_SESSION["error"]["form"]["customer_view"] = "failed";
			header("Location: ../index.php?page=customers/view.php&id=". $obj_customer->id ."");
			exit(0);
		}
		else
		{
			$_SESSION["error"]["form"]["customer_add"] = "failed";
			header("Location: ../index.php?page=customers/add.php");
			exit(0);
		}
	}


	/*
		Process Data
	*/

	// start transaction
	$sql_obj = New sql_query;
	$sql_obj->trans_begin();

	// update customer
	$obj_customer->action_update();
	$obj_customer->action_update_taxes();

	// commit
	if (error_check())
	{
		$sql_obj->trans_rollback();
	}
	else
	{	
		//if successful, change the number of contacts if there were some deleted
		for ($i=0; $i<$num_contacts; $i++)
		{
			$_SESSION["error"]["num_records_$i"] = $_SESSION["error"]["num_records_$i"] - $num_del_records[$i];
		}
		
		$_SESSION["error"]["num_contacts"] = $_SESSION["error"]["num_contacts"] - $num_del_contacts;
		$sql_obj->trans_commit();
	}


	// display updated details
	header("Location: ../index.php?page=customers/view.php&id=". $obj_customer->id);
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
