<?php
/*
	accounts/accounts.php

	Summary/Link page for other accounts sections
*/

class page_output
{
	var $obj_form_ar;
	var $obj_form_ap;

	var $total_ar_unpaid;
	var $total_ar_overdue;

	function check_permissions()
	{
		return user_online();
	}

	function check_requirements()
	{
		// nothing todo
		return 1;
	}

	function execute()
	{

		if (user_permissions_get("accounts_ar_view"))
		{
			/*
				Customer invoice selection form
			*/
			$this->obj_form_cust		= New form_input;
			$this->obj_form_cust->formname	= "customer_invoice_quickselect";
			$this->obj_form_cust->language	= $_SESSION["user"]["lang"];

			$structure = form_helper_prepare_dropdownfromdb("id", "SELECT id, code_customer as label, name_customer as label1 FROM customers ORDER BY name_customer");

			if (@count($structure["values"]) == 0)
			{
				$structure["defaultvalue"] = "No customers in database.";
			}

			$this->obj_form_cust->add_input($structure);


			$structure = NULL;
			$structure["fieldname"]		= "page";
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= "customers/invoices.php";
			$this->obj_form_cust->add_input($structure);
			
			// submit button
			$structure = NULL;
			$structure["fieldname"] 	= "submit";
			$structure["type"]		= "submit";
			$structure["defaultvalue"]	= "Display";
			$this->obj_form_cust->add_input($structure);



			/*
				AR invoice selection form
			*/
			$this->obj_form_ar		= New form_input;
			$this->obj_form_ar->formname	= "ar_invoice_quickselect";
			$this->obj_form_ar->language	= $_SESSION["user"]["lang"];

			$structure = form_helper_prepare_dropdownfromdb("id", "SELECT id, code_invoice as label FROM account_ar WHERE amount_total!=amount_paid ORDER BY code_invoice");

			if (count($structure["values"]) == 0)
			{
				$structure["defaultvalue"] = "All AR invoices have been paid.";
			}

			$this->obj_form_ar->add_input($structure);


			$structure = NULL;
			$structure["fieldname"]		= "page";
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= "accounts/ar/invoice-view.php";
			$this->obj_form_ar->add_input($structure);
			
			// submit button
			$structure = NULL;
			$structure["fieldname"] 	= "submit";
			$structure["type"]		= "submit";
			$structure["defaultvalue"]	= "Display";
			$this->obj_form_ar->add_input($structure);


			/*
				AR invoice totals
			*/


			// unpaid
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT SUM(amount_total) as amount_total, SUM(amount_paid) as amount_paid FROM account_ar WHERE amount_total!=amount_paid";
			$sql_obj->execute();
			$sql_obj->fetch_array();

			$this->total_ar_unpaid = $sql_obj->data[0]["amount_total"] - $sql_obj->data[0]["amount_paid"];


			// overdue
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT SUM(amount_total) as amount_total, SUM(amount_paid) as amount_paid FROM account_ar WHERE amount_total!=amount_paid AND date_due < '". date("Y-m-d") ."'";
			$sql_obj->execute();
			$sql_obj->fetch_array();

			$this->total_ar_overdue = $sql_obj->data[0]["amount_total"] - $sql_obj->data[0]["amount_paid"];

		} // end of AR




		if (user_permissions_get("accounts_ap_view"))
		{
			/*
				Vendor invoice selection form
			*/
			$this->obj_form_vend		= New form_input;
			$this->obj_form_vend->formname	= "vendor_invoice_quickselect";
			$this->obj_form_vend->language	= $_SESSION["user"]["lang"];

			$structure = form_helper_prepare_dropdownfromdb("id", "SELECT id, code_vendor as label, name_vendor as label1 FROM vendors ORDER BY name_vendor");

			if (@count($structure["values"]) == 0)
			{
				$structure["defaultvalue"] = "No vendors in database.";
			}

			$this->obj_form_vend->add_input($structure);


			$structure = NULL;
			$structure["fieldname"]		= "page";
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= "vendors/invoices.php";
			$this->obj_form_vend->add_input($structure);
			
			// submit button
			$structure = NULL;
			$structure["fieldname"] 	= "submit";
			$structure["type"]		= "submit";
			$structure["defaultvalue"]	= "Display";
			$this->obj_form_vend->add_input($structure);



			/*
				AP invoice selection form
			*/
			$this->obj_form_ap		= New form_input;
			$this->obj_form_ap->formname	= "ap_invoice_quickselect";
			$this->obj_form_ap->language	= $_SESSION["user"]["lang"];

			$structure = form_helper_prepare_dropdownfromdb("id", "SELECT id, code_invoice as label FROM account_ap WHERE amount_total!=amount_paid ORDER BY code_invoice");

			if (@count($structure["values"]) == 0)
			{
				$structure["defaultvalue"] = "All AP invoices have been paid.";
			}

			$this->obj_form_ap->add_input($structure);


			$structure = NULL;
			$structure["fieldname"]		= "page";
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= "accounts/ap/invoice-view.php";
			$this->obj_form_ap->add_input($structure);
			
			// submit button
			$structure = NULL;
			$structure["fieldname"] 	= "submit";
			$structure["type"]		= "submit";
			$structure["defaultvalue"]	= "Display";
			$this->obj_form_ap->add_input($structure);


			/*
				AP invoice totals
			*/


			// unpaid
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT SUM(amount_total) as amount_total, SUM(amount_paid) as amount_paid FROM account_ap WHERE amount_total!=amount_paid";
			$sql_obj->execute();
			$sql_obj->fetch_array();

			$this->total_ap_unpaid = $sql_obj->data[0]["amount_total"] - $sql_obj->data[0]["amount_paid"];


			// overdue
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT SUM(amount_total) as amount_total, SUM(amount_paid) as amount_paid FROM account_ap WHERE amount_total!=amount_paid AND date_due < '". date("Y-m-d") ."'";
			$sql_obj->execute();
			$sql_obj->fetch_array();

			$this->total_ap_overdue = $sql_obj->data[0]["amount_total"] - $sql_obj->data[0]["amount_paid"];

		} // end of AP


		return 1;
	}

	function render_html()
	{
		print "<h3>ACCOUNTS</h3>";
		print "<br>";


		/*
			Accounts AR
		*/
		if (user_permissions_get("accounts_ar_view"))
		{
			print "<br>";
			print "<table width=\"100%\"><tr>";

				// blurb
				print "<td width=\"35%\">";

					// display
					format_linkbox("default", "index.php?page=accounts/ar/ar.php", "<p><b>AR INVOICES</b></p>
							<p>AR invoices are the invoices you create to bill your customers.<br>
							<br>
							". format_money($this->total_ar_unpaid) ." in unpaid invoices.<br>
							". format_money($this->total_ar_overdue) ." in overdue invoices.</p><br><br><br>");
				
				print "</td>";

				// quick select form
				print "<td width=\"35%\" class=\"table_highlight\">";

					print "<p><b>VIEW OPEN INVOICES:</b></p>";

					print "<form method=\"get\" action=\"index.php\">";

					$this->obj_form_ar->render_field("id");
					$this->obj_form_ar->render_field("page");

					if (count($this->obj_form_ar->structure["id"]["values"]))
					{
						$this->obj_form_ar->render_field("submit");
					}
					print "</form>";

					print "<br>";

					print "<p><b>VIEW INVOICES FOR CUSTOMER:</b></p>";

					print "<form method=\"get\" action=\"index.php\">";

					$this->obj_form_cust->render_field("id");
					$this->obj_form_cust->render_field("page");

					if (count($this->obj_form_cust->structure["id"]["values"]))
					{
						$this->obj_form_cust->render_field("submit");
					}
					print "</form>";



				print "</td>";

			print "</tr></table>";
		}






		/*
			Accounts AP
		*/
		if (user_permissions_get("accounts_ap_view"))
		{
			print "<br>";
			print "<table width=\"100%\"><tr>";

				// blurb
				print "<td width=\"35%\">";

					// display
					format_linkbox("default", "index.php?page=accounts/ap/ap.php", "<p><b>AP INVOICES</b></p>
							<p>AP invoices are the invoices sent to you from your vendors/suppliers.<br>
							<br>
							". format_money($this->total_ap_unpaid) ." in unpaid invoices.<br>
							". format_money($this->total_ap_overdue) ." in overdue invoices.</p><br><br><br>");
				
				print "</td>";

				// quick select form
				print "<td width=\"35%\" class=\"table_highlight\">";

					print "<p><b>VIEW OPEN INVOICES:</b></p>";

					print "<form method=\"get\" action=\"index.php\">";

					$this->obj_form_ap->render_field("id");
					$this->obj_form_ap->render_field("page");

					if (@count($this->obj_form_ap->structure["id"]["values"]))
					{
						$this->obj_form_ap->render_field("submit");
					}
					print "</form>";

					print "<br>";

					print "<p><b>VIEW INVOICES FROM VENDOR:</b></p>";

					print "<form method=\"get\" action=\"index.php\">";

					$this->obj_form_vend->render_field("id");
					$this->obj_form_vend->render_field("page");

					if (@count($this->obj_form_vend->structure["id"]["values"]))
					{
						$this->obj_form_vend->render_field("submit");
					}
					print "</form>";



				print "</td>";

			print "</tr></table>";
		}


	}
}

?>	
