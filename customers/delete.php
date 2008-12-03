<?php
/*
	customers/delete.php
	
	access:	customers_write

	Allows an unwanted customer to be deleted.
*/

if (user_permissions_get('customers_write'))
{
        $_SESSION["error"]["pagestate"] = 1;

		
	class page_output
	{
		var $id;
		var $obj_menu_nav;
		var $obj_form;


		/*
			Constructor
		*/
		function page_output()
		{
			// fetch variables
			$this->id = security_script_input('/^[0-9]*$/', $_GET["id"]);


			// verifiy that customer exists
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM customers WHERE id='". $this->id ."'";
			$sql_obj->execute();

			if (!$sql_obj->num_rows())
			{
				log_write("error", "The requested customer (". $this->id .") does not exist - possibly the customer has been deleted.");
			}

			unset($sql_obj);


			// define the navigiation menu
			$this->obj_menu_nav = New menu_nav;

			$this->obj_menu_nav->add_item("Customer's Details", "page=customers/view.php&id=$id");
			$this->obj_menu_nav->add_item("Customer's Journal", "page=customers/journal.php&id=$id");
			$this->obj_menu_nav->add_item("Customer's Invoices", "page=customers/invoices.php&id=$id");
			$this->obj_menu_nav->add_item("Customer's Services", "page=customers/services.php&id=$id");
			$this->obj_menu_nav->add_item("Delete Customer", "page=customers/services.php&id=$id", TRUE);
		}



		/*
			Logic Code
		*/
		function execute()
		{
			/*
				Define form structure
			*/
			$this->obj_form = New form_input;
			$this->obj_form->formname = "customer_delete";
			$this->obj_form->language = $_SESSION["user"]["lang"];

			$this->obj_form->action = "customers/delete-process.php";
			$this->obj_form->method = "post";
			

			// general
			$structure = NULL;
			$structure["fieldname"] 	= "name_customer";
			$structure["type"]		= "text";
			$this->obj_form->add_input($structure);


			// hidden
			$structure = NULL;
			$structure["fieldname"] 	= "id_customer";
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= "$id";
			$this->obj_form->add_input($structure);
			
			
			// confirm delete
			$structure = NULL;
			$structure["fieldname"] 	= "delete_confirm";
			$structure["type"]		= "checkbox";
			$structure["options"]["label"]	= "Yes, I wish to delete this customer and realise that once deleted the data can not be recovered.";
			$this->obj_form->add_input($structure);



			/*
				Check that the customer can be deleted
			*/

			$locked = 0;
			

			// make sure customer does not belong to any invoices
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM account_ar WHERE customerid='$id'";
			$sql_obj->execute();

			if ($sql_obj->num_rows())
			{
				$locked = 1;
			}

			// make sure customer has no time groups assigned to it
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT id FROM time_groups WHERE customerid='$id'";
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
				$structure["defaultvalue"]	= "<i>This customer can not be deleted because it belongs to an invoice or time group.</i>";
			}
			else
			{
				$structure["type"]		= "submit";
				$structure["defaultvalue"]	= "delete";
			}
					
			$this->obj_form->add_input($structure);


			
			// define subforms
			$this->obj_form->subforms["customer_delete"]	= array("name_customer");
			$this->obj_form->subforms["hidden"]		= array("id_customer");
			$this->obj_form->subforms["submit"]		= array("delete_confirm", "submit");

			
			// fetch the form data
			$this->obj_form->sql_query = "SELECT name_customer FROM `customers` WHERE id='$id' LIMIT 1";		
			$this->obj_form->load_data();
			
		} // end of execute function



		/*
			Output: HTML
		*/
		function render_html()
		{

			// title/summary
			print "<h3>DELETE CUSTOMER</h3><br>";
			print "<p>This page allows you to delete an unwanted customers. Note that it is only possible to delete a customer if they do not belong to any invoices or time groups. If they do, you can not delete the customer, but instead you can disable the customer by setting the date_end field.</p>";

			// display the form
			$this->obj_form->render_form();
		}


	} // end page_output

} // end of if logged in
else
{
	error_render_noperms();
}

?>
