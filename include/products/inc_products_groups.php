<?php
/*
	include/products/inc_products_groups.php

	Functions and classes for managing product groups.
*/


/*
	CLASS: product_groups

	Functions for managing product groups.
*/
class product_groups
{
	var $id;	// ID of the product group



	/*
		verify_id

		Checks that the provided ID is a valid product group

		Results
		0	Failure - No matching product groups
		1	Success - Matching product groups
	*/

	function verify_id()
	{
		log_debug("product_groups", "Executing verify_id()");

		if ($this->id)
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM `product_groups` WHERE id='". $this->id ."' LIMIT 1";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				return 1;
			}
		}

		return 0;

	} // end of verify_id



	/*
		verify_group_name

		Check if the group name is in use by any other group.

		Results
		0	Failure - name in use
		1	Success - name is available
	*/

	function verify_group_name()
	{
		log_debug("product_groups", "Executing verify_name_customer()");

		$sql_obj			= New sql_query;
		$sql_obj->string		= "SELECT id FROM `product_groups` WHERE group_name='". $this->data["group_name"] ."' AND id_parent='". $this->data["id_parent"] ."'";

		if ($this->id)
			$sql_obj->string	.= " AND id!='". $this->id ."'";

		$sql_obj->string		.= " LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			return 0;
		}
		
		return 1;

	} // end of verify_group_name




	/*
		check_delete_lock

		Check if the product group is safe to delete or not.

		Results
		0	Unlocked
		1	Locked
	*/

	function check_delete_lock()
	{
		log_debug("product_groups", "Executing check_delete_lock()");


		// make sure product group is safe to delete
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM products WHERE id_product_group='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			return 1;
		}

		unset($sql_obj);


		// unlocked
		return 0;

	}  // end of check_delete_lock



	/*
		load_data

		Load the product_groups information into the $this->data array.

		Returns
		0	failure
		1	success
	*/
	function load_data()
	{
		log_debug("product_groups", "Executing load_data()");

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT * FROM product_groups WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$sql_obj->fetch_array();

			$this->data = $sql_obj->data[0];

			return 1;
		}

		// failure
		return 0;

	} // end of load_data





	/*
		action_create

		Create a new product group based on the data in $this->data

		Results
		0	Failure
		#	Success - return ID
	*/
	function action_create()
	{
		log_debug("product_groups", "Executing action_create()");

		// create a new product group
		$sql_obj		= New sql_query;
		$sql_obj->string	= "INSERT INTO `product_groups` (group_name) VALUES ('". $this->data["group_name"]. "')";
		$sql_obj->execute();

		$this->id = $sql_obj->fetch_insert_id();

		return $this->id;

	} // end of action_create




	/*
		action_update

		Update a product group based on the data in $this->data. If no ID is provided,
		it will first call the action_create function.

		Returns
		0	failure
		#	success - returns the ID
	*/
	function action_update()
	{
		log_debug("product_groups", "Executing action_update()");

		/*
			Start Transaction
		*/
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();



		/*
			If no ID supplied, create a new product group first
		*/
		if (!$this->id)
		{
			$mode = "create";

			if (!$this->action_create())
			{
				return 0;
			}
		}
		else
		{
			$mode = "update";
		}



		/*
			Update Group Details
		*/

		$sql_obj->string	= "UPDATE `product_groups` SET "
						."group_name='". $this->data["group_name"] ."', "
						."group_description='". $this->data["group_description"] ."', "
						."id_parent='". $this->data["id_parent"] ."' "
						."WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		

		/*
			Commit
		*/

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "product_groups", "An error occured when updating the product group.");

			return 0;
		}
		else
		{
			$sql_obj->trans_commit();

			if ($mode == "update")
			{
				log_write("notification", "product_groups", "Product group successfully updated.");
			}
			else
			{
				log_write("notification", "product_groups", "Product group successfully created.");
			}
			
			return $this->id;
		}

	} // end of action_update



	/*
		action_delete

		Deletes an old product group.

		Note: the check_delete_lock function should be executed before calling this function to ensure database integrity.


		Results
		0	failure
		1	success
	*/
	function action_delete()
	{
		log_debug("product_groups", "Executing action_delete()");


				
		$id_parent = sql_get_singlevalue("SELECT id_parent AS value FROM product_groups WHERE id='{$this->id}' LIMIT 1");
		$child_groups = sql_get_singlecol("SELECT id AS col FROM product_groups WHERE id_parent='{$this->id}'");
		
		
		//exit("<pre>".print_r($child_groups,true)."</pre>");
		/*
			Start Transaction
		*/

		$sql_obj = New sql_query;
		$sql_obj->trans_begin();
		
		/*
			Delete product group
		*/
			
		$sql_obj->string	= "DELETE FROM product_groups WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();
	
		$child_count = count($child_groups);
		if($child_count > 0) {
			$sql_obj->string	= "UPDATE `product_groups` SET `id_parent` = '{$id_parent}' WHERE `id` IN('".implode("', '", $child_groups)."') LIMIT {$child_count};";
			$sql_obj->execute();		
		}


		/*
			Commit
		*/

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "product_groups", "An error occured whilst trying to delete the product group.");

			return 0;
		}
		else
		{			
			$sql_obj->trans_commit();

			log_write("notification", "product_groups", "Product group has been successfully deleted.");

			return 1;
		}
	}



} // end of class product_groups



?>
