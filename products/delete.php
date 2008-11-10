<?php
/*
	products/delete.php

	access: products_write (write access)

	Allows users to delete products which have not been added to any invoices.
*/


// include form functions
require("include/products/inc_product_forms.php");



if (user_permissions_get('products_write'))
{
	$id = $_GET["id"];
	
	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "Product Details";
	$_SESSION["nav"]["query"][]	= "page=products/view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Product Journal";
	$_SESSION["nav"]["query"][]	= "page=products/journal.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Delete Product";
	$_SESSION["nav"]["query"][]	= "page=products/delete.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=products/delete.php&id=$id";



	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		print "<h3>PRODUCT DELETE</h3><br>";
		print "<p>This page allows you to delete unwanted products. Note that you can't delete a product once it has been added to an invoice,
		in this case you should instead set the dates to mark this product as being no-longer sold.</p>";

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
			
			products_form_delete_render($id);

		}

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
