<?php
/*
	products/view.php

	access: products_view (read-only)
		products_write (write access)

	Displays the selected product and if the user has correct permissions
	allows the product to be updated.
*/


// include form functions
require("include/products/inc_product_forms.php");



if (user_permissions_get('products_view'))
{
	$id = $_GET["id"];
	
	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "Product Details";
	$_SESSION["nav"]["query"][]	= "page=products/view.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=products/view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Product Journal";
	$_SESSION["nav"]["query"][]	= "page=products/journal.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Delete Product";
	$_SESSION["nav"]["query"][]	= "page=products/delete.php&id=$id";



	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		print "<h3>PRODUCT DETAILS</h3><br>";
		print "<p>This page allows you to view and adjust the product's records.</p>";

		$mysql_string	= "SELECT id FROM `products` WHERE id='$id'";
		$mysql_result	= mysql_query($mysql_string);
		$mysql_num_rows	= mysql_num_rows($mysql_result);

		if (!$mysql_num_rows)
		{
			print "<p><b>Error: The requested product does not exist. <a href=\"index.php?page=products/products.php\">Try looking for your product on the product list page.</a></b></p>";
		}
		else
		{
			/*
				Render details form
			*/
			
			products_form_details_render($id, "edit");

		}

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
