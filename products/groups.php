<?php
/*
	products/groups.php

	access:	products_write

	Allows definition of product groups that can be used to organise products for billing purposes.
*/


class page_output
{
	var $obj_table;


	function check_permissions()
	{
		return user_permissions_get("products_write");
	}

	function check_requirements()
	{
		// nothing todo
		return 1;
	}


	/*
		Define table and load data
	*/
	function execute()
	{
		// establish a new table object
		$this->obj_table = New table;

		$this->obj_table->language	= $_SESSION["user"]["lang"];
		$this->obj_table->tablename	= "product_groups";


		// define all the columns and structure
		$this->obj_table->add_column("standard", "group_name", "");
		$this->obj_table->add_column("standard", "group_description", "");

		// defaults
		$this->obj_table->columns		= array("group_name", "group_description");
		$this->obj_table->columns_order		= array("id_parent", "group_name");

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("product_groups");
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "");
		$this->obj_table->sql_obj->prepare_sql_addfield("id_parent", "");

		// fetch all the product group information
		$this->obj_table->generate_sql();
		$this->obj_table->load_data_sql();
		
		// sort the data by parent ID and index by id, add a prefix to make it associative
		$sorted_data = array();
		foreach($this->obj_table->data as $data_row)
		{	
			$data_row['level'] = 0;
			$sorted_data['pid_'.$data_row['id_parent']]['id_'.$data_row['id']] = $data_row;
		}
		
		$regenerated_list = array();
		// add the items with no parent  and unset the parent group
		$regenerated_list = $sorted_data['pid_0'];
		unset($sorted_data['pid_0']);
		
		// loop while there is still sorted data remaining
		while(count($sorted_data) > 0)
		{
			// loop through the sorted data
			foreach($sorted_data as $sorted_key => $sorted_rows) 
			{
				// obtain the parent ID from the key
				$parent_id = (int)str_replace("pid_", '', $sorted_key);
				if(isset($regenerated_list['id_'.$parent_id])) 
				{	
					// generate the target parent key, increment the level and modify the name of the items
					$parent_key = "id_$parent_id";
					$parent_level = $regenerated_list['id_'.$parent_id]['level'];
					$set_level = $parent_level + 1;
					foreach($sorted_rows as $row_key => $row) 
					{
						$sorted_rows[$row_key]['level'] = $set_level;
						$sorted_rows[$row_key]['group_name'] = str_repeat("-", $set_level)." ".$row['group_name'];
					}
					$regenerated_list = array_insert_after($regenerated_list, $parent_key, $sorted_rows);
					// unset the sorted data after adding it to the new list.
					unset($sorted_data[$sorted_key]);
				}			
			}
		}
		$this->obj_table->data = array_values($regenerated_list);
		//echo "<pre>".print_r( $sorted_data, true ).//"</pre>";
		//echo "<pre>".print_r( $regenerated_list, true )."</pre>";

	}



	function render_html()
	{
		// heading
		print "<h3>PRODUCT GROUPS</h3><br>";
		print "<p>Here you can define the different groups available for organising products, which is used on some invoices.</p>";


		// display options form
		$this->obj_table->render_options_form();


		// display table
		if (!$this->obj_table->data_num_rows)
		{
			format_msgbox("important", "<p>There are currently no product groups in the database.</p>");
		}
		else
		{
			// links
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("tbl_lnk_details", "products/groups-view.php", $structure);

			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("tbl_lnk_delete", "products/groups-delete.php", $structure);


			// display the table
			$this->obj_table->render_table_html();

			// display CSV/PDF download link
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=csv&page=products/groups.php\">Export as CSV</a></p>";
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=pdf&page=products/groups.php\">Export as PDF</a></p>";

		}

	}


	function render_csv()
	{
		$this->obj_table->render_table_csv();
	}


	function render_pdf()
	{
		$this->obj_table->render_table_pdf();
	}
	
}

?>
