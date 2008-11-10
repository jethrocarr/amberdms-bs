<?php
/*
	vendors/delete.php
	
	access:	vendors_write

	Allows an unwanted vendor to be deleted.
*/

if (user_permissions_get('vendors_write'))
{
	$id = $_GET["id"];
	
	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "Vendor's Details";
	$_SESSION["nav"]["query"][]	= "page=vendors/view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Vendors's Journal";
	$_SESSION["nav"]["query"][]	= "page=vendors/journal.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Delete Vendor";
	$_SESSION["nav"]["query"][]	= "page=vendors/delete.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=vendors/delete.php&id=$id";


	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		print "<h3>DELETE VENDOR</h3><br>";
		print "<p>This page allows you to delete an unwanted vendors. Note that it is only possible to delete a vendor if they do not belong to any
		invoices or time groups. If they do, you can not delete the vendor, but instead you can disable the vendor by setting the date_end field.</p>";

		$mysql_string	= "SELECT id FROM `vendors` WHERE id='$id'";
		$mysql_result	= mysql_query($mysql_string);
		$mysql_num_rows	= mysql_num_rows($mysql_result);

		if (!$mysql_num_rows)
		{
			print "<p><b>Error: The requested vendor does not exist. <a href=\"index.php?page=vendors/vendors.php\">Try looking for your vendor on the vendor list page.</a></b></p>";
		}
		else
		{
			/*
				Define form structure
			*/
			$form = New form_input;
			$form->formname = "vendor_delete";
			$form->language = $_SESSION["user"]["lang"];

			$form->action = "vendors/delete-process.php";
			$form->method = "post";
			

			// general
			$structure = NULL;
			$structure["fieldname"] 	= "name_vendor";
			$structure["type"]		= "text";
			$form->add_input($structure);


			// hidden
			$structure = NULL;
			$structure["fieldname"] 	= "id_vendor";
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= "$id";
			$form->add_input($structure);
			
			
			// confirm delete
			$structure = NULL;
			$structure["fieldname"] 	= "delete_confirm";
			$structure["type"]		= "checkbox";
			$structure["options"]["label"]	= "Yes, I wish to delete this vendor and realise that once deleted the data can not be recovered.";
			$form->add_input($structure);



			/*
				Check that the vendor can be deleted
			*/

			$locked = 0;
			

			// make sure vendor does not belong to any invoices
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM account_ap WHERE vendorid='$id'";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				$locked = 1;
			}

	
			// define submit field
			$structure = NULL;
			$structure["fieldname"] = "submit";

			if ($locked)
			{
				$structure["type"]		= "message";
				$structure["defaultvalue"]	= "<i>This vendor can not be deleted because it belongs to an invoice or time group.</i>";
			}
			else
			{
				$structure["type"]		= "submit";
				$structure["defaultvalue"]	= "delete";
			}
					
			$form->add_input($structure);


			
			// define subforms
			$form->subforms["vendor_delete"]	= array("name_vendor");
			$form->subforms["hidden"]		= array("id_vendor");
			$form->subforms["submit"]		= array("delete_confirm", "submit");

			
			// fetch the form data
			$form->sql_query = "SELECT name_vendor FROM `vendors` WHERE id='$id' LIMIT 1";		
			$form->load_data();

			// display the form
			$form->render_form();

		}

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
