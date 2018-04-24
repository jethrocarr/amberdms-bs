<?php
/*
	projects/expenses.php
	
	access: projects_view

	Page to list all the items on the project.
	
*/

// custom includes
require("include/accounts/inc_invoices.php");
require("include/accounts/inc_invoices_items.php");
require("include/accounts/inc_charts.php");


class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_table_items;


	function __construct()
	{
		$this->requires["css"][]	= "include/accounts/css/invoice-items-edit.css";
		// fetch variables
		$this->id = @security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Project Details", "page=projects/view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Project Phases", "page=projects/phases.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Timebooked", "page=projects/timebooked.php&id=". $this->id ."");
                $this->obj_menu_nav->add_item("Project Expenses", "page=projects/expenses.php&id=". $this->id ."",TRUE);
		$this->obj_menu_nav->add_item("Timebilled/Grouped", "page=projects/timebilled.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Project Journal", "page=projects/journal.php&id=". $this->id ."");

		if (user_permissions_get("projects_write"))
		{
			$this->obj_menu_nav->add_item("Delete Project", "page=projects/delete.php&id=". $this->id ."");
		}
	}



	function check_permissions()
	{
		return user_permissions_get("projects_view");
	}



	function check_requirements()
	{
		// verify that project exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM projects WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested project (". $this->id .") does not exist - possibly the project has been deleted.");
			return 0;
		}

		unset($sql_obj);


		return 1;
	}


	function execute()
	{
		$this->obj_table_items			= New invoice_list_items;
		$this->obj_table_items->type		= "project";
		$this->obj_table_items->invoiceid	= $this->id;
		$this->obj_table_items->page_view	= "projects/expenses-edit.php";
		$this->obj_table_items->page_delete	= "projects/expenses-delete-process.php";
		
		$this->obj_table_items->execute();
	}

	function render_html()
	{
		// heading
		print "<h3>EXPENSES ITEMS</h3><br>";
		print "<p>This page shows all the items used during the project and allows you to edit them. Costs shown are the internal cost price.</p>";
		
		// display summary box
		invoice_render_summarybox("project", $this->id);

		// display form
		$this->obj_table_items->render_html();
	}
	
}

?>
