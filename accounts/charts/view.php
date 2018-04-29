<?php
/*
	charts/view.php
	
	access: accounts_charts_view
		accounts_charts_write

	Displays all the details for the chart and if the user has correct
	permissions allows the chart to be updated.
*/


class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_form;

	var $chart_type;	// hold the ID of the chart type


	function __construct()
	{
		// fetch variables
		$this->id = @security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Account Details", "page=accounts/charts/view.php&id=". $this->id ."", TRUE);
		
                $isheading= sql_get_singlevalue("SELECT (VALUE='Heading') as value FROM account_chart_type LEFT JOIN account_charts ON account_chart_type.id = account_charts.chart_type WHERE account_charts.id ='". $this->id ."'");
                if(!$isheading)
                {
                    $this->obj_menu_nav->add_item("Account Ledger", "page=accounts/charts/ledger.php&id=". $this->id ."");
                }

		if (user_permissions_get("accounts_charts_write"))
		{
			$this->obj_menu_nav->add_item("Delete Account", "page=accounts/charts/delete.php&id=". $this->id ."");
		}
	}



	function check_permissions()
	{
		return user_permissions_get("accounts_charts_view");
	}



	function check_requirements()
	{
		// verify that the account exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id, chart_type FROM account_charts WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested account (". $this->id .") does not exist - possibly the account has been deleted.");
			return 0;
		}
		else
		{
			// get the chart type - we use this for configuring the options that we display.
			$sql_obj->fetch_array();

			$this->chart_type = $sql_obj->data[0]["chart_type"];
		}

		unset($sql_obj);


		return 1;
	}


	function execute()
	{
		/*
			Define form structure
		*/
		$this->obj_form = New form_input;
		$this->obj_form->formname = "chart_view";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "accounts/charts/edit-process.php";
		$this->obj_form->method = "post";
		

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "code_chart";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);
		
		$structure = NULL;
		$structure["fieldname"] 	= "description";
		$structure["type"]		= "input";
		$structure["options"]["req"]	= "yes";
		$this->obj_form->add_input($structure);
	
		$structure = NULL;
		$structure["fieldname"]		= "chart_type";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);
	
		$this->obj_form->subforms["chart_details"]	= array("code_chart", "description", "chart_type");


		/*
			Menu Configuration

			Only select the menu options suitable for the account type, to avoid confusion for users.
		*/
		$sql_obj = New sql_query;
		$sql_obj->string = "SELECT groupname FROM account_chart_menu GROUP BY groupname";
		$sql_obj->execute();
		
		if ($sql_obj->num_rows())
		{
			$sql_obj->fetch_array();

			foreach ($sql_obj->data as $data)
			{
				// get all the menu entries for this group
				$sql_obj_menu = New sql_query;
				$sql_obj_menu->string = "SELECT account_chart_menu.id, value, description FROM account_chart_menu LEFT JOIN account_chart_types_menus ON account_chart_types_menus.menuid = account_chart_menu.id WHERE account_chart_types_menus.chart_typeid='". $this->chart_type ."' AND groupname='". $data["groupname"] ."'";
				$sql_obj_menu->execute();

				if ($sql_obj_menu->num_rows())
				{
					$sql_obj_menu->fetch_array();

					foreach ($sql_obj_menu->data as $data_menu)
					{
						// define checkbox
						$structure = NULL;
						$structure["fieldname"]		= $data_menu["value"];
						$structure["type"]		= "checkbox";
						$structure["options"]["label"]	= $data_menu["description"];

						// checkbox - checked or unchecked
						$sql_obj_checked = New sql_query;
						$sql_obj_checked->string = "SELECT id FROM account_charts_menus WHERE chartid='". $this->id ."' AND menuid='". $data_menu["id"] ."'";
						$sql_obj_checked->execute();

						if ($sql_obj_checked->num_rows())
							$structure["defaultvalue"] = "enabled";

						// add checkbox to group subform
						$this->obj_form->add_input($structure);
						$this->obj_form->subforms[$data["groupname"] ." Menu Options"][] = $data_menu["value"];
					}

				} // end if valid menu options for account type
					
			}
		}


		// hidden
		$structure = NULL;
		$structure["fieldname"] 	= "id_chart";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->id;
		$this->obj_form->add_input($structure);


	
		// submit section
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Save Changes";
		$this->obj_form->add_input($structure);
	
	
		// define subforms
		$this->obj_form->subforms["hidden"]	= array("id_chart");
		
		if (user_permissions_get("accounts_charts_write"))
		{
			$this->obj_form->subforms["submit"]	= array("submit");
		}
		else
		{
			$this->obj_form->subforms["submit"]	= array();
		}

		
		// fetch the form data
		$this->obj_form->sql_query = "SELECT code_chart, description, account_chart_type.value as chart_type FROM `account_charts` LEFT JOIN account_chart_type ON account_chart_type.id = account_charts.chart_type WHERE account_charts.id='". $this->id ."' LIMIT 1";
		$this->obj_form->load_data();


	}


	function render_html()
	{
		// heading
		print "<h3>ACCOUNT DETAILS</h3>";
		print "<p>This page displays the details of the account and all the menus it can appear under.</p>";
		
		// display the form
		$this->obj_form->render_form();

		if (!user_permissions_get("accounts_charts_write"))
		{
			format_msgbox("locked", "<p>Sorry, you do not have permission to edit this account</p>");
		}
	}
}

?>
