<?php
/*
	accounts/gl/delete.php
	
	access:	accounts_gl_write

	Allows an unwanted transaction to be deleted.
*/


class page_output
{
	var $id;
	
	var $obj_menu_nav;
	var $obj_form;
	
	var $locked;
	

	function __construct()
	{
		// fetch variables
		$this->id = @security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Transaction Details", "page=accounts/gl/view.php&id=". $this->id ."");
		$this->obj_menu_nav->add_item("Delete Transaction", "page=accounts/gl/delete.php&id=". $this->id ."", TRUE);
	}


	function check_permissions()
	{
		return user_permissions_get("accounts_gl_write");
	}



	function check_requirements()
	{
		// verify that the account exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id, locked FROM account_gl WHERE id='". $this->id ."' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "page_output", "The requested transaction (". $this->id .") does not exist - possibly the transaction has been deleted.");
			return 0;
		}
		else
		{
			$sql_obj->fetch_array();

			$this->locked = $sql_obj->data[0]["locked"];
		}

		unset($sql_obj);


		return 1;
	}


	function execute()
	{
		/*
			Define form structure
		*/
		$this->obj_form = New form_input;
		$this->obj_form->formname = "transaction_delete";
		$this->obj_form->language = $_SESSION["user"]["lang"];

		$this->obj_form->action = "accounts/gl/delete-process.php";
		$this->obj_form->method = "post";
		

		// general
		$structure = NULL;
		$structure["fieldname"] 	= "code_gl";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);

		$structure = NULL;
		$structure["fieldname"] 	= "description";
		$structure["type"]		= "text";
		$this->obj_form->add_input($structure);


		// hidden
		$structure = NULL;
		$structure["fieldname"] 	= "id_transaction";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->id;
		$this->obj_form->add_input($structure);
		
		
		// confirm delete
		$structure = NULL;
		$structure["fieldname"] 	= "delete_confirm";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "Yes, I wish to delete this transaction and realise that once deleted the data can not be recovered.";
		$this->obj_form->add_input($structure);



		/*
			Check that the transaction can be deleted
		*/

		// define submit field
		$structure = NULL;
		$structure["fieldname"]		= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "delete";
				
		$this->obj_form->add_input($structure);


		
		// define subforms
		$this->obj_form->subforms["transaction_delete"]	= array("code_gl", "description");
		$this->obj_form->subforms["hidden"]		= array("id_transaction");
		
		if ($this->locked)
		{
			$this->obj_form->subforms["submit"]	= array();
		}
		else
		{
			$this->obj_form->subforms["submit"]	= array("delete_confirm", "submit");
		}

		
		// fetch the form data
		$this->obj_form->sql_query = "SELECT code_gl, description FROM `account_gl` WHERE id='". $this->id ."' LIMIT 1";
		$this->obj_form->load_data();

	}

	function render_html()
	{
		// Title + Summary
		print "<h3>DELETE TRANSACTION</h3><br>";
		print "<p>This page allows you to delete an unwanted transaction, provided that it hasn't been locked.</p>";

		// display the form
		$this->obj_form->render_form();


		if ($this->locked)
		{
			format_msgbox("locked", "<p>This transaction has been locked and can no longer be removed.</p>");
		}

	}

}

?>
