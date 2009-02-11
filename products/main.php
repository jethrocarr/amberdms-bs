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
	var $obj_form_products;


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
		/*
			Product selection form
		*/
		$this->obj_form_products		= New form_input;
		$this->obj_form_products->formname	= "products_quickselect";
		$this->obj_form_products->language	= $_SESSION["user"]["lang"];

		$structure = form_helper_prepare_dropdownfromdb("id", "SELECT id, code_product as label, name_product as label1 FROM products ORDER BY code_product");

		if (count($structure["values"]) == 0)
		{
			$structure["defaultvalue"] = "No products in database";
		}

		$this->obj_form_products->add_input($structure);


		$structure = NULL;
		$structure["fieldname"]		= "page";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= "products/view.php";
		$this->obj_form_products->add_input($structure);
		
		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Display";
		$this->obj_form_products->add_input($structure);



		/*
			Service selection form
		*/
		$this->obj_form_services		= New form_input;
		$this->obj_form_services->formname	= "services_quickselect";
		$this->obj_form_services->language	= $_SESSION["user"]["lang"];

		$structure = form_helper_prepare_dropdownfromdb("id", "SELECT id, name_service as label FROM services ORDER BY name_service");

		if (count($structure["values"]) == 0)
		{
			$structure["defaultvalue"] = "No services in database";
		}

		$this->obj_form_services->add_input($structure);


		$structure = NULL;
		$structure["fieldname"]		= "page";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= "services/view.php";
		$this->obj_form_services->add_input($structure);
		
		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Display";
		$this->obj_form_services->add_input($structure);



		/*
			Project selection form
		*/
		$this->obj_form_projects		= New form_input;
		$this->obj_form_projects->formname	= "projects_quickselect";
		$this->obj_form_projects->language	= $_SESSION["user"]["lang"];

		$structure = form_helper_prepare_dropdownfromdb("id", "SELECT id, code_project as label, name_project as label1 FROM projects ORDER BY name_project");

		if (count($structure["values"]) == 0)
		{
			$structure["defaultvalue"] = "No projects in database";
		}

		$this->obj_form_projects->add_input($structure);


		$structure = NULL;
		$structure["fieldname"]		= "page";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= "projects/view.php";
		$this->obj_form_projects->add_input($structure);
		
		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Display";
		$this->obj_form_projects->add_input($structure);


		return 1;
	}



	function render_html()
	{
		print "<h3>PRODUCTS, SERVICES AND PROJECTS</h3>";
		print "<br>";

		/*
			Products
		*/
		if (user_permissions_get("products_view"))
		{
			print "<br>";
			print "<table width=\"100%\"><tr>";

				// blurb
				print "<td width=\"60%\">";

					format_linkbox("default", "index.php?page=products/products.php", "<p><b>PRODUCTS</b></p>
							<p>Products are used to add line items to invoices that support various
							tax configurations, as well as quantity and unit fields.</p>");
				
				print "</td>";

				// quick select form
				print "<td width=\"40%\" class=\"table_highlight\">";

					print "<p><b>QUICK SELECT PRODUCT:</b></p>";

					print "<form method=\"get\" action=\"index.php\">";

					$this->obj_form_products->render_field("id");
					$this->obj_form_products->render_field("page");

					if (count($this->obj_form_products->structure["id"]["values"]))
					{
						$this->obj_form_products->render_field("submit");
					}
					print "</form>";


				print "</td>";

			print "</tr></table>";
		}


		/*
			Services
		*/
		if (user_permissions_get("services_view"))
		{
			print "<br>";
			print "<table width=\"100%\"><tr>";

				// blurb
				print "<td width=\"60%\">";

					format_linkbox("default", "index.php?page=services/services.php", "<p><b>SERVICES</b></p>
							<p>Services are used for regular billing of a provided service, such as an
							internet connection or a monthly support service.</p>");
				
				print "</td>";

				// quick select form
				print "<td width=\"40%\" class=\"table_highlight\">";

					print "<p><b>QUICK SELECT SERVICE:</b></p>";

					print "<form method=\"get\" action=\"index.php\">";

					$this->obj_form_services->render_field("id");
					$this->obj_form_services->render_field("page");

					if (count($this->obj_form_services->structure["id"]["values"]))
					{
						$this->obj_form_services->render_field("submit");
					}
					print "</form>";


				print "</td>";

			print "</tr></table>";
		}



		/*
			Projects
		*/
		if (user_permissions_get("projects_view"))
		{
			print "<br>";
			print "<table width=\"100%\"><tr>";

				// blurb
				print "<td width=\"60%\">";

					format_linkbox("default", "index.php?page=projects/projects.php", "<p><b>PROJECTS</b></p>
							<p>All time booked needs to be assigned to a specific project. Once time has
							been assigned to a project, it can then be group and added to an invoice or marked
							as non-billable hours.</p>");
				
				print "</td>";

				// quick select form
				print "<td width=\"40%\" class=\"table_highlight\">";

					print "<p><b>QUICK SELECT PROJECT:</b></p>";

					print "<form method=\"get\" action=\"index.php\">";

					$this->obj_form_projects->render_field("id");
					$this->obj_form_projects->render_field("page");

					if (count($this->obj_form_projects->structure["id"]["values"]))
					{
						$this->obj_form_projects->render_field("submit");
					}
					print "</form>";


				print "</td>";

			print "</tr></table>";
		}






	}
}

?>	
