<?php
/*
	products/add.php
	
	access: products_write

	Form to add a new product to the database.
*/

// include form functions
require("include/products/inc_product_forms.php");


if (user_permissions_get('products_write'))
{
	function page_render()
	{
		/*
			Title + Summary
		*/
		print "<h3>ADD PRODUCT</h3><br>";
		print "<p>This page allows you to add a new product.</p>";


		/*
			Render details form
		*/
		products_form_details_render(0, "add");

		
	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
