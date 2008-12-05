<?php
/*
	products/products.php

	Summary/Link page to direct the user to the 3 other sections:
	* Products
	* Services
	* Projects
*/

class page_output
{
	function check_permissions()
	{
		return user_online();
	}

	function check_requirements()
	{
		// do nothing
		return 1;
	}

	function execute()
	{
		// do nothing
		return 1;
	}

	function render_html()
	{
		print "<h3>Products, Services and Projects</h3>";
		print "<p>Have some blurb about the differences between products, services + projects here.</p>";
	}
}

?>	
