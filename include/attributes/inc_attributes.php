<?php
/*
	attributes/inc_attributes.php

	Provides classes for managing attributes.
*/



/*
	CLASS: attributes

	Provides functions for managing attribute values
*/

class attributes {

	var $id;		// ID of attribute
	var $id_owner;		// ID of attribute's owner
	var $id_group;		// ID of attribute's group
	var $type;		// type of attribute

	var $data;		// holds values of record fields



	/*
		verify_id

		Checks that the provided ID is a valid attribute key

		Results
		0	Failure to find the ID
		1	Success - attribute key exists
	*/

	function verify_id()
	{
		log_debug("attributes_value", "Executing verify_id()");

		if ($this->id)
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM `attributes` WHERE id='". $this->id ."' LIMIT 1";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				return 1;
			}
		}

		return 0;

	} // end of verify_id



	/*

	// TODO: is this actually any use?

		verify_id_from_type

		Take the supplied type and type ID and return the 

		Fields
		type		Type of attribute - eg customer, vendor, product, etc
		id_owner	ID of attribute's owner, eg the customer ID.

		Results
		0	Failure to find the ID
		1	Success - attribute key exists

	function verify_id_from_type($type, $id_owner)
	{
		log_debug("attributes_value", "Executing verify_id_from_type(type, id_owner)");

		$this->type		= $type;
		$this->id_owner		= $id_owner;

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM `attributes` WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			return 1;
		}

		return 0;

	} // end of verify_id_from_type

	*/



	/*
		load_data

		Load the value information into the $this->data array.

		Returns
		0	failure
		1	success
	*/
	function load_data()
	{
		log_debug("attributes", "Executing load_data()");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id_owner, id_group, type, `key`, value, group_name  
						FROM attributes LEFT JOIN attributes_group ON attributes.id_group = attributes_group.id 
						WHERE attributes.id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$sql_obj->fetch_array();

			$this->id_owner		= $sql_obj->data[0]["id_owner"];
			$this->id_group		= $sql_obj->data[0]["id_group"];
			$this->group_name	= $sql_obj->data[0]["group_name"];
			$this->type		= $sql_obj->data[0]["type"];

			$this->data["key"]	= $sql_obj->data[0]["key"];
			$this->data["value"]	= $sql_obj->data[0]["value"];

			return 1;
		}

		// failure
		return 0;

	} // end of load_data



	/*
		load_data_all

		Loads all the value attributes, filtered by owner, key or type if specified

		Returns
		0	failure
		1	success
	*/
	function load_data_all()
	{
		log_debug("attributes", "Executing load_data_all()");

		$sql_obj = New sql_query;

		$sql_obj->prepare_sql_settable("attributes");
		$sql_obj->prepare_sql_addjoin("left join attributes_group on attributes.id_group = attributes_group.id");
		$sql_obj->prepare_sql_addfield("attributes.id");
		$sql_obj->prepare_sql_addfield("id_owner");
		$sql_obj->prepare_sql_addfield("id_group");
		$sql_obj->prepare_sql_addfield("group_name");
		$sql_obj->prepare_sql_addfield("type");
		$sql_obj->prepare_sql_addfield("`key`");
		$sql_obj->prepare_sql_addfield("value");


		if ($this->id_owner)
		{
			$sql_obj->prepare_sql_addwhere("id_owner='". $this->id_owner ."'");
		}

		if ($this->id_group)
		{
			$sql_obj->prepare_sql_addwhere("id_owner='". $this->id_group ."'");
		}
		
		if ($this->type)
		{
			$sql_obj->prepare_sql_addwhere("type='". $this->type ."'");
		}
/*
		if ($this->data["key"])
		{
			$sql_obj->prepare_sql_addwhere("key='". $this->data["key"] ."'");
		}

		if ($this->data["value"])
		{
			$sql_obj->prepare_sql_addwhere("value='". $this->data["value"] ."'");
		}
*/

		$sql_obj->generate_sql();
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$sql_obj->fetch_array();

			$this->data = $sql_obj->data;

			return 1;
		}

		// failure
		return 0;

	} // end of load_data_all



	/*
		action_create

		Create a new attribute value based on the data in $this->data

		Results
		0	Failure
		#	Success - return ID
	*/
	function action_create()
	{
		log_debug("attributes", "Executing action_create()");

		// create a new attribute
		$sql_obj		= New sql_query;
		$sql_obj->string	= "INSERT INTO `attributes` ( id, type, id_owner, id_group, `key`, value) 
						VALUES ('". $this->id ."', '". $this->type ."', '". $this->id_owner ."', '". $this->id_group . "', '". $this->data["key"].  "', '". $this->data["value"]. "')";
		$sql_obj->execute();

		$this->id = $sql_obj->fetch_insert_id();

//		return $this->id; 
return 1;

	} // end of action_create



	/*
		action_update

		Update an attribute value's details based on the data in $this->data. If no ID is provided,
		it will first call the action_create function.

		Returns
		0	failure 
		#	success - returns the ID
	*/
	function action_update()
	{
		log_debug("attributes", "Executing action_update()");


		/*
			Start Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();


		/*
			If no ID exists, create a new attribute value first

			(Note: if this function has been called by the action_update() wrapper function
			this step will already have been performed and we can just ignore it)
		*/
//		if (!$this->id)
//		{
//			$mode = "create";
//
//			if (!$this->action_create())
//			{
//				return 0;
//			}
//		}
//		else
//		{
//			$mode = "update";
//		}


		/*
			Update product details
		*/

		$sql_obj->string	= "UPDATE `attributes` SET "
						."`id_owner`='".$this->id_owner ."', "
						."`id_group`='".$this->id_group ."', "
						."`type`='". $this->type ."', "
						."`key`='".$this->data["key"] ."', "
						."`value`='". $this->data["value"] ."'"
						."WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();



		/*
			Commit
		*/
		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "attributes", "An error occured whilst attempting to update the attribute value. No changes were made.");

			return 0;
		}
		else
		{
			$sql_obj->trans_commit();

			return $this->id;
		}

	} // end of action_update



	/*
		action_delete

		Deletes an attribute value.

		Results
		0	failure
		1	success
	*/
	
	function action_delete()
	{
		log_debug("attributes", "Executing action_delete()");

		// start transaction
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();

//print "this is id". $this->id; die;
		// delete the attribute
		$sql_obj->string	= "DELETE FROM attributes WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();



		// commit
		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error". "inc_attributes". "An error occured whilst attempting to delete the attribute. No changes have been made.");
		}
		else
		{
			$sql_obj->trans_commit();

			log_write("notification", "inc_attributes", "Attribute has been successfully deleted.");
		}

		return 1;
	}

} // end of class attributes


?>
