<?php
/*
	accounts/quotes/quotes-view.php
	
	access: account_quotes_view

	Form to display and allow adjustments to quote details.
	
*/

// custom includes
require("include/accounts/inc_quotes_forms.php");



class page_output
{
	var $id;
	var $obj_menu_nav;
	var $obj_form_quote;


	function page_output()
	{
		// fetch quote ID
		$this->id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Quote Details", "page=accounts/quotes/quotes-view.php&id=". $this->id ."", TRUE);
		$this->obj_menu_nav->add_item("Quote Items", "page=accounts/quotes/quotes-items.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Quote Journal", "page=accounts/quotes/journal.php&id=". $this->id ."");

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
		// verify that the quote exists
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
		$this->obj_form_quote			= New quote_form_details;
		$this->obj_form_quote->quoteid		= $this->id;
		$this->obj_form_quote->processpage	= "accounts/quotes/quotes-edit-process.php";
		
		$this->obj_form_quote->execute();
	}

	function render_html()
	{
		// heading
		print "<h3>VIEW QUOTE</h3><br>";
		print "<p>This page allows you to view the basic details of the quote. You can use the links in the green navigation menu above to change to different sections of the quote, in order to add items, payments or journal entries to the quote.</p>";

		// display summary box
		quotes_render_summarybox("quotes", $this->id);

		// display form
		$this->obj_form_quote->render_html();
	}
	
}

?>
