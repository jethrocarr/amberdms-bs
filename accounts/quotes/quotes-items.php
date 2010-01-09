<?php
/*
	accounts/quotes/quotes-items.php
	
	access: account_quotes_view

	Page to list all the items on the quote. We call some of the invoice functions here since the code
	needed for invoice items is the mostly the same as the code needed for quote items.
	
*/


// custom includes
require("include/accounts/inc_quotes_forms.php");



class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_table_items;


	function page_output()
	{
		// fetch quote ID
		$this->id = @security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Quote Details", "page=accounts/quotes/quotes-view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Quote Items", "page=accounts/quotes/quotes-items.php&id=". $this->id ."", TRUE);
		$this->obj_menu_nav->add_item("Quote Journal", "page=accounts/quotes/journal.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Export Quote", "page=accounts/quotes/quotes-export.php&id=". $this->id ."");

		if (user_permissions_get("accounts_quotes_write"))
		{
			$this->obj_menu_nav->add_item("Convert to Invoice", "page=accounts/quotes/quotes-convert.php&id=". $this->id ."");
			$this->obj_menu_nav->add_item("Delete Quote", "page=accounts/quotes/quotes-delete.php&id=". $this->id ."");
		}
	}



	function check_permissions()
	{
		return user_permissions_get("accounts_quotes_view");
	}



	function check_requirements()
	{
		// verify that the quote
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM account_quotes WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested quote (". $this->id .") does not exist - possibly the quote has been deleted.");
			return 0;
		}

		unset($sql_obj);


		return 1;
	}


	function execute()
	{
		$this->obj_table_items			= New invoice_list_items;
		$this->obj_table_items->type		= "quotes";
		$this->obj_table_items->invoiceid	= $this->id;
		$this->obj_table_items->page_view	= "accounts/quotes/quotes-items-edit.php";
		$this->obj_table_items->page_delete	= "accounts/quotes/quotes-items-delete-process.php";
		
		$this->obj_table_items->execute();
	}

	function render_html()
	{
		// heading
		print "<h3>QUOTE ITEMS</h3><br>";
		print "<p>This page shows all the items belonging to the quote and allows you to edit them.</p>";
		
		// display summary box
		quotes_render_summarybox($this->id);

		// display form
		$this->obj_table_items->render_html();
	}


}

?>
