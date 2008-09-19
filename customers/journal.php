<?php
/*
	customers/journal.php
	
	access: customers_view (read-only)
		customers_write (write access)

	Standard journal for customer records and audit trail.
*/


if (user_permissions_get('customers_view'))
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
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		print "<h3>CUSTOMER JOURNAL</h3><br>";
		print "<p>The journal is a place where you can put your own notes, files and view the history of this customer account.</p>";

		print "<p><b><a href=\"index.php?page=customers/journal-edit.php&type=text&id=$id\">Add new journal entry</a> || <a href=\"index.php?page=customers/journal-edit.php&type=file&id=$id\">Upload File</a></b></p>";


		// make sure the customer exists
		$sql = New sql_query;
		$sql->string = "SELECT id FROM `customers` WHERE id='$id'";
		$sql->execute();
		
		if (!$sql->num_rows())
		{
			print "<p><b>Error: The requested customer  does not exist. <a href=\"index.php?page=customers/customers.php\">Try looking for your customer on the customer list page.</a></b></p>";
		}
		else
		{
			/*
				Define the journal structure
			*/

			// basic
			$journal		= New journal_display;
			$journal->journalname	= "customers";
			
			// set the pages to use for forms or file downloads
			$journal->prepare_set_form_process_page("customers/journal-edit.php");
			$journal->prepare_set_download_page("customers/journal-download-process.php");


			// define SQL structure
			$journal->sql_obj->prepare_sql_addwhere("customid='$id'");		// we only want journal entries for this ticket!

			// process SQL			
			$journal->generate_sql();
			$journal->load_data();

			// display			
			$journal->render_journal();
			
		}

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
