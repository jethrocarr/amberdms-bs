<?php
/*
	groups/view.php
	
	access: services_view (read-only)
		services_write (write access)

	Displays the details for the selected service group and allows
	them to be changed if the user has the appropiate permissions.
*/


require("include/services/inc_services_groups.php");

class page_output
{
	var $obj_service_group;
	var $obj_menu_nav;
	var $obj_form;


	function page_output()
	{
		// init
		$this->obj_service_group	= New service_groups;

		// fetch variables
		$this->obj_service_group->id	= @security_script_input('/^[0-9]*$/', $_GET["id"]);


		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Service Group Details", "page=services/groups-view.php&id=". $this->obj_service_group->id ."", TRUE);

		if (user_permissions_get("services_write"))
		{
			$this->obj_menu_nav->add_item("Delete Service Group", "page=services/groups-delete.php&id=". $this->obj_service_group->id ."");
		}
	}



	function check_permissions()
	{
		return user_permissions_get("services_view");
	}



	function check_requirements()
	{
		// verify that service group exists
		if (!$this->obj_service_group->verify_id())
		{
			log_write("error", "page_output", "The requested service group (". $this->id .") does not exist - possibly the service group has been deleted.");
			return 0;
		}

		unset($sql_obj);


		return 1;
	}


	function execute()
	{

		/*
			Define form structure
		*/
		$this->obj_form			= New form_input;
		$this->obj_form->formname	= "service_group_view";
		$this->obj_form->language	= $_SESSION["user"]["lang"];

		$this->obj_form->action		= "services/groups-edit-process.php";
		$this->obj_form->method		= "post";
		


		// general
		$structure = NULL;
		$structure["fieldname"] 	= "group_name";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"]		= "group_description";
		$structure["type"]		= "input";
		$this->obj_form->add_input($structure);
		
		
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id, group_name as label, id_parent FROM service_groups";
		$sql_obj->execute();		
		$sql_obj->fetch_array();
		
		
		$service_group_data = $sql_obj->data;
		
		$sorted_data = array();
		$reindexed_data = array();
		foreach($service_group_data as $data_row)
		{	
			$sorted_data['pid_'.$data_row['id_parent']]['id_'.$data_row['id']] = $data_row;
			$reindexed_data['id_'.$data_row['id']] = $data_row;
		}
		
		$target_ids = array($this->obj_service_group->id);
		$at_endpoint = false;
		// frankenloop, eliminates the selected item and all its children from the list.
		while(count($target_ids) > 0)
		{
			$new_target_ids = array();
			foreach($target_ids as $target_id)
			{
				unset($reindexed_data["id_$target_id"]);				
				if(isset($sorted_data["pid_$target_id"]))
				{
					foreach($sorted_data["pid_$target_id"] as $sorted_data_set)
					{
						$new_target_ids[] = $sorted_data_set['id'];
					}
					unset($sorted_data["pid_$target_id"]);
				}
			}
			$target_ids = $new_target_ids;
		}

		$keys_to_delete = array();
		$value_array = array();

		
		
		$structure = NULL;
		$structure['values'] = array();
		$structure['translations'] = array();
		foreach($reindexed_data as $reindexed_row) 
		{
			$value_array[] = $reindexed_row['id'];
			$structure['values'][] = $reindexed_row['id'];
			$structure['translations'][$reindexed_row['id']] = $reindexed_row['label'];
		}
		
		$structure["fieldname"]			= "id_parent";
		$structure["type"]			= "dropdown";
		$structure["options"]["search_filter"]	= "yes";
		$this->obj_form->add_input($structure); 

		// member services
		$structure = NULL;
		$structure["fieldname"]		= "group_members";
		$structure["type"]		= "message";
		$structure["defaultvalue"]	= "<table class=\"table_highlight\">";

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id as id_service, name_service, description FROM services WHERE id_service_group='". $this->obj_service_group->id ."'";
		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			$sql_obj->fetch_array();

			foreach ($sql_obj->data as $data_component)
			{
				$structure["defaultvalue"]	.= "<tr>"
								."<td>Group Member: <b>". $data_component["name_service"] ."</b></td>"
								."<td><a class=\"button_small\" href=\"index.php?page=services/view.php&id=". $data_component["id_service"] ."\">View Service</a></td>"
								."</tr>";
			}
		}
		else
		{
			$structure["defaultvalue"]	.= "<tr>"
							."<td>There are no services that currently belong to the group.</td>"
							."</tr>";
		}

		$structure["defaultvalue"] .= "</table>";
		$this->obj_form->add_input($structure);


		// submit section
		if (user_permissions_get("services_write"))
		{
			$structure = NULL;
			$structure["fieldname"] 	= "submit";
			$structure["type"]		= "submit";
			$structure["defaultvalue"]	= "Save Changes";
			$this->obj_form->add_input($structure);
		
		}
		else
		{
			$structure = NULL;
			$structure["fieldname"] 	= "submit";
			$structure["type"]		= "message";
			$structure["defaultvalue"]	= "<p><i>Sorry, you don't have permissions to make changes to service groups.</i></p>";
			$this->obj_form->add_input($structure);
		}

		// hidden
		$structure = NULL;
		$structure["fieldname"] 	= "id_service_group";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->obj_service_group->id;
		$this->obj_form->add_input($structure);
					
		
		// define subforms
		$this->obj_form->subforms["service_group_view"]		= array("group_name", "group_description", "id_parent");
		$this->obj_form->subforms["service_group_members"]	= array("group_members");
		$this->obj_form->subforms["hidden"]			= array("id_service_group");

		if (user_permissions_get("customers_write"))
		{
			$this->obj_form->subforms["submit"]		= array("submit");
		}
		else
		{
			$this->obj_form->subforms["submit"]		= array();
		}

		
		// fetch the form data
		$this->obj_form->sql_query = "SELECT * FROM `service_groups` WHERE id='". $this->obj_service_group->id ."' LIMIT 1";
		$this->obj_form->load_data();

	}


	function render_html()
	{
		// title	
		print "<h3>SERVICE GROUP DETAILS</h3><br>";
		print "<p>Use this page to adjust the details on the service group.</p>";

		// display the form
		$this->obj_form->render_form();

		if (!user_permissions_get("services_write"))
		{
			format_msgbox("locked", "<p>Sorry, you do not have permission to edit this service group.</p>");
		}

	}


} // end of page_output class

?>
