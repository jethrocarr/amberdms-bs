<?php
/*
	products/products.php

	Summary/Link page to direct the user to the 3 other sections:
	* Products
	* Services
	* Projects
*/

if (user_online())
{
	function page_render()
	{
		print "<h3>Products, Services and Projects</h3>";

		print "<p>Have some blurb about the differences between products, services + projects here.</p>";

	}
}
else
{
	error_render_noperms();
}

?>	
