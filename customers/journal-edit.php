<?php
/*
	customers/journal_edit.php
	
	access: customers_write

	Allows the addition or adjustment of journal entries.
*/

if (user_permissions_get('customers_write'))
{
	$id = $_GET["id"];
	
	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "Customer's Details";
	$_SESSION["nav"]["query"][]	= "page=customers/view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Customer's Journal";
	$_SESSION["nav"]["query"][]	= "page=customers/journal.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=customers/journal.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Customer's Services";
	$_SESSION["nav"]["query"][]	= "page=account/services/services.php&customer_id=$id";

	$_SESSION["nav"]["title"][]	= "Delete Customer";
	$_SESSION["nav"]["query"][]	= "page=customers/delete.php&id=$id";


	function page_render()
	{
		$id		= security_script_input('/^[0-9]*$/', $_GET["id"]);
		$journalid	= security_script_input('/^[0-9]*$/', $_GET["journalid"]);

		/*
			Title + Summary
		*/
		print "<h3>CUSTOMER JOURNAL</h3><br>";
		print "<p>This page allows you to add or adjust an entry to the customer's journal.</p>";


		/*
			Edit/Add journal form
		*/
		$journal_form = New journal_input;
		
		// basic details of this entry
		$journal_form->prepare_set_journalname("customers");
		$journal_form->prepare_set_journalid($journalid);
		$journal_form->prepare_set_customid($id);

		// set the processing form
		$journal_form->prepare_set_form_process_page("customers/journal-edit-process.php");

		// draw text entry form
		$journal_form->render_text_form();		
		


	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
