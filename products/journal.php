<?php
/*
	products/journal.php
	
	access: products_view (read-only)
		products_write (write access)

	Standard journal for product records and audit trail.
*/


if (user_permissions_get('products_view'))
{
	$id = $_GET["id"];


	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "Product Details";
	$_SESSION["nav"]["query"][]	= "page=products/view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Product Journal";
	$_SESSION["nav"]["query"][]	= "page=products/journal.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=products/journal.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Delete Product";
	$_SESSION["nav"]["query"][]	= "page=products/delete.php&id=$id";



	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		print "<h3>PRODECT JOURNAL</h3><br>";
		print "<p>The journal is a place where you can put your own notes, files and view the history of this product.</p>";

		print "<p><b><a href=\"index.php?page=products/journal-edit.php&type=text&id=$id\">Add new journal entry</a> || <a href=\"index.php?page=products/journal-edit.php&type=file&id=$id\">Upload File</a></b></p>";


		// make sure the product exists
		$sql = New sql_query;
		$sql->string = "SELECT id FROM `products` WHERE id='$id'";
		$sql->execute();
		
		if (!$sql->num_rows())
		{
			print "<p><b>Error: The requested product  does not exist. <a href=\"index.php?page=products/products.php\">Try looking for your product on the product list page.</a></b></p>";
		}
		else
		{
			/*
				Define the journal structure
			*/

			// basic
			$journal		= New journal_display;
			$journal->journalname	= "products";
			
			// set the pages to use for forms or file downloads
			$journal->prepare_set_form_process_page("products/journal-edit.php");
			$journal->prepare_set_download_page("products/journal-download-process.php");


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
